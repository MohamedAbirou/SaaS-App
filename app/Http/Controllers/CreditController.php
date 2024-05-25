<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeatureResource;
use App\Http\Resources\PackageResource;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditController extends Controller
{
    public function index()
    {
        $packages = Package::all();
        $features = Feature::where("active", true)->get();

        return Inertia::render("Credit/Index", [
            'packages' => PackageResource::collection($packages),
            'features' => FeatureResource::collection($features),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function buyCredits(Package $package)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package->name . '-' . $package->credits . ' credits',
                        ],
                        'unit_amount' => $package->price * 100,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => route('credit.success', [], true),
            'cancel_url' => route('credit.cancel', [], true),
        ]);

        Transaction::create([
            'status' => 'pending',
            'price' => $package->price,
            'credits' => $package->credits,
            'session_id' => $checkout_session->id,
            'user_id' => auth()->user()->id,
            'package_id' => $package->id
        ]);

        return redirect($checkout_session->url);
    }

    public function success()
    {
        return to_route('credit.index')->with('success', 'You have successfully purchased new credits');
    }

    public function cancel()
    {
        return to_route('credit.index')->with('error', 'There was an error while purchasing new credits, please try again later');
    }

    public function webhook()
    {
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            http_response_code(400);
            exit();
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                $transaction = Transaction::where('session_id', $session->id)->first();

                if ($transaction && $transaction->status == 'pending') {
                    $transaction->status = 'paid';
                    $transaction->save();
                    $transaction->user->available_credits += $transaction->credits;
                    $transaction->user->save();
                }
                break;
            default:
                echo 'Received unknown event type ' . $event->type;
                exit();
        }

        return response('Success', 200);
    }
}
