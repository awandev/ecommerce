<?php

namespace App\Mail;

use App\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $customer;
    protected $randomPassword;

    // meminta data berupa informasi customer dan random password yang belum diencrypt
    public function __construct(Customer $customer, $randomPassword)
    {
        $this->customer = $customer;
        $this->randomPassword = $randomPassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // mengeset subject email, view mana yang akan di-load dan data apa yang akan dipassing ke view
        return $this->subject('Verifikasi Pendaftaran Anda')
            ->view('emails.register')
            ->with([
                'customer'  => $this->customer,
                'password'  => $this->randomPassword
            ]);
    }
}
