import React, { useEffect, useState, useRef } from 'react';
import { Col, Input, Row, message, Form, Tag, Avatar, Typography, Spin } from 'antd';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import { Access, useAccess, useIntl } from 'umi';
import moment from 'moment';
import { getModel, ModelType } from '@/services/model';
import { getAttachementList, getShortLeaveAttachementList } from '@/services/leave';
import { getEmployeeCurrentDetails } from '@/services/employee';
import PostOtRequestDetailTable from './postOtRequestDetailTableView';
import { getReceiptAttachment } from '@/services/expenseModule';
import request, { APIResponse } from '@/utils/request';
import ApprovalLevelDetails from './approvalLevelDetails';
import ProTable from '@ant-design/pro-table';
import { DownloadOutlined } from '@ant-design/icons';

const { TextArea } = Input;

type ClaimProps = {
  postOtRequestData: any;
  employeeFullName: string;
  setLeaveDataSet: any;
  fromLeaveRquestList: boolean;
  employeeId: string;
  scope: any;
  actions?: any;
  setApproverComment: any;
  setIsShowCancelView?: any;
  isShowCancelView?: any;
  setPostOtRequestData?: any;
  attendanceSheetData?: any;
  setAttendanceSheetData?: any;
  intialData?: any;
  requestState?: any;
  isApproveActionAvailable?: any;
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
    setSelectedClaimType(props.postOtRequestData.claimTypeId);
    setSelectedFinancialYear(props.postOtRequestData.financialYearId);
    setCaimRequestId(props.postOtRequestData.id);
  });

  useEffect(() => {
    if (props.postOtRequestData.id != undefined) {
      //   getClaimRequestReceiptDetails();
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

  const getRequestedOtLabel = (count) => {
    let label = '00h 00m';

    if (count && count > 0) {
      label = Math.floor(count / 60) + 'h ' + (count % 60) + 'm';
    }

    return label;
  };

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
                  <FormattedMessage id="leaveDetails" defaultMessage="Request Year/Month :" />
                </span>{' '}
                &nbsp; &nbsp;
                <span style={{ fontWeight: 600, fontSize: 15, color: 'gray' }}>
                  {props.postOtRequestData.month}
                </span>
              </Col>
            </Row>
            <Row style={{ marginBottom: 20, marginTop: 20, fontWeight: 400 }}>
              <Col span={24} style={{ backgroundColor: '' }}>
                <Row>
                  <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                    <span>
                      <FormattedMessage
                        id="receiptDetails"
                        defaultMessage="Post OT Request Details"
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
                marginLeft: 10,
              }}
            >
              <Col span={24} style={{ marginBottom: 55 }}>
                <Spin spinning={props.attendanceSheetData.length == 0}>
                  <PostOtRequestDetailTable
                    isApproveActionAvailable={props.isApproveActionAvailable}
                    requestState={props.requestState}
                    intialData={props.intialData}
                    setAttendanceSheetData={props.setAttendanceSheetData}
                    attendanceSheetData={props.attendanceSheetData}
                    postOtRequestData={props.postOtRequestData}
                    scope={props.scope}
                    others={false}
                    accessLevel={'employee'}
                  />
                </Spin>
              </Col>
            </Row>
            <Row style={{ marginBottom: 5, width: '100%' }}>
              <Col span={16} style={{ backgroundColor: '' }}>
                <Row>
                  <Col style={{ fontWeight: 500, fontSize: 18 }}>
                    <FormattedMessage id="totalRequestedOt" defaultMessage="Total Requested OT " />{' '}
                    : {getRequestedOtLabel(props.postOtRequestData.totalRequestedOtMins)}
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
            {props.postOtRequestData.workflowInstanceId ? (
              <ApprovalLevelDetails
                workflowInstanceId={props.postOtRequestData.workflowInstanceId}
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
