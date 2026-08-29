<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.orders.print_title', ['order' => $order->order_number]) }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 16mm 18mm;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand h1 { margin: 0 0 4px; font-size: 22px; color: #0f172a; }
        .brand p { margin: 0; color: #64748b; }
        .doc-title { text-align: right; }
        .doc-title h2 { margin: 0 0 6px; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title .meta { color: #64748b; font-size: 11px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
        .block { border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; }
        .block h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: .5px; }
        .block p { margin: 2px 0; }
        .block strong { color: #0f172a; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items thead th {
            text-align: left; background: #f8fafc; color: #64748b;
            font-size: 10px; text-transform: uppercase; letter-spacing: .5px;
            padding: 8px 10px; border-bottom: 2px solid #e2e8f0;
        }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.items tbody tr:last-child td { border-bottom: 0; }
        .num { text-align: right; white-space: nowrap; }
        .pos { color: #94a3b8; }
        .totals { display: flex; justify-content: flex-end; }
        .totals table { border-collapse: collapse; width: 260px; }
        .totals td { padding: 5px 10px; }
        .totals td:last-child { text-align: right; font-weight: 600; }
        .totals .grand { border-top: 2px solid #f59e0b; font-size: 14px; }
        .footer {
            margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0;
            color: #94a3b8; font-size: 10px; text-align: center;
        }
        footer .legal { color: #64748b; }
        @media print {
            body { padding: 0; }
            .sheet { margin: 0; box-shadow: none; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <div class="brand">
                <h1>{{ $seller['name'] ?: $seller['legal_name'] ?: setting('site.name', 'BlediShop') }}</h1>
                @if ($seller['legal_name'] && $seller['legal_name'] !== $seller['name'])
                    <p>{{ $seller['legal_name'] }}</p>
                @endif
                @if ($seller['address']) <p>{{ $seller['address'] }}</p> @endif
                @if ($seller['city'] || $seller['country'])
                    <p>{{ trim(($seller['city'] ?? '').' '.($seller['country'] ?? '')) }}</p>
                @endif
                @if ($seller['vat_number']) <p>{{ __('admin.orders.vat_number') }} : {{ $seller['vat_number'] }}</p> @endif
                @if ($seller['trade_register']) <p>{{ __('admin.orders.trade_register') }} : {{ $seller['trade_register'] }}</p> @endif
                @if ($seller['email'] || $seller['phone'])
                    <p>
                        @if ($seller['phone']) {{ $seller['phone'] }} @endif
                        @if ($seller['phone'] && $seller['email']) · @endif
                        @if ($seller['email']) {{ $seller['email'] }} @endif
                    </p>
                @endif
            </div>
            <div class="doc-title">
                <h2>{{ __('admin.orders.invoice') }}</h2>
                <div class="meta">
                    <p>{{ __('admin.orders.column_number') }} : <strong>{{ $order->order_number }}</strong></p>
                    <p>{{ __('admin.orders.date') }} : {{ optional($order->created_at)->format('d/m/Y H:i') }}</p>
                    <p>{{ __('admin.orders.column_payment') }} : {{ $order->payment_status->label() }}</p>
                    <p>{{ __('admin.orders.column_status') }} : {{ $order->status->label() }}</p>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="block">
                <h3>{{ __('admin.orders.billed_to') }}</h3>
                <p><strong>{{ $order->customerFullName() }}</strong></p>
                @if ($order->customer_email) <p>{{ $order->customer_email }}</p> @endif
                @if ($order->customer_phone) <p>{{ $order->customer_phone }}</p> @endif
            </div>
            <div class="block">
                <h3>{{ __('admin.orders.ship_to') }}</h3>
                <p><strong>{{ $order->customerFullName() }}</strong></p>
                <p>{!! nl2br(e($order->shippingAddressLines())) !!}</p>
                @if ($order->customer_notes)
                    <p class="pos" style="margin-top:6px"><em>{{ __('admin.orders.customer_note') }} : {{ $order->customer_notes }}</em></p>
                @endif
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('admin.orders.item_product') }}</th>
                    <th>{{ __('admin.orders.item_variant') }}</th>
                    <th>{{ __('admin.orders.item_sku') }}</th>
                    <th class="num">{{ __('admin.orders.item_qty') }}</th>
                    <th class="num">{{ __('admin.orders.item_price') }}</th>
                    <th class="num">{{ __('admin.orders.item_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->variant_name ?? '—' }}</td>
                        <td>{{ $item->sku ?? '—' }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ format_price($item->unitPriceAmount()) }}</td>
                        <td class="num">{{ format_price($item->lineTotalAmount()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr><td>{{ __('admin.orders.subtotal') }}</td><td>{{ format_price($order->subtotalAmount()) }}</td></tr>
                <tr><td>{{ __('admin.orders.discount') }}</td><td>{{ format_price($order->discountAmount()) }}</td></tr>
                <tr><td>{{ __('admin.orders.shipping') }}</td><td>{{ format_price($order->shippingAmount()) }}</td></tr>
                <tr><td>{{ __('admin.orders.tax') }}</td><td>{{ format_price($order->taxAmount()) }}</td></tr>
                <tr class="grand"><td>{{ __('admin.orders.total') }}</td><td>{{ format_price($order->totalAmount()) }}</td></tr>
            </table>
        </div>

        <div class="footer">
            <p>{{ __('admin.orders.print_footer') }}</p>
            @if ($seller['legal_name'])
                <p class="legal">{{ $seller['legal_name'] }} — {{ $seller['vat_number'] ? __('admin.orders.vat_number').' : '.$seller['vat_number'] : '' }}</p>
            @endif
        </div>
    </div>
</body>
</html>
