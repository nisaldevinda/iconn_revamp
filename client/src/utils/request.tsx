/** Request 网络请求工具 更详细的 api 文档: https://github.com/umijs/umi-request */
import { extend  } from 'umi-request';
import { message, notification } from 'antd';
import { getAuthority, unsetAuthority, setAuthority } from './authority';
import { history } from 'umi';

const codeMessage: Record<number, string> = {
  200: 'The server successfully returned the requested data. ',
  201: 'Create or modify data successfully. ',
  202: 'A request has entered the background queue (asynchronous task). ',
  204: 'Delete data successfully. ',
  400: 'There is an error in the request sent, and the server did not create or modify data. ',
  401: 'Unathorized! Session expired please relogin to continue',
  403: 'The user is authorized, but access is forbidden. ',
  404: 'The request sent is for a record that does not exist, and the server is not operating. ',
  406: 'The requested format is not available. ',
  410: 'The requested resource has been permanently deleted and will no longer be available. ',
  422: 'When creating an object, a validation error occurred. ',
  500: 'An error occurred in the server, please check the server. ',
  502: 'Gateway error. ',
  503: 'The service is unavailable, the server is temporarily overloaded or maintained. ',
  504: 'The gateway has timed out. ',
};

// for count unauthorized request
let errorCount=0;

export type APIResponse = {
  error: boolean;
  message: string;
  data: any;
};

/**
 * @zh-CN 异常处理程序
 * @en-US Exception handler
 */
const errorHandler = (error: { response: Response, data: any }, bypassErrorHandler: boolean = false, isAuthenticated: boolean = true): APIResponse => {
  const { response, data } = error;
  const { status } = response || {};
  const { message: responseMsg } = data || {};

  if (isAuthenticated && status == 401) {
    errorCount++;
    localStorage.clear();
    if (window.location.pathname !== '/auth/login') {
      if (errorCount == 1) {
        // prevent to show multiple error messages
        message.error(codeMessage[status]);
      }
      history.replace({
        pathname: '/auth/login',
      });
    }
  }

  let _message = status === undefined ? 'Network anomaly' : codeMessage[status];

  if (typeof responseMsg != "undefined") {
    _message = responseMsg;
  }

  if (!bypassErrorHandler) {
    message.error(_message);
  }

  throw { status, ...data, message : _message };
};

/**
 * @en-US Configure the default parameters for request
 * @zh-CN 配置request请求时的默认参数
 */
 const extendRequest = extend({
  // timeout: 1000,
  prefix: BACKEND_SERVER_HOST,
  errorHandler: (error) => errorHandler(error),
  headers: {
    'Content-Type': 'application/json, multipart/form-data',
  },
 });


const request = async (url:string, options:any = null, bypassErrorHandler:boolean = false, isAuthenticated: boolean = true): Promise<APIResponse> => {
  if(bypassErrorHandler) {
    extendRequest.extendOptions({
      errorHandler: (error) => errorHandler(error, bypassErrorHandler, isAuthenticated),
    });
  }

  if (url && url.charAt(0) === '/')
    url = url.substring(1);

  let userSession = getAuthority();
  if (userSession) {
    const currentTimeStamp = new Date().getTime()/1000;
    const isAccessTokenExpired = !userSession.access_token_expire_at
      || userSession.access_token_expire_at < currentTimeStamp;
    const isRefreshTokenExpired = !userSession.refresh_token_expire_at
      || userSession.refresh_token_expire_at < currentTimeStamp;

    if (isRefreshTokenExpired) {
      // console.log('Refresh token has been expired');
      unsetAuthority();
      history.push('/auth/login');
      return Promise.reject();
    }

    if (isAccessTokenExpired) {
      try {
        const response = await extendRequest('api/authentication', {
          method: 'POST',
          data: {
            grant_type: 'refresh_token'
          },
          credentials: 'include',
        });

        setAuthority(response.data);
        console.log('refresh >>>>');
        return request(url, options, bypassErrorHandler);

      } catch (error) {
        console.log('utils->requests.ts->request->ifAccessTokenExpired', error);
        unsetAuthority();
      }

      userSession = getAuthority();
    }
  }

  let response;
  if (options) {
    /**
     * credentials
     * same-origin: Send user credentials (cookies, basic http auth, etc..) if the URL is on the same origin as the calling script. This is the default value.
     * include: Always send user credentials (cookies, basic http auth, etc..), even for cross-origin calls.
     */
    options.credentials = 'include';
    console.log(url);
    response = extendRequest(url, options);
  } else {
    console.log(url);
    response = extendRequest.get(url, { credentials : 'include' });
  }

  return response;
};

export default request;
