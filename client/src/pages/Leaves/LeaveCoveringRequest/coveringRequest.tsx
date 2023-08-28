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
  Typography,
} from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import { Access, useAccess, useIntl } from 'umi';
import { ProFormDateRangePicker, ProFormSelect, ProFormDatePicker } from '@ant-design/pro-form';
import moment from 'moment';
import { getModel, ModelType } from '@/services/model';
import { getAttachementList, getShortLeaveAttachementList } from '@/services/leave';
import { CalendarOutlined } from '@ant-design/icons';
import { getMyprofile, getEmployee, getEmployeeCurrentDetails } from '@/services/employee';
import { getEmployee as getTeamMember } from '@/services/myTeams';
import firstHalfDayIcon from '../../../assets/leave/icon-first-half-day.svg';
import secondHalfDayIcon from '../../../assets/leave/icon-second-half.svg';
import fullDayIcon from '../../../assets/leave/icon-full-day.svg';
import ProTable from '@ant-design/pro-table';

import request, { APIResponse } from '@/utils/request';
import { getBase64 } from '@/utils/fileStore';
import { format } from 'prettier';

const { TextArea } = Input;

type LeaveProps = {
  leaveData: any;
  employeeFullName: string;
  setLeaveDataSet: any;
  coveringRequestData: any;
  fromLeaveRquestList: boolean;
  employeeId: string;
  scope: any;
  setCoveringPersonComment: any;
};

