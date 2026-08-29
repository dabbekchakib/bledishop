<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class OrderPrintController extends Controller
{
    /**
     * Render an A4 invoice/print document for an order.
     */
    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'user']);

        return view('admin.orders.print', [
            'order' => $order,
            'seller' => [
                'name' => setting('seller.name', ''),
                'legal_name' => setting('seller.legal_name', ''),
                'address' => setting('seller.address', ''),
                'city' => setting('seller.city', ''),
                'country' => setting('seller.country', ''),
                'vat_number' => setting('seller.vat_number', ''),
                'trade_register' => setting('seller.trade_register', ''),
                'email' => setting('seller.email', ''),
                'phone' => setting('seller.phone', ''),
                'currency_code' => setting('shop.currency', 'TND'),
            ],
        ]);
    }
}
