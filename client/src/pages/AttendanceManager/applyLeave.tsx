import React, { useEffect, useRef, useState } from 'react';
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
  Popconfirm,
  Typography,
  Select,
  DatePicker,
  Carousel,
} from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import PermissionDeniedPage from '../403';
import { Access, useAccess, useIntl, history } from 'umi';
import { ProFormDateRangePicker, ProFormSelect, ProFormDatePicker } from '@ant-design/pro-form';
import moment from 'moment';
import { ContactsOutlined, DoubleLeftOutlined, DoubleRightOutlined, LeftOutlined, RightOutlined, UploadOutlined } from '@ant-design/icons';
import {
  addLeave,
  getLeaveTypesForApplyLeave,
  getEmployeeEntitlementCount,
  calculateWorkingDaysCountForLeave,
  getShiftData,
} from '@/services/leave';
import BriefCaseIcon from '../../assets/attendance/BriefCase.png';
import { getBase64 } from '@/utils/fileStore';
import './style.css';
import _ from 'lodash';
import { position } from 'html2canvas/dist/types/css/property-descriptors/position';

const { TextArea } = Input;

const ApplyLeave: React.FC = () => {
  const { RangePicker } = DatePicker;

  const access = useAccess();
  const { hasPermitted } = access;
  const [shiftData, setShiftData] = useState<any>(null);
  const [fromDate, setFromDate] = useState<Date | null>(null);
  const [toDate, setToDate] = useState<Date | null>(null);
  const [fromTime, setFromTime] = useState<string | null>(null);
  const [toTime, setToTime] = useState<string | null>(null);
  const [radioVal, setRadioVal] = useState<any | null>(1);
  const [leavePeriodType, setLeavePeriodType] = useState<string | null>('FULL_DAY');
  const [selectedleaveType, setSelectedLeaveType] = useState<string | null>(null);
  const [selectedleaveTypeObject, setSelectedLeaveTypeObject] = useState(
    {
      fullDayAllowed: true,
      halfDayAllowed: true,
      shortLeaveAllowed: false,
    });
  const [toTimeDisableState, setToTimeDisableState] = useState<boolean>(true);
  const [isAttachementMandatory, setIsAttachementMandatory] = useState<boolean>(false);
  const [canShowAttachement, setCanShowAttachement] = useState<boolean>(true);
  const [leaveReason, setLeaveReason] = useState<string | null>(null);
  const [attachmentList, setAttachmentList] = useState<any>([]);
  const [shortLeaveDuration, setShortLeaveDuration] = useState<any>(null);
  const [leaveTypes, setLeaveTypes] = useState<Array<any>>([]);
  const [workingDaysCount, setWorkingDaysCount] = useState<String | null>();
  const [entitlementCount, setEntitlementCount] = useState<Array<any>>([]);
  const [fileFormatError, setfileFormatError] = useState(false);
  const [form] = Form.useForm();
  const intl = useIntl();
  const [leaveTypesArr, setLeaveTypesArr] = useState([])
  const { Title, Paragraph, Text } = Typography;
  const [showCount, setShowCount] = useState(false)
  const slider = useRef();

  useEffect(() => {
    getLeaveTypes()
    init();
  }, []);

  useEffect(() => {
    calculateWorkingDaysCount();
  }, [selectedleaveType, fromDate, toDate]);

  useEffect(() => {

    if ((leavePeriodType == 'IN_SHORT_LEAVE' || leavePeriodType == 'OUT_SHORT_LEAVE')  && fromDate) {
      getShiftDataForLeaveDate(leavePeriodType);

    }

  }, [fromDate]);


  const getShiftDataForLeaveDate = async (periodType: any) => {
    const res = await getShiftData(fromDate, toDate);
    setShiftData(res.data.shift);

    form.setFieldsValue({ fromTime: null, toTime: null });
    if (periodType == 'IN_SHORT_LEAVE' && res.data.shift != null && fromDate) {
      let time = moment(res.data.shift.startTime, 'HH:mm').add(shortLeaveDuration, 'minutes').format('HH:mm');

      let toTime = (shortLeaveDuration) ? moment(time, 'HH:mm') : null;
      

      form.setFieldsValue({ fromTime: moment(res.data.shift.startTime, 'HH:mm'), toTime: toTime });
      setToTime(time);
      setFromTime(res.data.shift.startTime);
    }

    if (periodType == 'OUT_SHORT_LEAVE' && res.data.shift != null && fromDate) {
      let time = moment(res.data.shift.endTime, 'HH:mm').subtract(shortLeaveDuration, 'minutes').format('HH:mm');
      let fromTime = (shortLeaveDuration) ? moment(time, 'HH:mm') : null;

      form.setFieldsValue({ toTime: moment(res.data.shift.endTime, 'HH:mm'), fromTime: fromTime });
      setToTime(res.data.shift.endTime);
      setFromTime(time);
    }
  }

  useEffect(() => {
      if (workingDaysCount!=null && Number(workingDaysCount)===0) {
          form.setFields([{
                  name: 'date',
                  errors: ['No Working days for the selected date range'] 
              }
          ]);
          return;
      }
  }, [workingDaysCount]);


  const validateDate = (val) => {
    if (!form.getFieldValue("date")) {
      return Promise.reject(new Error(" "));
    }
    if (workingDaysCount!=null && Number(workingDaysCount)===0) {
      return Promise.reject(new Error('No Working days for the selected date range'));
    }

    return Promise.resolve();
  }
  const init = async () => {

    const res = await getEmployeeEntitlementCount();
    setEntitlementCount(res.data);
  };

  const calculateWorkingDaysCount = async () => {
    if (selectedleaveType && fromDate && toDate) {
      const res = await calculateWorkingDaysCountForLeave(selectedleaveType, fromDate, toDate);
      if (leavePeriodType != 'FULL_DAY' && leavePeriodType != 'IN_SHORT_LEAVE' && leavePeriodType != 'OUT_SHORT_LEAVE') {
         setWorkingDaysCount(res.data.count == 1 ?'0.5' : null);
       } else {
         setWorkingDaysCount(res.data.count ?? null);
       }
      
      // setShiftData(res.data.shift);
    } else {
      setWorkingDaysCount(null);
    }
  };

  const uploaderProps = {
    beforeUpload: file => {
      const isValidFormat = file.type === 'image/jpeg' || file.type === "application/pdf";
      if (!isValidFormat) {
        message.error("File format should be JPG or PDF")
      }
      return isValidFormat || Upload.LIST_IGNORE;
    },
    onChange({ file, fileList }) {
      if (file.status !== 'uploading') {
        form.setFieldsValue({upload : fileList});
        setAttachmentList(fileList);
        setfileFormatError(false);
      }
      // for handle error
      if (file.status === 'error') {
        const { uid } = file;
        const index = fileList.findIndex((file: any) => file.uid == uid);
        const newFile = { ...file };
        if (index > -1) {
          newFile.status = 'done';
          newFile.percent = 100;
          delete newFile.error;
          fileList[index] = newFile;
          setAttachmentList([...fileList]);
        }
      }
    },
  };

  const handleUpload = async (file: any): Promise<object> => {
    const base64File = await getBase64(file.originFileObj);

    const tempObj = {
      fileName: file.name,
      fileSize: file.size,
      data: base64File,
    };
    return tempObj;
  };

  const changeDateRange = (ranges: object) => {
    if (ranges != null) {
      setShowCount(true)
      setFromDate(ranges[0].format('YYYY-MM-DD'));
      setToDate(ranges[1].format('YYYY-MM-DD'));
    } else {
      setShowCount(false)
      setFromDate(null);
      setToDate(null);
    }
  };

  const applyLeave = async () => {
    try {
      if (workingDaysCount!=null && Number(workingDaysCount)===0) {
        form.setFields([{
                name: 'date',
                errors: ['No Working days for the selected date range'] 
            }
        ]);
        return;
      }
      
      if (fileFormatError) {
        message.error("File format should be JPG or PDF")
      } else {
        await form.validateFields();
        const selectedAttachment: Array<object> = [];

        if (canShowAttachement) {
          for (let index = 0; index < attachmentList.length; index++) {
            const base64File = await getBase64(attachmentList[index].originFileObj);
            selectedAttachment[index] = {
              fileName: attachmentList[index].name,
              fileSize: attachmentList[index].size,
              data: base64File,
            };
          }
        }


        let calcWorkingDays = 0;
        if (leavePeriodType != 'FULL_DAY' && leavePeriodType != 'IN_SHORT_LEAVE' && leavePeriodType != 'OUT_SHORT_LEAVE') {
          calcWorkingDays = 0.5;
        } else {
          if (leavePeriodType != 'IN_SHORT_LEAVE' && leavePeriodType != 'OUT_SHORT_LEAVE') {
            let startDate = moment(fromDate, 'YYYY-MM-DD');
            let endDate = moment(toDate, 'YYYY-MM-DD');

            calcWorkingDays =
              1 + (endDate.diff(startDate, 'days') * 5 - (startDate.day() - endDate.day()) * 2) / 7;

            if (endDate.day() == 6) calcWorkingDays--; //SAT
            if (startDate.day() == 0) calcWorkingDays--; //SUN
          }
        }


        if (fromTime && toTime) {
          if (fromTime > toTime) {
            return;
          }

          let ftime = moment(fromTime, 'HH:mm');
          let ttime = moment(toTime, 'HH:mm');

          let duration = moment.duration(ttime.diff(ftime));

          let diffFromMin = duration.asMinutes();

          if (diffFromMin > shortLeaveDuration) {
            let hrs = shortLeaveDuration / 60;
            let msg = "Short leave duration cannot exceed "+hrs+ " hours.";
            return message.error(msg);
          }

        }

        const params: any = {
          leaveTypeId: selectedleaveType,
          leavePeriodType: leavePeriodType,
          fromDate: fromDate,
          toDate: toDate,
          fromTime: fromTime,
          toTime: toTime,
          reason: leaveReason,
          numberOfLeaveDates: calcWorkingDays.toString(),
          attachmentList: selectedAttachment,
          isGoThroughWf: true,
        };

        // await form.validateFields();
        //const hide = message.loading('Applying...');
        const result = await addLeave(params);
       // hide();
        message.success(result.message);
        history.push('/ess/my-requests');
        // window.location.reload();
      }
    } catch (error) {
      if (!_.isEmpty(error)) {
        let errorMessage;
        let errorMessageInfo;

        if (error.message && error.message.includes(".")) {
          let errorMessageData = error.message.split(".");
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        if (error.message) {
          message.error({
            content:
              error.message ?
                <>
                  {errorMessage ?? error.message}
                  <br />
                  <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                    {errorMessageInfo ?? ''}
                  </span>
                </>
                : <></>
          });
        }
      }
    }
  };

  const getLeaveTypes = async () => {
    const actions: any = [];

    const res = await getLeaveTypesForApplyLeave();

    if (!_.isEmpty(res.data)) {
      setLeaveTypes(res.data);
    }

    await res.data.forEach(async (element: any) => {
      await actions.push({ value: element['id'], label: element['name'] });
    });
    setLeaveTypesArr(actions);
  };


  const changeLeavePeriod = (event) => {
    form.setFieldsValue({ fromTime: null, toTime: null, date: null })
    setRadioVal(event);
    switch (event) {
      case 1:
        form.setFieldsValue({ date: null })
        setLeavePeriodType('FULL_DAY');
        break;
      case 2:
        setLeavePeriodType('FIRST_HALF_DAY');
        break;
      case 3:
        setLeavePeriodType('SECOND_HALF_DAY');
        break;
      case 4:
        setLeavePeriodType('IN_SHORT_LEAVE'); 
        if (fromDate) {
          getShiftDataForLeaveDate('IN_SHORT_LEAVE');
        }
        break;
      case 5:
        setLeavePeriodType('OUT_SHORT_LEAVE');
        if (fromDate) {
          getShiftDataForLeaveDate('OUT_SHORT_LEAVE');
        }
        break;
      default:
        break;
    }
  };

  const initValues: object = {
    leaveType: null,
    date: null,
    leavePeriodType: 1,
    fromTime: null,
    toTime: null,
    reason: '',
  };

  const getDisabledHours = () => {
    var hours = [];
    for (var i = 0; i < moment(fromTime, 'HH:mm').hour(); i++) {
      hours.push(i);
    }
    return hours;
  };

  const getDisabledMinutes = (selectedHour) => {
    var minutes = [];
    if (moment(fromTime, 'HH:mm').hour() === selectedHour) {
      console.log(selectedHour);
      for (var i = 0; i < moment(fromTime, 'HH:mm').minute(); i++) {
        minutes.push(i);
      }
    }
    return minutes;
  };

  const settings = {
    slidesToShow:  entitlementCount.length>1?2:1,
    dots: false,
    infinite: true,
    centerMode: true,
    centerPadding:8,
    responsive: [
      {
        breakpoint: 1900,
        settings: {
          slidesToShow: entitlementCount.length>1?2:1,
          dots: false,
          infinite: true,
          centerMode: true,


        }
      },
      {
        breakpoint: 1600,
        settings: {
          slidesToShow: 1,
          dots: false,
          infinite: true,
          centerMode: true,
          centerPadding:90,


        }
      },
      {
        breakpoint: 1400,
        settings: {
          slidesToShow: 1,
          dots: false,
          infinite: true,
          centerMode: true,
          centerPadding:90,


        }
      },
      {
        breakpoint: 1200,
        settings: {
          slidesToShow: 1,
          dots: false,
          infinite: true,
          centerMode: true,
          centerPadding:90,


        }
      },
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 1,
          dots: false,
          infinite: true,
          centerMode: true,
          centerPadding:90,

        }
      },
      {
        breakpoint: 600,
        settings: {
          slidesToShow: 1,
          dots: false,
          infinite: true,
          centerMode: true,
          centerPadding:90,


        }
      },
      {
        breakpoint: 480,
        settings: {
          slidesToShow: 1,
          dots: false,
          infinite: true,
          centerMode: true,
          centerPadding:40,


        }
      }

    ]

  }
  return (
    <>
      <Access
        accessible={hasPermitted('attendance-employee-access')}
        fallback={<PermissionDeniedPage />}
      >
        <div>
          <PageContainer
            header={{
              ghost: true,
            }}
          >
            <ProCard
              direction="column"
              ghost
              gutter={[0, 16]}
              style={{ padding: 0, margin: 0, height: '100%' }}
            >
              <Row style={{ width: '100%' }} gutter={16}>
                <Col flex="auto" order={2} xl={{order:1}} >
                  <Card style={{ width: '100%', borderRadius: '10px' }}>
                    <Form form={form} layout="vertical" initialValues={initValues} >

                      <Row style={{ marginLeft: 12,paddingBottom: 0, marginBottom:0 } }>
                        <Col span={6}>
                          <Form.Item
                            name="leaveType"
                            label="Leave Type"
                            style={{marginBottom: 16, width:320}}

                            rules={[
                              {
                                required: true,
                                message: 'Required',
                              },
                            ]}
                          >
                            <Select
                              allowClear
                              showSearch
                              placeholder="Select Leave Type"
                              optionFilterProp="label"
                              onChange={(value) => {

                                if (!value) {

                                  setCanShowAttachement(true);
                                  setIsAttachementMandatory(false);

                                  setSelectedLeaveTypeObject({
                                    fullDayAllowed: true,
                                    halfDayAllowed: true,
                                    shortLeaveAllowed: false,
                                  })
                                  form.setFieldsValue({ leavePeriodType: 1 })
                                  setRadioVal(1)
                                  return

                                }
                                const leaveTypeObject = leaveTypes.find((leaveType) => leaveType.id == value);
                                
                                if (!leaveTypeObject.fullDayAllowed && leaveTypeObject.halfDayAllowed) {
                                  form.setFieldsValue({ leavePeriodType: 2 })

                                }
                                else {
                                  form.setFieldsValue({ leavePeriodType: 1 })

                                }


                                if (leaveTypeObject.shortLeaveAllowed) {
                                  setShortLeaveDuration(leaveTypeObject.short_leave_duration);
                                  form.setFieldsValue({ leavePeriodType: 4 })
                                }

                                if (leaveTypeObject.allowAttachment) {
                                  setCanShowAttachement(true);
                                  if (leaveTypeObject.attachmentManadatory) {
                                    setIsAttachementMandatory(true);
                                  } else {
                                    setIsAttachementMandatory(false);
                                  }
                                } else {
                                  setCanShowAttachement(false);
                                }

                                if (!leaveTypeObject.shortLeaveAllowed) {

                                  if (leaveTypeObject.halfDayAllowed && leaveTypeObject.fullDayAllowed) {
                                    changeLeavePeriod(1)
                                  }

                                  if (leaveTypeObject.halfDayAllowed && !leaveTypeObject.fullDayAllowed) {
                                    changeLeavePeriod(2)
                                  }

                                } else {
                                  changeLeavePeriod(4)
                                }

                                setSelectedLeaveType(value);
                                setSelectedLeaveTypeObject(
                                  leaveTypes.find((leaveType) => leaveType.id == value),);
                              }}
                              options={leaveTypesArr}

                            />
                          </Form.Item>
                        </Col>
                      </Row>

                      {
                        // (leavePeriodType !== 'SHORT_LEAVE') ? 
                        // (
                          <Row style={{ marginLeft: 12 }}>
                            <Col md={24} lg={24} xl={24} xxl={18}>
                              <Form.Item
                                style={{marginBottom: 12}}

                                name="leavePeriodType"
                                label={<FormattedMessage id="period" defaultMessage="Period" />}
                              >
                                <Radio.Group onChange={(event) => changeLeavePeriod(event.target.value)} value={radioVal}>
                                  {selectedleaveTypeObject?.fullDayAllowed ? (
                                    <Radio value={1}>Full Day</Radio>
                                  ) : null}
                                  {selectedleaveTypeObject?.halfDayAllowed ? (
                                    <Radio value={2}>First Half Day</Radio>
                                  ) : null}
                                  {selectedleaveTypeObject?.halfDayAllowed ? (
                                    <Radio value={3}>Second Half Day</Radio>
                                  ) : null}
                                  {selectedleaveTypeObject?.shortLeaveAllowed ? (
                                    <Radio value={4}>In Short Leave</Radio>
                                  ) : null}
                                  {selectedleaveTypeObject?.shortLeaveAllowed ? (
                                    <Radio value={5}>Out Short Leave</Radio>
                                  ) : null}
                                </Radio.Group>
                              </Form.Item>

                            </Col>
                          </Row>
                        // ) : (
                        //   <></>
                        // )
                      }
                      


                      <Row style={{ marginLeft: 12 }} justify="start">
                        <Col >

                          {leavePeriodType === 'FULL_DAY' ? (
                          <Form.Item
                              style={{marginBottom: 16, width: 320}}
                            
                              name="date"
                              label="Date"

                              rules={leavePeriodType === 'FULL_DAY' ? [{
                                required: true,
                                message: 'Required',
                              }] : []}
                            >
                              <RangePicker
                                format="DD-MM-YYYY"
                                onChange={changeDateRange}
                                style={{width: '100%'}}

                              />
                            </Form.Item>

                          ) : (
                            <Form.Item
                              style={{marginBottom: 16, width: 150}}                              
                              name="date"
                              label="Date"
                              rules={leavePeriodType !== 'FULL_DAY' ? [{
                                required: true,
                                message: 'Required',
                              }] : []}
                            >
                              <DatePicker
                                name="date"
                                style={{width: '100%'}}
                                format={'DD-MM-YYYY'}
                                onChange={(value) => {
                                  if (value != null) {
                                    setShowCount(true)
                                    let date =
                                      !_.isNull(value) && !_.isUndefined(value)
                                        ? value.format('YYYY-MM-DD')
                                        : null;
                            
                                    setFromDate(date);
                                    setToDate(date);
                                  } else {
                                    setShowCount(false)
                                    setFromDate(null);
                                    setToDate(null);
                                  }
                                }}


                              />
                            </Form.Item>
                          )}
                        </Col >
                        <Col span={6} offset={2} >
                          {(showCount && leavePeriodType !== 'IN_SHORT_LEAVE' && leavePeriodType !== 'OUT_SHORT_LEAVE') ?

                            <span >
                              <Text style={{ color: "#626D6C", fontSize: 14 }}>Leave days</Text>
                              <div
                                style={{
                                  verticalAlign: " text-top",
                                  textAlign: 'left',
                                  height: 38,
                                  fontSize: 32,

                                }}
                              >
                                <Text style={{

                                  fontWeight: 400,
                                  color: '#626D6C',
                                }}>
                                  {workingDaysCount} {Number(workingDaysCount) > 1 ? "days" : "day"}
                                </Text>
                              </div>
                            </span>
                            : <></>}
                        </Col>
                      </Row>
                      <Row style={{ marginLeft: 12 }}>
                        {(leavePeriodType === 'IN_SHORT_LEAVE' || leavePeriodType === 'OUT_SHORT_LEAVE') ? (
                          <Col span={18} style={{width: '100%' }}>
                            <Row>
                              <Col style={{paddingBottom: 8, color: '#626D6C'}}>
                                <FormattedMessage id="timePeriod" defaultMessage="Time Period" />
                              </Col>
                            </Row>
                            <Row>
                              <Col>
                                <Form.Item
                                  style={{marginBottom: 16, width:150}}
                                 
                                  name="fromTime"
                                // label={<FormattedMessage id="period" defaultMessage="Period" />}
                                >
                                  <TimePicker
                                    placeholder={'Start Time'}
                                    style={{width: '100%'}}
                                    format={'HH:mm'}
                                    onSelect={(value) => {
                                      
                                      form.setFieldsValue({fromTime : null});
                                      if (value != null) {
                                        setFromTime(value.format('HH:mm'));
                                        let fromTimeVal =value.format('HH:mm');
                                        form.setFieldsValue({fromTime : moment(fromTimeVal,'HH:mm')});
                                        setToTimeDisableState(false);
                                        if (toTime) {
                                          let tTime = moment(toTime, 'HH:mm');
                                          let fTime = moment(value.format('HH:mm'), 'HH:mm');
                                          let duration = tTime.diff(fTime);
                                          
                                          if (duration < 0) {
                                            form.setFields([{
                                                    name: 'fromTime',
                                                    errors: ['Should be before To Time'] 
                                                }
                                            ]);
                                          } else {
                                            form.setFields([{
                                                    name: 'fromTime',
                                                    errors: [] 
                                                }, {
                                                  name: 'toTime',
                                                  errors: []
                                                }
                                            ]);
                                          }

                                        }
                                      } else {
                                        setFromTime(null);
                                        setToTime(null);
                                        // setToTimeDisableState(true);
                                      }
                                    }}
                                  />
                                </Form.Item>
                              </Col>
                              <Col style={{ paddingLeft: 16 }}>
                              <Form.Item
                                style={{marginBottom: 16, width:150}}                                
                                name="toTime">
                                  <TimePicker
                                    placeholder={'End Time'}
                                    format={'HH:mm'}
                                    style={{width: '100%'}}
                                    onSelect={(value) => {

                                      form.setFieldsValue({toTime : null});
                                      if (value != null) {
                                        setToTime(value.format('HH:mm'));
                                        let toTimeVal =value.format('HH:mm');
                                        form.setFieldsValue({toTime : moment(toTimeVal,'HH:mm')});
                                        if (fromTime) {
                                          let tTime = moment(value.format('HH:mm'), 'HH:mm');
                                          let fTime = moment(fromTime, 'HH:mm');

                                          let duration = tTime.diff(fTime);
                                          
                                          if (duration < 0) {
                                            form.setFields([{
                                                    name: 'toTime',
                                                    errors: ['Should be after From Time'] 
                                                }
                                            ]);
                                          } else {
                                            form.setFields([{
                                                    name: 'fromTime',
                                                    errors: [] 
                                                }, {
                                                  name: 'toTime',
                                                  errors: []
                                                }
                                            ]);
                                          }
                                        }
                                      } else {
                                        setToTime(null);
                                      }
                                     
                                    }}
                                    // disabled={toTimeDisableState}
                                  />
                                </Form.Item>
                              </Col>
                            </Row>
                          </Col>
                        ) : (
                          <></>
                        )}
                      </Row>
                      <Row style={{ marginLeft: 12 }}>
                        <Col span={17} style={{}}>
                          {/* <Row><Col><FormattedMessage id="reason" defaultMessage="Reason" /></Col></Row> */}
                          <Row>
                            <Col style={{ width: '100%' }}>
                            <Form.Item
                                style={{marginBottom: 16}}                                
                                name="reason"
                                label={<FormattedMessage id="reason" defaultMessage="Reason" />}
                                rules={[{ max: 250, message: 'Maximum length is 250 characters.' }]}
                              >
                                <Input.TextArea
                                  maxLength={251}
                                  rows={4}
                                  onChange={(event) => {
                                    setLeaveReason(event.target.value);
                                  }}
                                />
                              </Form.Item>
                            </Col>
                          </Row>
                        </Col>
                      </Row>
                      <Row style={{ marginLeft: 12, marginBottom: 12 }}>
                        <Col span={9}>
                          {
                            canShowAttachement ? (
                              <Form.Item
                                name="upload"
                                label={<FormattedMessage
                                  id="attachDocument"
                                  defaultMessage="Attach Document"
                                />}
                                rules={[
                                  {
                                    required: isAttachementMandatory,
                                    message: 'Required',
                                  },
                                ]}
                              >
                                <Upload {...uploaderProps} className="upload-btn">
                                  <Button style={{ borderRadius: 6 }} icon={<UploadOutlined />}>
                                    Upload
                                  </Button>
                                  <span style={{ paddingLeft: 8, color: '#AAAAAA' }}> JPG or PDF</span>
                                </Upload>
                                {fileFormatError && <Row style={{ color: '#ff4d4f' }}><Col><FormattedMessage id="attachDocument" defaultMessage="File format should be JPG or PDF" /></Col></Row>}
                              </Form.Item>
                            ) : (
                              <></>
                              
                            )
                          }

                          
                        </Col>
                      </Row>
                      <Row  >
                        <Col span={17} >
                          <Row justify='end'>
                            <Popconfirm
                              key="reset"
                              title={intl.formatMessage({
                                id: 'are_you_sure',
                                defaultMessage: 'Are you sure?',
                              })}
                              onConfirm={() => {
                                setWorkingDaysCount(null)
                                form.resetFields();
                                setAttachmentList([]);
                                setLeavePeriodType('FULL_DAY');
                                setSelectedLeaveTypeObject({
                                  fullDayAllowed: true,
                                  halfDayAllowed: true,
                                  shortLeaveAllowed: false
                                })
                                setShowCount(false)
                              }

                              }
                              okText="Yes"
                              cancelText="No"
                            >
                              <Button style={{ borderRadius: 6 }}>
                                {intl.formatMessage({
                                  id: 'reset',
                                  defaultMessage: 'Reset',
                                })}
                              </Button>
                            </Popconfirm>
                            <Button
                              type="primary"
                              onClick={applyLeave}
                              style={{ marginLeft: 25, borderRadius: 6 }}
                            >
                              {intl.formatMessage({
                                id: 'Apply',
                                defaultMessage: 'Apply',
                              })}
                            </Button>

                          </Row>
                        </Col>
                      </Row>
                    </Form>
                  </Card>
                </Col>
                <Col style={{width:440}} order={1} xl={{order:2}}>
                  <Row justify="center" style={{ height: 150, marginBottom:16}}>

                    <Col span={2} >
                      <Row align='middle' justify='start' style={{ height: 150 }}>
                        <LeftOutlined color='#626D6C' onClick={() => { slider.current.next() }} />
                      </Row>
                    </Col>
                    <Col span={20}>
                      <Carousel
                        {...settings}
                        ref={ref => {
                          slider.current = ref;
                        }}
                        style={{  height: 150, top: 0, left: 0 }}>

                        {entitlementCount.map((entitlement, index) => (
                        
                          <div id={`${index}`}>
                            <div style={{ width: 154, height: 145, backgroundColor: 'white', borderRadius: 10 }}>
                              <div style={{ background: entitlement.leaveTypeColor, height: 10, width: 154, borderTopLeftRadius: 6, borderTopRightRadius: 6, top: 0, left: 0 }} />
                              <div style={{ paddingTop: 4, paddingLeft: 12, paddingRight: 12, paddingBottom: 12 }}>
                                <Row justify="center" align="top" style={{ height: 32, marginTop: 0, marginBottom: 6, textAlign: "center" }}>
                                  <span className='card-widget-title'>
                                    {entitlement.name}
                                  </span>
                                </Row>
                                <Row justify="center" align="middle" style={{ paddingBottom: 0 }} >
                                  <Col className='card-available'>
                                    {entitlement.total - (entitlement.used + entitlement.pending)}
                                  </Col>
                                </Row>
                                <Row justify="center" style={{ marginTop: 0 }}>
                                  <span className='card-available-title'>
                                    Available
                                  </span>
                                </Row>
                                <Row style={{ height: 30, marginTop: 8 }} justify="space-between"  >
                                  <Col span={12} className={"card-taken-txt"}>
                                    Taken &nbsp; {entitlement.used}
                                  </Col>
                                  <Col span={12} className={"card-used-txt"}>
                                    Total &nbsp; {entitlement.total}

                                  </Col>
                                </Row>
                              </div>
                            </div>
                          </div>
                        ))}
                      </Carousel>
                    </Col>
                    <Col span={2}>
                      <Row align='middle' justify='end' style={{ height: 150 }}>
                        <RightOutlined color='#626D6C' onClick={() => { slider.current.prev() }} />
                      </Row>
                    </Col>
                  </Row>
                </Col>

                {/* <Col style={{ marginLeft: 10 }}>
                                                <div style={{ width: 140, backgroundColor: 'white', padding: 10 }}>
                                                    <Row style={{ backgroundColor: '' }}>
                                                        <Col style={{ height: 50, width: 40, backgroundColor: '' }}><Image src={SeaUmbIcon} style={{ width: 30, height: 40, }} preview={false} /></Col>
                                                        <Col>
                                                            <Row><Col style={{ fontWeight: 'bold', height: 25, fontSize: 18, backgroundColor: '' }}>Annual</Col></Row>
                                                            <Row><Col style={{ fontSize: 10, height: 20, backgroundColor: '' }}>Total 07</Col></Row>
                                                        </Col>
                                                    </Row>
                                                    <Row style={{ backgroundColor: '' }}>
                                                        <Col style={{ height: 40, width: 40, backgroundColor: '', fontWeight: 'bold', fontSize: 30, }}>01</Col>
                                                        <Col>
                                                            <Row><Col style={{ fontWeight: 'normal', height: 20, fontSize: 13, backgroundColor: '' }}>Available</Col></Row>
                                                            <Row><Col style={{ fontSize: 10, height: 20, backgroundColor: '', color: 'gray' }}>Taken 06</Col></Row>
                                                        </Col>
                                                    </Row>
                                                </div>
                                            </Col> */}
              </Row>
            </ProCard>
          </PageContainer>
        </div>
      </Access>
    </>
  );
};

export default ApplyLeave;
