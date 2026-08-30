<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationPriority;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Pages\Notifications as NotificationsPage;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\AdminNotification;
use App\Services\AdminNotificationService;
use App\Services\OrderStatusService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function service(): AdminNotificationService
    {
        return app(AdminNotificationService::class);
    }

    public function test_service_creates_notification_for_eligible_admin(): void
    {
        $admin = $this->createUserWithRole(Role::Admin->value);

        $order = Order::factory()->create();

        $this->service()->notify(NotificationType::OrderCreated, $order);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => AdminNotification::class,
        ]);
    }

    public function test_notification_payload_carries_type_priority_and_url(): void
    {
        $admin = $this->createUserWithRole(Role::Manager->value);
        $order = Order::factory()->create();

        $this->service()->notify(NotificationType::OrderStatusChanged, $order, ['status' => 'Confirmée']);

        $row = Notification::query()
            ->where('notifiable_id', $admin->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(NotificationType::OrderStatusChanged->value, $row->data['type']);
        $this->assertSame(NotificationPriority::Info->value, $row->data['priority']);
        $this->assertNotEmpty($row->data['title']);
        $this->assertNotEmpty($row->data['message']);
        $this->assertNotNull($row->actionUrl());
    }

    public function test_order_status_change_notifies_admins(): void
    {
        $admin = $this->createUserWithRole(Role::Admin->value);
        $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);

        app(OrderStatusService::class)->transition($order, OrderStatus::Confirmed, $admin);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => AdminNotification::class,
        ]);

        $row = Notification::query()->where('notifiable_id', $admin->id)->first();
        $this->assertSame(NotificationType::OrderStatusChanged->value, $row->data['type']);
    }

    public function test_notifications_are_permission_filtered(): void
    {
        $customer = $this->createUserWithRole(Role::Customer->value);
        $order = Order::factory()->create();

        $this->service()->notify(NotificationType::OrderStatusChanged, $order);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $customer->id,
        ]);
    }

    public function test_stock_alert_creates_notification_and_is_deduplicated(): void
    {
        $manager = $this->createUserWithRole(Role::Manager->value);
        $product = Product::factory()->create([
            'manage_stock' => true,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        $stock = app(StockService::class);

        $stock->adjust($product, 4);
        $stock->adjust($product, 3);

        $this->assertSame(1, Notification::query()
            ->where('notifiable_id', $manager->id)
            ->where('data->type', NotificationType::LowStock->value)
            ->whereNull('read_at')
            ->count());
    }

    public function test_out_of_stock_notification_priority_is_danger(): void
    {
        $manager = $this->createUserWithRole(Role::Manager->value);
        $product = Product::factory()->create([
            'manage_stock' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 0,
        ]);

        app(StockService::class)->adjust($product, 0);

        $row = Notification::query()
            ->where('notifiable_id', $manager->id)
            ->where('data->type', NotificationType::OutOfStock->value)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(NotificationPriority::Danger->value, $row->data['priority']);
    }

    public function test_unread_and_mark_read(): void
    {
        $admin = $this->createUserWithRole(Role::Admin->value);
        $order = Order::factory()->create();

        $this->service()->notify(NotificationType::OrderCreated, $order);

        $row = Notification::query()->where('notifiable_id', $admin->id)->first();
        $this->assertNull($row->read_at);

        $row->markAsRead();
        $this->assertNotNull($row->fresh()->read_at);
    }

    public function test_page_mark_all_read_and_delete(): void
    {
        $admin = $this->createUserWithRole(Role::Admin->value);
        $order = Order::factory()->create();

        $this->service()->notify(NotificationType::OrderCreated, $order);
        $this->service()->notify(NotificationType::ConfigChanged);

        Livewire::actingAs($admin)
            ->test(NotificationsPage::class)
            ->call('markAllRead');

        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $admin->id, 'read_at' => null]);

        $row = Notification::query()->where('notifiable_id', $admin->id)->first();

        Livewire::actingAs($admin)
            ->test(NotificationsPage::class)
            ->call('delete', $row->id);

        $this->assertDatabaseMissing('notifications', ['id' => $row->id]);
    }

    public function test_page_only_exposes_own_notifications(): void
    {
        $adminA = $this->createUserWithRole(Role::Admin->value);
        $adminB = $this->createUserWithRole(Role::Admin->value);
        $order = Order::factory()->create();

        $this->actingAs($adminA);
        $this->service()->notify(NotificationType::OrderCreated, $order);
        $this->actingAs($adminB);
        $this->service()->notify(NotificationType::OrderCreated, $order);

        $this->actingAs($adminA);
        $page = Livewire::actingAs($adminA)->test(NotificationsPage::class);

        foreach ($page->get('notifications') as $notification) {
            $this->assertSame($adminA->id, (int) $notification->notifiable_id);
        }
    }

    public function test_other_user_cannot_act_on_foreign_notification(): void
    {
        $adminA = $this->createUserWithRole(Role::Admin->value);
        $adminB = $this->createUserWithRole(Role::Admin->value);
        $order = Order::factory()->create();

        $this->actingAs($adminA);
        $this->service()->notify(NotificationType::OrderCreated, $order);

        $row = Notification::query()->where('notifiable_id', $adminA->id)->first();

        Livewire::actingAs($adminB)
            ->test(NotificationsPage::class)
            ->call('markRead', $row->id);

        $this->assertNull($row->fresh()->read_at);
    }

    public function test_open_marks_read_and_redirects(): void
    {
        $admin = $this->createUserWithRole(Role::Admin->value);
        $order = Order::factory()->create();

        $this->service()->notify(NotificationType::OrderCreated, $order);
        $row = Notification::query()->where('notifiable_id', $admin->id)->first();
        $this->assertNotNull($row->actionUrl());

        $component = Livewire::actingAs($admin)->test(NotificationsPage::class);
        $component->call('open', $row->id);
        $component->assertRedirect();

        $this->assertNotNull($row->fresh()->read_at);
    }

    public function test_center_page_is_accessible_to_admin(): void
    {
        $admin = $this->createUserWithRole(Role::Admin->value);

        $this->withSession(['admin_locale' => 'en'])
            ->actingAs($admin)
            ->get('/admin/notifications')
            ->assertSuccessful();
    }

    public function test_translations_are_localized(): void
    {
        $this->assertSame('New order', __('admin.notifications.titles.order_created', [], 'en'));
        $this->assertSame('Nouvelle commande', __('admin.notifications.titles.order_created', [], 'fr'));

        app()->setLocale('en');
        $this->assertSame('Notifications', __('admin.notifications.page_title'));
    }
}
