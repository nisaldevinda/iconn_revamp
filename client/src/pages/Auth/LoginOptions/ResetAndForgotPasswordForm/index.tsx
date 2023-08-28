import { UnlockOutlined } from '@ant-design/icons';
import { Alert, Typography, Spin ,Form} from 'antd';
import React from 'react';
import ProForm, { ProFormText } from '@ant-design/pro-form';
import { useIntl, connect, FormattedMessage } from 'umi';
import type { Dispatch } from 'umi';
import type { ResetandForgotPasswordParamsType } from '@/services/login';
import type { ConnectState } from '@/models/connect';
import type { StateType } from '@/models/login';
import { IntlShape } from 'react-intl';
import { PasswordInput} from 'antd-password-input-strength';

import styles from './index.less';

const { Title } = Typography;


export type ResetandForgotPasswordProps = {
    dispatch: Dispatch;
    userResetPassword: StateType;
    submitting?: boolean;
    createPasswordSubmitting?: boolean;
    type: 'forgot-password' | 'reset-password' | 'create-password';
    userId: string;
    verficationToken: string;
};

export type SubmittingStates = {
    submitting?: boolean;
    createPasswordSubmitting?: boolean;
};

const RenderPasswordMessage: React.FC<{
    type: any;
    intl: IntlShape;
    resetPasswordStates: StateType;
    submittingStates: SubmittingStates;
}> = ({ type, intl, resetPasswordStates, submittingStates }) => {
    const { status, type: resetPasswordType } = resetPasswordStates;

    if (status === 'error' && resetPasswordType === 'password' && !submittingStates.submitting) {
        if (type) {
            switch (type) {
                case 'forgot-password' || 'reset-password':
                    return (
                        <Alert
                            style={{
                                marginBottom: 24,
                            }}
                            message={intl.formatMessage({
                                id: 'pages.login.resetPassword.accountResetPassword.errorMessage',
                                defaultMessage: 'Password reset failed.',
                            })}
                            type="error"
                            showIcon
                        />
                    );
                case 'create-password':
                    return (
                        <Alert
                            style={{
                                marginBottom: 24,
                            }}
                            message={intl.formatMessage({
                                id: 'pages.login.createPassword.accountCreatePassword.errorMessage',
                                defaultMessage: 'Create password failed.',
                            })}
                            type="error"
                            showIcon
                        />
                    );
                default:
                    return <Spin />;
            }
        }
    }
    return null;
};

const RenderTitle: React.FC<{
    type: any;
    intl: IntlShape;
}> = ({ type, intl }) => {
    if (type) {
        switch (type) {
            case 'forgot-password':
                return (
                    <div className={styles.forgotAndResetTitle}>
                        <Title level={3}>
                            {intl.formatMessage({
                                id: 'pages.login.forgotPassword.forgotPasswordTitle',
                                defaultMessage: 'Forgot Password',
                            })}
                        </Title>
                    </div>
                );
            case 'reset-password':
                return (
                    <div className={styles.forgotAndResetTitle}>
                        <Title level={3}>
                            {intl.formatMessage({
                                id: 'pages.login.resetPassword.resetPasswordTitle',
                                defaultMessage: 'Reset Your Password',
                            })}
                        </Title>
                    </div>
                );
            case 'create-password':
                return (
                    <div className={styles.createPasswordTitle}>
                        <Title level={5}>
                            {intl.formatMessage({
                                id: 'pages.login.createPassword.createPasswordTitle',
                                defaultMessage: 'Setup password for your new account',
                            })}
                        </Title>
                    </div>
                );
            default:
                return <Spin />;
        }
    }
    return <Spin />;
};

