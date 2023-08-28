import request from '@/utils/request';

export async function createCheckoutSession(data: any) {
  return request(
    '/api/payments/create-checkout-session',
    {
      method: 'POST',
      data: {
        ...data,
      },
    }
  );
}

export async function billingInfo() {
  return request('/api/payments/info');
}

export async function paymentMethods() {
  return request('/api/payments/payment-methods');
}

export async function payments() {
  return request('/api/payments/list');
}

export async function updateSubscription() {
  return request('/api/payments/update-subscription', {
    method: 'PUT',
    data: {}
  });
}

export async function cancelSubscription() {
  return request('/api/payments/cancel-subscription', {
    method: 'POST',
    data: {},
  });
}

export async function reactivateSubscription() {
  return request('/api/payments/reactivate-subscription', {
    method: 'POST',
    data: {},
  });
}