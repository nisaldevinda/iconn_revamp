import { UserOutlined } from '@ant-design/icons';
import { Alert, Typography } from 'antd';
import React from 'react';
import ProForm, { LoginForm, ProFormText } from '@ant-design/pro-form';
import { useIntl, connect, FormattedMessage, Link } from 'umi';
import type { Dispatch } from 'umi';
import type { ForgotPasswordParamsType } from '@/services/login';
import type { ConnectState } from '@/models/connect';
import type { StateType } from '@/models/login';

import styles from './index.less';

const { Title } = Typography;

export type ForgotPasswordProps = {
    dispatch: Dispatch;
    userForgotPassword: StateType;
    submitting?: boolean;
};

const ForgotPasswordMessage: React.FC<{
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

const ForgotPassword: React.FC<ForgotPasswordProps> = (props) => {
    const { userForgotPassword = {}, submitting } = props;
    const { status, type: forgotPasswordType } = userForgotPassword;
    const intl = useIntl();
    const handleSubmit = (values: ForgotPasswordParamsType) => {
        const { dispatch } = props;
        dispatch({
            type: 'login/forgotPassword',
            payload: values,
        });
    };
    const titleRender = () => {
        return (<>
            <Title className={styles.title} level={1}>{intl.formatMessage({
                id: 'pages.forgotPassword.forgotpasswordTitle',
                defaultMessage: 'Forgot Password',
            })}</Title>
            <br />
        </>)
    }
    return (
        <div className={styles.main}>
            <LoginForm
                title={titleRender()}
                initialValues={{
                    autoForgotPassword: true,
                }}
                submitter={{
                    render: (_, dom) => dom.pop(),
                    submitButtonProps: {
                        loading: submitting,
                        size: 'large',
                        style: {
                            width: '100%',
                        },
                    },
                }}
                onFinish={(values) => {
                    handleSubmit(values as ForgotPasswordParamsType);
                    return Promise.resolve();
                }}
            >
                {status === 'error' && forgotPasswordType === 'password' && !submitting && (
                    <ForgotPasswordMessage
                        content={intl.formatMessage({
                            id: 'pages.forgotPassword.accountForgotPassword.errorMessage',
                            defaultMessage: 'Incorrect account or password（admin/ant.design)',
                        })}
                    />
                )}

                <ProFormText
                    name="email"
                    fieldProps={{
                        size: 'large',
                        prefix: <UserOutlined className={styles.prefixIcon} />,
                    }}
                    placeholder={intl.formatMessage({
                        id: 'pages.forgotPassword.username.placeholder',
                        defaultMessage: 'Email Address',
                    })}
                    rules={[
                        {
                            required: true,
                            message: (
                                <FormattedMessage
                                    id="pages.forgotPassword.username.required"
                                    defaultMessage="Please enter user email address!"
                                />
                            ),
                        },
                    ]}
                />
                <Link to="/auth/login" >
                    <FormattedMessage id="pages.forgotPassword.backToLogin" defaultMessage="Back to login" />
                </Link>
            </LoginForm>
        </div>
    );
};

export default connect(({ login, loading }: ConnectState) => ({
    userForgotPassword: login,
    submitting: loading.effects['login/forgotPassword'],
}))(ForgotPassword);
