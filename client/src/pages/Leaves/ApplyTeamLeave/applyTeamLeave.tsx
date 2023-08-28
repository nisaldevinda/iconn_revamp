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
  Empty,
  Form,
  Popconfirm,
  Typography,
  Select,
  Space,
  DatePicker,
  Carousel,
} from 'antd';
import { ModalForm } from '@ant-design/pro-form';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import PermissionDeniedPage from '../../403';
import { Access, useAccess, useIntl,  } from 'umi';
import {  ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import { FieldTimeOutlined,UploadOutlined } from '@ant-design/icons';
import {
  addTeamLeave,
  getLeaveTypesForAdminApplyLeaveForEmployee,
  getEmployeeEntitlementCount,
  calculateWorkingDaysCountForLeave,
  getShiftData,
  getEntitlementCountByEmployeeId,
} from '@/services/leave';
import { getBase64 } from '@/utils/fileStore';
import '../style.css';
import PendingIcon from '../../../assets/leave/icon-circle-orange.svg';
import UsedIcon from '../../../assets/leave/icon-circle-green.svg';
import BalanceIcon from '../../../assets/leave/icon-circle-grey.svg';
import _ from 'lodash';
import { getEmployeeList } from '@/services/dropdown';
import LeaveHistoryList from './employeeLeaveHistoryList';
import { Pie } from '@ant-design/charts';

const ApplyLeaveForEmployees: React.FC = () => {

  const access = useAccess();
  const { hasPermitted } = access;
  const [shiftData, setShiftData] = useState<any>(null);
  const [fromDate, setFromDate] = useState<Date | null>(null);
  const [disableButton, setDisableButton] = useState<boolean>(false);
  const [toDate, setToDate] = useState<Date | null>(null);
  const [fromTime, setFromTime] = useState<string | null>(null);
  const [toTime, setToTime] = useState<string | null>(null);
  const [fromDateRadioVal, setFromDateRadioVal] = useState<any | null>(1);
  const [toDateRadioVal, setToDateRadioVal] = useState<any | null>(1);
  const [fromDateLeavePeriodType, setFromDateLeavePeriodType] = useState<string | null>('FULL_DAY');
  const [toDateleavePeriodType, setToDateLeavePeriodType] = useState<string | null>('FULL_DAY');
  const [selectedleaveType, setSelectedLeaveType] = useState<string | null>(null);
  const [selectedleaveTypeObject, setSelectedLeaveTypeObject] = useState({
    fullDayAllowed: true,
    halfDayAllowed: true,
    shortLeaveAllowed: false,
  });
  const [currentLeaveAllocationDetail, setCurrentLeaveAllocationDetail] = useState({});
  const [toTimeDisableState, setToTimeDisableState] = useState<boolean>(true);
  const [isAllowShowFromDateRowOnly, setIsAllowShowFromDateRowOnly] = useState<boolean>(false);
  const [isAttachementMandatory, setIsAttachementMandatory] = useState<boolean>(false);
  const [canShowAttachement, setCanShowAttachement] = useState<boolean>(true);
  const [leaveReason, setLeaveReason] = useState<string | null>(null);
  const [attachmentList, setAttachmentList] = useState<any>([]);
  const [shortLeaveDuration, setShortLeaveDuration] = useState<any>(null);
  const [leaveTypes, setLeaveTypes] = useState<Array<any>>([]);
  const [workingDaysCount, setWorkingDaysCount] = useState<String | null>('0');
  const [selectedEmployee, setSelectedEmployee] = useState<string | null>(null);
  const [relatedLeaveTypes, setRelatedLeaveTypes] = useState([]);
  const [relatedEmployees, setRelatedEmployees] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [entitlementCount, setEntitlementCount] = useState<Array<any>>([]);
  const [fileFormatError, setfileFormatError] = useState(false);
  const [listModalVisible, handleListModalVisible] = useState<boolean>(false);
  const [form] = Form.useForm();
  const intl = useIntl();
  const [leaveTypesArr, setLeaveTypesArr] = useState([]);
  const { Title, Paragraph, Text } = Typography;
  const [showCount, setShowCount] = useState(false);
  const slider = useRef();
  const [isEmpSelected, setIsEmpSelected] = useState<boolean>(true);

  useEffect(() => {
    getRelatedEmployees();
  }, []);

  useEffect(() => {
    calculateWorkingDaysCount();
  }, [selectedleaveType, fromDate, toDate, fromDateLeavePeriodType, toDateleavePeriodType]);

  useEffect(() => {
    if (fromDateLeavePeriodType == 'FIRST_HALF_DAY') {
      changeFromDateLeavePeriod(1);
    }
  }, [toDate]);

  const showListModal = async (event) => {
    await handleListModalVisible(true);
  };

  useEffect(() => {
    if (
      (fromDateLeavePeriodType == 'IN_SHORT_LEAVE' ||
        fromDateLeavePeriodType == 'OUT_SHORT_LEAVE') &&
      fromDate
    ) {
      getShiftDataForLeaveDate(fromDateLeavePeriodType);
    }
  }, [fromDate]);

  const getRelatedEmployees = async () => {
    try {
      const employees: any = [];

      const { data } = await getEmployeeList('ADMIN');
      const subordinateEmployees = data.map((employee: any) => {
        employees.push({ value: employee.id, label: employee.employeeNumber+' | '+employee.employeeName });
        return {
          label: employee.employeeNumber+' | '+employee.employeeName,
          value: employee.id,
        };
      });
    
      setRelatedEmployees(employees);
      return res;
    } catch (err) {
      console.log(err);
      return [];
    }

    // const res = await request(path, { params });
    // await res.data.forEach(async (element: any) => {
    //   let fullName = element['firstName'] + ' ' + element['lastName'];
    //   await actions.push({ value: element['id'], label: fullName });
    // });
    // setRelatedEmployees(actions);
  };
  const getShiftDataForLeaveDate = async (periodType: any) => {
    const res = await getShiftData(fromDate, toDate);
    setShiftData(res.data.shift);

    form.setFieldsValue({ fromTime: null, toTime: null });
    if (periodType == 'IN_SHORT_LEAVE' && res.data.shift != null && fromDate) {
      let time = moment(res.data.shift.startTime, 'HH:mm')
        .add(shortLeaveDuration, 'minutes')
        .format('HH:mm');

      let toTime = shortLeaveDuration ? moment(time, 'HH:mm') : null;

      form.setFieldsValue({ fromTime: moment(res.data.shift.startTime, 'HH:mm'), toTime: toTime });
      setToTime(time);
      setFromTime(res.data.shift.startTime);
    }

    if (periodType == 'OUT_SHORT_LEAVE' && res.data.shift != null && fromDate) {
      let time = moment(res.data.shift.endTime, 'HH:mm')
        .subtract(shortLeaveDuration, 'minutes')
        .format('HH:mm');
      let fromTime = shortLeaveDuration ? moment(time, 'HH:mm') : null;

      form.setFieldsValue({ toTime: moment(res.data.shift.endTime, 'HH:mm'), fromTime: fromTime });
      setToTime(res.data.shift.endTime);
      setFromTime(time);
    }
  };

  useEffect(() => {
    if (workingDaysCount != null && Number(workingDaysCount) === 0) {
      form.setFields([
        {
          name: 'date',
          errors: ['No Working days for the selected date range'],
        },
      ]);
      return;
    }
  }, [workingDaysCount]);

  const config = {
    width: 130,
    padding: 130,
    height: 130,
    angleField: 'value',
    colorField: 'type',
    radius: 0.6,
    renderer: 'svg',
    innerRadius: 0.7,
    label: {
      type: 'inner',
      offset: '-50%',
      content: '{value}',
      style: {
        textAlign: 'center',
        fontSize: 3,
      },
    },
    color: ['#74b425', '#FFB039', '#e3e7ed' ,'#FF4D4F'],
    legend: false,
  };

  const individualChartConfig = {
    autoFit: true,
    angleField: 'value',
    colorField: 'type',
    radius: 0.6,
    renderer: 'svg',
    innerRadius: 0.7,
    label: {
      type: 'inner',
      offset: '-50%',
      content: '{value}',
      style: {
        textAlign: 'center',
        fontSize: 0,
      },
    },
    color: ['#74b425', '#FFB039', '#e3e7ed' , '#FF4D4F'],
    legend: false,
  };

  const calculateWorkingDaysCount = async () => {
    try {
      if (selectedleaveType && fromDate && toDate) {
        const res = await calculateWorkingDaysCountForLeave(selectedleaveType, fromDate, toDate, selectedEmployee);
  
        var leaveCount = Number(res.data.count);
        if (
          fromDateLeavePeriodType != 'FULL_DAY' &&
          fromDateLeavePeriodType != 'IN_SHORT_LEAVE' &&
          fromDateLeavePeriodType != 'OUT_SHORT_LEAVE'
        ) {
          leaveCount = leaveCount > 0 ? leaveCount - 0.5 : 0;
        }
  
        if (
          toDateleavePeriodType != 'FULL_DAY' &&
          toDateleavePeriodType != 'IN_SHORT_LEAVE' &&
          toDateleavePeriodType != 'OUT_SHORT_LEAVE'
        ) {
          leaveCount = leaveCount > 0 ? leaveCount - 0.5 : 0;
        }
  
        setWorkingDaysCount(leaveCount.toString());
      } else {
        setWorkingDaysCount('0');
      }
      
    } catch (error) {
      console.log('error:', error);
    }
  };

  const uploaderProps = {
    beforeUpload: (file) => {
      const isValidFormat = file.type === 'image/jpeg' || file.type === 'application/pdf';
      if (!isValidFormat) {
        message.error('File format should be JPG or PDF');
      }
      return isValidFormat || Upload.LIST_IGNORE;
    },
    onChange({ file, fileList }) {
      if (file.status !== 'uploading') {
        form.setFieldsValue({ upload: fileList });
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


  const applyLeave = async () => {
    try {
      setDisableButton(true);
      if (workingDaysCount != null && Number(workingDaysCount) === 0) {
        message.error('No any working days within the selected date range');
        setDisableButton(false);
        return;
      }

      if (fileFormatError) {
        message.error('File format should be JPG or PDF');
        setDisableButton(false);
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
        if (
          fromDateLeavePeriodType != 'FULL_DAY' &&
          fromDateLeavePeriodType != 'IN_SHORT_LEAVE' &&
          fromDateLeavePeriodType != 'OUT_SHORT_LEAVE'
        ) {
          calcWorkingDays = 0.5;
        } else {
          if (
            fromDateLeavePeriodType != 'IN_SHORT_LEAVE' &&
            fromDateLeavePeriodType != 'OUT_SHORT_LEAVE'
          ) {
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
            let msg = 'Short leave duration cannot exceed ' + hrs + ' hours.';
            setDisableButton(false);
            return message.error(msg);
          }
        }

        const params: any = {
          leaveTypeId: selectedleaveType,
          leavePeriodType: fromDateLeavePeriodType,
          fromDateLeavePeriodType: fromDateLeavePeriodType,
          toDateleavePeriodType: isAllowShowFromDateRowOnly ? null : toDateleavePeriodType,
          fromDate: fromDate,
          toDate: toDate,
          fromTime: fromTime,
          toTime: toTime,
          reason: leaveReason,
          numberOfLeaveDates: calcWorkingDays.toString(),
          attachmentList: selectedAttachment,
          employeeId: selectedEmployee,
          isGoThroughWf: true,
        };

        const result = await addTeamLeave(params);
        setDisableButton(false);
        message.success(result.message);
  
      }
    } catch (error) {
      setDisableButton(false);
      if (!_.isEmpty(error)) {
        let errorMessage;
        let errorMessageInfo;

        if (error.message && error.message.includes('.')) {
          let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        if (error.message) {
          message.error({
            content: error.message ? (
              <>
                {errorMessage ?? error.message}
                <br />
                <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                  {errorMessageInfo ?? ''}
                </span>
              </>
            ) : (
              <></>
            ),
          });
        }
      }
    }
  };

  const getLeaveTypes = async (empId: any) => {

    try {
      const actions: any = [];
      if (empId != undefined) {
        setIsEmpSelected(false);
        const res = await getLeaveTypesForAdminApplyLeaveForEmployee(empId);

        if (!_.isEmpty(res.data)) {
          setLeaveTypes(res.data);
        }

        res.data.forEach(async (element: any) => {
          actions.push({ value: element['id'], label: element['name'] });
        });
      } else {
        setIsEmpSelected(true);
      }
      setLeaveTypesArr(actions);
      
    } catch (error) {
      message.error(error.message);
    }
    
  };

  const changeFromDateLeavePeriod = (event) => {
    form.setFieldsValue({ fromTime: null, toTime: null, date: null });
    setFromDateRadioVal(event);
    switch (event) {
      case 1:
        form.setFieldsValue({ date: null, fromDateLeavePeriodType: 1 });
        setFromDateLeavePeriodType('FULL_DAY');
        break;
      case 2:
        setFromDateLeavePeriodType('FIRST_HALF_DAY');
        break;
      case 3:
        setFromDateLeavePeriodType('SECOND_HALF_DAY');
        break;
      case 4:
        setFromDateLeavePeriodType('IN_SHORT_LEAVE');
        if (fromDate) {
          getShiftDataForLeaveDate('IN_SHORT_LEAVE');
        }
        break;
      case 5:
        setFromDateLeavePeriodType('OUT_SHORT_LEAVE');
        if (fromDate) {
          getShiftDataForLeaveDate('OUT_SHORT_LEAVE');
        }
        break;
      default:
        break;
    }
  };

  const changeToDateLeavePeriod = (event) => {
    form.setFieldsValue({ fromTime: null, toTime: null, date: null });
    setToDateRadioVal(event);
    switch (event) {
      case 1:
        form.setFieldsValue({ date: null });
        setToDateLeavePeriodType('FULL_DAY');
        break;
      case 2:
        setToDateLeavePeriodType('FIRST_HALF_DAY');
        break;
      case 3:
        setToDateLeavePeriodType('SECOND_HALF_DAY');
        break;
      case 4:
        setToDateLeavePeriodType('IN_SHORT_LEAVE');
        if (fromDate) {
          getShiftDataForLeaveDate('IN_SHORT_LEAVE');
        }
        break;
      case 5:
        setToDateLeavePeriodType('OUT_SHORT_LEAVE');
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
    fromDateLeavePeriodType: 1,
    toDateLeavePeriodType: 1,
    fromTime: null,
    toTime: null,
    reason: '',
  };


  const disabledToDates = (current) => {
    return fromDate ? current < moment(fromDate) : null;
  };
  const disabledFromDates = (current) => {
    return toDate ? current > moment(toDate) : null;
  };

  const getStats = (entitlement: any) => {
    let used = entitlement.exceeding ? ( entitlement.exceeding + entitlement.used ) : entitlement.used;
    
    let contentString = used.toString() + '/' + entitlement.total.toString();
    return {
      title: false,
      content: {
        style: {
          whiteSpace: 'pre-wrap',
          overflow: 'hidden',
          fontSize: 13,
          fontWeight: 100,
          textOverflow: 'ellipsis',
        },
        content: contentString,
      },
    };
  };

  const getIndividualStats = (entitlement: any) => {
    let used = currentLeaveAllocationDetail.exceeding ? ( currentLeaveAllocationDetail.exceeding + currentLeaveAllocationDetail.used ) : currentLeaveAllocationDetail.used;

    let contentString =
      used.toString() +
      '/' +
      currentLeaveAllocationDetail.total.toString();
    return {
      title: false,
      content: {
        style: {
          whiteSpace: 'pre-wrap',
          overflow: 'hidden',
          fontSize: 28,
          fontWeight: 100,
          textOverflow: 'ellipsis',
        },
        content: contentString,
      },
    };
  };

  const getDataForIndividualChart = () => {
    if (currentLeaveAllocationDetail === undefined) {
      return [];
    }
    return [
      {
        type: 'Used',
        value: currentLeaveAllocationDetail ? currentLeaveAllocationDetail.used : 0,
      },
      {
        type: 'Pending',
        value: currentLeaveAllocationDetail ? currentLeaveAllocationDetail.pending : 0,
      },
      {
        type: 'Availble',
        value: currentLeaveAllocationDetail
          ? currentLeaveAllocationDetail.total -
            (currentLeaveAllocationDetail.used + currentLeaveAllocationDetail.pending)
          : 0,
      },
      {
        type:'Exceeded Leaves',
        value: currentLeaveAllocationDetail ?  currentLeaveAllocationDetail.exceeding : 0
      }
    ];
  };

  return (
    <>
      <Access
        accessible={hasPermitted('apply-team-leave')}
        fallback={<PermissionDeniedPage />}
      >
        <div>
          {/* <PageContainer
            header={{
              ghost: true,
            }}
          > */}
          <ProCard
            direction="column"
            ghost
            gutter={[0, 16]}
            style={{ padding: 0, margin: 0, height: '100%' }}
          >
            <Row style={{ width: '100%' }} gutter={16}>
              <Col span={16}>
                <Card
                  title={intl.formatMessage({
                    id: 'leaveTitle',
                    defaultMessage: 'Leave',
                  })}
                  extra={
                    <Button
                      type="primary"
                      danger={true}
                      style={{ backgroundColor: '#FFA500', borderColor: '#FFA500' }}
                      icon={<FieldTimeOutlined />}
                      disabled={!selectedEmployee}
                      onClick={showListModal}
                    >
                      {intl.formatMessage({
                        id: 'leaveHistory',
                        defaultMessage: 'Leave History',
                      })}
                    </Button>
                  }
                >
                  <Form form={form} layout="vertical" initialValues={initValues}>
                    <Row style={{ marginLeft: 12, paddingBottom: 0, marginBottom: 0 }}>
                     <Col span={9}>
                        <Form.Item
                          name="employeeId"
                          label={intl.formatMessage({
                            id: 'employee',
                            defaultMessage: 'Employee',
                          })}
                          style={{ marginBottom: 16, width: '80%' }}
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
                            fieldProps={{
                              optionItemRender(item) {
                                return item.label;
                              },
                              onChange: async (value) => {
                                form.setFieldsValue({ leaveType: null, date: null });
                                setShowCount(false);
                                if (value) {
                                  let params = { employee: value, 'access-level': 'Apply-Leave' };
                                  const res = await getEntitlementCountByEmployeeId(params);
                                  setEntitlementCount(res.data);

                                  setSelectedEmployee(value);
                                  getLeaveTypes(value);
                                } else {
                                  setRelatedLeaveTypes([]);
                                  setSelectedLeaveType(null);
                                  setSelectedEmployee(null);
                                  setShowCount(false);
                                }
                              },
                            }}
                            options={relatedEmployees}
                            placeholder={intl.formatMessage({
                              id: 'employee.placeholder',
                              defaultMessage: 'Select Employee'
                            })}
                            style={{ marginBottom: 0 }}
                          />
                          </Form.Item>
                      </Col> 
                      <Col span={9} >
                        <Form.Item
                          name="leaveType"
                          label= {intl.formatMessage({
                            id: 'leave.leaveType',
                            defaultMessage:'Leave Type',
                          })}
                          style={{ marginBottom: 16, width: '80%' }}
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
                            disabled={isEmpSelected}
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
                                });
                                form.setFieldsValue({
                                  fromDateLeavePeriodType: 1,
                                  toDateleavePeriodType: 1,
                                  fromDate: null,
                                  toDate: null,
                                });
                                setFromDateRadioVal(1);
                                setToDateRadioVal(1);
                                setIsAllowShowFromDateRowOnly(false);
                                setFromDate(null);
                                setToDate(null);
                                setSelectedLeaveType(null);
                                setCurrentLeaveAllocationDetail({});

                                return;
                              }

                              setFromDate(null);
                              setToDate(null);
                              form.setFieldsValue({ fromDate: null, toDate: null });

                              const leaveTypeObject = leaveTypes.find(
                                (leaveType) => leaveType.id == value,
                              );

                              if (
                                !leaveTypeObject.fullDayAllowed &&
                                leaveTypeObject.halfDayAllowed
                              ) {
                                setIsAllowShowFromDateRowOnly(true);
                                form.setFieldsValue({ fromDateLeavePeriodType: 2 });
                              } else {
                                setIsAllowShowFromDateRowOnly(false);
                                form.setFieldsValue({ fromDateLeavePeriodType: 1 });
                              }

                              if (leaveTypeObject.shortLeaveAllowed) {
                                setIsAllowShowFromDateRowOnly(true);
                                setShortLeaveDuration(leaveTypeObject.short_leave_duration);
                                form.setFieldsValue({ fromDateLeavePeriodType: 4 });
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
                                  changeFromDateLeavePeriod(1);
                                  changeToDateLeavePeriod(1);
                                }

                                if (
                                  leaveTypeObject.halfDayAllowed &&
                                  !leaveTypeObject.fullDayAllowed
                                ) {
                                  // changeLeavePeriod(2);
                                  changeFromDateLeavePeriod(2);
                                  changeToDateLeavePeriod(2);
                                }
                              } else {
                                changeFromDateLeavePeriod(4);
                                changeToDateLeavePeriod(4);
                              }

                              let entitlementAllocation = entitlementCount.find(
                                (entitlement) => entitlement.leaveTypeID === value,
                              );

                              setCurrentLeaveAllocationDetail(entitlementAllocation);

                              setSelectedLeaveType(value);
                              setSelectedLeaveTypeObject(
                                leaveTypes.find((leaveType) => leaveType.id == value),
                              );
                            }}
                            options={leaveTypesArr}
                          />
                        </Form.Item>
                      </Col>
                    </Row>
                    <Row style={{ marginLeft: 12 }} justify="start">
                      <Col span={16}>
                        <Row>
                          <Col md={{ span: 10 }} lg={{ span: 7 }} xl={{ span: 7 }}>
                            <Form.Item
                              style={{ marginBottom: 16 }}
                              name="fromDate"
                              label={isAllowShowFromDateRowOnly ? intl.formatMessage({
                                id: 'date',
                                defaultMessage: 'Date',
                              }) : intl.formatMessage({
                                id: 'fromDate',
                                defaultMessage: 'From Date',
                              })}
                              rules={[
                                {
                                  required: true,
                                  message: 'Required',
                                },
                              ]}
                            >
                              <DatePicker
                                name="fromDate"
                                style={{ width: '100%' }}
                                disabledDate={disabledFromDates}
                                format={'DD-MM-YYYY'}
                                onChange={(value) => {
                                  if (value != null) {
                                    setShowCount(true);
                                    let date =
                                      !_.isNull(value) && !_.isUndefined(value)
                                        ? value.format('YYYY-MM-DD')
                                        : null;
                                    setFromDate(date);

                                    if (isAllowShowFromDateRowOnly) {
                                      setToDate(date);
                                    }
                                  } else {
                                    setShowCount(false);
                                    setFromDate(null);
                                    if (!isAllowShowFromDateRowOnly) {
                                      setToDate(null);
                                    }
                                    form.setFieldsValue({ toDate: null, fromDate: null });
                                  }
                                }}
                              />
                            </Form.Item>
                          </Col>
                          <Col
                            lg={{ span: 16, offset: 1 }}
                            xl={{ span: 15 }}
                            style={{ marginTop: 30 }}
                          >
                            <Form.Item
                              style={{ marginBottom: 12 }}
                              name="fromDateLeavePeriodType"
                              label={''}
                            >
                              <Radio.Group
                                onChange={(event) => changeFromDateLeavePeriod(event.target.value)}
                                value={fromDateRadioVal}
                                style={{ display: 'flex' }}
                              >
                                {selectedleaveTypeObject?.fullDayAllowed ? (
                                  <Radio value={1}>{
                                    intl.formatMessage({
                                      id: 'fullDay',
                                      defaultMessage: 'Full Day',
                                    })
                                  }</Radio>
                                ) : null}
                                {selectedleaveTypeObject?.halfDayAllowed ? (
                                  <Radio
                                    disabled={
                                      isAllowShowFromDateRowOnly || !(Number(workingDaysCount) > 1)
                                        ? false
                                        : true
                                    }
                                    value={2}
                                  >
                                    {
                                      intl.formatMessage({
                                        id: 'firstHalf',
                                        defaultMessage: 'First Half',
                                      })
                                    }
                                  </Radio>
                                ) : null}
                                {selectedleaveTypeObject?.halfDayAllowed ? (
                                  <Radio value={3}>{
                                    intl.formatMessage({
                                      id: 'secondHalf',
                                      defaultMessage: 'Second Half',
                                    })
                                  }</Radio>
                                ) : null}
                                {selectedleaveTypeObject?.shortLeaveAllowed ? (
                                  <Radio value={4}>{
                                    intl.formatMessage({
                                      id: 'inShortLeave',
                                      defaultMessage: 'In Short Leave',
                                    })
                                  }</Radio>
                                ) : null}
                                {selectedleaveTypeObject?.shortLeaveAllowed ? (
                                  <Radio value={5}>{
                                    intl.formatMessage({
                                      id: 'outShortLeave',
                                      defaultMessage: 'Out Short Leave',
                                    })
                                  }</Radio>
                                ) : null}
                              </Radio.Group>
                            </Form.Item>
                          </Col>
                        </Row>
                        {!isAllowShowFromDateRowOnly ? (
                          <Row>
                            <Col md={{ span: 10 }} lg={{ span: 7 }} xl={{ span: 7 }}>
                              <Form.Item
                                style={{ marginBottom: 16 }}
                                name="toDate"
                                label="To Date"
                                rules={[
                                  {
                                    required: true,
                                    message: 'Required',
                                  },
                                ]}
                              >
                                <DatePicker
                                  name="toDate"
                                  disabled={!fromDate}
                                  disabledDate={disabledToDates}
                                  style={{ width: '100%' }}
                                  format={'DD-MM-YYYY'}
                                  onChange={(value) => {
                                    if (value != null) {
                                      setShowCount(true);
                                      let date =
                                        !_.isNull(value) && !_.isUndefined(value)
                                          ? value.format('YYYY-MM-DD')
                                          : null;
                                      setToDate(date);
                                    } else {
                                      setShowCount(false);
                                      // setFromDate(null);
                                      setToDate(null);
                                    }
                                  }}
                                />
                              </Form.Item>
                            </Col>
                            <Col
                              lg={{ span: 16, offset: 1 }}
                              xl={{ span: 15 }}
                              style={{ marginTop: 30 }}
                            >
                              {Number(workingDaysCount) > 1 ? (
                                <Form.Item
                                  style={{ marginBottom: 12 }}
                                  name="toDateLeavePeriodType"
                                  label={''}
                                >
                                  <Radio.Group
                                    onChange={(event) =>
                                      changeToDateLeavePeriod(event.target.value)
                                    }
                                    value={toDateRadioVal}
                                    style={{ display: 'flex' }}
                                  >
                                    {selectedleaveTypeObject?.fullDayAllowed ? (
                                      <Radio value={1}>{
                                        intl.formatMessage({
                                          id: 'fullDay',
                                          defaultMessage: 'Full Day',
                                        })
                                      }</Radio>
                                    ) : null}
                                    {selectedleaveTypeObject?.halfDayAllowed ? (
                                      <Radio value={2}>{
                                        intl.formatMessage({
                                          id: 'firstHalf',
                                          defaultMessage: 'First Half',
                                        })
                                      }</Radio>
                                    ) : null}
                                    {selectedleaveTypeObject?.halfDayAllowed ? (
                                      <Radio
                                        disabled={isAllowShowFromDateRowOnly ? false : true}
                                        value={3}
                                      >
                                        {
                                          intl.formatMessage({
                                            id: 'secondHalf',
                                            defaultMessage: 'Second Half',
                                          })
                                        }
                                      </Radio>
                                    ) : null}
                                    {selectedleaveTypeObject?.shortLeaveAllowed ? (
                                      <Radio value={4}>{
                                        intl.formatMessage({
                                          id: 'inShortLeave',
                                          defaultMessage: 'In Short Leave',
                                        })
                                      }</Radio>
                                    ) : null}
                                    {selectedleaveTypeObject?.shortLeaveAllowed ? (
                                      <Radio value={5}>{
                                        intl.formatMessage({
                                          id: 'outShortLeave',
                                          defaultMessage: 'Out Short Leave',
                                        })
                                      }</Radio>
                                    ) : null}
                                  </Radio.Group>
                                </Form.Item>
                              ) : (
                                <></>
                              )}
                            </Col>
                          </Row>
                        ) : (
                          <Row>
                            {fromDateLeavePeriodType === 'IN_SHORT_LEAVE' ||
                            fromDateLeavePeriodType === 'OUT_SHORT_LEAVE' ? (
                              <Col span={18} style={{ width: '100%' }}>
                                <Row>
                                  <Col style={{ paddingBottom: 8, color: '#626D6C' }}>
                                    <FormattedMessage
                                      id="timePeriod"
                                      defaultMessage="Time Period"
                                    />
                                  </Col>
                                </Row>
                                <Row>
                                  <Col>
                                    <Form.Item
                                      style={{ marginBottom: 16, width: 150 }}
                                      name="fromTime"
                                      // label={<FormattedMessage id="period" defaultMessage="Period" />}
                                    >
                                      <TimePicker
                                        placeholder={'Start Time'}
                                        style={{ width: '100%' }}
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
                                  <Col style={{ paddingLeft: 16 }}>
                                    <Form.Item
                                      style={{ marginBottom: 16, width: 150 }}
                                      name="toTime"
                                    >
                                      <TimePicker
                                        placeholder={'End Time'}
                                        format={'HH:mm'}
                                        style={{ width: '100%' }}
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
                        )}
                      </Col>
                      <Col span={3}>
                        {fromDateLeavePeriodType !== 'IN_SHORT_LEAVE' &&
                        fromDateLeavePeriodType !== 'OUT_SHORT_LEAVE' ? (
                          <Row
                            style={{
                              backgroundColor: '#f2fced',
                              width: 140,
                              height: 130,
                              marginTop: 30,
                              borderRadius: 6,
                            }}
                          >
                            <div style={{ marginTop: 28, marginLeft: 20 }}>
                              <Text style={{ color: '#626D6C', fontSize: 14 }}>{
                                    intl.formatMessage({
                                      id: 'leaveDays',
                                      defaultMessage: 'Leave days',
                                    })
                                  }</Text>
                              <br></br>
                              <Text
                                style={{
                                  fontWeight: 400,
                                  color: '#74b425',
                                  fontSize: 26,
                                }}
                              >
                                {workingDaysCount}{' '}
                                {Number(workingDaysCount) > 1 || Number(workingDaysCount) == 0
                                  ?  intl.formatMessage({
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
                        ) : (
                          <></>
                        )}
                      </Col>
                    </Row>
                    <Row style={{ marginLeft: 12 }}>
                      <Col span={13} style={{}}>
                        {/* <Row><Col><FormattedMessage id="reason" defaultMessage="Reason" /></Col></Row> */}
                        <Row>
                          <Col style={{ width: '100%' }}>
                            <Form.Item
                              style={{ marginBottom: 16 }}
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
                                placeholder={intl.formatMessage({
                                  id: 'leave.comment',
                                  defaultMessage :'Type here'
                                })}
                              />
                            </Form.Item>
                          </Col>
                        </Row>
                      </Col>
                    </Row>
                    <Row style={{ marginLeft: 12, marginBottom: 12 }}>
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
                                {
                                  intl.formatMessage({
                                    id: 'upload',
                                    defaultMessage: 'Upload',
                                  })
                                }
                              </Button>
                              <span style={{ paddingLeft: 8, color: '#AAAAAA' }}>{intl.formatMessage({
                                    id: 'jpgOrPdf',
                                    defaultMessage: 'JPG or PDF',
                                  })}</span>
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
                      <Col span={24}>
                        <Row justify="end">
                          <Popconfirm
                            key="reset"
                            title={intl.formatMessage({
                              id: 'are_you_sure',
                              defaultMessage: 'Are you sure?',
                            })}
                            onConfirm={() => {
                              setWorkingDaysCount('0');
                              setFromDate(null);
                              setToDate(null);
                              form.resetFields();
                              setAttachmentList([]);
                              setFromDateLeavePeriodType('FULL_DAY');
                              setSelectedLeaveTypeObject({
                                fullDayAllowed: true,
                                halfDayAllowed: true,
                                shortLeaveAllowed: false,
                              });
                              setShowCount(false);
                            }}
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
                            disabled={disableButton}
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
              <Col span={8}>
                <Row className="entitlmentSummery" style={{ height: 150, marginBottom: 16 }}>
                  <Card
                    style={{ width: '100%' }}
                    title={intl.formatMessage({
                      id: 'leaveAllocation',
                      defaultMessage: 'Leave Allocation',
                    })}
                    extra={
                      <>
                        <Space className={'dayType'}>
                          <p className={'dayTypeIcon'}>
                            <Image src={UsedIcon} preview={false} height={15} />
                          </p>
                          <p className={'dayTypeContent'}>{intl.formatMessage({
                              id: 'used',
                              defaultMessage: 'Used',
                            })}</p>
                        </Space>
                        <Space className={'dayType'}>
                          <p className={'dayTypeIcon'}>
                            <Image src={PendingIcon} preview={false} height={15} />
                          </p>
                          <p className={'dayTypeContent'}>{intl.formatMessage({
                              id: 'pending',
                              defaultMessage: 'Pending',
                            })}</p>
                        </Space>
                        <Space className={'dayType'}>
                          <p className={'dayTypeIcon'}>
                            <Image src={BalanceIcon} preview={false} height={15} />
                          </p>
                          <p className={'dayTypeContent'}>{intl.formatMessage({
                              id: 'balanceAvailable',
                              defaultMessage: 'Balance/Available',
                            })}</p>
                        </Space>
                      </>
                    }
                  >
                    {selectedleaveType && currentLeaveAllocationDetail ? (
                      <div className="single-chart-div">
                        <div className="single-chart-section">
                          <div className="single-chart-header" />
                          <Row>
                            <Col className="single-chart-title-section">
                              <span className="individual-chart-widget-title">
                                {currentLeaveAllocationDetail.name}
                              </span>
                            </Col>
                          </Row>
                          <Row>
                            <Col className="single-chart-details-section">
                              <Row>
                                <Col className="single-chart-details-section-col">{intl.formatMessage({
                                  id: 'allocated',
                                  defaultMessage: 'Allocated',
                                })}</Col>
                                <Col className="single-chart-details-section-col">{intl.formatMessage({
                                  id: 'used',
                                  defaultMessage: 'Used',
                                })}</Col>
                                <Col className="single-chart-details-section-col">{intl.formatMessage({
                                  id: 'pending',
                                  defaultMessage: 'Pending',
                                })}</Col>
                                <Col className="single-chart-details-section-col">{intl.formatMessage({
                                  id: 'balance',
                                  defaultMessage: 'Balance',
                                })}</Col>
                              </Row>
                              <Row style={{ paddingTop: 10 }}>
                                <Col className="single-chart-details-section-col">
                                  {currentLeaveAllocationDetail.total}
                                </Col>
                                <Col className="single-chart-details-section-col">
                                  {currentLeaveAllocationDetail.used}
                                </Col>
                                <Col className="single-chart-details-section-col">
                                  {currentLeaveAllocationDetail.pending}
                                </Col>
                                <Col className="single-chart-details-section-col">
                                    {(
                                      currentLeaveAllocationDetail.total -
                                      (currentLeaveAllocationDetail.used +
                                      currentLeaveAllocationDetail.pending)
                                    )}
                                </Col>
                              </Row>
                              {currentLeaveAllocationDetail.exceeding && currentLeaveAllocationDetail.exceeding !== 0 &&
                                  <Row style={{ paddingTop : 10 }}>
                                   <Col className="entitlement-chart-exceeded-col">
                                     {intl.formatMessage({
                                      id: 'exceededLeaves',
                                      defaultMessage: 'Exceeded Leaves',
                                    })}
                                  </Col>
                                   <Col className="entitlement-chart-exceeded-details-col">
                                    { currentLeaveAllocationDetail.exceeding }
                                  </Col>
                                </Row>
                                }
                            </Col>
                          </Row>
                          <Row className="single-chart-body">
                            <Col className="single-chart-body-col">
                              <Pie
                                data={getDataForIndividualChart()}
                                statistic={getIndividualStats([])}
                                {...individualChartConfig}
                              />
                            </Col>
                          </Row>
                          <Row className="single-chart-footer-section">
                            <Col className="single-chart-footer-section-col">
                              <span className="card-single-chart-bottom">{'Available'}</span>
                            </Col>
                          </Row>
                        </div>
                      </div>
                    ) : (
                      <div
                        className={
                          entitlementCount.length > 3
                            ? 'entitlement-chart-widget-with-scroll'
                            : 'entitlement-chart-widget-without-scroll'
                        }
                      >
                        {entitlementCount.length === 0 ? (
                          <Empty style={{ marginTop: '30%' }}></Empty>
                        ) : (
                          <></>
                        )}
                        {entitlementCount.map((entitlement, index) => (
                          <div className="entitlement-chart-widget-div">
                            <div className="entitlement-chart-widget-header" />
                            <Row>
                              <Col className="entitlement-chart-widget-title-section">
                                <span className="card-widget-title">{entitlement.name}</span>
                              </Col>
                            </Row>
                            <Row className="entitlement-chart-section">
                              <Col className="entitlement-chart-section-col">
                                <Pie
                                  data={[
                                    {
                                      type: 'Used',
                                      value: Number(entitlement.used),
                                    },
                                    {
                                      type: 'Pending',
                                      value: Number(entitlement.pending),
                                    },
                                    {
                                      type: 'Availble',
                                      value:
                                        entitlement.total -
                                        (entitlement.used + entitlement.pending),
                                    },
                                    {
                                      type:'Exceeded Leaves',
                                      value: Number(entitlement.exceeding)
                                    }
                                  ]}
                                  statistic={getStats(entitlement)}
                                  {...config}
                                />
                              </Col>
                              <Col className="entitlement-chart-details-section">
                                <Row>
                                  <Col className="entitlement-chart-details-col">{intl.formatMessage({
                                    id: 'allocated',
                                    defaultMessage: 'Allocated',
                                  })}</Col>
                                  <Col className="entitlement-chart-details-col">{intl.formatMessage({
                                    id: 'used',
                                    defaultMessage: 'Used',
                                  })}</Col>
                                  <Col className="entitlement-chart-details-col">{intl.formatMessage({
                                    id: 'pending',
                                    defaultMessage: 'Pending',
                                  })}</Col>
                                  <Col className="entitlement-chart-details-col">{intl.formatMessage({
                                    id: 'balance',
                                    defaultMessage: 'Balance',
                                  })}</Col>
                                </Row>
                                <Row style={{ paddingTop: 10 }}>
                                  <Col className="entitlement-chart-details-col">
                                    {entitlement.total}
                                  </Col>
                                  <Col className="entitlement-chart-details-col">
                                    {entitlement.used}
                                  </Col>
                                  <Col className="entitlement-chart-details-col">
                                    {entitlement.pending}
                                  </Col>
                                  <Col className="entitlement-chart-details-col">
                                    {entitlement.total - (entitlement.used + entitlement.pending)}
                                  </Col>
                                </Row>
                                {entitlement.exceeding && entitlement.exceeding !== 0 &&
                                  <Row style={{ paddingTop : 10 }}>
                                   <Col className="entitlement-chart-exceeded-col">
                                     {intl.formatMessage({
                                      id: 'exceededLeaves',
                                      defaultMessage: 'Exceeded Leaves',
                                    })}
                                  </Col>
                                   <Col className="entitlement-chart-exceeded-details-col">
                                    { entitlement.exceeding }
                                  </Col>
                                </Row>
                                }
                              </Col>
                            </Row>
                            <Row className="entitlement-chart-footer-section">
                              <Col className="entitlement-chart-footer-section-col">
                                <span className="card-widget-bottom">{'Available'}</span>
                              </Col>
                            </Row>
                          </div>
                        ))}
                      </div>
                    )}
                  </Card>
                </Row>
              </Col>
            </Row>
          </ProCard>
          {/* </PageContainer> */}
        </div>
      </Access>

      <ModalForm
        width={'90%'}
        title={intl.formatMessage({
          id: 'leaveHistoryList',
          defaultMessage: 'Leave History',
        })}
        modalProps={{
          destroyOnClose: true,
          bodyStyle: {height: 700}
        }}
        onFinish={async (values: any) => {}}
        visible={listModalVisible}
        onVisibleChange={handleListModalVisible}
        initialValues={{
          useMode: 'chapter',
        }}
        submitter={{
          render: () => {
            return [<>{[]}</>];
          },
        }}
      >
        <LeaveHistoryList listType="leave" employeeId={selectedEmployee}></LeaveHistoryList>
      </ModalForm>
    </>
  );
};

export default ApplyLeaveForEmployees;
