import React, { useEffect, useState, useRef } from 'react';
import {
  Button,
  Card,
  Col,
  Image,
  Input,
  Radio,
  Row,
  Upload,
  TimePicker,
  message,
  Form,
  Tag,
  Avatar,
  List,
  Typography,
  Checkbox,
  Table,
} from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
// import PermissionDeniedPage from '../403';
import { Access, useAccess, useIntl } from 'umi';
import { ProFormDateRangePicker, ProFormSelect, ProFormDatePicker } from '@ant-design/pro-form';
import moment from 'moment';
import { getModel, ModelType } from '@/services/model';
import { getAttachementList, getShortLeaveAttachementList } from '@/services/leave';
import { CalendarOutlined, CommentOutlined } from '@ant-design/icons';
import { getMyprofile, getEmployee, getEmployeeCurrentDetails } from '@/services/employee';
import { getEmployee as getTeamMember } from '@/services/myTeams';

import request, { APIResponse } from '@/utils/request';
import { getBase64 } from '@/utils/fileStore';
import LeaveAttachmnetList from './leaveAttchementList';
import ApprovalLevelDetails from './approvalLevelDetails';
import firstHalfDayIcon from '../../assets/leave/icon-first-half-day.svg';
import secondHalfDayIcon from '../../assets/leave/icon-second-half.svg';
import fullDayIcon from '../../assets/leave/icon-full-day.svg';
import ProTable from '@ant-design/pro-table';
import { format } from 'prettier';
import type { ProColumns, ActionType } from '@ant-design/pro-table';

const { TextArea } = Input;

type LeaveProps = {
  cancelShortLeaveData: any;
  employeeFullName: string;
  setLeaveDataSet: any;
  fromLeaveRquestList: boolean;
  employeeId: string;
  scope: any;
  actions?: any;
  setApproverComment: any;
  setIsShowCancelView?: any;
  isShowCancelView?: any;
  setLeaveCancelDates?: any;
};

type TableListItem = {
  date: string;
  details: any;
};

