<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exceptions\CheckoutException;
use App\Http\Requests\CheckoutRequest;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $redirect = $this->guardGuestCheckout();

        if ($redirect) {
            return $redirect;
        }

        $cart = $this->cart->getCart();

        if ($cart['empty']) {
            return $this->backToCart($request, __('checkout.errors.cart_empty'));
        }

        return view('shop.checkout', [
            'cart' => $cart,
            'prefill' => $this->prefill($request),
            'guestCheckoutEnabled' => (bool) setting('shop.guest_checkout_enabled', true),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $redirect = $this->guardGuestCheckout();

        if ($redirect) {
            return $redirect;
        }

        $data = $request->validated();

        try {
            $result = $this->checkout->checkout($data, (bool) ($data['create_account'] ?? false));
        } catch (CheckoutException $e) {
            return $this->backToCart($request, $e->getMessage());
        }

        if ($result['user']) {
            Auth::login($result['user']);
        }

        $this->sendConfirmationEmail($result['order']);

        $this->notifyAdmins($result['order']);

        return redirect()->route('shop.order.confirmation', [
            'locale' => $request->route('locale'),
            'order' => $result['order']->order_number,
            'token' => $result['order']->public_token,
        ]);
    }

    public function confirmation(Request $request): View|RedirectResponse
    {
        $order = Order::where('order_number', $request->route('order'))->firstOrFail();

        if (! $this->canViewOrder($request, $order)) {
            abort(403);
        }

        return view('shop.confirmation', [
            'order' => $order,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function prefill(Request $request): array
    {
        $user = $request->user();
        $first = '';
        $last = '';

        if ($user) {
            $parts = preg_split('/\s+/', trim((string) $user->name));
            $first = (string) array_shift($parts);
            $last = implode(' ', $parts);
        }

        return [
            'first_name' => old('first_name', $first),
            'last_name' => old('last_name', $last),
            'email' => old('email', $user?->email ?? ''),
            'phone' => old('phone', ''),
            'address' => old('address', ''),
            'city' => old('city', ''),
            'postal_code' => old('postal_code', ''),
            'country' => old('country', ''),
            'notes' => old('notes', ''),
        ];
    }

    private function guardGuestCheckout(): ?RedirectResponse
    {
        if (Auth::check() || (bool) setting('shop.guest_checkout_enabled', true)) {
            return null;
        }

        return redirect()->route('login')->with('info', __('checkout.errors.login_required'));
    }

    private function backToCart(Request $request, string $message): RedirectResponse
    {
        return redirect()->route('shop.cart.show', ['locale' => $request->route('locale')])
            ->with('warning', $message);
    }

    private function canViewOrder(Request $request, Order $order): bool
    {
        if ($request->query('token') === $order->public_token) {
            return true;
        }

        if (! $request->user()) {
            return false;
        }

        return $request->user()->getAuthIdentifier() === $order->user_id;
    }

    private function sendConfirmationEmail(Order $order): void
    {
        try {
            Mail::to($order->customer_email)
                ->queue(new OrderConfirmationMail($order, current_locale()));
        } catch (\Throwable) {
            report(new \RuntimeException('Impossible d\'envoyer l\'email de confirmation de commande '.$order->order_number));
        }
    }

    private function notifyAdmins(Order $order): void
    {
        try {
            $admins = User::role(Role::adminPanelRoles())->get();
        } catch (\Throwable) {
            return;
        }

        foreach ($admins as $admin) {
            $admin->notify(new NewOrderNotification($order));
        }
    }
}
