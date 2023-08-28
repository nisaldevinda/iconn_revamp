import React, { useState, useEffect } from 'react';
import {
  Row,
  Card,
  Table,
  Space,
  Button,
  Typography,
  Progress,
  Tag,
  Alert,
  Col,
  message as Message,
  Spin,
  Empty,
  Modal,
} from 'antd';
import moment from 'moment';
import PaymentMethods from './PaymentMethods';
import SubscribeOrUpgrade from './SubscribeOrUpgrade';
import { createCheckoutSession, billingInfo, paymentMethods, payments, cancelSubscription, reactivateSubscription } from '@/services/payment';

const { Text } = Typography;
const { confirm } = Modal;

const Billing: React.FC = () => {
  const [showModal, setShowModal] = useState(false);
  const [tenant, setTenant] = useState({
    accountType: 'TRIAL',
    numberOfLicenses: 0,
    validFrom: Date.now() / 1000,
    validTo: Date.now() / 1000,
  });
  const [paymentMethodsData, setPaymentMethodsData] = useState([]);
  const [paymentsData, setPaymentsData] = useState([]);
  const [subscriptionStatus, setSubscriptionStatus] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  interface DataType {
    key: string;
    date: string;
    description: string;
    amount: string;
  }

  const columns: ColumnsType<DataType> = [
    {
      title: 'Date',
      dataIndex: 'date',
      key: 'date',
      render: (text) => <a>{text}</a>,
    },
    {
      title: 'Description',
      dataIndex: 'description',
      key: 'description',
    },
    {
      title: 'Amount',
      dataIndex: 'amount',
      key: 'amount',
    },
    {
      title: 'Invoice',
      key: 'invoice',
      render: (_, record: any) => (
        <Space size="middle">
          <a href={record.pdf}>PDF</a>
          <a href={record.view} target='_blank' >View</a>
        </Space>
      ),
    },
  ];

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const { data: billingData } = await billingInfo();
      console.log('billingData > ', billingData);
      const { accountType, numberOfLicenses, validFrom, validTo } = billingData;
      setTenant({ ...tenant, accountType, numberOfLicenses, validFrom, validTo });
      setSubscriptionStatus(billingData.subscription?.status ?? null);
      if (billingData.accountType === 'PAID') {
        const { data: paymentMethodsData } = await paymentMethods();
        const { data: paymentsData } = await payments();
        console.log('paymentsData > ', paymentsData);
        const history = paymentsData.filter((payment: any) => { return payment?.status === 'paid' }).map((payment: any) => {
          const { id } = payment;
          return {
            key: id,
            date: payment?.status_transitions?.paid_at
              ? moment.unix(payment?.status_transitions?.paid_at).format('MMM DD, YYYY')
              : '',
            description: payment?.status_transitions?.paid_at
              ? moment.unix(payment?.status_transitions?.paid_at).format('MMMM YYYY')
              : '-',
            amount: (payment?.amount_paid / 100).toFixed(2),
            pdf: payment?.invoice_pdf,
            view: payment?.hosted_invoice_url,
          };
        });
        setPaymentMethodsData(paymentMethodsData);
        console.log('history > ', history);
        setPaymentsData(history);
      }
      setLoading(false);
    } catch (error: any) {
      console.log(error);
      setLoading(false);
    }
  };

  const handleOk = async (data: any) => {
    try {
      setSubmitting(true);
      const { data: session } = await createCheckoutSession(data);
      window.location.assign(session.url);
    } catch (error: any) {
      console.log(error);
      Message.error(error?.message);
      setSubmitting(false);
    }
  };

  const subscriptionCancellationConfirm = () => {
    confirm({
      title: 'Subscription Cancellation',
      content: `The subscription will be canceled at the end of the period. You can reactivate the subscription before ${moment.unix(tenant.validTo).format('MMM DD, YYYY')}. Do you wish to continue?`,
      onOk: async () => {
        try {
          setSubmitting(true);
          const result = await cancelSubscription();
          console.log(result);
          const { error, message } = result;
          if (!error) {
            Message.success(message);
            setSubscriptionStatus("CANCELED");
          } else {
            Message.error(message);
          }
          setSubmitting(false);
        } catch (error: any) {
          console.log(error);
          Message.error(error?.message);
          setSubmitting(false);
        }
      },
      onCancel() {},
      okText: 'Yes, cancel it!',
      cancelText: 'No, return',
    });
  }

  const subscriptionReactiveConfirm = () => {
    confirm({
      title: 'Subscription Reactive',
      content: `Do you wish to reactive this subscription ?`,
      onOk: async () => {
        try {
          setSubmitting(true);
          const result = await reactivateSubscription();
          console.log(result);
          const { error, message } = result;
          if (!error) {
            Message.success(message);
            setSubscriptionStatus('ACTIVE');
          } else {
            Message.error(message);
          }
          setSubmitting(false);
        } catch (error: any) {
          console.log(error);
          Message.error(error?.message);
          setSubmitting(false);
        }
      },
      onCancel() {},
      okText: 'Yes, activate it!',
      cancelText: 'No, return',
    });
  };

  return (
    <Spin spinning={loading}>
      <Space direction="vertical" size="middle" style={{ display: 'flex' }}>
        <Card loading={loading}>
          <Row gutter={[8, 8]}>
            <Col span={12}>
              {tenant.accountType === 'PAID' ? (
                <>
                  <Typography.Title level={5} style={{ margin: 0 }}>
                    Active until {moment.unix(tenant.validTo).format('MMM DD, YYYY')}
                  </Typography.Title>
                  <Typography.Text style={{ margin: 0 }}>
                    We will send you a notification upon Subscription expiration
                  </Typography.Text>
                  <Typography.Title level={5} style={{ marginTop: 30 }}>
                    {tenant?.numberOfLicenses} Licences ${tenant?.numberOfLicenses * 4.5} Per Month
                  </Typography.Title>
                </>
              ) : (
                <>
                  <Typography.Title level={5} style={{ margin: 0 }}>
                    Free trial until {moment.unix(tenant.validTo).format('MMM DD, YYYY')}
                  </Typography.Title>
                  <Typography.Text style={{ margin: 0 }}>
                    We will send you a notification upon Subscription expiration
                  </Typography.Text>
                  <Typography.Title level={5} style={{ marginTop: 30 }}>
                    {tenant?.numberOfLicenses} Licences $0 Per Month
                  </Typography.Title>
                </>
              )}
            </Col>
            <Col span={12}>
              {/* <Typography.Title level={5} style={{ margin: 0 }}>
                Licences {tenant?.numberOfLicenses}
              </Typography.Title> */}
              <div style={{ width: '100%' }}>
                <Space>
                  {tenant.accountType === 'PAID' ? (
                    <Typography.Title level={5} style={{ margin: 0 }}>
                      Subscription status{' '}
                      <Tag color={subscriptionStatus == 'ACTIVE' ? 'green' : 'warning'}>
                        {subscriptionStatus}
                      </Tag>
                    </Typography.Title>
                  ) : null}
                  <Button
                    hidden={tenant.accountType !== 'TRIAL'}
                    type="primary"
                    onClick={() => {
                      setShowModal(true);
                    }}
                  >
                    Subscribe
                  </Button>
                  <Button
                    hidden={tenant.accountType === 'TRIAL' || subscriptionStatus != 'ACTIVE'}
                    onClick={subscriptionCancellationConfirm}
                  >
                    Cancel Subscription
                  </Button>
                  <Button
                    hidden={tenant.accountType === 'TRIAL' || subscriptionStatus != 'CANCELED'}
                    onClick={subscriptionReactiveConfirm}
                  >
                    Reactivate Subscription
                  </Button>
                </Space>
              </div>
            </Col>
          </Row>
        </Card>
        <Card
          loading={loading}
          title="Payment Methods"
          extra={<Text type="secondary">Credit / Debit Card</Text>}
        >
          {paymentMethodsData.length > 0 ? (
            <>
              <Row gutter={[8, 8]}>
                <Col span={24}>
                  <Typography.Title level={5} style={{ margin: 0 }}>
                    My Cards
                  </Typography.Title>
                </Col>
              </Row>
              <Row gutter={[8, 8]}>
                <Col span={12}>
                  <Row>
                    <Space>
                      <Text strong>{paymentMethodsData[0]?.billing_details.name}</Text>
                      <Tag color="green">Active</Tag>
                    </Space>
                  </Row>
                  <Row>
                    <Space>
                      <PaymentMethods icon={paymentMethodsData[0]?.card.brand} />
                      <Space direction="vertical">
                        <Text
                          strong
                        >{`${paymentMethodsData[0]?.card.brand} **** ${paymentMethodsData[0]?.card.last4}`}</Text>
                        <Text type="secondary">
                          Card expires at {paymentMethodsData[0]?.card.exp_month} /{' '}
                          {paymentMethodsData[0]?.card.exp_year}
                        </Text>
                      </Space>
                    </Space>
                  </Row>
                </Col>
                <Col span={12}>
                  <Alert
                    message="Important Note!"
                    description="Please carefully read Product Termsadding your new payment card"
                    type="info"
                    action={
                      <Space>
                        <Button
                          type="primary"
                          ghost
                          onClick={() => {
                            handleOk({ mode: 'setup' });
                          }}
                        >
                          Change Card
                        </Button>
                      </Space>
                    }
                  />
                </Col>
              </Row>
            </>
          ) : (
            <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
          )}
        </Card>
        <Card loading={loading} title="Billing History">
          <Table columns={columns} dataSource={paymentsData} />
        </Card>
      </Space>
      <SubscribeOrUpgrade
        isSubmitting={submitting}
        isModalOpen={showModal}
        handleOk={handleOk}
        handleCancel={() => {
          setShowModal(false);
        }}
      />
    </Spin>
  );
};

export default Billing;
