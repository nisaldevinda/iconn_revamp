import { stringify } from 'querystring';
import { Reducer, Effect, getIntl, getLocale } from 'umi';
import { history } from 'umi';
import _ from 'lodash';

import {
  authentication,
  forgotPassword,
  resetPasswordByEmail,
  ssoAuthLogin,
  createUserPassword,
  getAuthenticatedUser,
  logout,
} from '@/services/login';
import { setAuthority } from '@/utils/authority';
import { setPermissions } from '@/utils/permission';
import { getPageQuery } from '@/utils/utils';
import { message } from 'antd';
import { getAllModel } from '@/services/model';
import { setModels } from '@/utils/model';

export type StateType = {
  status?: 'ok' | 'error';
  type?: string;
  currentAuthority?: 'user' | 'guest' | 'admin';
  captureRequires?: boolean;
};

export type LoginModelType = {
  namespace: string;
  state: StateType;
  effects: {
    login: Effect;
    logout: Effect;
    forgotPassword: Effect;
    ssoLogin: Effect;
    resetPasswordByEmail: Effect;
    changeUserPassword: Effect;
  };
  reducers: {
    changeLoginStatus: Reducer<StateType>;
    changePermissions: Reducer<StateType>;
    changeModels: Reducer<StateType>;
  };
};

const Model: LoginModelType = {
  namespace: 'login',

  state: {
    status: undefined,
  },

  effects: {
    *login({ payload }, { call, put }) {
      try {
        const response = yield call(authentication, payload);
        yield put({
          type: 'changeLoginStatus',
          payload: response,
        });

        const authUserResponse = yield call(getAuthenticatedUser, payload);
        yield put({
          type: 'changePermissions',
          payload: authUserResponse,
        });

        const allModelsResponse = yield call(getAllModel, payload);
        yield put({
          type: 'changeModels',
          payload: allModelsResponse,
        });

        if (response.statusCode === 200) {
          //   const intl = useIntl();
          const urlParams = new URL(window.location.href);
          const params = getPageQuery();
          message.success(
            getIntl(getLocale()).formatMessage({
              id: 'pages.login.welcome.message',
              defaultMessage: 'Welcome',
            }),
          );
          let { redirect } = params as { redirect: string };
          if (redirect) {
            const redirectUrlParams = new URL(redirect);
            if (redirectUrlParams.origin === urlParams.origin) {
              redirect = redirect.substr(urlParams.origin.length);
              if (window.routerBase !== '/') {
                redirect = redirect.replace(window.routerBase, '/');
              }
              if (redirect.match(/^\/.*#/)) {
                redirect = redirect.substr(redirect.indexOf('#'));
              }
            }
          }
          window.location.href = '/';
          return;
        } else {
          yield put({
            type: 'changeLoginStatus',
            payload: response.message,
          });
        }
      } catch (error) {
        message.error(
          getIntl(getLocale()).formatMessage({
            id: 'pages.login.error.message',
            defaultMessage: error.message,
          }),
        );
        yield put({
          type: 'changeLoginStatus',
          payload: error,
        });
      }
    },

    *forgotPassword({ payload }, { call, put }) {
      try {
        const response = yield call(forgotPassword, payload);

        if (response.statusCode === 200) {
          message.success(
            'Password Reset link has been sent to your Email Account. Please check your email',
          );
          window.location.href = '#/auth/login';
        }
      } catch (error) {
        yield put({
          type: 'changeLoginStatus',
          payload: error,
        });
      }
    },

    *resetPasswordByEmail({ payload }, { call, put }) {
      const response = yield call(resetPasswordByEmail, payload);
      yield put({
        type: 'changeLoginStatus',
        payload: response,
      });
      if (response.statusCode === 200) {
        message.success('Password was reset successfully.');
        window.location.href = '#/auth/login';
      }
    },

    *changeUserPassword({ payload }, { call, put }) {
      const response = yield call(createUserPassword, payload);
      yield put({
        type: 'changeLoginStatus',
        payload: response,
      });
      if (response.statusCode === 200) {
        message.success('Password created successfully.');
        window.location.href = '#/auth/login/';
      }
    },

    *logout({ payload }, { call }) {
      const response = yield call(logout, payload);
      const { redirect } = getPageQuery();
      localStorage.clear();
      // Note: There may be security issues, please note
      if (window.location.pathname !== '/auth/login' && !redirect) {
        history.replace({
          pathname: '/auth/login',
          search: stringify({
            redirect: window.location.href,
          }),
        });
      }
    },

    *ssoLogin({ payload }, { call, put }) {
      try {
        const response = yield call(ssoAuthLogin, payload);
        yield put({
          type: 'changeLoginStatus',
          payload: response,
        });

        const authUserResponse = yield call(getAuthenticatedUser, payload);
        yield put({
          type: 'changePermissions',
          payload: authUserResponse,
        });

        const allModelsResponse = yield call(getAllModel, payload);
        yield put({
          type: 'changeModels',
          payload: allModelsResponse,
        });

        if (response.statusCode === 200) {
          //   const intl = useIntl();
          const urlParams = new URL(window.location.href);
          const params = getPageQuery();
          message.success(
            getIntl(getLocale()).formatMessage({
              id: 'pages.login.welcome.message',
              defaultMessage: 'Welcome',
            }),
          );
          let { redirect } = params as { redirect: string };
          if (redirect) {
            const redirectUrlParams = new URL(redirect);
            if (redirectUrlParams.origin === urlParams.origin) {
              redirect = redirect.substr(urlParams.origin.length);
              if (window.routerBase !== '/') {
                redirect = redirect.replace(window.routerBase, '/');
              }
              if (redirect.match(/^\/.*#/)) {
                redirect = redirect.substr(redirect.indexOf('#'));
              }
            }
          }
          window.location.href = '/';
          return;
        } else {
          yield put({
            type: 'changeLoginStatus',
            payload: response.message,
          });
        }
      } catch (error) {
        message.error(
          getIntl(getLocale()).formatMessage({
            id: 'pages.login.error.message',
            defaultMessage: error.message,
          }),
        );
        yield put({
          type: 'changeLoginStatus',
          payload: error,
        });
      }
    },
  },

  reducers: {
    changeLoginStatus(state, { payload }) {
      if (payload.statusCode === 200) {
        setAuthority(payload.data);
        return {
          ...state,
          status: 'ok',
          type: 'account',
          captureRequires: false,
        };
      } else {
        return {
          ...state,
          status: 'error',
          type: 'account',
          captureRequires: payload.data?.captureRequires ?? false,
        };
      }
    },
    changePermissions(state, { payload }) {
      const { data } = payload;
      if (data && data.permissions) {
        setPermissions(data.permissions);
        return {
          ...state,
          status: 'ok',
          type: 'account',
        };
      }
      if (_.isEmpty(data.permissions) || !_.isObject(data.permissions)) {
        return {
          ...state,
          status: 'error',
          type: 'account',
        };
      }
    },
    changeModels(state, { payload }) {
      if (payload && payload.data) setModels(payload.data);

      return {
        ...state,
        status: 'ok',
        type: 'account',
      };
    },
  },
};

export default Model;
