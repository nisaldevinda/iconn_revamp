<?php

namespace App\Library\Interfaces;

interface PaymentInterface
{
    public function createCheckoutSession($mode, $customerId, $quantity, $priceId, $packageId, $companyId);
    public function fetchCheckoutSession($sessionId);
    public function createSubscription($options);
    public function updateSubscription($subscriptionId, $quantity);
    public function cancelSubscription($subscriptionId);
    public function reactivateSubscription($subscriptionId);
    public function getSubscriptionById($subscriptionId);
    public function createCustomer($name, $email, $tenantId);
    public function getCustomerById($id);
    public function getCustomerInvoices($customerId);
    public function getCustomerInvoiceById($id);
    public function changePaymentCard($options);
    public function getCustomerPaymentCards($customerId);
    public function detachPaymentMethod($customerId, $paymentMethodId);
}
