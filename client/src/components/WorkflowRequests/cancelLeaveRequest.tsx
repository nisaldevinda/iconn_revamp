import React, { useEffect, useState, useRef } from 'react';
import {
  Image,
  Col,
  Row,
  List,
  DatePicker,
  TimePicker,
  Space,
  Input,
  Avatar,
  message,
  Tag,
} from 'antd';
import { FormattedMessage } from 'react-intl';
import TextArea from 'antd/lib/input/TextArea';
import request, { APIResponse } from '@/utils/request';
import { getModel, ModelType } from '@/services/model';
import { getEmployeeCurrentDetails } from '@/services/employee';

import { DownloadOutlined, PaperClipOutlined, CheckCircleFilled, CloseCircleFilled } from '@ant-design/icons';
import './index.less';
import moment from 'moment';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import { CalendarOutlined, CommentOutlined } from '@ant-design/icons';
import ApprovalLevelDetails from './approvalLevelDetails';
import ProTable from '@ant-design/pro-table';
import { format } from 'prettier';

type CancelLeaveProps = {
  cancelLeaveRequestData: any;
  employeeFullName: any;
  scope: any;
  employeeId: any;
  actions?: any;
  setApproverComment: any;
};

const CancelLeaveRequest: React.FC<CancelLeaveProps> = (props) => {
  // const [attachementSet, setAttachementSet] = useState<any>(props.attachementList);
  const [shiftDateModel, setShiftDateModel] = useState<string | null>(null);
  const [currentShiftModel, setCurrentShiftModel] = useState<string | null>(null);
  const [newShiftModel, setNewShiftModel] = useState<string | null>(null);
  const [reasonModel, setReasonModel] = useState('');
  const [imageUrl, setimageUrl] = useState<string | null>(null);
  const [selectedleaveType, setSelectedLeaveType] = useState<string | null>(null);
  const [cancelLeaveRequestId, setCancelLeaveRequestId] = useState<string | null>(null);
  const [jobTitle, setJobTitle] = useState<string | null>(null);
  const [breakDetails, setBreakDetails] = useState<any | null>([]);
  const [diffDetails, setDiffDetails] = useState<any | null>([]);
  const [leaveType, setLeaveType] = useState<any>([]);
  const [fromDate, setFromDate] = useState<Date | null>(null);
  const [toDate, setToDate] = useState<Date | null>(null);
  const viewTableRef = useRef<TableType>();

  useEffect(() => {
    const shiftDateRecord = props.cancelLeaveRequestData.shiftDate
      ? moment(props.cancelLeaveRequestData.shiftDate).format('YYYY-MM-DD')
      : '-';
    const currentShift = props.cancelLeaveRequestData.currentShiftName
      ? props.cancelLeaveRequestData.currentShiftName
      : '-';
    const newShift = props.cancelLeaveRequestData.newShiftName
      ? props.cancelLeaveRequestData.newShiftName
      : '-';
    
    console.log(props.cancelLeaveRequestData);

    setSelectedLeaveType(props.cancelLeaveRequestData.leaveTypeId);
    setShiftDateModel(shiftDateRecord);
    setCurrentShiftModel(currentShift);
    setNewShiftModel(newShift);
    setFromDate(props.cancelLeaveRequestData.fromDate);
    setToDate(props.cancelLeaveRequestData.toDate);

    if (
      props.cancelLeaveRequestData.cancelReason == null ||
      props.cancelLeaveRequestData.cancelReason == undefined
    ) {
      setReasonModel('_');
    } else {
      setReasonModel(props.cancelLeaveRequestData.cancelReason);
    }
    setCancelLeaveRequestId(props.cancelLeaveRequestData.id);
  });

  useEffect(() => {
    if (props.cancelLeaveRequestData.id != undefined) {
      const breaks =
        props.cancelLeaveRequestData.breakDetails != undefined
          ? props.cancelLeaveRequestData.breakDetails
          : [];
      setBreakDetails(breaks);
      getEmployeeProfileImage();
      getEmployeeRelateDataSet();
      getLeaveTypes();
      processDateDiff(props.cancelLeaveRequestData.cancelDatesDetails, props.cancelLeaveRequestData.originalDatesDetails);
    }
  }, [cancelLeaveRequestId]);

  const processDateDiff = async (cancelDates : any, originalDatesDetails: any) => {
      let dateDiffArr = [];
      
      originalDatesDetails.forEach((element: any, elementIndex) => {
          
          let tempObj = {};
          let cancelDayTypes = [];
          let unChangedDayTypes = [];
          
        //   console.log(moment(cancelDates[0]['date'], 'DD-MM-YYYY').format('YYYY-MM-DD'));
        //   console.log(moment(item.date).format('DD-MM-YYYY'));


          const index = cancelDates.findIndex((item) => moment(element.leaveDate).format('YYYY-MM-DD') === moment(item.date, 'DD-MM-YYYY').format('YYYY-MM-DD'));

          tempObj.date = element.leaveDate;
          tempObj.previousInfo = {
              'dayType': (element.leavePeriodType == 'FULL_DAY') ? 'Full Day' : (element.leavePeriodType == 'FIRST_HALF_DAY') ? 'First Half' : 'Second Half',
          }
          tempObj.newInfo = {
              'isShowFullDay': false,
              'isShowFirstHalf': false,
              'isShowSecondHalf' : false
          }

          if (index != -1) {
              if (cancelDates[index]['isCheckedSecondHalf'] && cancelDates[index]['isCheckedFirstHalf']) {
                tempObj.newInfo.isShowFullDay = true;
                tempObj.newInfo.isShowFirstHalf = false;
                tempObj.newInfo.isShowSecondHalf = false;

                cancelDayTypes.push('Full Day');
              } else if (!cancelDates[index]['isCheckedFirstHalf'] && cancelDates[index]['isCheckedSecondHalf']) {
                tempObj.newInfo.isShowFullDay = false;
                
                if (element.leavePeriodType == 'FULL_DAY') {
                  tempObj.newInfo.isShowFirstHalf = true;
                  tempObj.newInfo.isShowSecondHalf = true;
                  cancelDayTypes.push('Second Half');
                  unChangedDayTypes.push('First Half');
                } else {
                  tempObj.newInfo.isShowSecondHalf = true;
                  cancelDayTypes.push('Second Half');
                }
              } else if (cancelDates[index]['isCheckedFirstHalf'] && !cancelDates[index]['isCheckedSecondHalf']) {
                tempObj.newInfo.isShowFullDay = false;
                
                if (element.leavePeriodType == 'FULL_DAY') {
                  tempObj.newInfo.isShowFirstHalf = true;
                  tempObj.newInfo.isShowSecondHalf = true;
                  unChangedDayTypes.push('Second Half');
                  cancelDayTypes.push('First Half');
                } else {
                  tempObj.newInfo.isShowSecondHalf = true;
                  cancelDayTypes.push('First Half');
                }
              } else {
                if (element.leavePeriodType == 'FULL_DAY') {
                  tempObj.newInfo.isShowFullDay = true;
                  tempObj.newInfo.isShowFirstHalf = false;
                  tempObj.newInfo.isShowSecondHalf = false;
                  unChangedDayTypes.push('Full Day');
                } else if (element.leavePeriodType == 'FIRST_HALF_DAY') {
                  tempObj.newInfo.isShowFullDay = false;
                  tempObj.newInfo.isShowFirstHalf = true;
                  tempObj.newInfo.isShowSecondHalf = false;
                  unChangedDayTypes.push('First Half');
                } else {
                  tempObj.newInfo.isShowFullDay = false;
                  tempObj.newInfo.isShowFirstHalf = false;
                  tempObj.newInfo.isShowSecondHalf = true;
                  unChangedDayTypes.push('Second Half');
                }
              }

              tempObj.cancelDayTypes = cancelDayTypes;
              tempObj.unChangedDayTypes = unChangedDayTypes;

          }

          dateDiffArr.push(tempObj);

      });
      setDiffDetails(dateDiffArr);
      console.log(dateDiffArr);

  }

  const getEmployeeProfileImage = async () => {
    try {
      const actions: any = [];
      const response = await getModel('employee');
      let path: string;

      if (!_.isEmpty(response.data)) {
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
      dataIndex: 'date',
      valueType: 'date',
      title: <div style={{paddingTop: 8, paddingLeft: 16, paddingBottom: 8 }}>{'Date'}</div>,
      width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span>{moment(el.date, 'YYYY-MM-DD').isValid() ? moment(el.date, 'YYYY-MM-DD').format('DD-MM-YYYY') : '--'}</span>
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'previousLeaveInfo',
      valueType: 'previousLeaveInfo',
      title: <div style={{paddingTop: 8, paddingLeft: 16, paddingBottom: 8 }}>{'Previous Leave Info'}</div>,
      width: '200px',
      filters: false,
      onFilter: false,
      render: (entity, el) => {
        return {
          children: (
            <Row style={{ fontSize: 15, fontWeight: 400 }}>
              <span style={{marginRight: 10}}>{<CheckCircleFilled style={{color:'#75BF00'}} />}</span>
              <span>{el.previousInfo.dayType}</span>
            </Row>
          ),
        };
      },
    },
    {
      dataIndex: 'newLeaveInfo',
      valueType: 'newLeaveInfo',
      title: <div style={{background: "#D3EFA7", paddingTop: 8, paddingLeft: 16, paddingBottom: 8 }}>{'New Leave Info'}</div>,
      width: '250px',
      filters: false,
      render: (entity, el) => {
        return {
          props: {
            style : {backgroundColor: '#F6FFED'}
          },
          children: (
            <Row style={{ display: 'flex' }}>
              {/* <CheckCircleFilled />
              <CloseCircleFilled /> */}

              {
                el.newInfo.isShowFullDay ? (
                  <div style={{marginRight: 20}}>
                    <span style={{marginRight: 10}}>
                      {
                        el.cancelDayTypes.includes('Full Day') ? 
                         <CloseCircleFilled style={{color:'#FF4D4F'}} />  : el.unChangedDayTypes.includes('Full Day') ? <CheckCircleFilled style={{color:'#75BF00'}} /> : <></>
                      }
                    </span>
                    <span>{'Full Day'}</span>
                  </div>
                ) : (
                  <></>
                )
              }
              {

               el.newInfo.isShowFirstHalf ? (
                  <div style={{marginRight: 20}}>
                    <span style={{marginRight: 10}}>
                      {
                        el.cancelDayTypes.includes('First Half') ? 
                         <CloseCircleFilled style={{color:'#FF4D4F'}} />  : el.unChangedDayTypes.includes('First Half') ? <CheckCircleFilled style={{color:'#75BF00'}} /> : <></>
                      }
                    </span>
                    <span>{'First Half'}</span>
                  </div>
                ) : (
                  <></>
                )
              }
              {
                el.newInfo.isShowSecondHalf ? (
                  <div style={{marginRight: 20}}>
                    <span style={{marginRight: 10}}>
                      {
                        el.cancelDayTypes.includes('Second Half') ? 
                         <CloseCircleFilled style={{color:'#FF4D4F'}} />  : el.unChangedDayTypes.includes('Second Half') ? <CheckCircleFilled style={{color:'#75BF00'}} /> : <></>
                      }
                    </span>
                    <span>{'Second Half'}</span>
                  </div>
                ) : (
                  <></>
                )
              }
              {/* <Image
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
              </span> */}
            </Row>
          ),
        };
      },
    },
  ];

  return (
    <>
      <Row style={{ width: '100%', marginLeft: 20 }}>
        {props.scope != 'EMPLOYEE' ? (
          <>
            <Row style={{ marginBottom: 30, marginTop: 30, width: '100%' }}>
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
                    <Row style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}>
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

        <Row style={{ marginBottom: 10, width: '100%' }}>
          <Col span={24} style={{ backgroundColor: '' }}>
            <Row>
              <Col span={24} style={{ fontWeight: 500, fontSize: 18, color: '#394241' }}>
                <span>
                  <FormattedMessage id="leaveDetails" defaultMessage="Leave Cancel Details" />
                </span>{' '}
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
            span={24}
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
              &nbsp; &nbsp;
              <Tag
                style={{
                  borderRadius: 20,
                  paddingRight: 20,
                  paddingLeft: 20,
                  paddingTop: 4,
                  paddingBottom: 2,
                  border: 0,
                  fontSize: 14,
                }}
                color={'#FFF7E6'}
              >
                <span style={{ color: '#D76B4F' }}>{leaveType['name']}</span>
              </Tag>
            </Row>
          </Col>
        </Row>
        <Row className='cancelLeaveView' style={{ marginBottom: 5, width: '100%' }}>
          <Col span={18}>
            <ProTable
              columns={viewDateColumns}
              size={'small'}
            //   scroll={leaveDateRangeList.length > 3 ? { y: 130 } : undefined}
              actionRef={viewTableRef}
              dataSource={diffDetails}
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
        {props.cancelLeaveRequestData.workflowInstanceId ? (
          <ApprovalLevelDetails
            workflowInstanceId={props.cancelLeaveRequestData.workflowInstanceId}
            setApproverComment={props.setApproverComment}
            actions={props.actions}
            scope={props.scope}
          ></ApprovalLevelDetails>
        ) : (
          <></>
        )}
      </Row>
    </>
  );
};

export default CancelLeaveRequest;
