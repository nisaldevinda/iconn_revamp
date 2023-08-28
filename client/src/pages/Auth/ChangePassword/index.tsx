import React from "react";
import { PageContainer } from "@ant-design/pro-layout";
import {
  Form,
  Row,
  Input,
  Col,
  Card,
  Space,
  Button,
  message as Message,
} from 'antd';
import { useIntl ,useParams} from 'umi';
import { changeUserPassword } from '@/services/user';
import { PasswordInput} from 'antd-password-input-strength';
export type EditUserParams = {
    id: string;
  };
const ChangePassword: React.FC = () => {
  const [form] = Form.useForm();
  const { id } = useParams<EditUserParams>();
  const intl = useIntl();
    
  const onFinish = async (values: any) => {
    try {
      const requestData ={
        id,
        currentPassword:values.currentPassword,
        password:values.password
      }
      const { message} = await changeUserPassword(requestData)
      Message.success(message);
      form.resetFields();
    } catch(error) {
      Message.error(error.message);
    } 
  };
  return (
    <PageContainer >
      <Card>
        <Col offset={1} span={8}>
          <Form form={form} layout="vertical" onFinish={onFinish}>
            <Form.Item
              name="currentPassword"
              label="Current Password"
              rules={[
                {
                  required: true,
                  message: `${intl.formatMessage({
                    id: 'currentPassword',
                    defaultMessage: 'Required',
                  })}`,
                },
              ]}
            >
              <Input.Password size="large" />
            </Form.Item>
            <Form.Item
              name="password"
              label="New Password"
              rules={[
                {
                  required: true,
                  message: `${intl.formatMessage({
                    id: 'currentPassword',
                    defaultMessage: 'Required',
                  })}`,
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
              hasFeedback
            >
              <PasswordInput 
                inputProps={{
                  size: 'large',
                }}
               />
            </Form.Item>
            <Form.Item
              name="confirm"
              label="Confirm Password"
              dependencies={['password']}
              hasFeedback
              rules={[
                {
                  required: true,
                  message: `${intl.formatMessage({
                    id: 'currentPassword',
                    defaultMessage: 'Required',
                  })}`,
                },
                ({ getFieldValue }) => ({
                    validator(_, value) {
                      if (!value || getFieldValue('password') === value) {
                        return Promise.resolve();
                      }
                      return Promise.reject(new Error('Password does not match. Try again.'));
                    },
                }),
              ]}
            >
              <Input.Password size="large" />
            </Form.Item>
            <Row>
              <Col span={24} style={{ textAlign: 'left' }}>
                <Form.Item>
                  <Space>
                    <Button type="primary" htmlType="submit">
                      save
                    </Button>
                  </Space>
                </Form.Item>
              </Col>
            </Row>
          </Form>
        </Col>
      </Card>
    </PageContainer>
  )
};

export default ChangePassword;
