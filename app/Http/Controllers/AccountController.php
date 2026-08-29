<?php

namespace App\Http\Controllers;

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

        return view('account.dashboard', [
            'user' => $user,
            'orders' => $orders,
            'ordersCount' => $orders->count(),
            'addressesCount' => $user->addresses()->count(),
        ]);
    }

    /**
     * List the authenticated customer's orders.
     */
    public function orders(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

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
            'order' => $order->loadMissing(['items']),
            'customerView' => true,
        ]);
    }
}
