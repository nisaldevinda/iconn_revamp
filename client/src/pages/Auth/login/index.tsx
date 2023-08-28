import {
  WindowsFilled,
  LockOutlined,
  MailOutlined,
  MobileOutlined,
  GoogleOutlined,
  UserOutlined,
} from '@ant-design/icons';
import { Alert, Button, Divider, message, Space, Tabs, Form } from 'antd';
import ProForm, { LoginForm, ProFormCaptcha, ProFormText } from '@ant-design/pro-form';
import React, { useEffect, useState } from 'react';
import { useIntl, connect, FormattedMessage, Link } from 'umi';
import { getFakeCaptcha } from '@/services/login';
import type { Dispatch } from 'umi';
import type { StateType } from '@/models/login';
import type { AuthenticationParamsType } from '@/services/login';
import type { ConnectState } from '@/models/connect';
import { useMsal, useIsAuthenticated, MsalProvider, useAccount } from "@azure/msal-react";
import { loginRequest, msalConfig } from "../../../../config/authConfig";
import styles from './index.less';
import { PublicClientApplication } from "@azure/msal-browser";
import { GoogleLogin } from 'react-google-login';
import Title from 'antd/lib/typography/Title';
import ReCAPTCHA from "react-google-recaptcha";
import _ from '@umijs/deps/compiled/lodash';
import lodash from 'lodash';

export type LoginProps = {
  dispatch: Dispatch;
  userLogin: StateType;
  submitting?: boolean;
};
const msalInstance = new PublicClientApplication(msalConfig);

const LoginMessage: React.FC<{
  content: string;
}> = ({ content }) => (
  <Alert
    style={{
      marginBottom: 24,
    }}
    message={content}
    type="error"
    showIcon
  />
);

