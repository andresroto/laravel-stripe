<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{

    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $products = Product::orderByDesc('id')->get();

        return view('product.index', compact('products'));
    }

    /**
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     * @throws ApiErrorException
     */
    public function checkout(): \Illuminate\Foundation\Application|Redirector|RedirectResponse|Application
    {
        $products = Product::all();
        $lineItems = [];
        $totalPrice = 0;

        foreach ($products as $product) {

            $totalPrice += $product->price;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->name,
                        'images' => [
                            $product->image,
                        ]
                    ],
                    'unit_amount' => $product->price * 100,
                ],
                'quantity' => 1,
            ];
        }

        $session = $this->stripe->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success', [], true),
            'cancel_url' => route('checkout.refund', [], true),
        ]);

        // Save current session
        Session::put('session_id', $session->id);

        $order = new Order;
        $order->status = 'unpaid';
        $order->total_price = $totalPrice;
        $order->session_id = $session->id;
        $order->save();

        return redirect($session->url);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function success(): \Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|Factory|Application
    {
        try {

            $sessionId = Session::get('session_id');

            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            if (!$session) {
                throw new NotFoundHttpException;
            }

            $customer = $session->customer_details;

            $order = Order::whereSessionId($sessionId)->firstOrFail();

            if ($order->status === 'unpaid') {
                $order->status = 'paid';
                $order->payment_intent_id = $session->payment_intent;;
                $order->save();
            }

            return view('product.checkout-success', compact(['customer']));
        } catch (Exception $e) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|RedirectResponse|Response|Redirector
     * @throws Exception
     */
    public function refund(): \Illuminate\Foundation\Application|Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        try {

            $sessionId = Session::get('session_id');

            $order = Order::whereSessionId($sessionId)->firstOrFail();

            $this->stripe->refunds->create(['payment_intent' => $order->payment_intent_id]);

            if ($order->status === 'refunded') {
                throw new Exception('You have already refunded this order');
            }

            $order->status = 'refunded';
            $order->save();

            return redirect(route('index'));
        } catch (ApiErrorException $e) {
            return response([
                'Error:' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|Response
     * @throws ApiErrorException
     */
    public function webhook(): \Illuminate\Foundation\Application|Response|Application|ResponseFactory
    {
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            return response('', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                if (!$session) {
                    throw new NotFoundHttpException;
                }

                $order = Order::whereSessionId($session->id)->first();

                Log::info('checkout.session.completed', [
                    'session' => $session,
                    'order' => $order
                ]);

                break;
            case 'charge.refunded':
                $session = $event->data->object;

                $order = Order::wherePaymentIntentId($session->payment_intent)->first();

               if (!$order) {
                   throw new NotFoundHttpException;
               }

               $order->update([
                   'session_id' => $session->id,
               ]);

                Log::info('charge.refunded', [
                    'session' => $session,
                    'order' => $order,
                ]);

                break;
            default:
                echo 'Received unknown event type ' . $event->type;
        }

        return response('OK');
    }
}
