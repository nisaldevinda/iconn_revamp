import React, { useEffect, useState, useRef } from 'react';
import { Button, Card, Col, Image, Input, Radio, Row, Upload, TimePicker, message, Form, Popconfirm, DatePicker, Typography, Select, Carousel } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import PermissionDeniedPage from '../403';
import { Access, useAccess, useIntl, history } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import { getModel, ModelType } from "@/services/model";
import { ClockCircleOutlined, RobotOutlined, UploadOutlined, LeftOutlined, RightOutlined } from '@ant-design/icons';
import { addLeave, assignLeave, getLeaveTypesForAssignLeave, getLeaveTypesForApplyLeave,
    getEntitlementCountByEmployeeId,
    calculateWorkingDaysCountForLeaveAssign,
    getShiftDataForAssignLeave
} from '@/services/leave';
import request, { APIResponse } from "@/utils/request";
import { getBase64 } from "@/utils/fileStore";
import BriefCaseIcon from '../../assets/attendance/BriefCase.png';
import './style.css';
import { getEmployeeList } from '@/services/dropdown';



const { TextArea } = Input;

const ApplyLeave: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [shiftData, setShiftData] = useState<any>(null);
    const [fromDate, setFromDate] = useState<Date | null>(null);
    const [toDate, setToDate] = useState<Date | null>(null);
    const [fromTime, setFromTime] = useState<string | null>(null);
    const [toTime, setToTime] = useState<string | null>(null);
    const [radioVal, setRadioVal] = useState<any | null>(1);
    const [leavePeriodType, setLeavePeriodType] = useState<string | null>('FULL_DAY');
    const [selectedleaveType, setSelectedLeaveType] = useState<string | null>(null);
    const [toTimeDisableState, setToTimeDisableState] = useState<boolean>(true);
    const [leaveReason, setLeaveReason] = useState<string | null>(null);
    const [attachmentList, setAttachmentList] = useState<any>([]);
    const [selectedEmployee, setSelectedEmployee] = useState<string | null>(null);
    const [relatedLeaveTypes, setRelatedLeaveTypes] = useState([]);
    const [relatedEmployees, setRelatedEmployees] = useState([]);
    const [leaveTypes, setLeaveTypes] = useState<Array<any>>([]);
    const [isEmpSelected, setIsEmpSelected] = useState<boolean>(true);
    const [fileFormatError, setfileFormatError] = useState(false);
    const [form] = Form.useForm();
    const [entitlementCount, setEntitlementCount] = useState<Array<any>>([]);
    const intl = useIntl();
    const [showCount, setShowCount] = useState(false);
    const { Title, Paragraph, Text } = Typography;
    const [workingDaysCount, setWorkingDaysCount] = useState<String | null>();
    const { RangePicker } = DatePicker;
    const [selectedleaveTypeObject, setSelectedLeaveTypeObject] = useState(
        { 
          fullDayAllowed:true,
          halfDayAllowed:true,
          shortLeaveAllowed: false
      });
    const slider = useRef();
    const [isAttachementMandatory, setIsAttachementMandatory] = useState<boolean>(false);
    const [canShowAttachement, setCanShowAttachement] = useState<boolean>(true);
    const [shortLeaveDuration, setShortLeaveDuration] = useState<any>(null);

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
                setfileFormatError(false);
                setAttachmentList(fileList);
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
        }
    };

    useEffect(() => {
        getRelatedEmployees();
    }, []);
    useEffect(() => {
        calculateWorkingDaysCount();
    }, [selectedleaveType, fromDate, toDate]);

    useEffect(() => {
        if (workingDaysCount != null && Number(workingDaysCount) === 0) {
            form.setFields([{
                name: 'date',
                errors: ['No Working days for the selected date range']
            }
            ]);
            return;
        }
    }, [workingDaysCount]);
    const handleUpload = async (file: any): Promise<object> => {

        const base64File = await getBase64(file.originFileObj);

        const tempObj = {
            fileName: file.name,
            fileSize: file.size,
            data: base64File
        }
        return tempObj;
    }

    useEffect(() => {

        if ((leavePeriodType == 'IN_SHORT_LEAVE' || leavePeriodType == 'OUT_SHORT_LEAVE')  && fromDate) {
          getShiftDataForLeaveDate(leavePeriodType);
    
        }
    
    }, [fromDate]);

    const getShiftDataForLeaveDate = async (periodType: any) => {

        if (selectedEmployee) {

            const res = await getShiftDataForAssignLeave(fromDate, toDate, selectedEmployee);
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
        } else {
            setShiftData(null);
        }
    }

    const changeDateRange = (ranges: object) => {
        if (ranges != null) {
            setShowCount(true);
            setFromDate(ranges[0].format("YYYY-MM-DD"));
            setToDate(ranges[1].format("YYYY-MM-DD"));
        } else {
            setShowCount(false);
            setFromDate(null);
            setToDate(null);
        }
    }

    const calculateWorkingDaysCount = async () => {
        if (selectedEmployee && selectedleaveType && fromDate && toDate) {
            const res = await calculateWorkingDaysCountForLeaveAssign(selectedleaveType, fromDate, toDate, selectedEmployee);
            setWorkingDaysCount(res.data.count ?? null);
        } else {
            setWorkingDaysCount(null);
        }
    };


    const applyLeave = async () => {

        try {
            if (workingDaysCount != null && Number(workingDaysCount) === 0) {
                form.setFields([{
                    name: 'date',
                    errors: ['No Working days for the selected date range']
                }
                ]);
                return;
            }

            if (fileFormatError) {
                message.error("File format should be JPG or PDF");
                return;
            }

            await form.validateFields();
            const selectedAttachment: Array<object> = [];
            if (canShowAttachement) {
                for (let index = 0; index < attachmentList.length; index++) {
    
                    const base64File = await getBase64(attachmentList[index].originFileObj);
                    selectedAttachment[index] = {
                        fileName: attachmentList[index].name,
                        fileSize: attachmentList[index].size,
                        data: base64File
                    };
                }
            }

            let calcWorkingDays = 0;
            if (leavePeriodType != 'FULL_DAY' && leavePeriodType != 'IN_SHORT_LEAVE' && leavePeriodType != 'OUT_SHORT_LEAVE') {
                calcWorkingDays = 0.5;
            } else {
                if (leavePeriodType != 'IN_SHORT_LEAVE' && leavePeriodType != 'OUT_SHORT_LEAVE') {
                    let startDate = moment(fromDate, "YYYY-MM-DD");
                    let endDate = moment(toDate, "YYYY-MM-DD");

                    calcWorkingDays = 1 + (endDate.diff(startDate, 'days') * 5 - (startDate.day() - endDate.day()) * 2) / 7;

                    if (endDate.day() == 6) calcWorkingDays--;//SAT
                    if (startDate.day() == 0) calcWorkingDays--;//SUN
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
      
                if (diffFromMin != shortLeaveDuration) {
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
                employeeId: selectedEmployee
            }

            // await form.validateFields();
            // const hide = message.loading('Asigning...');
            const result = await assignLeave(params);
            // hide();
            message.success('Successfully Assigned');
            history.push('/leave/admin-leave-request');
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
    }

    const getLeaveTypes = async (empId: any) => {
        const actions: any = [];
        if (empId != undefined) {
            setIsEmpSelected(false);
            const res = await getLeaveTypesForAssignLeave(empId);
            if (!_.isEmpty(res.data)) {
                setLeaveTypes(res.data);
            }
            await res.data.forEach(async (element: any) => {
                await actions.push({ value: element['id'], label: element['name'] });
            });
        } else {
            setIsEmpSelected(true);
        }

        setRelatedLeaveTypes(actions);

    }

    const getRelatedEmployees = async () => {
        try {
            const actions: any = [];
            const { data } = await getEmployeeList('ADMIN');
            const res = data.map((employee: any) => {
                actions.push({ value: employee.id, label: employee.employeeName });
              return {
                label: employee.employeeName,
                value: employee.id,
              };
            });
            setRelatedEmployees(actions);
            return res;

        } catch (err) {
            console.log(err);
            return [];
        }

        const res = await request(path, { params });
        await res.data.forEach(async (element: any) => {
            let fullName = element['firstName'] + ' ' + element['lastName'];
            await actions.push({ value: element['id'], label: fullName });

        });
        setRelatedEmployees(actions);
       


    }

    const changeLeavePeriod = (event) => {
        form.setFieldsValue({ fromTime: null, toTime: null })
        setRadioVal(event);
        switch (event) {
            case 1:
                form.setFieldsValue({ date: null })
                setLeavePeriodType('FULL_DAY')
                break;
            case 2:
                setLeavePeriodType('FIRST_HALF_DAY')
                break;
            case 3:
                setLeavePeriodType('SECOND_HALF_DAY')
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

    const getDisabledHours = () => {
        var hours = [];
        for (var i = 0; i < moment(fromTime, "HH:mm").hour(); i++) {
            hours.push(i);
        }
        return hours;
    }

    const getDisabledMinutes = (selectedHour) => {
        var minutes = [];
        if (moment(fromTime, "HH:mm").hour() === selectedHour) {
            console.log(selectedHour);
            for (var i = 0; i < moment(fromTime, "HH:mm").minute(); i++) {
                minutes.push(i);
            }
        }
        return minutes;
    }

    const initValues: object = {
        employee: null,
        leaveType: null,
        date: null,
        leavePeriodType: 1,
        fromTime: null,
        toTime: null,
        reason: ''
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
        <Access accessible={hasPermitted('assign-leave')} fallback={<PermissionDeniedPage />}>
          <div
            style={{
              backgroundColor: 'white',
              borderTopLeftRadius: '30px',
              paddingLeft: '50px',
              paddingTop: '50px',
              paddingBottom: '50px',
              width: '100%',
              paddingRight: '0px',
            }}
          >
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
                  <Col
                    flex="auto"
                    xs={{ order: 2 }}
                    sm={{ order: 2 }}
                    md={{ order: 2 }}
                    lg={{ order: 2 }}
                    xl={{ order: 1 }}
                    xxl={{ order: 1 }}
                  >
                    <Card>
                      <Form
                        form={form}
                        style={{ marginLeft: 12 }}
                        className="leaveAssignForm"
                        layout="vertical"
                        initialValues={initValues}
                      >
                        <Row style={{ paddingBottom: 10 }}>
                          <Col span={9} style={{ backgroundColor: '' }}>
                            <Form.Item
                              name="employee"
                              label="Employee"
                              style={{ width: '80%' }}
                              rules={[
                                {
                                  required: true,
                                  message: 'Required',
                                },
                              ]}
                            >
                              <ProFormSelect
                                name="employeeSelect"
                                showSearch
                                // options={selectorEmployees}
                                fieldProps={{
                                  optionItemRender(item) {
                                    return item.label;
                                  },
                                  onChange: async (value) => {
                                    form.setFieldsValue({ leaveType: null, date: null });
                                    setShowCount(false);
                                    if (value) {
                                      let params = { employee: value };
                                      const res = await getEntitlementCountByEmployeeId(params);
                                      setEntitlementCount(res.data);

                                      setSelectedEmployee(value);
                                      getLeaveTypes(value);
                                    } else {
                                      setRelatedLeaveTypes([]);
                                      setSelectedLeaveType(null);
                                      setShowCount(false);
                                    }
                                  },
                                }}
                                // request={getRelatedEmployees}
                                options={relatedEmployees}
                                placeholder="Select Employee"
                                style={{ marginBottom: 0 }}
                              />
                            </Form.Item>
                          </Col>
                          <Col span={9} style={{ backgroundColor: '' }}>
                            <Form.Item
                              name="leaveType"
                              label="Leave Type"
                              style={{ width: '80%' }}
                              rules={[
                                {
                                  required: true,
                                  message: 'Required',
                                },
                              ]}
                            >
                              <Select
                                allowClear
                                disabled={isEmpSelected}
                                showSearch
                                optionFilterProp="label"
                                placeholder="Select Leave Type"
                                onChange={(value) => {
                                  if (!value) {
                                    setCanShowAttachement(true);
                                    setIsAttachementMandatory(false);

                                    setSelectedLeaveTypeObject({
                                      fullDayAllowed: true,
                                      halfDayAllowed: true,
                                      shortLeaveAllowed: false,
                                    });
                                    form.setFieldsValue({ leavePeriodType: 1 });
                                    setRadioVal(1);
                                    return;
                                  }
                                  setSelectedLeaveType(value);
                                  setShowCount(false);

                                  const leaveTypeObject = leaveTypes.find(
                                    (leaveType) => leaveType.id == value,
                                  );

                                  if (
                                    !leaveTypeObject.fullDayAllowed &&
                                    leaveTypeObject.halfDayAllowed
                                  ) {
                                    form.setFieldsValue({ leavePeriodType: 2 });
                                  } else {
                                    form.setFieldsValue({ leavePeriodType: 1 });
                                  }

                                  if (leaveTypeObject.shortLeaveAllowed) {
                                    setShortLeaveDuration(leaveTypeObject.short_leave_duration);
                                    form.setFieldsValue({ leavePeriodType: 4 });
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
                                    if (
                                      leaveTypeObject.halfDayAllowed &&
                                      leaveTypeObject.fullDayAllowed
                                    ) {
                                      changeLeavePeriod(1);
                                    }

                                    if (
                                      leaveTypeObject.halfDayAllowed &&
                                      !leaveTypeObject.fullDayAllowed
                                    ) {
                                      changeLeavePeriod(2);
                                    }
                                  } else {
                                    changeLeavePeriod(4);
                                  }

                                  form.setFieldsValue({ date: null });
                                  // if (value == 0) {
                                  //     setLeavePeriodType('SHORT_LEAVE');
                                  // }
                                  setSelectedLeaveTypeObject(
                                    leaveTypes.find((leaveType) => leaveType.id == value),
                                  );
                                }}
                                options={relatedLeaveTypes}
                              />
                            </Form.Item>
                          </Col>
                        </Row>
                        <Row>
                          {
                            // (leavePeriodType != 'SHORT_LEAVE') ?
                            // <Col span={16} style={{ paddingBottom: 10 }}>
                            <Col md={24} lg={20} xl={24} xxl={24}>
                              {/* <Row><Col>Period</Col></Row> */}
                              <Row>
                                <Col>
                                  <Form.Item
                                    name="leavePeriodType"
                                    label={<FormattedMessage id="period" defaultMessage="Period" />}
                                  >
                                    <Radio.Group
                                      onChange={(event) => changeLeavePeriod(event.target.value)}
                                      value={radioVal}
                                    >
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
                            </Col>
                          }
                        </Row>
                        <Row>
                          <Col style={{ paddingBottom: 10 }}>
                            <Row>
                              <Col>
                                {leavePeriodType === 'FULL_DAY' ? (
                                  <Form.Item
                                    name="date"
                                    label="Date"
                                    style={{ width: 280 }}
                                    rules={
                                      leavePeriodType === 'FULL_DAY'
                                        ? [
                                            {
                                              required: true,
                                              message: 'Required',
                                            },
                                          ]
                                        : []
                                    }
                                  >
                                    <RangePicker
                                      ranges={{
                                        Today: [moment(), moment()],
                                        'This Month': [
                                          moment().startOf('month'),
                                          moment().endOf('month'),
                                        ],
                                      }}
                                      format="DD-MM-YYYY"
                                      onChange={changeDateRange}
                                    />
                                  </Form.Item>
                                ) : (
                                  <Form.Item
                                    name="date"
                                    label="Date"
                                    rules={
                                      leavePeriodType !== 'FULL_DAY'
                                        ? [
                                            {
                                              required: true,
                                              message: 'Required',
                                            },
                                          ]
                                        : []
                                    }
                                  >
                                    <DatePicker
                                      name="date"
                                      className="assignLeaveDatePicker"
                                      format="DD-MM-YYYY"
                                      style={{ width: 300 }}
                                      onChange={(value) => {
                                        if (value != null) {
                                          setShowCount(true);
                                          let date =
                                            !_.isNull(value) && !_.isUndefined(value)
                                              ? value.format('YYYY-MM-DD')
                                              : null;
                                          setFromDate(date);
                                          setToDate(date);
                                        } else {
                                          setShowCount(false);
                                          setFromDate(null);
                                          setToDate(null);
                                        }
                                      }}
                                      // fieldProps={{

                                      // }}
                                    />
                                  </Form.Item>
                                )}
                              </Col>
                            </Row>
                          </Col>
                          <Col span={6} offset={2}>
                            {showCount &&
                            leavePeriodType !== 'IN_SHORT_LEAVE' &&
                            leavePeriodType !== 'OUT_SHORT_LEAVE' ? (
                              <span>
                                <Text style={{ color: '#626D6C', fontSize: 14 }}>Leave days</Text>
                                <div
                                  style={{
                                    verticalAlign: ' text-top',
                                    textAlign: 'left',
                                    height: 38,
                                    fontSize: 32,
                                  }}
                                >
                                  <Text
                                    style={{
                                      fontWeight: 400,
                                      color: '#626D6C',
                                    }}
                                  >
                                    {workingDaysCount}{' '}
                                    {Number(workingDaysCount) > 1 ? 'days' : 'day'}
                                  </Text>
                                </div>
                              </span>
                            ) : (
                              <></>
                            )}
                          </Col>
                        </Row>
                        <Row>
                          {leavePeriodType === 'IN_SHORT_LEAVE' ||
                          leavePeriodType === 'OUT_SHORT_LEAVE' ? (
                            <Col span={16} style={{ paddingBottom: 10 }}>
                              <Row>
                                <Col style={{ paddingBottom: 8, color: '#626D6C' }}>
                                  Time Period
                                </Col>
                              </Row>
                              <Row>
                                <Col>
                                  <Form.Item name="fromTime" style={{ width: 160 }}>
                                    <TimePicker
                                      placeholder={'Start Time'}
                                      format={'HH:mm'}
                                      onSelect={(value) => {
                                        form.setFieldsValue({ fromTime: null });
                                        if (value != null) {
                                          setFromTime(value.format('HH:mm'));
                                          let fromTimeVal = value.format('HH:mm');
                                          form.setFieldsValue({
                                            fromTime: moment(fromTimeVal, 'HH:mm'),
                                          });
                                          setToTimeDisableState(false);

                                          if (toTime) {
                                            let tTime = moment(toTime, 'HH:mm');
                                            let fTime = moment(value.format('HH:mm'), 'HH:mm');
                                            let duration = tTime.diff(fTime);

                                            if (duration < 0) {
                                              form.setFields([
                                                {
                                                  name: 'fromTime',
                                                  errors: ['Should be before To Time'],
                                                },
                                              ]);
                                            } else {
                                              form.setFields([
                                                {
                                                  name: 'fromTime',
                                                  errors: [],
                                                },
                                                {
                                                  name: 'toTime',
                                                  errors: [],
                                                },
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
                                <Col style={{ paddingLeft: 0 }}>
                                  <Form.Item name="toTime">
                                    <TimePicker
                                      placeholder={'End Time'}
                                      format={'HH:mm'}
                                      onSelect={(value) => {
                                        form.setFieldsValue({ toTime: null });
                                        if (value != null) {
                                          setToTime(value.format('HH:mm'));
                                          let toTimeVal = value.format('HH:mm');
                                          form.setFieldsValue({
                                            toTime: moment(toTimeVal, 'HH:mm'),
                                          });
                                          if (fromTime) {
                                            let tTime = moment(value.format('HH:mm'), 'HH:mm');
                                            let fTime = moment(fromTime, 'HH:mm');

                                            let duration = tTime.diff(fTime);

                                            if (duration < 0) {
                                              form.setFields([
                                                {
                                                  name: 'toTime',
                                                  errors: ['Should be after From Time'],
                                                },
                                              ]);
                                            } else {
                                              form.setFields([
                                                {
                                                  name: 'fromTime',
                                                  errors: [],
                                                },
                                                {
                                                  name: 'toTime',
                                                  errors: [],
                                                },
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
                        <Row>
                          <Col span={16} style={{ paddingBottom: 10 }}>
                            {/* <Row><Col>Reason</Col></Row> */}
                            <Row>
                              <Col style={{ width: '100%' }}>
                                <Form.Item
                                  name="reason"
                                  style={{ width: '100%' }}
                                  label={<FormattedMessage id="reason" defaultMessage="Reason" />}
                                  rules={[
                                    { max: 250, message: 'Maximum length is 250 characters.' },
                                  ]}
                                >
                                  <TextArea
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
                        <Row style={{ marginTop: 12 }}>
                          <Col span={9}>
                            {canShowAttachement ? (
                              <Form.Item
                                name="upload"
                                label={
                                  <FormattedMessage
                                    id="attachDocument"
                                    defaultMessage="Attach Document"
                                  />
                                }
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
                                  <span style={{ paddingLeft: 8, color: '#AAAAAA' }}>
                                    {' '}
                                    JPG or PDF
                                  </span>
                                </Upload>
                                {fileFormatError && (
                                  <Row style={{ color: '#ff4d4f' }}>
                                    <Col>
                                      <FormattedMessage
                                        id="attachDocument"
                                        defaultMessage="File format should be JPG or PDF"
                                      />
                                    </Col>
                                  </Row>
                                )}
                              </Form.Item>
                            ) : (
                              <></>
                            )}
                          </Col>
                        </Row>

                        <Row>
                          <Col span={16}>
                            <Row justify="end">
                              <Popconfirm
                                key="reset"
                                title={intl.formatMessage({
                                  id: 'are_you_sure',
                                  defaultMessage: 'Are you sure?',
                                })}
                                onConfirm={() => {
                                  form.resetFields();
                                  setLeavePeriodType('FULL_DAY');
                                  setEntitlementCount([]);
                                  setShowCount(false);
                                  setSelectedLeaveTypeObject({
                                    fullDayAllowed: true,
                                    halfDayAllowed: true,
                                    shortLeaveAllowed: false,
                                  });
                                }}
                                okText="Yes"
                                cancelText="No"
                              >
                                <Button>
                                  {intl.formatMessage({
                                    id: 'reset',
                                    defaultMessage: 'Reset',
                                  })}
                                </Button>
                              </Popconfirm>
                              <Button
                                type="primary"
                                onClick={applyLeave}
                                style={{ marginLeft: 15 }}
                              >
                                {intl.formatMessage({
                                  id: 'SUBMIT',
                                  defaultMessage: 'Assign',
                                })}
                              </Button>
                            </Row>
                          </Col>
                        </Row>
                      </Form>
                    </Card>
                  </Col>
                  <Col
                    style={{ width: 440 }}
                    xs={{ order: 1 }}
                    sm={{ order: 1 }}
                    md={{ order: 1 }}
                    lg={{ order: 1 }}
                    xl={{ order: 2 }}
                    xxl={{ order: 2 }}
                  >
                    {entitlementCount.length > 0 ? (
                      <Row justify="center" style={{ height: 150, marginBottom: 16 }}>
                        <Col span={2}>
                          <Row align="middle" justify="start" style={{ height: 150 }}>
                            <LeftOutlined
                              color="#626D6C"
                              onClick={() => {
                                slider.current.next();
                              }}
                            />
                          </Row>
                        </Col>
                        <Col span={20}>
                          <Carousel
                            {...settings}
                            ref={(ref) => {
                              slider.current = ref;
                            }}
                            style={{ height: 150, top: 0, left: 0 }}
                          >
                            {entitlementCount.map((entitlement, index) => (
                              <div id={`${index}`}>
                                <div
                                  style={{
                                    width: 154,
                                    height: 145,
                                    backgroundColor: 'white',
                                    borderRadius: 10,
                                  }}
                                >
                                  <div
                                    style={{
                                      background: entitlement.leaveTypeColor,
                                      height: 10,
                                      width: 154,
                                      borderTopLeftRadius: 6,
                                      borderTopRightRadius: 6,
                                      top: 0,
                                      left: 0,
                                    }}
                                  />
                                  <div
                                    style={{
                                      paddingTop: 4,
                                      paddingLeft: 12,
                                      paddingRight: 12,
                                      paddingBottom: 12,
                                    }}
                                  >
                                    <Row
                                      justify="center"
                                      align="top"
                                      style={{
                                        height: 32,
                                        marginTop: 0,
                                        marginBottom: 6,
                                        textAlign: 'center',
                                      }}
                                    >
                                      <span className="card-widget-title">{entitlement.name}</span>
                                    </Row>
                                    <Row
                                      justify="center"
                                      align="middle"
                                      style={{ paddingBottom: 0 }}
                                    >
                                      <Col className="card-available">
                                        {entitlement.total -
                                          (entitlement.used + entitlement.pending)}
                                      </Col>
                                    </Row>
                                    <Row justify="center" style={{ marginTop: 0 }}>
                                      <span className="card-available-title">Available</span>
                                    </Row>
                                    <Row
                                      style={{ height: 30, marginTop: 8 }}
                                      justify="space-between"
                                    >
                                      <Col span={12} className={'card-taken-txt'}>
                                        Taken &nbsp; {entitlement.used}
                                      </Col>
                                      <Col span={12} className={'card-used-txt'}>
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
                          <Row align="middle" justify="end" style={{ height: 150 }}>
                            <RightOutlined
                              color="#626D6C"
                              onClick={() => {
                                slider.current.prev();
                              }}
                            />
                          </Row>
                        </Col>
                      </Row>
                    ) : (
                      <></>
                    )}
                  </Col>
                </Row>
              </ProCard>
            </PageContainer>
          </div>
        </Access>
      </>
    );
};

export default ApplyLeave;