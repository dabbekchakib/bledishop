<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when the checkout cannot proceed (e.g. empty or invalid cart). The
 * controller catches it and redirects the customer back to the cart with a
 * friendly message.
 */
class CheckoutException extends RuntimeException {}
