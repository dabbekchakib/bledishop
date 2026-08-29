<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    /**
     * Stream a CSV of orders, honouring the same filters as the admin table.
     *
     * The route is guarded by the orders.export permission (see web.php) and
     * the export logic lives server-side: the frontend never supplies prices
     * or totals, only the filter criteria used to narrow the selection.
     */
    public function show(Request $request): StreamedResponse
    {
        $orders = Order::query()
            ->with(['items', 'user'])
            ->when(
                $status = $request->query('status'),
                fn ($q, $value) => $q->where('status', $value),
            )
            ->when(
                $payment = $request->query('payment_status'),
                fn ($q, $value) => $q->where('payment_status', $value),
            )
            ->when(
                $type = $request->query('customer_type'),
                fn ($q, $value) => $value === 'guest'
                    ? $q->whereNull('user_id')
                    : $q->whereNotNull('user_id'),
            )
            ->when(
                $from = $request->query('from'),
                fn ($q, $value) => $q->whereDate('created_at', '>=', Carbon::parse($value)),
            )
            ->when(
                $until = $request->query('until'),
                fn ($q, $value) => $q->whereDate('created_at', '<=', Carbon::parse($value)),
            )
            ->when(
                $min = $request->query('min_total'),
                fn ($q, $value) => $q->where('total', '>=', (int) round(((float) $value) * 100)),
            )
            ->when(
                $max = $request->query('max_total'),
                fn ($q, $value) => $q->where('total', '<=', (int) round(((float) $value) * 100)),
            )
            ->when(
                $search = trim((string) $request->query('search', '')),
                fn ($q, $value) => $q->where(function ($inner) use ($value) {
                    $inner->where('order_number', 'like', "%{$value}%")
                        ->orWhere('customer_first_name', 'like', "%{$value}%")
                        ->orWhere('customer_last_name', 'like', "%{$value}%")
                        ->orWhere('customer_email', 'like', "%{$value}%")
                        ->orWhere('customer_phone', 'like', "%{$value}%");
                }),
            )
            ->orderByDesc('created_at')
            ->get();

        $filename = 'commandes-'.Carbon::now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                __('admin.orders.column_number'),
                __('admin.orders.column_customer'),
                __('admin.orders.column_email'),
                __('admin.orders.column_phone'),
                __('admin.orders.column_city'),
                __('admin.orders.column_status'),
                __('admin.orders.column_payment'),
                __('admin.orders.column_total'),
                __('admin.orders.column_date'),
            ], ';');

            foreach ($orders as $order) {
                /** @var Order $order */
                fputcsv($handle, [
                    $order->order_number,
                    $order->customerFullName(),
                    $order->customer_email,
                    $order->customer_phone,
                    $order->shipping_city,
                    $order->status->label(),
                    $order->payment_status->label(),
                    number_format($order->totalAmount(), 2, ',', ' '),
                    optional($order->created_at)->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
