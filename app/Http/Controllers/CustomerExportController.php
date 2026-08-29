<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExportController extends Controller
{
    /**
     * Stream a CSV of customers, honouring the same filters as the admin table.
     *
     * The route is guarded by the customers.export permission (see web.php).
     * The route path deliberately avoids "/admin/customers/export" because
     * that segment is shadowed by the CustomerResource "{record}" route.
     */
    public function show(Request $request): StreamedResponse
    {
        $customers = User::query()
            ->role('customer')
            ->withCount('orders')
            ->withSum(
                ['orders' => fn ($q) => $q->where('status', '!=', OrderStatus::Cancelled)],
                'total',
            )
            ->when(
                $active = $request->query('active'),
                function ($q, $value) {
                    if ($value === 'true') {
                        return $q->where('is_active', true);
                    }

                    if ($value === 'false') {
                        return $q->where('is_active', false);
                    }

                    return $q;
                },
            )
            ->when(
                $withOrders = $request->query('with_orders'),
                function ($q, $value) {
                    if ($value === 'true') {
                        return $q->has('orders');
                    }

                    if ($value === 'false') {
                        return $q->doesntHave('orders');
                    }

                    return $q;
                },
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
                $search = trim((string) $request->query('search', '')),
                function ($q, $value) {
                    return $q->where(function ($inner) use ($value) {
                        $inner->where('first_name', 'like', "%{$value}%")
                            ->orWhere('last_name', 'like', "%{$value}%")
                            ->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%");
                    });
                },
            )
            ->orderByDesc('created_at')
            ->get();

        $filename = 'clients-'.Carbon::now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($customers): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                __('admin.customers.export_column_name'),
                __('admin.customers.export_column_email'),
                __('admin.customers.export_column_phone'),
                __('admin.customers.export_column_locale'),
                __('admin.customers.export_column_status'),
                __('admin.customers.export_column_orders'),
                __('admin.customers.export_column_total'),
                __('admin.customers.export_column_joined'),
                __('admin.customers.export_column_last_login'),
            ], ';');

            foreach ($customers as $customer) {
                /** @var User $customer */
                $lastLogin = $customer->last_login_at
                    ? Carbon::parse($customer->last_login_at)->format('d/m/Y H:i')
                    : '';

                fputcsv($handle, [
                    $customer->fullName(),
                    $customer->email,
                    $customer->phone ?? '',
                    $customer->locale ?? '',
                    $customer->is_active ? __('admin.customers.active') : __('admin.customers.inactive'),
                    $customer->orders_count,
                    number_format(((int) $customer->orders_sum_total) / 100, 2, ',', ' '),
                    optional($customer->created_at)->format('d/m/Y H:i'),
                    $lastLogin,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
