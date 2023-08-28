import React, { useEffect, useState, useRef } from 'react';
import { Col, Input, Row, message, Form, Tag, Avatar, Typography } from 'antd';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import { Access, useAccess, useIntl } from 'umi';
import moment from 'moment';
import { getModel, ModelType } from '@/services/model';
import { getAttachementList, getShortLeaveAttachementList } from '@/services/leave';
import { getEmployeeCurrentDetails } from '@/services/employee';
import { getReceiptAttachment } from '@/services/expenseModule';
import request, { APIResponse } from '@/utils/request';
import ApprovalLevelDetails from './approvalLevelDetails';
import ProTable from '@ant-design/pro-table';
import { DownloadOutlined } from '@ant-design/icons';

const { TextArea } = Input;

type ClaimProps = {
  claimRequestData: any;
  employeeFullName: string;
  setLeaveDataSet: any;
  fromLeaveRquestList: boolean;
  employeeId: string;
  scope: any;
  actions?: any;
  setApproverComment: any;
  setIsShowCancelView?: any;
  isShowCancelView?: any;
  setClaimRequestData?: any;
};

type TableListItem = {
  date: string;
  details: any;
};

const ClaimRequest: React.FC<ClaimProps> = (props) => {
  const access = useAccess();
  const [selectedClaimType, setSelectedClaimType] = useState<string | null>(null);
  const [selectedFinancialYear, setSelectedFinancialYear] = useState<string | null>(null);
  const [claimType, setClaimType] = useState<any>([]);
  const [financialYear, setFinancialYear] = useState<any>([]);
  const [imageUrl, setimageUrl] = useState<string | null>(null);
  const [claimRequestId, setCaimRequestId] = useState<string | null>(null);
  const [form] = Form.useForm();
  const intl = useIntl();
  const [jobTitle, setJobTitle] = useState<string | null>(null);
  const [receiptList, setReceiptList] = useState<any>([]);
  const { Title, Paragraph, Text } = Typography;
  const receiptTableRef = useRef<TableType>();
  const viewTableRef = useRef<TableType>();

  useEffect(() => {
    setSelectedClaimType(props.claimRequestData.claimTypeId);
    setSelectedFinancialYear(props.claimRequestData.financialYearId);
    setCaimRequestId(props.claimRequestData.id);
  });

  useEffect(() => {
    if (props.claimRequestData.id != undefined) {
      getClaimRequestReceiptDetails();
      getClaimType();
      getFinancialYear();
      getEmployeeProfileImage();
      getEmployeeRelateDataSet();
    }
  }, [claimRequestId]);

  useEffect(() => {
    return () => {
      props.setLeaveDataSet({});
    };
  }, []);

  const getReceiptAmountLabel = (amount) => {
    let label = '-';
    if (amount) {
      if ((amount ^ 0) !== amount) {
        label = amount !== 0 ? amount.toFixed(2).toString() : '0.00';
      } else {
        label = amount !== 0 ? amount.toString() + '.00' : '0.00';
      }
    }

    return label;
  };

  const columns = [
    {
      dataIndex: 'receiptNumber',
      title: 'Receipt No',
      // width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          props: {
            // style: { height: 10},
          },
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span style={{ marginTop: 1 }}>{el.receiptNumber}</span>&nbsp; &nbsp;
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'receiptDate',
      title: 'Receipt Date',
      // width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          props: {
            // style: { height: 10},
          },
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span style={{ marginTop: 1 }}>{el.receiptDate}</span>&nbsp; &nbsp;
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'receiptAmount',
      title: 'Receipt Amount',
      // width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          props: {
            // style: { height: 10},
          },
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span style={{ marginTop: 1 }}>{getReceiptAmountLabel(el.receiptAmount)}</span>&nbsp;
              &nbsp;
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'fileName',
      title: 'Attachment',
      // width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          props: {
            // style: { height: 10},
          },
          children: el.fileAttachementId ? (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span style={{ marginTop: 1 }}>{el.fileName}</span>&nbsp; &nbsp;{' '}
              <a
                onClick={async () => {
                  const res = await getReceiptAttachment(el.fileAttachementId);
                  console.log(res.data.data);

                  if (res.data.data) {
                    var anchor = document.createElement('a');
                    (anchor.href = res?.data.data), (anchor.download = res?.data?.name);
                    anchor.click();
                  }
                }}
              >
                <DownloadOutlined
                  style={{ marginLeft: 10, fontSize: 14, color: '#86C129' }}
                ></DownloadOutlined>
              </a>
            </Row>
          ) : (
            <>{'-'}</>
          ),
        };
      },
    },
  ];

  const getEmployeeRelateDataSet = () => {
    try {
      getEmployeeCurrentDetails(props.employeeId).then((res) => {
        if (res.data) {
          let jobTitle = res.data.jobTitle != null ? res.data.jobTitle : '-';
          setJobTitle(jobTitle);
        }
      });
    } catch (error) {
      if (_.isEmpty(error)) {
        const hide = message.loading('Error');
        message.error('Validation errors');
        hide();
      }
    }
  };

  const getClaimRequestReceiptDetails = async () => {
    try {
      console.log('pppppppppppp');
      if (claimRequestId != null) {
        let path: string;
        path =
          `/api/expense-management/get-claim-request-receipt-details/` + props.claimRequestData.id;
        const result = await request(path);
        if (result['data'] !== null) {
          setReceiptList(result['data']);
        }
      }
    } catch (error) {
      console.log(error);
    }
  };

  const getClaimType = async () => {
    try {
      let path: string;

      if (selectedClaimType != null) {
        path = `/api/expense-management/claimTypes/` + selectedClaimType;
        const res = await request(path);
        setClaimType(res['data']);
      }
    } catch (error) {
      const hide = message.loading('Error');
      message.error(error.message);
      hide();
    }
  };

  const getFinancialYear = async () => {
    try {
      const actions: any = [];
      let path: string;

      if (selectedFinancialYear != null) {
        path = `/api/financialYears/` + selectedFinancialYear;
        const res = await request(path);
        setFinancialYear(res['data']);
      }
    } catch (error) {
      const hide = message.loading('Error');
      message.error(error.message);
      hide();
    }
  };

  const getEmployeeProfileImage = async () => {
    try {
      const actions: any = [];
      const response = await getModel('employee');
      let path: string;

      if (!_.isEmpty(response.data) && selectedClaimType != null) {
        path =
          `/api${response.data.modelDataDefinition.path}/` + props.employeeId + `/profilePicture`;
        const result = await request(path);
        if (result['data'] !== null) {
          setimageUrl(result['data']['data']);
        }
      }
    } catch (error) {
      console.log(error);
    }
  };

  return (
    <ProCard
      direction="column"
      ghost
      gutter={[0, 16]}
      style={{ padding: 0, margin: 0, height: '100%', borderRadius: 10 }}
    >
      <Row style={{ width: '100%', paddingLeft: 30, paddingRight: 20 }}>
        <Col style={{ width: '100%' }}>
          {props.scope != 'EMPLOYEE' ? (
            <>
              <Row style={{ marginBottom: 20, marginTop: 10 }}>
                <Col span={16} style={{ backgroundColor: '' }}>
                  <Row>
                    <Col>
                      {imageUrl ? (
                        <Avatar style={{ fontSize: 22, border: 1 }} src={imageUrl} size={55} />
                      ) : (
                        <Avatar style={{ backgroundColor: 'blue', fontSize: 18 }} size={55}>
                          {props.employeeFullName != null
                            ? props.employeeFullName
                                .split(' ')
                                .map((x) => x[0])
                                .join('')
                            : ''}
                        </Avatar>
                      )}
                    </Col>
                    <Col style={{ paddingLeft: 10 }}>
                      <Row style={{ fontWeight: 500, fontSize: 20, color: '#394241' }}>
                        {props.employeeFullName}
                      </Row>
                      <Row
                        style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}
                      >
                        {jobTitle}
                      </Row>
                    </Col>
                  </Row>
                </Col>
              </Row>
            </>
          ) : (
            <></>
          )}

          <>
            <Row style={{ marginBottom: 10 }}>
              <Col span={24}>
                <span style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}>
                  <FormattedMessage id="leaveDetails" defaultMessage="Claim Type" />
                </span>{' '}
                &nbsp; &nbsp;
                <Tag
                  style={{
                    borderRadius: 20,
                    paddingRight: 20,
                    paddingLeft: 20,
                    paddingTop: 2,
                    paddingBottom: 2,
                    border: 0,
                    fontSize: 14,
                  }}
                  color={'#FFF7E6'}
                >
                  <span style={{ color: '#D76B4F' }}>{claimType['typeName']}</span>
                </Tag>
              </Col>
            </Row>
            <Row style={{ marginBottom: 10 }}>
              <Col span={24}>
                <span style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}>
                  <FormattedMessage id="leaveDetails" defaultMessage="Fiancial Year :" />
                </span>{' '}
                &nbsp; &nbsp;
                <span style={{ fontWeight: 600, fontSize: 15, color: 'gray' }}>
                  {financialYear['financialDateRangeString']}
                </span>
              </Col>
            </Row>
            {props.claimRequestData.claimMonth != null ? (
              <Row style={{ marginBottom: 10 }}>
                <Col span={24}>
                  <span style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}>
                    <FormattedMessage id="leaveDetails" defaultMessage="Claim Month :" />
                  </span>{' '}
                  &nbsp; &nbsp;
                  <span style={{ fontWeight: 600, fontSize: 15, color: 'gray' }}>
                    {props.claimRequestData.claimMonth}
                  </span>
                </Col>
              </Row>
            ) : (
              <></>
            )}
            <Row style={{ marginBottom: 20, marginTop: 20, fontWeight: 400 }}>
              <Col span={24} style={{ backgroundColor: '' }}>
                <Row>
                  <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                    <span>
                      <FormattedMessage
                        id="receiptDetails"
                        defaultMessage="Claim Request Receipt Details"
                      />
                    </span>
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row
              style={{
                marginBottom: 20,
                color: '#626D6C',
                fontWeight: 400,
                fontSize: 14,
                marginLeft: 40,
              }}
            >
              <Col
                span={24}
                style={{
                  paddingTop: 10,
                }}
              >
                <ProTable
                  columns={columns}
                  size={'small'}
                  scroll={receiptList.length > 7 ? { y: 210 } : undefined}
                  actionRef={receiptTableRef}
                  dataSource={receiptList}
                  toolBarRender={false}
                  rowKey="id"
                  key={'allTable'}
                  pagination={false}
                  search={false}
                  options={{ fullScreen: false, reload: true, setting: false }}
                />
              </Col>
            </Row>
            <Row style={{ marginBottom: 5, width: '100%' }}>
              <Col span={16} style={{ backgroundColor: '' }}>
                <Row>
                  <Col style={{ fontWeight: 500, fontSize: 18 }}>
                    <FormattedMessage
                      id="totalReceiptAmount"
                      defaultMessage="Total Receipt Amount"
                    />{' '}
                    : {getReceiptAmountLabel(props.claimRequestData.totalReceiptAmount)}
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
              <Col span={20}>
                <Row>
                  <Col>{}</Col>
                </Row>
              </Col>
            </Row>
            {props.claimRequestData.workflowInstanceId ? (
              <ApprovalLevelDetails
                workflowInstanceId={props.claimRequestData.workflowInstanceId}
                setApproverComment={props.setApproverComment}
                actions={props.actions}
                scope={props.scope}
              ></ApprovalLevelDetails>
            ) : (
              <></>
            )}
          </>
        </Col>
      </Row>
    </ProCard>
  );
};

export default ClaimRequest;
