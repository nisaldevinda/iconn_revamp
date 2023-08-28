// https://umijs.org/config/
import { defineConfig } from 'umi';
import defaultSettings from './defaultSettings';
import proxy from './proxy';
import routes from './routes';

const {
  REACT_APP_ENV,
  RECAPTCHA_SITEKEY,
  VERSION,
  SSO_REDIRECT_URI,
  MICROSOFT_REDIRECT_URI,
  MICROSOFT_CLIENTID,
  MICROSOFT_AUTHORITY,
  BACKEND_SERVER_HOST,
  REDIRECT_URI,
  GOOGLE_LOGIN_CLIENT_ID,
  TINY_API_KEY,
  STRIPE_PUBLISHABLE_KEY,
  NUMBER_OF_FREE_LICENSES,
} = process.env;

console.log(">>> BACKEND_SERVER_HOST >>> ", BACKEND_SERVER_HOST);

export default defineConfig({
  hash: true,
  antd: {},
  dva: {
    hmr: true,
  },
  history: {
    type: 'hash',
  },
  locale: {
    // default zh-CN
    default: 'en-US',
    antd: true,
    // default true, when it is true, will use `navigator.language` overwrite default
    baseNavigator: true,
  },
  dynamicImport: {
    loading: '@/components/PageLoading/index',
  },
  targets: {
    ie: 11,
  },
  // umi routes: https://umijs.org/docs/routing
  routes,
  // Theme for antd: https://ant.design/docs/react/customize-theme-cn
  theme: {
    'primary-color': defaultSettings.primaryColor,
  },
  title: false,
  ignoreMomentLocale: true,
  proxy: proxy[REACT_APP_ENV || 'dev'],
  manifest: {
    basePath: '/',
  },
  // 快速刷新功能 https://umijs.org/config#fastrefresh
  fastRefresh: {},
  esbuild: {},
  define: {
    VERSION: VERSION || null,
    BACKEND_SERVER_HOST: BACKEND_SERVER_HOST || 'http://localhost:8080/',
    GOOGLE_LOGIN_CLIENT_ID: GOOGLE_LOGIN_CLIENT_ID || '',
    REDIRECT_URI: REDIRECT_URI,
    MICROSOFT_CLIENTID: MICROSOFT_CLIENTID,
    MICROSOFT_AUTHORITY: MICROSOFT_AUTHORITY,
    MICROSOFT_REDIRECT_URI: MICROSOFT_REDIRECT_URI,
    SSO_REDIRECT_URI: SSO_REDIRECT_URI,
    RECAPTCHA_SITEKEY: RECAPTCHA_SITEKEY,
    TINY_API_KEY: TINY_API_KEY,
    STRIPE_PUBLISHABLE_KEY,
    NUMBER_OF_FREE_LICENSES,
  },

  webpack5: {},
});
