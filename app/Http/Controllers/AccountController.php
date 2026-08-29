<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    use AuthorizesRequests;

    /**
     * Customer dashboard: quick stats and recent orders.
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $orders = $user->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        $totalSpent = $orders
            ->reject(fn (Order $order): bool => $order->status === OrderStatus::Cancelled)
            ->sum(fn (Order $order): int => (int) $order->total);

        return view('account.dashboard', [
            'user' => $user,
            'orders' => $orders,
            'ordersCount' => $orders->count(),
            'inProgressCount' => $orders->filter(
                fn (Order $order): bool => in_array($order->status, [
                    OrderStatus::Pending,
                    OrderStatus::Confirmed,
                    OrderStatus::Processing,
                    OrderStatus::OnHold,
                    OrderStatus::Shipped,
                ], true)
            )->count(),
            'deliveredCount' => $orders->filter(
                fn (Order $order): bool => $order->status === OrderStatus::Delivered
            )->count(),
            'totalSpent' => $totalSpent,
            'addressesCount' => $user->addresses()->count(),
        ]);
    }

    /**
     * List the authenticated customer's orders, with optional status filter
     * and search by order number.
     */
    public function orders(Request $request): View
    {
        $query = $request->user()->orders()->with('items');

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();

            if ($status === 'in_progress') {
                $query->whereIn('status', [
                    OrderStatus::Pending,
                    OrderStatus::Confirmed,
                    OrderStatus::Processing,
                    OrderStatus::OnHold,
                    OrderStatus::Shipped,
                ]);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->string('search')->toString().'%');
        }

        $orders = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('account.orders.index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show a single order belonging to the authenticated customer.
     */
    public function order(Request $request, string $locale, string $orderNumber): View
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $this->authorize('view', $order);

        return view('account.orders.show', [
            'order' => $order->loadMissing(['items', 'statusHistories']),
            'customerView' => true,
        ]);
    }
}