const Login: React.FC<LoginProps> = (props) => {

  const { userLogin, submitting } = props;
  const { status, type: loginType } = userLogin;
  const [grant_type, setGrantType] = useState<string>('password');
  const intl = useIntl();
  const [captchaValue, setCaptchaValue] = useState(false)
  const [captchaShow, setCaptchaShow] = useState(false)
  const [form] = Form.useForm();

  useEffect(() => {
    setCaptchaValue(false);
    setCaptchaShow(props.userLogin.captureRequires ?? false);
    }, [props.userLogin])

  const captchaOnchange = (value: any) => {
    form.setFieldsValue({ captcha: value });
    setCaptchaValue(true);
  }

  const captchaOnExpire = () => {
    setCaptchaValue(false);
  }

  const handleSubmit = async (values: AuthenticationParamsType) => {
    if (!captchaShow || (captchaShow && captchaValue ) ) {
      const { dispatch } = props;
      await dispatch({
        type: 'login/login',
        payload: { ...values, grant_type },
      });
    } else {
      message.error("Captcha verification failed");
    }
  };

  const SsoSignInButton = () => {
    const { dispatch } = props;
    const { instance, accounts } = useMsal();
    const isAuthenticated = useIsAuthenticated();
    const handleMicrosoftLogin = async () => {

      const res: any = await instance.loginPopup(loginRequest).catch(async () => {
      });

      if (!captchaShow || (captchaShow && captchaValue)) {
        await dispatch({
            type: 'login/ssoLogin',
            payload: {
                type: 'microsoft',
                token: res.idToken,
                email: res.account.username,
            },
            callback: (payload) => {
              console.log(payload);
            }
        });
      } else {
        message.error("Captcha verification failed");
      }
    }

    const handleGoogleLogin = async (googleData: any) => {
      if (!captchaShow || (captchaShow && captchaValue)) {
        await dispatch({
          type: 'login/ssoLogin',
          payload: {
            type: "google",
            token: lodash.get(googleData, "tokenId", ""),
            email: lodash.get(googleData, "profileObj.email", ""),
          },
        });
      } else {
        message.error("Captcha verification failed");
      }
    }

    return (
      <div className={styles.buttonGroup}>
        <Button data-key="microsoftLogin" className={styles.ssoButton} icon={<WindowsFilled />} size="large" onClick={handleMicrosoftLogin} >
          {intl.formatMessage({
            id: 'pages.login.microsoftlogin',
            defaultMessage: 'Sign in with Microsoft',
          })}
        </Button>
        <div className={styles.divider} />
        <GoogleLogin
          render={renderProps => (
            <Button data-key="googleLogin" className={styles.ssoButton} icon={<GoogleOutlined />} size="large" onClick={renderProps.onClick} disabled={renderProps.disabled} >
              {intl.formatMessage({
                id: 'pages.login.googleLogin',
                defaultMessage: 'Sign in with Google',
              })}
            </Button>
            // <GoogleOutlined onClick={renderProps.onClick} disabled={renderProps.disabled} className={styles.icon}/>
          )}
          clientId={GOOGLE_LOGIN_CLIENT_ID}
          buttonText="Log in with Google"
          onSuccess={handleGoogleLogin}
          redirectUri={REDIRECT_URI}
        />
        {/* {isAuthenticated?<div>{accounts[0].username}</div>:<div></div>} */}
      </div>
    )
  }

  const titleRender = () => {
    return (<>
      <Title className={styles.title} level={1}>{intl.formatMessage({
        id: 'pages.login.formTitle',
        defaultMessage: 'Login to Your Account',
      })}</Title>
      <br />
    </>)
  }
  return (
    <MsalProvider instance={msalInstance}>
      <div className={styles.main}>

        <LoginForm
          title={titleRender()}
          className={styles.form}
          initialValues={{
            autoLogin: true,
          }}

          submitter={{
            render: (_, dom) => dom.pop(),
            submitButtonProps: {
              loading: submitting,
              size: 'large',
              style: {
                width: '100%',
              },
              id: 'login'
            },
            searchConfig: {
              submitText: intl.formatMessage({
                id: 'pages.login.accountLogin.loginButton',
                defaultMessage: 'Login',
              })
            }
          }}

          onFinish={(values) => {
            const authenticationParams = {
              grant_type: values.grant_type,
              email: values.email,
              password: values.password,
              refresh_token: values.refresh_token,
              captureStatus: captchaValue
            }

            handleSubmit(authenticationParams as AuthenticationParamsType);
            return Promise.resolve();
          }}
        >

          {status === 'error' && loginType === 'password' && !submitting && (
            <LoginMessage
              content={intl.formatMessage({
                id: 'pages.login.accountLogin.errorMessage',
                defaultMessage: 'Incorrect account or password（admin/ant.design)',
              })}
            />
          )}
          {grant_type === 'password' && (
            <>
              <ProFormText
                data-key="email"
                name="email"
                fieldProps={{
                  size: 'large',
                  prefix: <UserOutlined className={styles.prefixIcon} />,
                }}
                placeholder={intl.formatMessage({
                  id: 'pages.login.username.placeholder',
                  defaultMessage: 'Email Address',
                })}
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.username.required"
                        defaultMessage="Please enter user email address!"
                      />
                    ),
                  },
                ]}
              />
              <ProFormText.Password
                data-key="password"
                name="password"
                fieldProps={{
                  size: 'large',
                  prefix: <LockOutlined className={styles.prefixIcon} />,
                }}
                placeholder={intl.formatMessage({
                  id: 'pages.login.password.placeholder',
                  defaultMessage: 'Password: ant.design',
                })}
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.password.required"
                        defaultMessage="Please enter password！"
                      />
                    ),
                  },
                ]}
              />

              {captchaShow ?
                <ReCAPTCHA 
                  style={{ width: "100%" }}
                  sitekey={RECAPTCHA_SITEKEY}
                  onChange={captchaOnchange}

                  onExpired={captchaOnExpire}
                /> : <></>}

            </>
          )}

          {status === 'error' && loginType === 'mobile' && !submitting && (
            <LoginMessage content="Verification code error" />
          )}
          {grant_type === 'mobile' && (
            <>
              <ProFormText
                fieldProps={{
                  size: 'large',
                  prefix: <MobileOutlined className={styles.prefixIcon} />,
                }}
                data-key="mobile"
                name="mobile"
                placeholder={intl.formatMessage({
                  id: 'pages.login.phoneNumber.placeholder',
                  defaultMessage: 'Phone number',
                })}
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.phoneNumber.required"
                        defaultMessage="Please enter phone number!"
                      />
                    ),
                  },
                  {
                    pattern: /^1\d{10}$/,
                    message: (
                      <FormattedMessage
                        id="pages.login.phoneNumber.invalid"
                        defaultMessage="Malformed phone number!"
                      />
                    ),
                  },
                ]}
              />
              <ProFormCaptcha
                fieldProps={{
                  size: 'large',
                  prefix: <MailOutlined className={styles.prefixIcon} />,
                }}
                captchaProps={{
                  size: 'large',
                }}
                placeholder={intl.formatMessage({
                  id: 'pages.login.captcha.placeholder',
                  defaultMessage: 'Please enter verification code',
                })}
                captchaTextRender={(timing, count) => {
                  if (timing) {
                    return `${count} ${intl.formatMessage({
                      id: 'pages.getCaptchaSecondText',
                      defaultMessage: 'Get verification code',
                    })}`;
                  }
                  return intl.formatMessage({
                    id: 'pages.login.phoneLogin.getVerificationCode',
                    defaultMessage: 'Get verification code',
                  });
                }}
                data-key="captcha"
                name="captcha"
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.captcha.required"
                        defaultMessage="Please enter verification code！"
                      />
                    ),
                  },
                ]}
                onGetCaptcha={async (mobile) => {
                  const result = await getFakeCaptcha(mobile);
                  if (result === false) {
                    return;
                  }
                  message.success(
                    'Get the verification code successfully! The verification code is: 1234',
                  );
                }}
              />
            </>
          )}
          <div
            style={{
              marginBottom: 24,
            }}
          >
            <Link
              style={{
                float: 'right',
              }}
              to="/auth/forgotPassword"
              data-key="forgotPassword"
            >
              <FormattedMessage id="pages.login.forgotPassword" defaultMessage="Forget password" />
            </Link>
            <div className={styles.divider} />

          </div>

        </LoginForm>
        <Divider>or</Divider>

        <SsoSignInButton></SsoSignInButton>
        {/* <TaobaoCircleOutlined className={styles.icon} />
        <WeiboCircleOutlined className={styles.icon} /> */}

      </div>
    </MsalProvider>
  );
};






export default connect(({ login, loading }: ConnectState) => ({
  userLogin: login,
  submitting: loading.effects['login/login'],
}))(Login);
