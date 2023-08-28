import request from '@/utils/request';

export type AuthenticationParamsType = {
  grant_type: string;
  email: string;
  password: string;
  refresh_token: string;
  captureStatus: boolean;
};

export type ssoLoginParams = {
  type: string;
  token:string;
  email:string;
};


export type ForgotPasswordParamsType = {
  email: string;
};

export type ResetandForgotPasswordParamsType = {
  userId: number;
  password: string;
  verificationToken: string;
  confirmPassword: string;
};

export async function authentication(params: AuthenticationParamsType) {
  return request('/api/authentication/', {
    method: 'POST',
    data: params,
  }, true, false);
}

export async function ssoAuthLogin(params: ssoLoginParams) {
  return request('/api/sso-login/', {
    method: 'POST',
    data: params,
  }, false, false);
}

export async function getMsLoginUrl() {
  return request(`api/get-ms-login-url`);
}

export async function getFakeCaptcha(mobile: string) {
  return request(`/api/login/captcha?mobile=${mobile}`);
}

export async function forgotPassword(params: ForgotPasswordParamsType) {
  return request('/api/users/forgot-password', {
    method: 'POST',
    data: params,
  }, false, false);
}

export async function resetPasswordByEmail(params: any) {
  console.log(params)
  return request(`/api/users/${params.verificationToken}/reset-password-by-email`, {
    method: 'PUT',
    data: params,
  }, false, false);
}

export async function getAuthenticatedUser() {
  return request(`api/authenticated-user`);
}

export async function createUserPassword(params: any) {
  return request(`/api/users/${params.verificationToken}/create-password`, {
    method: 'PUT',
    data: params,
  }, false, false);
}

export async function checkVerficationToken(tokenProps: any) {
  return request(`/api/users/${tokenProps.verificationToken}/is-token-active/${tokenProps.type}`, {
    method: 'GET'
  }, true, false);
}

export async function logout() {
  return request('/api/logout', {
    method: 'POST'
  });
}
