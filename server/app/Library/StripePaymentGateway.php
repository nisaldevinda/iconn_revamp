<?php

namespace App\Library;

use App\Library\Interfaces\PaymentInterface;
use Stripe\StripeClient;

class StripePaymentGateway implements PaymentInterface
{
    private $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    public function createCheckoutSession($mode, $customerId, $quantity, $priceId, $packageId, $tenantId)
    {
        try {
            $clientHost = config('app.client_url');
            $options = [];

            if ($mode === 'subscription') {
                $options = [
                    'payment_method_types' => ["card"],
                    'client_reference_id' => $tenantId,
                    'line_items' => [
                        [
                            'price' => $priceId,
                            'quantity' => $quantity
                        ]
                    ],
                    'customer' => $customerId,
                    'metadata' => [
                        'package_id' => $packageId,
                        'tenant_id' => $tenantId
                    ],
                    'mode' => $mode,
                    // ?session_id={CHECKOUT_SESSION_ID} means the redirect will have the session ID set as a query param
                    'success_url' => "$clientHost/#/settings/billing?session_id={CHECKOUT_SESSION_ID}",
                    'cancel_url' => "$clientHost/#/settings/billing?session_id=null"
                ];
            } else if ($mode === 'setup') {
                $options = [
                    'customer' => $customerId,
                    'metadata' => [
                        'tenant_id' => $tenantId
                    ],
                    'payment_method_types' => ['card'],
                    'mode' => 'setup',
                    'success_url' => "$clientHost/#/settings/billing?session_id={CHECKOUT_SESSION_ID}",
                    'cancel_url' => "$clientHost/#/settings/billing?session_id=null",
                ];
            }

            $checkoutSession = $this->stripe->checkout->sessions->create($options);

            return $checkoutSession;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function fetchCheckoutSession($sessionId)
    {
    }
    public function createSubscription($options)
    {
    }

    public function updateSubscription($subscriptionId, $quantity)
    {
        try {
            return $this->stripe->subscriptions->update($subscriptionId, ['quantity' => $quantity]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function cancelSubscription($subscriptionId)
    {
        try {
            // return $this->stripe->subscriptions->cancel($subscriptionId);
            return $this->stripe->subscriptions->update($subscriptionId, ['cancel_at_period_end' => true]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function reactivateSubscription($subscriptionId)
    {
        try {
            return $this->stripe->subscriptions->update($subscriptionId, ['cancel_at' => null]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getSubscriptionById($subscriptionId)
    {
        try {
            return $this->stripe->subscriptions->retrieve($subscriptionId);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createCustomer($name, $email, $tenantId)
    {
        try {
            $options = [
                'email' => $email,
                'name' => $name,
                'metadata' => [
                    'tenantId' => $tenantId
                ]
            ];

            $customer = $this->stripe->customers->create($options);

            return $customer;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getCustomerById($id)
    {
    }

    public function getCustomerPaymentCards($customerId)
    {
        try {
            return $this->stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card'
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getCustomerInvoices($customerId)
    {
        try {
            return $this->stripe->invoices->all([[
                'customer' => $customerId,
            ]]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getCustomerInvoiceById($id)
    {
    }

    public function changePaymentCard($options)
    {
    }

    public function detachPaymentMethod($customerId, $paymentMethodId)
    {
        try {
            return $this->stripe->paymentMethods->detach(
                $paymentMethodId,
                ['customer' => $customerId]
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
