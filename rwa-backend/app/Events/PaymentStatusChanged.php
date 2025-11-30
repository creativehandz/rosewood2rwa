<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Payment $payment;
    public string $previousStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment, string $previousStatus)
    {
        $this->payment = $payment;
        $this->previousStatus = $previousStatus;
    }
}