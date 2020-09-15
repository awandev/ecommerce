<?php

namespace App\Mail;

use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $order;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // dan kirim email dengan subject berikut
        // template yang digunakan adalah order.blade.php yang ada di folder emails
        // dan passing data order ke file order.blade.php 
        return $this->subject('Pesanan Anda Dikirim ' . $this->order->invoice)
            ->view('emails.order')
            ->with([
                'order' => $this->order,
            ]);
    }
}