const LeaveRequest: React.FC<LeaveProps> = (props) => {
  const access = useAccess();
  const { hasPermitted } = access;
  const [drawerVisible, setDrawerVisible] = useState(false);
  const [fromDate, setFromDate] = useState<Date | null>(null);
  const [toDate, setToDate] = useState<Date | null>(null);
  const [fromTime, setFromTime] = useState<string | null>(null);
  const [toTime, setToTime] = useState<string | null>(null);
  const [comment, setComment] = useState<string | null>(null);
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
  const { Title, Paragraph, Text } = Typography;
  const [leaveDateList, setLeaveDateList] = useState<any>([]);
  const [leaveDateRangeList, setLeaveDateRangeList] = useState<any>([]);
  const viewTableRef = useRef<TableType>();

  useEffect(() => {
    setSelectedLeaveType(props.leaveData.leaveTypeId);
    let reason = props.leaveData.reason ? props.leaveData.reason : '_';
    setLeaveReason(reason);
    setFromDate(props.leaveData.fromDate);
    setToDate(props.leaveData.toDate);

    if (props.leaveData.fromTime) {
      setFromTime(moment(props.leaveData.fromTime, 'hh:mm:ss').format('hh:mm A'));
    }
    if (props.leaveData.toTime) {
      setToTime(moment(props.leaveData.toTime, 'hh:mm:ss').format('hh:mm A'));
    }
    setLeaveRequestId(props.leaveData.id);

    setLeavePeriodType(props.leaveData.leavePeriodType);
    switch (props.leaveData.leavePeriodType) {
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
    if (props.leaveData.id != undefined) {
      form.setFieldsValue({ coveringPersonComment: props.coveringRequestData.comment });
      getAttachments();
      getLeaveDataDetails();
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

  const getAttachments = () => {
    let params = {
      id: props.leaveData.id,
    };
    if (props.leaveData.shortLeaveType) {
      getShortLeaveAttachementList(params).then((response) => {
        setAttachment(response.data);
      });
    } else {
      getAttachementList(params).then((response) => {
        setAttachment(response.data);
      });
    }
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

  const getLeaveDataDetails = async () => {
    try {
      console.log();
      if (leavePeriodType !== 'OUT_SHORT_LEAVE' && leavePeriodType !== 'IN_SHORT_LEAVE') {
        setLeaveDateList([]);
        setLeaveDateRangeList([]);
        let path: string;
        path = `/api/get-leave-data-details/` + props.leaveData.id;
        const result = await request(path);
        if (result['data'] !== null) {
          setLeaveDateList(result['data']['leaveDatesArray']);
          
          setLeaveDateRangeList(result['data']['leaveDateRangeArray']);
        }
      }
    } catch (error) {
      console.log(error);
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
              <Row style={{ marginBottom: 30, marginTop: 30 }}>
                <Col span={16} style={{ backgroundColor: '' }}>
                  <Row>
                    <Col>
                      {imageUrl ? (
                        <Avatar style={{ fontSize: 22, border: 1 }} src={imageUrl} size={60} />
                      ) : (
                        <Avatar style={{ backgroundColor: 'blue', fontSize: 18 }} size={60}>
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
          <Row style={{ marginBottom: 20 }}>
            <Col span={12} style={{ backgroundColor: '' }}>
              <Row>
                      <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                        <span>
                          <FormattedMessage id="leaveDetails" defaultMessage="Leave Details" />
                        </span>{' '}
                        &nbsp; &nbsp;
                        {
                          <>
                            {/* <div> */}
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
                              <span style={{ color: '#D76B4F' }}>{leaveType['name']}</span>
                            </Tag>
                            {/* {leavePeriodTypeLabel} */}
                            {/* </div> */}
                          </>
                        }
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
            }}
          >
            <Col
              span={16}
              style={{
                paddingTop: 10,
              }}
            >
              <Row>
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
                <span style={{ fontSize: 16 }}>From</span> &nbsp;{' '}
                <span style={{ fontSize: 16, fontWeight: 600 }}>
                  {moment(fromDate, 'YYYY-MM-DD').isValid()
                    ? moment(fromDate).format('DD-MM-YYYY')
                    : null}
                </span>
                &nbsp; &nbsp;<span style={{ fontSize: 16 }}>to</span> &nbsp;{' '}
                <span style={{ fontSize: 16, fontWeight: 600 }}>
                  {moment(toDate, 'YYYY-MM-DD').isValid()
                    ? moment(toDate).format('DD-MM-YYYY')
                    : null}{' '}
                </span>
              </Row>
              <Row style={{ marginTop: 10, marginLeft: 34 }}>
                <Col span={24}>
                  <ProTable
                    columns={viewDateColumns}
                    size={'small'}
                    scroll={leaveDateRangeList.length > 3 ? { y: 130 } : undefined}
                    actionRef={viewTableRef}
                    dataSource={leaveDateRangeList}
                    toolBarRender={false}
                    rowKey="id"
                    key={'allTable'}
                    pagination={false}
                    search={false}
                    options={{ fullScreen: false, reload: true, setting: false }}
                  />
                </Col>
              </Row>
            </Col>
            <Col style={{ paddingLeft: 10 }} span={8}>
              <Row
                style={{
                  backgroundColor: '#f2fced',
                  width: 162,
                  height: 110,
                  borderRadius: 6,
                  marginTop: 50,
                }}
              >
                <div style={{ marginTop: 8, marginLeft: 20 }}>
                  <Text style={{ color: '#626D6C', fontSize: 16, fontWeight: 400 }}>
                    {intl.formatMessage({
                      id: 'Period',
                      defaultMessage: 'Period',
                    })}
                  </Text>
                  <br></br>
                  <Text
                    style={{
                      fontWeight: 400,
                      color: '#74b425',
                      fontSize: 35,
                    }}
                  >
                    {props.leaveData.numberOfLeaveDates}{' '}
                    {Number(props.leaveData.numberOfLeaveDates) > 1 ||
                    Number(props.leaveData.numberOfLeaveDates) == 0
                      ? intl.formatMessage({
                          id: 'days',
                          defaultMessage: 'Days',
                        })
                      : intl.formatMessage({
                          id: 'day',
                          defaultMessage: 'Day',
                        })}
                  </Text>
                </div>
              </Row>
            </Col>
          </Row>

          <Row style={{ marginBottom: 10 }}>
            <Col span={16} style={{ backgroundColor: '' }}>
              <Row>
                <Col style={{ fontWeight: 800, fontSize: 16, color: '#394241' }}>
                  <FormattedMessage id="reason" defaultMessage="Reason" />
                </Col>
              </Row>
            </Col>
          </Row>
          <Row style={{ marginBottom: 10 }}>
            <p style={{ color: '#626D6C' }}>{leaveReason}</p>
          </Row>

          {props.fromLeaveRquestList && props.leaveData.canShowLeaveBalance ? (
            <>
              <Row style={{ marginBottom: 10 }}>
                <Col span={16} style={{ backgroundColor: '' }}>
                  <Row>
                    <Col style={{ fontWeight: 800, fontSize: 16, color: '#394241' }}>
                      <FormattedMessage id="reason" defaultMessage="Leave Balance" />
                    </Col>
                  </Row>
                </Col>
              </Row>
              <Row style={{ marginBottom: 30 }}>
                {leavePeriodType !== 'OUT_SHORT_LEAVE' && leavePeriodType !== 'IN_SHORT_LEAVE' ? (
                  <Col style={{ color: '#626D6C' }}>
                    <p style={{ fontSize: 18, color: 'grey', marginBottom: 0 }}>
                      {props.leaveData.leaveBalance}
                      {props.leaveData.leaveBalance == 1 ? ' Day' : ' Days'}
                    </p>
                  </Col>
                ) : (
                  <Col style={{ color: '#626D6C' }}>
                    <p style={{ fontSize: 18, color: 'grey', marginBottom: 0 }}>
                      {props.leaveData.leaveBalance}
                      {props.leaveData.leaveBalance == 1 ? '' : ''}
                    </p>
                  </Col>
                )}
              </Row>
            </>
          ) : (
            <></>
          )}

          <>
            <Row>
              <Col span={24} style={{ backgroundColor: '' }}>
                <Row>
                  <Col style={{ fontWeight: 800, fontSize: 16, color: '#394241', marginRight: 5 }}>
                    <FormattedMessage
                      id="coveringPersonApproval"
                      defaultMessage="Covering Person's Comment"
                    />
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row>
              <Col span={22} style={{ marginTop: 15 }}>
                <Form form={form}>
                  <Form.Item
                    style={{ marginBottom: 16 }}
                    name="coveringPersonComment"
                    rules={[{ max: 250, message: 'Maximum length is 250 characters.' }]}
                  >
                    <TextArea
                      placeholder={'No Comment'}
                      style={{ borderRadius: 6 }}
                      disabled={props.coveringRequestData.state != 'PENDING'}
                      autoSize={{ minRows: 3, maxRows: 5 }}
                      onChange={(val) => {
                        if (val.target.value) {
                          props.setCoveringPersonComment(val.target.value);
                        } else {
                          props.setCoveringPersonComment(null);
                        }
                      }}
                    />
                  </Form.Item>
                </Form>
              </Col>
            </Row>
          </>
        </Col>
      </Row>
    </ProCard>
  );
};

export default LeaveRequest;
