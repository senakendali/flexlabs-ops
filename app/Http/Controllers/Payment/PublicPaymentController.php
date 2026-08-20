<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PublicPaymentController extends Controller
{
    public function __construct(
        protected PaymentController $paymentController
    ) {
    }

    /**
     * Public payment page.
     *
     * Gunakan flow milik PaymentController agar admin invoice dan public
     * invoice memakai relasi, customer, serta financial summary yang sama.
     * PublicPaymentController tidak menghitung ulang invoice di sini.
     */
    public function show(string $token): View
    {
        return $this->paymentController->publicShow($token);
    }

    /**
     * Backward-compatible alias jika route lama masih memakai method show_.
     */
    public function show_(string $token): View
    {
        return $this->show($token);
    }
}