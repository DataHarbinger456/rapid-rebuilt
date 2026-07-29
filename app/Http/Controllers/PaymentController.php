<?php
namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Request $request)
    {
        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $request->amount * 100, // Cents
                    'product_data' => [
                        'name' => $request->description,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
        ]);

        // Save payment record
        Payment::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'status' => 'pending',
            'stripe_payment_intent_id' => $checkout_session->id
        ]);

        return response()->json(['url' => $checkout_session->url]);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                config('services.stripe.webhook_secret')
            );

            // Handle specific event types
            if ($event->type == 'checkout.session.completed') {
                $session = $event->data->object;
                $payment = Payment::where('stripe_payment_intent_id', $session->id)->first();
                
                if ($payment) {
                    $payment->update([
                        'status' => 'completed',
                        'payment_method' => $session->payment_method_types[0]
                    ]);
                }
            }

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}