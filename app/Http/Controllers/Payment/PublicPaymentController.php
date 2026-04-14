<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicPaymentController extends Controller
{
    public function show(string $token): View
    {
        $payment = Payment::with([
                'order:id,student_id,batch_id,final_price,status',
                'order.student:id,full_name,email,phone,city',
                'order.batch:id,program_id,name,start_date,end_date',
                'order.batch.program:id,name',
                'paymentSchedule:id,order_id,title,amount,due_date,status',
            ])
            ->where('public_token', $token)
            ->first();

        if (!$payment) {
            throw new NotFoundHttpException('Payment link not found.');
        }

        $isExpired = $payment->expired_at && now()->gt($payment->expired_at);
        $isPaid = $payment->status === 'paid';

        return view('payments.public-show', [
            'payment' => $payment,
            'order' => $payment->order,
            'student' => $payment->order?->student,
            'batch' => $payment->order?->batch,
            'program' => $payment->order?->batch?->program,
            'schedule' => $payment->paymentSchedule,
            'isExpired' => $isExpired,
            'isPaid' => $isPaid,
        ]);
    }
}