import React, { useEffect, useState, useRef } from 'react';
import { Button, Card, Col, Image, Input, Radio, Row, Upload, TimePicker, message, Form, Tag, Avatar, List, Typography,Checkbox, Table } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
// import PermissionDeniedPage from '../403';
import { Access, useAccess, useIntl } from 'umi';
import { ProFormDateRangePicker, ProFormSelect, ProFormDatePicker } from '@ant-design/pro-form';
import moment from 'moment';
import { getModel, ModelType } from "@/services/model";
import { getAttachementList, getShortLeaveAttachementList } from '@/services/leave';
import { CalendarOutlined, CommentOutlined } from '@ant-design/icons';
import { getMyprofile, getEmployee, getEmployeeCurrentDetails } from '@/services/employee';
import { getEmployee as getTeamMember } from '@/services/myTeams';


import request, { APIResponse } from "@/utils/request";
import { getBase64 } from "@/utils/fileStore";
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
    leaveData: any,
    employeeFullName: string,
    setLeaveDataSet: any,
    fromLeaveRquestList: boolean,
    employeeId: string,
    scope: any,
    actions?:any,
    setApproverComment: any,
    setIsShowCancelView ?: any,
    isShowCancelView ?: any,
    setLeaveCancelDates?: any
    
};

type TableListItem = {
    date: string;
    details: any;
  };


