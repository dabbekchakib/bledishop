<?php

namespace App\Http\Controllers;

use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterService $service,
    ) {}

    /**
     * Subscribe to the newsletter. Returns JSON for the AJAX form, or a
     * redirect for a fallback non-JS submission.
     */
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->service->enabled()) {
            return $this->respond($request, [
                'success' => false,
                'type' => 'warning',
                'message' => __('shop.newsletter.disabled'),
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:191'],
            'name' => ['nullable', 'string', 'max:120'],
            'honey' => ['nullable', 'string', 'max:0'],
        ]);

        if (filled($validated['honey'] ?? null)) {
            // Honeypot anti-spam : pretend success without saving.
            return $this->respond($request, [
                'success' => true,
                'message' => __('shop.newsletter.success'),
            ]);
        }

        $result = $this->service->subscribe(
            $validated['email'],
            $validated['name'] ?? null,
            (string) $request->input('source', 'footer'),
        );

        return $this->respond($request, $result);
    }

    /**
     * Unsubscribe using the secure token present in the email link.
     */
    public function unsubscribe(Request $request): View|RedirectResponse
    {
        $token = (string) $request->route('token');

        $result = $this->service->unsubscribe($token);

        return view('shop.newsletter-unsubscribe', [
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(Request $request, array $payload): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->back()->with($payload['success'] ? 'success' : 'error', $payload['message']);
    }
}
