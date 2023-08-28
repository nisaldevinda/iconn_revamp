<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Session;
use App\Library\Store;
use App\Library\StripePaymentGateway;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    
    private $store;
    private $session;
    private $paymentGateway;


    public function __construct(Store $store, Session $session, StripePaymentGateway $paymentGateway)
    {
        $this->store = $store;
        $this->session = $session;
        $this->paymentGateway = $paymentGateway;
    }

    public function createCheckoutSession($data)
    {
        try {
            $tenantId = $this->session->getTenantId();
            $customerId = null;
            $mode = empty($data['mode']) ? null : $data['mode'];

            if (!in_array($mode, ['subscription', 'setup'])) {
                return $this->error(400, Lang::get('paymentMessages.basic.ERR_CHECKOUT_SESSION_MODE'), null);
            }

            $tenant = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)->first();

            if (!$tenant) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_COMPANY_NOT_EXIST'), null);
            }

            if ($mode === 'setup') {
                $customerId = $tenant->paymentGatewayCustomerId;
                $checkoutSession = $this->paymentGateway->createCheckoutSession($mode, $customerId, null, null, null, $tenantId);

                return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), $checkoutSession);
            }


            if (!is_null($tenant->subscriptionId)) {
                $subscription = DB::connection('portal')->table('subscription')
                                    ->where('id', '=', $tenant->subscriptionId)
                                    ->whereNotIn('status', ['CANCELLED'])
                                    ->first();

                if ($subscription) {
                    return $this->error(400, Lang::get('paymentMessages.basic.ERR_ONGOING_SUBSCRIPTION_EXIST'), null);
                }
            }

            $package = DB::connection('portal')->table('package')->first();

            if (!$package) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_PACKAGE_NOT_EXIST'), null);
            }

            if (is_null($tenant->paymentGatewayCustomerId)) {
                $sessionUser = $this->session->getUser();
                $email = $sessionUser->email;
                $name = "$sessionUser->firstName $sessionUser->lastName";
                $customer = $this->paymentGateway->createCustomer($name, $email, $tenantId);
                $customerId = $customer['id'];
            } else {
                $customerId = $tenant->paymentGatewayCustomerId;
            }

            $packageId = $package->id;
            $productId = $package->paymentGatewayProductId;
            $quantity = empty($data['quantity']) ? 0 : $data['quantity'];
            

            $checkoutSession = $this->paymentGateway->createCheckoutSession($mode, $customerId, $quantity, $productId, $packageId, $tenantId);

            return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), $checkoutSession);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('paymentMessages.basic.ERR_GET'), null);
        }
    }

    public function updateSubscription($quantity = null)
    {
        try {

            if (!config('app.portal_enabled')) {
                return ['error' => false, 'message' => 'Portal is not enabled.'];
            }

            $tenantId = $this->session->getTenantId();

            if (empty($tenantId)) {
                $company = DB::table('company')->first(['tenantId']);
                $tenantId = $company->tenantId;
            }

            if (is_null($quantity)) {
                $numOfEmployees = DB::table('employee')->where('isActive', '=', true)
                            ->where('isDelete', '=',  false)
                            ->count(['id']);
                $quantity = $numOfEmployees;
            }

            $minimumLicensesCount = config('app.number_of_free_licenses');
            $quantity = $quantity < $minimumLicensesCount ? $minimumLicensesCount : $quantity;

            $subscription = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)
                        ->join('subscription', 'subscription.id', '=', 'tenant.subscriptionId')
                        ->first();

            if (!$subscription) {
                return ['error' => true, 'message' => Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_NOT_EXIST')];
            }

            $subscriptionId = $subscription->paymentGatewaySubscriptionId;

            $this->paymentGateway->updateSubscription($subscriptionId, $quantity);

            return ['error' => false, 'message' => 'Success'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ['error' => true, 'message' => Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION')];
        }
    }

    public function info()
    {
        try {
            $tenantId = $this->session->getTenantId();

            $tenant = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)->first();

            if (!$tenant) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_COMPANY_NOT_EXIST'), null);
            }

            $tenant->subscription = null;

            if (!is_null($tenant->subscriptionId)) {
                $subscription = DB::connection('portal')->table('subscription')->where('id', '=', $tenant->subscriptionId)->first();
                $tenant->subscription = $subscription;
            }

            return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), $tenant);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('paymentMessages.basic.ERR_GET'), null);
        }
    }

    public function getPaymentMethods()
    {
        try {
            $tenantId = $this->session->getTenantId();

            $tenant = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)->first();

            if (!$tenant) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_COMPANY_NOT_EXIST'), null);
            }

            if (is_null($tenant->paymentGatewayCustomerId)) {
                return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), []);
            }

            $cards = $this->paymentGateway->getCustomerPaymentCards($tenant->paymentGatewayCustomerId);

            return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), $cards['data']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('paymentMessages.basic.ERR_GET'), null);
        }
    }

    public function getPayments()
    {
        try {
            $tenantId = $this->session->getTenantId();

            $tenant = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)->first();

            if (!$tenant) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_COMPANY_NOT_EXIST'), null);
            }

            if (is_null($tenant->paymentGatewayCustomerId)) {
                return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), []);
            }

            $invoices = $this->paymentGateway->getCustomerInvoices($tenant->paymentGatewayCustomerId);

            return $this->success(200, Lang::get('paymentMessages.basic.SUCC_GET'), $invoices['data']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('paymentMessages.basic.ERR_GET'), null);
        }
    }

    public function cancelSubscription()
    {
        try {

            $tenantId = $this->session->getTenantId();

            if (empty($tenantId)) {
                $company = DB::table('company')->first(['tenantId']);
                $tenantId = $company->tenantId;
            }

            $subscription = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)
                ->join('subscription', 'subscription.id', '=', 'tenant.subscriptionId')
                ->first();

            if (!$subscription) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_NOT_EXIST'), null);
            }

            if (strtolower($subscription->status) === 'canceled') {
                return $this->error(400, Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_ALREADY_CANCELED'), null);
            }

            $subscriptionId = $subscription->paymentGatewaySubscriptionId;

            $result = $this->paymentGateway->cancelSubscription($subscriptionId);

            return $this->success(200, Lang::get('paymentMessages.basic.SUCC_SUBSCRIPTION_CANCELED'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_CANCEL'), null);
        }
    }

    public function reactivateSubscription()
    {
        try {

            $tenantId = $this->session->getTenantId();

            if (empty($tenantId)) {
                $company = DB::table('company')->first(['tenantId']);
                $tenantId = $company->tenantId;
            }

            $subscription = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)
            ->join('subscription', 'subscription.id', '=', 'tenant.subscriptionId')
            ->first();

            if (!$subscription) {
                return $this->error(404, Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_NOT_EXIST'), null);
            }

            if (strtolower($subscription->status) !== 'canceled') {
                return $this->error(400, Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_NOT_CANCELED'), null);
            }

            $subscriptionId = $subscription->paymentGatewaySubscriptionId;

            $result = $this->paymentGateway->reactivateSubscription($subscriptionId);

            return $this->success(200, Lang::get('paymentMessages.basic.SUCC_SUBSCRIPTION_REACTIVATED'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('paymentMessages.basic.ERR_SUBSCRIPTION_REACTIVATED'), null);
        }
    }

    public function validatePaymentStatus($numberOfPendingRecords = 1)
    {
        try {

            if (!config('app.portal_enabled')) {
                return ['error' => false, 'message' => 'success'];
            }

            $tenantId = $this->session->getTenantId();

            if (empty($tenantId)) {
                $company = DB::table('company')->first(['tenantId']);
                $tenantId = $company->tenantId;
            }

            $subscription = DB::connection('portal')->table('tenant')->where('subdomain', '=', $tenantId)
                            ->join('subscription', 'subscription.id', '=', 'tenant.subscriptionId', 'left')
                            ->first();

            if ($subscription->accountType === 'PAID' && in_array(strtoupper($subscription->status), ['CANCELED', 'UNPAID'])) {
                return ['error' => true, 'message' => 'Subscription has been canceled.'];
            }

            if ($subscription->accountType === 'TRIAL') {
                $numOfEmployees = DB::table('employee')->where('isActive', '=', true)
                ->where('isDelete', '=',  false)
                    ->count(['id']);

                $pendingTotal = $numOfEmployees + $numberOfPendingRecords;

                if (config('app.number_of_free_licenses') < $pendingTotal) {
                    return ['error' => true, 'message' => 'The free license count has been exceeded.'];
                }
            }

            return ['error' => false, 'message' => 'success'];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ['error' => true, 'message' => 'An error has occurred during the validation of the payment status.'];
        }
    }

}
