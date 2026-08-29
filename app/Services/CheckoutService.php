<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates a full checkout from a validated form:
 *
 *  1. loads and refreshes the cart (empty cart is aborted);
 *  2. creates the order atomically (see OrderService);
 *  3. optionally creates a customer account and links it to the order;
 *  4. clears the cart only after the transaction has committed.
 *
 * The customer can always check out as a guest; creating an account is purely
 * optional and never blocks an order.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    /**
     * @param  array<string, mixed>  $customer  validated customer snapshot fields
     * @return array{order: Order, user: ?User, cart: array<string, mixed>}
     */
    public function checkout(array $customer, bool $createAccount = false): array
    {
        $cart = $this->cart->getCart();

        if ($cart['empty']) {
            throw new CheckoutException(__('checkout.errors.cart_empty'));
        }

        $userId = Auth::id();
        $createdUser = null;

        $order = DB::transaction(function () use ($customer, $cart, $createAccount, $userId, &$createdUser): Order {
            $order = $this->orders->createOrder($customer, $cart, $userId);

            if ($createAccount && $userId === null && ! User::where('email', $customer['email'])->exists()) {
                $user = User::create([
                    'name' => trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? '')),
                    'email' => $customer['email'],
                    'password' => $customer['password'],
                    'locale' => current_locale(),
                    'is_active' => true,
                ]);

                $user->assignRole(Role::Customer);

                $order->forceFill(['user_id' => $user->id])->save();

                $createdUser = $user;
            }

            return $order;
        });

        $this->cart->clear();

        return ['order' => $order, 'user' => $createdUser, 'cart' => $cart];
    }
}