const CancelShortLeaveRequest: React.FC<LeaveProps> = (props) => {
  const access = useAccess();
  const { hasPermitted } = access;
  const [drawerVisible, setDrawerVisible] = useState(false);
  const [isInitialState, setIsInitialState] = useState(false);
  const [fromDate, setFromDate] = useState<Date | null>(null);
  const [toDate, setToDate] = useState<Date | null>(null);
  const [numOfCancelLeaveDates, setNumOfCancelLeaveDates] = useState<number | null>(0);
  const [fromTime, setFromTime] = useState<string | null>(null);
  const [toTime, setToTime] = useState<string | null>(null);
  const [radioVal, setRadioVal] = useState<any | null>(1);
  const [leavePeriodType, setLeavePeriodType] = useState<string | null>('FULL_DAY');
  const [selectedleaveType, setSelectedLeaveType] = useState<string | null>(null);
  const [leaveType, setLeaveType] = useState<any>([]);
  const [leavePeriodTypeLabel, setLeavePeriodTypeLabel] = useState<string | null>(null);
  const [toTimeDisableState, setToTimeDisableState] = useState<boolean>(true);
  const [leaveReason, setLeaveReason] = useState<string | null>(null);
  const [imageUrl, setimageUrl] = useState<string | null>(null);
  const [attachmentList, setAttachmentList] = useState<any>([]);
  const [leaveRequestId, setLeaveRequestId] = useState<string | null>(null);
  const [attachment, setAttachment] = useState<any>([]);
  const [employeeData, setEmployeeData] = useState<any>([]);
  const [form] = Form.useForm();
  const intl = useIntl();
  const [jobTitle, setJobTitle] = useState<string | null>(null);
  const [approvalLevelList, setApprovalLevelList] = useState<any>([]);
  const [leaveDateList, setLeaveDateList] = useState<any>([]);
  const [leaveDateRangeList, setLeaveDateRangeList] = useState<any>([]);
  const { Title, Paragraph, Text } = Typography;
  const [reasonModel, setReasonModel] = useState('');
  const canceTableRef = useRef<TableType>();
  const viewTableRef = useRef<TableType>();

  useEffect(() => {
    console.log(props.cancelShortLeaveData);
    setSelectedLeaveType(props.cancelShortLeaveData.leaveTypeId);
    let reason = props.cancelShortLeaveData.reason ? props.cancelShortLeaveData.reason : '_';
    setLeaveReason(reason);
    setFromDate(props.cancelShortLeaveData.date);
    setToDate(props.cancelShortLeaveData.toDate);
    setReasonModel(props.cancelShortLeaveData.cancelReason);

    if (props.cancelShortLeaveData.fromTime) {
      setFromTime(moment(props.cancelShortLeaveData.fromTime, 'hh:mm:ss').format('hh:mm A'));
    }
    if (props.cancelShortLeaveData.toTime) {
      setToTime(moment(props.cancelShortLeaveData.toTime, 'hh:mm:ss').format('hh:mm A'));
    }
    setLeaveRequestId(props.cancelShortLeaveData.id);

    setLeavePeriodType(props.cancelShortLeaveData.leavePeriodType);
    switch (props.cancelShortLeaveData.leavePeriodType) {
      case 'FULL_DAY':
        setRadioVal(1);
        setLeavePeriodTypeLabel('Full Day');
        break;
      case 'FIRST_HALF_DAY':
        setRadioVal(2);
        setLeavePeriodTypeLabel('First Half Day');
        break;
      case 'SECOND_HALF_DAY':
        setRadioVal(3);
        setLeavePeriodTypeLabel('Second Half Day');
        break;
      case 'IN_SHORT_LEAVE':
        setRadioVal(4);
        setLeavePeriodTypeLabel('In Short Leave');
        break;
      case 'OUT_SHORT_LEAVE':
        setRadioVal(5);
        setLeavePeriodTypeLabel('Out Short Leave');
        break;
      default:
        break;
    }
  });

  useEffect(() => {
    if (props.cancelShortLeaveData.id != undefined) {
      getLeaveDataDetails();
      getAttachments();
      getLeaveTypes();
      getEmployeeProfileImage();
      getEmployeeRelateDataSet();
    }
  }, [leaveRequestId]);

  useEffect(() => {
    return () => {
      props.setLeaveDataSet({});
    };
  }, []);

  useEffect(() => {
    calculateNumOfCancelLeaveDates(leaveDateList);
  }, [leaveDateList]);

  const getAttachments = () => {
    let params = {
      id: props.cancelShortLeaveData.id,
    };
    if (props.cancelShortLeaveData.shortLeaveType) {
      getShortLeaveAttachementList(params).then((response) => {
        setAttachment(response.data);
      });
    } else {
      getAttachementList(params).then((response) => {
        setAttachment(response.data);
      });
    }
  };

  const columns = [
    {
      dataIndex: 'date',
      valueType: 'date',
      title: 'Short Leave Date',
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
              <span style={{ marginTop: 1 }}>{el.date}</span>&nbsp; &nbsp;
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'fromTime',
      valueType: 'From Time',
      title: 'From Time',
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
              <span style={{ marginTop: 1 }}>{el.fromTime}</span>&nbsp; &nbsp;
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'toTime',
      valueType: 'To Time',
      title: 'To Time',
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
              <span style={{ marginTop: 1 }}>{el.toTime}</span>&nbsp; &nbsp;
            </Row>
          ),
        };
      },
    },
  ];

  const viewDateColumns = [
    {
      dataIndex: 'fromDate',
      valueType: 'fromDate',
      title: 'Start Date',
      // width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span>{el.fromDate}</span>
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'toDate',
      valueType: 'toDate',
      title: 'End Date',
      // width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span>{el.toDate}</span>
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'details',
      valueType: 'index',
      title: 'Leave Day Type',
      // width: '100px',
      filters: false,
      render: (entity, el) => {
        return {
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <Image
                src={
                  el.leavePeriodType == 'FULL_DAY'
                    ? fullDayIcon
                    : el.leavePeriodType == 'FIRST_HALF_DAY'
                    ? firstHalfDayIcon
                    : secondHalfDayIcon
                }
                preview={false}
                height={20}
                style={{ marginTop: 0 }}
              />
              &nbsp; &nbsp;
              <span>
                {el.leavePeriodType == 'FULL_DAY'
                  ? 'Full Day'
                  : el.leavePeriodType == 'FIRST_HALF_DAY'
                  ? 'First Half'
                  : 'Second Half'}
              </span>
            </Row>
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

  const getLeaveDataDetails = async () => {
    try {
      console.log();
      setLeaveDateList([
        {
          date: moment(fromDate, 'YYYY-MM-DD').isValid()
            ? moment(fromDate).format('DD-MM-YYYY')
            : null,
          fromTime: fromTime,
          toTime: toTime,
        },
      ]);
    } catch (error) {
      console.log(error);
    }
  };

  const calculateNumOfCancelLeaveDates = (leaveDateList) => {
    let cancelDateCount = 0;

    leaveDateList.forEach((leaveDateData) => {
      if (leaveDateData.isCheckedFirstHalf && leaveDateData.isCheckedSecondHalf) {
        cancelDateCount += 1;
      } else if (leaveDateData.isCheckedFirstHalf && !leaveDateData.isCheckedSecondHalf) {
        cancelDateCount += 0.5;
      } else if (!leaveDateData.isCheckedFirstHalf && leaveDateData.isCheckedSecondHalf) {
        cancelDateCount += 0.5;
      } else {
        cancelDateCount += 0;
      }
    });

    setNumOfCancelLeaveDates(cancelDateCount);
  };

  const getLeaveTypes = async () => {
    try {
      const actions: any = [];
      const response = await getModel('leaveType');
      let path: string;

      if (!_.isEmpty(response.data) && selectedleaveType != null) {
        path = `/api${response.data.modelDataDefinition.path}/` + selectedleaveType;
        const res = await request(path);
        setLeaveType(res['data']);
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

      if (!_.isEmpty(response.data) && selectedleaveType != null) {
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
            <Row style={{ marginBottom: 20 }}>
              <Col span={24} style={{ backgroundColor: '' }}>
                <Row>
                  <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                    <span>
                      <FormattedMessage
                        id="leaveDetails"
                        defaultMessage="Cancel Short Leave Details"
                      />
                    </span>
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row style={{ marginBottom: 10 }}>
              <Tag
                style={{
                  borderRadius: 30,
                  paddingRight: 10,
                  paddingLeft: 10,
                  paddingTop: 5,
                  paddingBottom: 5,
                  border: 0,
                }}
                color={'cyan'}
              >
                <CalendarOutlined />
              </Tag>
              <span style={{ fontSize: 16 }}>Date</span> &nbsp;{' '}
              <span style={{ fontSize: 16, fontWeight: 600 }}>
                {moment(fromDate, 'YYYY-MM-DD').isValid()
                  ? moment(fromDate).format('DD-MM-YYYY')
                  : null}
              </span>
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
                  //   scroll={leaveDateList.length > 7 ? { y: 210 } : undefined}
                  actionRef={canceTableRef}
                  dataSource={leaveDateList}
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
                    <FormattedMessage id="reason" defaultMessage="Reason" />
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
              <Col span={20}>
                <Row>
                  <Col>{reasonModel}</Col>
                </Row>
              </Col>
            </Row>
            {props.cancelShortLeaveData.workflowInstanceId ? (
              <ApprovalLevelDetails
                workflowInstanceId={props.cancelShortLeaveData.workflowInstanceId}
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

export default CancelShortLeaveRequest;