const LeaveRequest: React.FC<LeaveProps> = (props) => {
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
    const canceTableRef = useRef<TableType>();
    const viewTableRef = useRef<TableType>();

    useEffect(() => {
        setSelectedLeaveType(props.leaveData.leaveTypeId);
        let reason = props.leaveData.reason ? props.leaveData.reason : '_';
        setLeaveReason(reason);
        setFromDate(props.leaveData.fromDate);
        setToDate(props.leaveData.toDate);

        if (props.leaveData.fromTime) {
            setFromTime(moment(props.leaveData.fromTime,'hh:mm:ss').format('hh:mm A'));
        }
        if (props.leaveData.toTime) {
            setToTime(moment(props.leaveData.toTime,'hh:mm:ss').format('hh:mm A'));
        }
        setLeaveRequestId(props.leaveData.id);

        setLeavePeriodType(props.leaveData.leavePeriodType);
        switch (props.leaveData.leavePeriodType) {
            case 'FULL_DAY':
                setRadioVal(1)
                setLeavePeriodTypeLabel('Full Day');
                break;
            case 'FIRST_HALF_DAY':
                setRadioVal(2)
                setLeavePeriodTypeLabel('First Half Day');
                break;
            case 'SECOND_HALF_DAY':
                setRadioVal(3)
                setLeavePeriodTypeLabel('Second Half Day');
                break;
            case 'IN_SHORT_LEAVE':
                setRadioVal(4)
                setLeavePeriodTypeLabel('In Short Leave');
                break;
            case 'OUT_SHORT_LEAVE':
                setRadioVal(5)
                setLeavePeriodTypeLabel('Out Short Leave');
                break;
            default:
                break;
        }
    });

    useEffect(() => {
        if (props.leaveData.id != undefined) {
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
        }
    },[]);

    useEffect(() => {
        calculateNumOfCancelLeaveDates(leaveDateList);
    },[leaveDateList]);
      

    const getAttachments = () => {
        let params = {
            id : props.leaveData.id
        }
        if (props.leaveData.shortLeaveType) {
            getShortLeaveAttachementList(params).then((response) => {
                setAttachment(response.data);
            });
        } else {
            getAttachementList(params).then((response) => {
                setAttachment(response.data);
            });

        }
    }


    const columns = [
      {
        dataIndex: 'date',
        valueType: 'date',
        title: 'Leave Date',
        // width: '100px',
        filters: false,
        onFilter: false,
        render: (entity, el) => {
            return {
                props: {
                    // style: { height: 10},
                },
                children: (
                    <Row style={{fontSize: 15, fontWeight: 400 }}>
                        <span style={{ marginTop: 1 }}>{el.date}</span>&nbsp; &nbsp;
                    </Row>
                )
            }

        },
      },
      {
        dataIndex: 'details',
        valueType: 'index',
        title: 'Leave Period Type',
        // width: '300px',
        filters: false,
        render: (entity, el) => {
          return {
            props: {
              style: { paddingTop: 2, paddingBottom: 2 },
            },
            children: (
              <Row style={{ fontSize: 15, fontWeight: 400 }}>
                <Col span={5}>
                  <span style={{ marginTop: 1 }}>
                    {el.leavePeriodTypeLabel}
                  </span>
                </Col>
                <Col span={19}>
                  {el.leavePeriodType == 'FULL_DAY' || el.leavePeriodType == 'FIRST_HALF_DAY' ? (
                    <Checkbox
                      checked={el.isCheckedFirstHalf}
                      style={{ marginLeft: 25 }}
                      onChange={(val) => {
                        const newData = [...leaveDateList];
                        const index = newData.findIndex((item) => el.date === item.date);
                        let item = newData[index];
                        item.isCheckedFirstHalf = val.target.checked;
                        if (item.isCheckedFirstHalf && item.isCheckedSecondHalf) {
                            item.leavePeriodTypeLabel = 'Full Day';
                        } else if (!item.isCheckedFirstHalf && item.isCheckedSecondHalf || item.isCheckedFirstHalf && !item.isCheckedSecondHalf) {
                            item.leavePeriodTypeLabel = 'Half Day';
                        } else {
                            item.leavePeriodTypeLabel = '--';    
                        }
                        newData.splice(index, 1, {
                          ...item,
                          ...el,
                        });

                        setLeaveDateList([...newData]);
                        props.setLeaveCancelDates([...newData]);
                      }}
                    >
                      First Half
                    </Checkbox>
                  ) : (
                    <></>
                  )}
                  {el.leavePeriodType == 'FULL_DAY' || el.leavePeriodType == 'SECOND_HALF_DAY' ? (
                    <Checkbox
                      checked={el.isCheckedSecondHalf}
                      style={{ marginLeft: 25 }}
                      onChange={(val) => {
                        const newData = [...leaveDateList];
                        const index = newData.findIndex((item) => el.date === item.date);
                        // console.log(row);
                        let item = newData[index];
                        item.isCheckedSecondHalf = val.target.checked;
                        if (item.isCheckedFirstHalf && item.isCheckedSecondHalf) {
                            item.leavePeriodTypeLabel = 'Full Day';
                        } else if (!item.isCheckedFirstHalf && item.isCheckedSecondHalf || item.isCheckedFirstHalf && !item.isCheckedSecondHalf) {
                            item.leavePeriodTypeLabel = 'Half Day';
                        } else {
                            item.leavePeriodTypeLabel = '--';    
                        }
                        newData.splice(index, 1, {
                          ...item,
                          ...el,
                        });

                        setLeaveDateList([...newData]);
                        props.setLeaveCancelDates([...newData]);
                      }}
                    >
                      Second Half
                    </Checkbox>
                  ) : (
                    <></>
                  )}
                </Col>
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
            getEmployeeCurrentDetails(props.employeeId).then((res)=> {
                if (res.data) {                
                    let jobTitle = (res.data.jobTitle != null) ? res.data.jobTitle : '-'
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
    }

    const getLeaveDataDetails = async () => {
      try {
        console.log();
        if (leavePeriodType !== 'OUT_SHORT_LEAVE' && leavePeriodType !== 'IN_SHORT_LEAVE') {
          setLeaveDateList([]);
          if (!props.fromLeaveRquestList) {
            props.setLeaveCancelDates([]);
          }
          setLeaveDateRangeList([]);
          let path: string;
          path = `/api/get-leave-data-details/` + props.leaveData.id;
          const result = await request(path);
          if (result['data'] !== null) {
            setLeaveDateList(result['data']['leaveDatesArray']);
            if (!props.fromLeaveRquestList) {
              props.setLeaveCancelDates(result['data']['leaveDatesArray']);
            }
            setLeaveDateRangeList(result['data']['leaveDateRangeArray']);
            calculateNumOfCancelLeaveDates(result['data']['leaveDatesArray']);
          }
        }
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

    }

    
    const getLeaveTypes = async () => {

        try {
            const actions: any = []
            const response = await getModel('leaveType')
            let path: string

            if (!_.isEmpty(response.data) && selectedleaveType != null) {
                path = `/api${response.data.modelDataDefinition.path}/`+selectedleaveType;
                const res = await request(path);
                setLeaveType(res['data']);
            }
            
        } catch (error) {
            const hide = message.loading('Error');
            message.error(error.message);
            hide();
        }
        
    }

    const getEmployeeProfileImage = async () => {
        try {
            const actions: any = []
            const response = await getModel('employee')
            let path: string

            if (!_.isEmpty(response.data) && selectedleaveType != null) {
                path = `/api${response.data.modelDataDefinition.path}/`+props.employeeId+`/profilePicture`;
                const result = await request(path);
                if (result['data'] !== null) {
                  setimageUrl(result['data']['data']);
                }
            }
        } catch (error) {
            console.log(error);
        }
    }

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

            {!props.isShowCancelView ? (
              <>
                <Row style={{ marginBottom: 10 }}>
                  <Col span={24} style={{ backgroundColor: '' }}>
                    <Row>
                      <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                        <span>
                          <FormattedMessage id="leaveDetails" defaultMessage="Leave Details" />
                        </span>{' '}
                        &nbsp; &nbsp;
                        {leavePeriodType !== 'IN_SHORT_LEAVE' &&
                        leavePeriodType !== 'OUT_SHORT_LEAVE' ? (
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
                                backgroundColor: '#CDE7FF',
                                fontSize: 14,
                              }}
                              color={'#FFF7E6'}
                            >
                              <span style={{ color: '#2D68FE' }}>{leaveType['name']}</span>
                            </Tag>
                            {/* {leavePeriodTypeLabel} */}
                            {/* </div> */}
                          </>
                        ) : (
                          <>
                            {/* <div > */}
                            <Tag
                              style={{
                                borderRadius: 20,
                                paddingRight: 20,
                                paddingLeft: 20,
                                paddingTop: 2,
                                paddingBottom: 2,
                                border: 0,
                              }}
                              color={'#FFF7E6'}
                            >
                              <span style={{ color: '#D76B4F' }}>
                                {intl.formatMessage({
                                  id: 'shortLeave',
                                  defaultMessage: 'Short Leave',
                                })}
                              </span>
                            </Tag>

                            {/* </div> */}
                          </>
                        )}
                      </Col>
                    </Row>
                  </Col>
                </Row>

                {leavePeriodType !== 'OUT_SHORT_LEAVE' && leavePeriodType !== 'IN_SHORT_LEAVE' ? (
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
                          backgroundColor: '#CDE7FF',
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
                              color: '#71839B',
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
                ) : (
                  <Row
                    style={{
                      marginBottom: 20,
                      color: '#626D6C',
                      fontWeight: 400,
                      fontSize: 14,
                    }}
                  >
                    <Col
                      span={10}
                      style={{
                        paddingTop: 10,
                      }}
                    >
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
                      Date &nbsp;|&nbsp;
                      {moment(fromDate, 'YYYY-MM-DD').isValid()
                        ? moment(fromDate).format('DD-MM-YYYY')
                        : null}
                    </Col>
                    <Col
                      span={14}
                      style={{
                        paddingTop: 12,
                      }}
                    >
                      Time Period &nbsp;|&nbsp;{fromTime}&nbsp; To &nbsp;&nbsp;{toTime}
                    </Col>
                  </Row>
                )}

                <Row style={{ marginBottom: 10 }}>
                  <Col span={16} style={{ backgroundColor: '' }}>
                    <Row>
                      <Col style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                        <FormattedMessage id="reason" defaultMessage="Reason" />
                      </Col>
                    </Row>
                  </Col>
                </Row>
                <Row style={{ marginBottom: 10 }}>
                  <p style={{ color: '#626D6C' }}>{leaveReason}</p>
                </Row>

                {attachment.length > 0 ? (
                  <LeaveAttachmnetList attachementList={attachment}></LeaveAttachmnetList>
                ) : (
                  <>
                    <Row style={{ marginBottom: 10 }}>
                      <Col span={16} style={{ backgroundColor: '' }}>
                        <Row>
                          <Col style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                            <FormattedMessage
                              id="attachedDocuments"
                              defaultMessage="Attachments:"
                            />
                          </Col>
                        </Row>
                      </Col>
                    </Row>
                    <Row style={{ marginBottom: 10 }}>
                      <Col>
                        <Row>
                          <Col>{'--'}</Col>
                        </Row>
                      </Col>
                    </Row>
                  </>
                )}
                {/* {props.fromLeaveRquestList && props.leaveData.canShowLeaveBalance ? (
                  <>
                    <Row style={{ marginBottom: 10 }}>
                      <Col span={16} style={{ backgroundColor: '' }}>
                        <Row>
                          <Col style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                            <FormattedMessage id="reason" defaultMessage="Leave Balance" />
                          </Col>
                        </Row>
                      </Col>
                    </Row>
                    <Row style={{ marginBottom: 30 }}>
                      {leavePeriodType !== 'OUT_SHORT_LEAVE' &&
                      leavePeriodType !== 'IN_SHORT_LEAVE' ? (
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
                )} */}

                {props.leaveData.manageCoveringPerson ? (
                  <>
                    <List.Item key={'converingPersonItem'}>
                      <List.Item.Meta
                        // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                        title={
                          <Row>
                            <p
                              key="commentedUserName"
                              style={{
                                fontSize: 18,
                                fontWeight: 500,
                                marginBottom: 0,
                                marginRight: 10,
                              }}
                            >
                              <FormattedMessage
                                id="coveringPersonApproval"
                                defaultMessage="Covering Person Approval"
                              />
                            </p>
                            <p
                              key="commentDateTime"
                              style={{
                                fontSize: 14,
                                marginBottom: 0,
                                marginRight: 10,
                                paddingTop: 2,
                                color: 'grey',
                              }}
                            >
                              {'(' +
                                props.leaveData.coveringPersonRequestsData.firstName +
                                ' ' +
                                props.leaveData.coveringPersonRequestsData.lastName +
                                ')'}
                            </p>
                            <Tag
                              style={{
                                borderRadius: 20,
                                fontSize: 14,
                                paddingRight: 20,
                                paddingLeft: 20,
                                paddingTop: 4,
                                paddingBottom: 2,
                                border: 0,
                              }}
                              color={props.leaveData.coveringPersonRequestsData.tagColor}
                            >
                              <span
                                style={{
                                  color: props.leaveData.coveringPersonRequestsData.tagFontColor,
                                }}
                              >
                                {props.leaveData.coveringPersonRequestsData.statusLabel}
                              </span>
                            </Tag>
                          </Row>
                        }
                        description={
                          props.leaveData.coveringPersonRequestsData.comment
                            ? props.leaveData.coveringPersonRequestsData.comment
                            : 'No Comment'
                        }
                      />
                    </List.Item>
                  </>
                ) : (
                  <></>
                )}

                {props.leaveData.workflowInstanceId && props.leaveData.canShowApprovalLevel ? (
                  <ApprovalLevelDetails
                    workflowInstanceId={props.leaveData.workflowInstanceId}
                    setApproverComment={props.setApproverComment}
                    actions={props.actions}
                    scope={props.scope}
                    isViewOnly={props.fromLeaveRquestList}
                  ></ApprovalLevelDetails>
                ) : (
                  <></>
                )}

                {props.leaveData.canCancelLeaveRequest ? (
                  <Row style={{ marginBottom: 10 }}>
                    <Col span={24} style={{ backgroundColor: '' }}>
                      {props.leaveData.isInInitialState ? (
                        <Row>
                          <Col>
                            <span style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                              <FormattedMessage
                                id="cancelRequestMessage"
                                defaultMessage="Do you want to cancel this Leave?"
                              />
                            </span>
                            &nbsp;
                            <span style={{ fontWeight: 500, fontSize: 18 }}>
                              <a
                                onClick={() => {
                                  props.setIsShowCancelView(true);
                                }}
                              >
                                <FormattedMessage
                                  id="cancelRequest"
                                  defaultMessage="Cancel Request"
                                />
                              </a>
                            </span>
                          </Col>
                        </Row>
                      ) : (
                        <Row>
                          <Col>
                            <span style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                              <FormattedMessage
                                id="cancelRequestMessage"
                                defaultMessage="Do you want to cancel this Leave?"
                              />
                            </span>
                            &nbsp;&nbsp;
                            <span style={{ fontWeight: 500, fontSize: 18 }}>
                              <a
                                onClick={() => {
                                  props.setIsShowCancelView(true);
                                }}
                              >
                                <FormattedMessage
                                  id="cancelRequest"
                                  defaultMessage="Send Cancel Request"
                                />
                              </a>
                            </span>
                          </Col>
                        </Row>
                      )}
                    </Col>
                  </Row>
                ) : (
                  <></>
                )}
              </>
            ) : (
              <>
                <Row style={{ marginBottom: 20 }}>
                  <Col span={24} style={{ backgroundColor: '' }}>
                    <Row>
                      <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                        <span>
                          <FormattedMessage
                            id="leaveDetails"
                            defaultMessage="Cancel Leave Details"
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
                    span={16}
                    style={{
                      paddingTop: 10,
                    }}
                  >
                    <ProTable
                      columns={columns}
                      size={'small'}
                      scroll={leaveDateList.length > 7 ? { y: 210 } : undefined}
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
                  <Col style={{ paddingLeft: 8 }} span={6}>
                    <Row
                      style={{
                        backgroundColor: '#CDE7FF',
                        width: 162,
                        height: 110,
                        borderRadius: 6,
                        marginTop: 10,
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
                            color: '#71839B',
                            fontSize: 35,
                          }}
                        >
                          {numOfCancelLeaveDates}{' '}
                          {Number(numOfCancelLeaveDates) > 1 || Number(numOfCancelLeaveDates) == 0
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
                {props.leaveData.isInInitialState ? (
                  <></>
                ) : (
                  <Row>
                    <Col span={22} style={{ marginTop: 15 }}>
                      <Row style={{ marginBottom: 15 }}>
                        <Col style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                          <FormattedMessage id="reason" defaultMessage="Reason of Cancellation" />
                        </Col>
                      </Row>
                      <Row>
                        <Col span={24}>
                          <Form.Item
                            name="cancelReason"
                            rules={[{ max: 250, message: 'Maximum length is 250 characters.' }]}
                          >
                            <Input.TextArea
                              maxLength={250}
                              rows={4}
                              style={{ borderRadius: 6 }}
                              onChange={(val) => {
                                // props.setApproverComment(val.target.value);
                              }}
                            />
                          </Form.Item>
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                )}
              </>
            )}
          </Col>
        </Row>
      </ProCard>
    );
};

export default LeaveRequest;
