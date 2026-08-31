<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterExportController extends Controller
{
    /**
     * Stream a CSV of newsletter subscribers, honouring the same filters as
     * the admin table.
     *
     * The route is guarded by the newsletter.manage permission (see web.php).
     */
    public function show(Request $request): StreamedResponse
    {
        $subscribers = NewsletterSubscriber::query()
            ->when(
                $active = $request->query('active'),
                function ($q, $value) {
                    if ($value === 'true') {
                        return $q->where('active', true);
                    }

                    if ($value === 'false') {
                        return $q->where('active', false);
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
                        $inner->where('email', 'like', "%{$value}%")
                            ->orWhere('name', 'like', "%{$value}%");
                    });
                },
            )
            ->orderByDesc('created_at')
            ->get();

        $filename = 'newsletter-'.Carbon::now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($subscribers): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                __('admin.newsletter.export_column_email'),
                __('admin.newsletter.export_column_name'),
                __('admin.newsletter.export_column_source'),
                __('admin.newsletter.export_column_status'),
                __('admin.newsletter.export_column_subscribed'),
                __('admin.newsletter.export_column_unsubscribed'),
            ], ';');

            foreach ($subscribers as $subscriber) {
                /** @var NewsletterSubscriber $subscriber */
                fputcsv($handle, [
                    $subscriber->email,
                    $subscriber->name ?? '',
                    $subscriber->source ?? '',
                    $subscriber->isActive() ? __('admin.newsletter.status_active') : __('admin.newsletter.status_inactive'),
                    optional($subscriber->subscribed_at)->format('d/m/Y H:i'),
                    optional($subscriber->unsubscribed_at)->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