const ResetAndForgotPasswordForm: React.FC<ResetandForgotPasswordProps> = (props) => {
    const {
        userResetPassword = {},
        submitting,
        type,
        userId,
        verficationToken,
        createPasswordSubmitting,
    } = props;
  
    const intl = useIntl();

    const dispatchCreateUserPassword = (values: any) => {
        const { dispatch } = props;
        dispatch({
            type: 'login/changeUserPassword',
            payload: values,
        });
    };

    const dispatchPasswordResetandForgot = (values: any) => {
        const { dispatch } = props;
        dispatch({
            type: 'login/resetPasswordByEmail',
            payload: values,
        });
    };

    const handlePasswordResetandForgotSubmit = (values: ResetandForgotPasswordParamsType) => {
        type == 'reset-password' || type == 'forgot-password'
            ? dispatchPasswordResetandForgot(values)
            : dispatchCreateUserPassword(values);
    };

    return (
        <div className={styles.main}>
            <ProForm
                initialValues={{
                    autoResetPassword: true,
                }}
                submitter={{
                    searchConfig: {
                        submitText: type == 'forgot-password' ? "Submit" : "Setup Password" ,
                    },
                    render: (_, dom) => dom.pop(),
                    submitButtonProps: {
                        loading: submitting || createPasswordSubmitting,
                        size: 'large',
                        style: {
                            width: '100%',
                        },
                    },
                }}
                onFinish={(values) => {
                    values['verificationToken'] = verficationToken;
                    values['id'] = userId;
                    handlePasswordResetandForgotSubmit(values as ResetandForgotPasswordParamsType);
                    return Promise.resolve();
                }}
            >
                <RenderPasswordMessage
                    submittingStates={{
                        submitting: submitting,
                        createPasswordSubmitting: createPasswordSubmitting,
                    }}
                    resetPasswordStates={userResetPassword}
                    type={type}
                    intl={intl}
                />
                <RenderTitle type={type} intl={intl} />
                <Form.Item
                  name="password"
                  rules={[
                  {
                    required: true,
                    message: (
                        <FormattedMessage
                            id="pages.login.resetPassword.password.required"
                            defaultMessage="Required"
                        />
                    ),
                  },
                  {
                    min: 8 ,
                    message :`${intl.formatMessage({
                      id: 'currentPassword',
                      defaultMessage: 'Minimum 8 characters',
                    })}`,
                  },
                  {
                    pattern:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
                    message: `${intl.formatMessage({
                       id: 'currentPassword',
                        defaultMessage: 'Password should contain at least one uppercase letter, one lowercase letter, one number and one special character ',
                    })}`,
                  }
                ]}
                >
                    <PasswordInput 
                      inputProps={{
                        size: 'large',
                        placeholder:`${intl.formatMessage({
                          id: 'pages.login.resetPassword.password.placeholder',
                          defaultMessage: 'New Password',
                        })}`,
                        prefix:<UnlockOutlined className={styles.prefixIcon} />,
                      }}
                    />
                </Form.Item>
                <ProFormText.Password
                    name="confirmPassword"
                    dependencies={['password']}
                    fieldProps={{
                        size: 'large',
                        prefix: <UnlockOutlined className={styles.prefixIcon} />,
                    }}
                    placeholder={intl.formatMessage({
                        id: 'pages.login.password.confirmPassword.placeholder',
                        defaultMessage: 'Re-Type New Password',
                    })}
                    rules={[
                        {
                            required: true,
                            message: (
                                <FormattedMessage
                                    id="pages.login.resetPassword.confirmPassword.required"
                                    defaultMessage="Required"
                                />
                            ),
                        },
                        ({ getFieldValue }) => ({
                            validator(_, value) {
                                if (!value || getFieldValue('password') === value) {
                                    return Promise.resolve();
                                }
                                return Promise.reject(
                                    new Error('Password does not match. Try again'),
                                );
                            },
                        }),
                    ]}
                />
            </ProForm>
        </div>
    );
};

export default connect(({ login, loading }: ConnectState) => ({
    userResetPassword: login,
    submitting: loading.effects['login/resetPasswordByEmail'],
    createPasswordSubmitting: loading.effects['login/changeUserPassword'],
}))(ResetAndForgotPasswordForm);
