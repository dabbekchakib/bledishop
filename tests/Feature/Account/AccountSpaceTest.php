<?php

namespace Tests\Feature\Account;

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountSpaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function makeOrder(User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-TEST-'.Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'currency' => 'TND',
            'subtotal' => 10000,
            'discount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 10000,
            'customer_first_name' => 'Ahmed',
            'customer_last_name' => 'Ben Salah',
            'customer_email' => $user->email,
            'customer_phone' => '+216 98 000 000',
            'customer_first_name' => 'Ahmed',
            'customer_last_name' => 'Ben Salah',
            'shipping_address' => '12 Rue de la Liberté',
            'shipping_city' => 'Tunis',
            'shipping_postal_code' => '1001',
            'shipping_country' => 'Tunisie',
            'public_token' => Str::random(64),
        ], $overrides));
    }

    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Domicile',
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Salah',
            'phone' => '+216 98 000 000',
            'email' => 'ahmed@example.com',
            'address' => '12 Rue de la Liberté',
            'city' => 'Tunis',
            'postal_code' => '1001',
            'country' => 'Tunisie',
        ], $overrides);
    }

    public function test_a_guest_is_redirected_to_login_when_accessing_the_account(): void
    {
        $this->get('/fr/account')->assertRedirect(route('login', ['locale' => 'fr']));
    }

    public function test_the_dashboard_is_accessible_and_shows_stats(): void
    {
        $user = User::factory()->create();
        $this->makeOrder($user);
        $user->addresses()->create([...$this->addressPayload()]);

        $response = $this->actingAs($user)->get('/fr/account');

        $response->assertOk();
        $response->assertSee('Tableau de bord', false);
        $response->assertSee(1, false);
    }

    public function test_the_orders_list_shows_only_the_customers_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $own = $this->makeOrder($user);
        $this->makeOrder($other);

        $response = $this->actingAs($user)->get('/fr/account/orders');

        $response->assertOk();
        $response->assertSee($own->order_number, false);
    }

    public function test_a_customer_can_view_their_order_detail(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Smartphone X Pro',
            'sku' => 'PRD-X',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        $response = $this->actingAs($user)->get('/fr/account/orders/'.$order->order_number);

        $response->assertOk();
        $response->assertSee($order->order_number, false);
    }

    public function test_a_customer_cannot_view_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->makeOrder($owner);

        $this->actingAs($other)
            ->get('/fr/account/orders/'.$order->order_number)
            ->assertForbidden();
    }

    public function test_a_customer_can_create_and_default_an_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/fr/account/addresses', $this->addressPayload());

        $response->assertRedirect(route('account.addresses.index', ['locale' => 'fr']));

        $address = CustomerAddress::firstOrFail();
        $this->assertSame($user->id, (int) $address->user_id);
        $this->assertTrue($address->is_default);
    }

    public function test_the_second_address_does_not_steal_the_default(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create([...$this->addressPayload(), 'is_default' => true]);

        $this->actingAs($user)->post('/fr/account/addresses', $this->addressPayload([
            'label' => 'Bureau',
        ]));

        $this->assertSame(2, CustomerAddress::count());
        $this->assertSame(1, CustomerAddress::where('is_default', true)->count());
        $this->assertSame('Domicile', CustomerAddress::where('is_default', true)->first()->label);
    }

    public function test_a_marked_default_address_reassigns_the_default_flag(): void
    {
        $user = User::factory()->create();
        $first = $user->addresses()->create([...$this->addressPayload()]);

        $this->actingAs($user)->post('/fr/account/addresses', $this->addressPayload([
            'label' => 'Bureau',
            'is_default' => true,
        ]));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertSame('Bureau', CustomerAddress::where('is_default', true)->first()->label);
    }

    public function test_a_customer_cannot_edit_someone_elses_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = $owner->addresses()->create([...$this->addressPayload()]);

        $this->actingAs($other)
            ->get('/fr/account/addresses/'.$address->id.'/edit')
            ->assertForbidden();

        $this->actingAs($other)
            ->put('/fr/account/addresses/'.$address->id, $this->addressPayload())
            ->assertForbidden();

        $this->actingAs($other)
            ->delete('/fr/account/addresses/'.$address->id)
            ->assertForbidden();

        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
    }

    public function test_a_customer_can_delete_an_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create([...$this->addressPayload()]);

        $this->actingAs($user)
            ->delete('/fr/account/addresses/'.$address->id)
            ->assertRedirect(route('account.addresses.index', ['locale' => 'fr']));

        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }
}
