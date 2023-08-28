<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService  = $paymentService;
    }

    public function createCheckoutSession(Request $request)
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result = $this->paymentService->createCheckoutSession($data);

        return $this->jsonResponse($result);
    }

    public function info()
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->paymentService->info();

        return $this->jsonResponse($result);
    }

    public function getPaymentMethods()
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->paymentService->getPaymentMethods();

        return $this->jsonResponse($result);
    }

    public function getPayments()
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->paymentService->getPayments();

        return $this->jsonResponse($result);
    }

    public function updateSubscription(Request $request)
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->paymentService->updateSubscription();

        return $this->jsonResponse($result);
    }

    public function cancelSubscription(Request $request)
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->paymentService->cancelSubscription();

        return $this->jsonResponse($result);
    }

    public function reactivateSubscription(Request $request)
    {
        $permission = $this->grantPermission('billing');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->paymentService->reactivateSubscription();

        return $this->jsonResponse($result);
    }

}
