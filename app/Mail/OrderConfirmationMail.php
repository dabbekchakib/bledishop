<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $mailLocale = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $locale = $this->mailLocale ?: app()->getLocale();

        return new Envelope(
            subject: __('checkout.email.subject', ['order' => $this->order->order_number], $locale),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $locale = $this->mailLocale ?: app()->getLocale();

        return new Content(
            markdown: 'emails.orders.confirmation',
            with: ['order' => $this->order, 'locale' => $locale],
        );
    }
}
