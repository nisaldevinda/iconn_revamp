import React, { useEffect, useRef, useState, useContext } from 'react';
import { InputRef, Select } from 'antd';
import { DownloadOutlined, SearchOutlined, InfoCircleFilled } from '@ant-design/icons';
import {
  Button,
  Tag,
  Space,
  Image,
  Row,
  Col,
  Tooltip,
  Spin,
  Modal,
  Popover,
  DatePicker,
  TimePicker,
  Form,
  message,
  Popconfirm,
  ConfigProvider,
  InputNumber,
  Card,
  Result,
  Table,
  Input,
  Badge
} from 'antd';
import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { ProFormSelect } from '@ant-design/pro-form';
import ProForm, { ProFormDateRangePicker } from '@ant-design/pro-form';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import _, { cond } from 'lodash';

import {
  approveTimeChange,
  approveTimeChangeAdmin,
  getAttendanceSheetAdminData,
  getAttendanceSheetEmployeeData,
  getAttendanceSheetManagerData,
  getAttendanceTimeChangeData,
  requestTimeChange,
  requestUpdateBreaks,
  accessibleWorkflowActions,
  updateInstance,
  downloadManagerAttendanceView,
  downloadAdminAttendanceView,
  getRelatedBreakes,
} from '@/services/attendance';
import LateIcon from '../../assets/attendance/icon-clock-red.svg';
import EarlyIcon from '../../assets/attendance/icon-clock-orange.svg';
import viewIcon from '../../assets/attendance/icon-view.svg';
import RequestIcon from '../../assets/attendance/Time-change-request.svg';
import ManagerRequestIcon from '../../assets/attendance/Time-change-notification-manager.svg';
import NonWorkingDayIcon from '../../assets/attendance/icon-circle-black.svg';
import HolidayIcon from '../../assets/attendance/icon-circle-orange.svg';
import AbsentIcon from '../../assets/attendance/icon-circle-red.svg';
import { APIResponse } from '@/utils/request';
import { downloadBase64File } from '@/utils/utils';
import TimeChangeRequest from '../WorkflowRequests/timeChangeRequest';
import { getEmployeeList, getOtPayTypeList } from '@/services/dropdown';
import { getAllWorkShifts } from '@/services/workShift';
import en_US from 'antd/lib/locale-provider/en_US';
import { MinusCircleOutlined, PlusOutlined } from '@ant-design/icons';
import styles from './attendance.less';
import ProCard from '@ant-design/pro-card';
import type { FormInstance } from 'antd/es/form';
import './index.css';

moment.locale('en');

const EditableContext = React.createContext<FormInstance<any> | null>(null);

interface Item {
  id: number;
  date: string;
  employeeIdNo: number;
  name: string;
  shiftId: number;
  summaryId: number;
  timeZone: string;
  requestedTimeChangeId: number | null;
  shift: string;
  day: {
    isWorked: number;
    dayType: string;
  };
  leave: {
    name: string;
    color: string;
  }[];
  in: {
    time: string;
    late: boolean;
    date?: string;
  };
  out: {
    time: string;
    early: boolean;
    date?: string;
    isDifferentOutDate: boolean;
  };
  duration: {
    worked: string;
    breaks: string;
  };
}

interface EditableRowProps {
  index: number;
}

const EditableRow: React.FC<EditableRowProps> = ({ index, ...props }) => {
  const [form] = Form.useForm();
  return (
    <Form form={form} component={false}>
      <EditableContext.Provider value={form}>
        <tr {...props} />
      </EditableContext.Provider>
    </Form>
  );
};

interface EditableCellProps {
  title: React.ReactNode;
  editable: boolean;
  children: React.ReactNode;
  dataIndex: keyof Item;
  record: Item;
  handleSave: (record: Item) => void;
  handleIntialData: (record: Item) => void;
  setCurrentEditingRow: any;
  currentEditingRow: any;
  checkHasChanges: any;
  setLoading: any;
}

const EditableCell: React.FC<EditableCellProps> = ({
  title,
  editable,
  children,
  dataIndex,
  record,
  handleSave,
  handleIntialData,
  setCurrentEditingRow,
  currentEditingRow,
  checkHasChanges,
  setLoading,
  ...restProps
}) => {
  const [editing, setEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const inputRef = useRef<InputRef>(null);
  const form = useContext(EditableContext)!;
  const [workShiftList, setWorkShiftList] = useState([]);

  const [recordInDateModel, setRecordInDateModel] = useState('');

  useEffect(() => {
    if (editing) {
      if (dataIndex == 'shift') {
        callGetAllWorkShifts();
      }
      inputRef.current!.focus();
    }
  }, [editing]);

  async function callGetAllWorkShifts() {
    try {
      const response = await getAllWorkShifts();
      const workShiftsArray = response.data.map((workshift: any) => {
        return {
          label: workshift.name,
          value: workshift.id,
        };
      });
      setWorkShiftList(workShiftsArray);
    } catch (err) {
      console.log(err);
    }
  }

  const toggleEdit = () => {
    if (!editing) {
      if (currentEditingRow != null && currentEditingRow != record.id) {
        console.log(currentEditingRow);
        console.log(record.id);
        return;
      }

      if (dataIndex == 'outDateAndTime' && !record.in.time) {
        let key = 'Error';
        message.error({
          content: 'Please add in time before edit out date and time',
          key,
        });
        setCurrentEditingRow(null);
        return;
      }

      if (record.isApprovedOtAttendance) {
        let key = 'Error';
        message.error({
          content: 'Can not change values ,because this attendance record has pending or approved post ot request',
          key,
        });
        setCurrentEditingRow(null);
        return;
      }
    }

    let fieldErrors = 0;

    if (form.getFieldsError().length > 0) {
      form.getFieldsError().forEach((element: any) => {
        if (element.errors.length > 0) {
          fieldErrors += 1;
        }
      });
    }

    if (fieldErrors > 0) {
      return;
    }

    setEditing(!editing);

    setRecordInDateModel(record.date);
    setCurrentEditingRow(record.id);
    switch (dataIndex) {
      case 'in':
        let inTime = moment(record[dataIndex].time, 'hh:mm A').isValid()
          ? moment(record[dataIndex].time, 'hh:mm A')
          : null;
        form.setFieldsValue({ [dataIndex]: inTime });
        break;
      case 'out':
        let outTime = moment(record[dataIndex].time, 'hh:mm A').isValid()
          ? moment(record[dataIndex].time, 'hh:mm A')
          : null;
        form.setFieldsValue({ [dataIndex]: outTime });
        break;
      case 'outDateAndTime':
        let outDateAndTime =
          moment(record.outDate, 'YYYY-MM-DD').isValid() &&
          moment(record.out.time, 'hh:mm A').isValid()
            ? moment(record.outDate + ' ' + record.out.time, 'YYYY-MM-DD hh:mm A')
            : undefined;
        form.setFieldsValue({ [dataIndex]: outDateAndTime });
        break;
      case 'inDateAndTime':
        let inDateAndTime =
          moment(record.in.date, 'YYYY-MM-DD').isValid() &&
          moment(record.in.time, 'hh:mm A').isValid()
            ? moment(record.in.date + ' ' + record.in.time, 'YYYY-MM-DD hh:mm A')
            : undefined;
        form.setFieldsValue({ [dataIndex]: inDateAndTime });
        break;
      case 'shift':
        form.setFieldsValue({ [dataIndex]: record.shiftId });
        break;

      default:
        form.setFieldsValue({ [dataIndex]: record[dataIndex] });
        break;
    }
  };

  const disableDatesForEditableCells = (current: any) => {
    let firstDate = moment(recordInDateModel).subtract(0, 'd').format('YYYY-MM-DD');
    let secondDate = moment(recordInDateModel).add(1, 'd').format('YYYY-MM-DD');
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');

    const isNextDay = moment(compareDate, 'YYYY-MM-DD') >= moment(firstDate, 'YYYY-MM-DD');
    const isPreviousDay = moment(compareDate, 'YYYY-MM-DD') <= moment(secondDate, 'YYYY-MM-DD');
    const isValidDate = isNextDay && isPreviousDay;

    return !isValidDate;
  };

  const validatedRow = (params: any): boolean => {
    var invalidParams: boolean = false;
    const inDateTimeString = params.inDate + ' ' + params.inTime;
    const outDateTimeString = params.outDate + ' ' + params.outTime;
    const inDateTime = new Date(inDateTimeString);
    const outDateTime = new Date(outDateTimeString);

    if (!params.inDate) {
      invalidParams = true;
    }

    if (!params.outDate) {
      invalidParams = true;
    }

    if (params.inTime === 'Invalid date' || !params.inTime) {
      invalidParams = true;
    }

    if (params.outTime === 'Invalid date' || !params.outTime) {
      invalidParams = true;
    }

    if (inDateTime >= outDateTime) {
      invalidParams = true;
    }

    return invalidParams;
  };

  const save = async () => {
    try {
      setIsSaving(true);
      const values = await form.validateFields();
      let tempArr = {
        id: record.id,
        date: record.date,
        shiftId: record.shiftId,
        shift: record.shift,
        in: record.in,
        out: record.out,
        outDate: record.outDate,
        employeeIdNo: record.employeeIdNo,
        incompleUpdate: record.incompleUpdate,
      };

      if (values.hasOwnProperty('shift')) {
        console.log(values);
        if (values.shift) {
          const index = workShiftList.findIndex((item) => values.shift == item.value);
          tempArr.shiftId = workShiftList[index].value;
          tempArr.shift = workShiftList[index].label;
        } else {
          tempArr.shiftId = null;
          tempArr.shift = null;
        }
      }

      // if (values.hasOwnProperty('in')) {
      //   let inTime = values.in?.format('hh:mm A');
      //   tempArr.in.time = inTime;
      // }

      if (values.hasOwnProperty('inDateAndTime')) {
        let inDate = values.inDateAndTime?.format('YYYY-MM-DD');
        let inTime = values.inDateAndTime?.format('hh:mm A');
        tempArr.in.date = inDate;
        tempArr.in.time = inTime;
      }

      if (values.hasOwnProperty('outDateAndTime')) {
        let outDate = values.outDateAndTime?.format('YYYY-MM-DD');
        let outTime = values.outDateAndTime?.format('hh:mm A');
        tempArr.outDate = outDate;
        tempArr.out.time = outTime;
      }

      let hasRecordChange = await checkHasChanges(tempArr);

      if (hasRecordChange) {
        if (
          tempArr.in.time &&
          tempArr.out.time &&
          tempArr.outDate &&
          tempArr.in.date &&
          tempArr.shiftId
        ) {
          let inTimeObj = moment(tempArr.in.time, 'hh:mm A');
          let outTimeObj = moment(tempArr.out.time, 'hh:mm A');
          let inDateObj = moment(tempArr.in.date, 'YYYY-MM-DD');
          let shiftDateObj = moment(tempArr.date, 'YYYY-MM-DD');
          let outDateObj = moment(tempArr.outDate, 'YYYY-MM-DD');

          const params = {
            shiftId: tempArr.shiftId,
            summaryId: tempArr.id,
            employeeId: tempArr.employeeIdNo,
            shiftDate: shiftDateObj?.format('YYYY-MM-DD'),
            inDate: inDateObj?.format('YYYY-MM-DD'),
            outDate: outDateObj?.format('YYYY-MM-DD'),
            inTime: inTimeObj?.format('HH:mm:ss'),
            outTime: outTimeObj?.format('HH:mm:ss'),
            reason: null,
            breakDetails: [],
          };
          const invalidParams = validatedRow(params);
          if (!invalidParams) {
            setLoading(true);
            await approveTimeChangeAdmin(params).then(async (response: any) => {
              toggleEdit();
              await handleSave({ ...response.data });
              let tempObj = {
                in: response.data.in.time,
                id: response.data.id,
                out: response.data.out.time,
                outDate: response.data.outDate,
                shiftId: response.data.shiftId,
                shift: response.data.shift,
              };

              await handleIntialData({ ...tempObj });
              setLoading(false);
            });
          }
        } else {
          tempArr.incompleUpdate = true;
          toggleEdit();
          setIsSaving(false);
          await handleSave({ ...record, ...tempArr });
        }
      } else {
        toggleEdit();
        setIsSaving(false);
        await handleSave({ ...record, ...tempArr });
      }
    } catch (errInfo) {
      console.log('Save failed:', errInfo);
    }
  };

  const getRules = (dataIndex) => {
    // if (dataIndex == 'in') {
    //   return [{ required: true, message: 'Required' }];
    // }
    return [];
  };

  const checkOutDateIsValid = (current: any) => {
    // let firstDate = moment(recordInDateModel).subtract(0, 'd').format('YYYY-MM-DD');
    let secondDate = moment(recordInDateModel).add(1, 'd').format('YYYY-MM-DD');
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    const isEqualToInDate = moment(compareDate, 'YYYY-MM-DD').isSame(recordInDateModel);
    const isEqualToNextDate = moment(compareDate, 'YYYY-MM-DD').isSame(secondDate);

    return isEqualToInDate || isEqualToNextDate;
  };

  let childNode = children;

  if (editable) {
    childNode = editing ? (
      <>
        {
          <Form.Item
            style={{
              margin: 0,
              width:
                dataIndex == 'shift'
                  ? 130
                  : dataIndex == 'in'
                  ? 95
                  : dataIndex == 'outDateAndTime' || dataIndex == 'inDateAndTime'
                  ? 150
                  : 100,
            }}
            name={dataIndex}
            rules={getRules(dataIndex)}
          >
            {dataIndex == 'outDateAndTime' ? (
              <DatePicker
                onChange={(date, dateString) => {}}
                format="DD-MM-YYYY HH:mm"
                placeholder='DD-MM-YYYY HH:mm'
                showTime
                onKeyDown={(e) => {
                  console.log(e.target.value);
                }}
                ref={inputRef}
                onSelect= {(value) => {
                  if (dataIndex == 'outDateAndTime') {
                      form.setFieldsValue({ ['outDateAndTime']: value });
                  }
                }}
                onOk={(value) => {
                  if (dataIndex == 'outDateAndTime') {
                    if (record.in.time) {
                      let inDateTime = moment(
                        record.date + ' ' + record.in.time,
                        'YYYY-MM-DD hh:mm A',
                      );

                      if (!value.isAfter(inDateTime)) {
                        let key = 'Error';
                        message.error({
                          content: 'Out date time must be greater than in date time',
                          key,
                        });
                        form.setFieldsValue({ outDateAndTime: null });
                        inputRef.current!.focus();
                        return;
                      }
                    }
                  }
                }}
                onBlur={(value) => {
                  if (value.target.value != '') {
                    let dateTime = moment(value.target.value, 'DD-MM-YYYY hh:mm A')
                    if (dataIndex == 'outDateAndTime') {

                      let isValid =  checkOutDateIsValid(moment(value.target.value, 'DD-MM-YYYY'));
                      if (!isValid) {
                          let key = 'Error';
                          message.error({
                          content: 'Invalid Date',
                          key,
                          });

                          if (dataIndex == 'outDateAndTime') {
                              form.setFieldsValue({ outDateAndTime: null });
                          }

                          inputRef.current!.focus();
                          return;
                      }


                      if (record.in.time) {
                        let inDateTime = moment(
                          record.date + ' ' + record.in.time,
                          'YYYY-MM-DD hh:mm A',
                        );
  
                        if (!dateTime.isAfter(inDateTime)) {
                          
                          let key = 'Error';
                          message.error({
                            content: 'Out date time must be greater than in date time',
                            key,
                          });
                          form.setFieldsValue({ outDateAndTime: null });
                          inputRef.current!.focus();
                          return;
                        }
                      }

                    }
                  }
                  save();
                }}
                disabledDate={disableDatesForEditableCells}
              />
            ) : dataIndex == 'inDateAndTime' ? (
              <DatePicker
                onChange={(date, dateString) => {}}
                format="DD-MM-YYYY HH:mm"
                placeholder='DD-MM-YYYY HH:mm'
                showTime
                onKeyDown={(e) => {
                  console.log(e.target.value);
                }}
                ref={inputRef}
                onSelect= {(value) => {
                  if (dataIndex == 'inDateAndTime') {
                      form.setFieldsValue({ ['inDateAndTime']: value });
                  }
                }}
                onOk={(value) => {
                  if (dataIndex == 'inDateAndTime') {
                    if (record.out.time && record.outDate && value) {

                      let outTimeObj = moment(
                        record.outDate + ' ' + record.out.time,
                        'YYYY-MM-DD hh:mm A',
                      );

                      if (!outTimeObj.isAfter(value)) {
                        let key = 'Error';
                        message.error({
                          content: 'In time must be less than out time',
                          key,
                        });
                        form.setFieldsValue({ inDateAndTime: null });
                        inputRef.current!.focus();
                        return;
                      }
                    }
                  }
                }}
                onBlur={(value) => {
                  if (value.target.value != '') {
                    let time = moment(value.target.value, 'DD-MM-YYYY hh:mm A');
                    if (dataIndex == 'inDateAndTime') {
                      if (record.out.time && record.outDate && time) {
                        let outTimeObj = moment(
                          record.outDate + ' ' + record.out.time,
                          'YYYY-MM-DD hh:mm A',
                        );
  
                        if (!outTimeObj.isAfter(time)) {
                          let key = 'Error';
                          message.error({
                            content: 'In time must be less than out time',
                            key,
                          });
                          form.setFieldsValue({ inDateAndTime: null });
                          inputRef.current!.focus();
                          return;
                        }
                      }
                    }
                  }
                  save();
                }}
                disabledDate={disableDatesForEditableCells}
              />
            ) : dataIndex == 'in' ? (
              <TimePicker
                // use24Hours
                format="HH:mm"
                placeholder='HH:mm'
                ref={inputRef}
                onBlur={(value) => {
                  if (value.target.value != '') {
                    let time = moment(value.target.value, 'hh:mm A');
                    if (dataIndex == 'in') {
                      if (record.out.time && time) {
                        let outTimeObj = moment(record.out.time, 'hh:mm A');
  
                        if (!outTimeObj.isAfter(time)) {
                          let key = 'Error';
                          message.error({
                            content: 'In time must be less than out time',
                            key,
                          });
                          form.setFieldsValue({ in: null });
                          inputRef.current!.focus();
                          return;
                        }
                      }
                    }
                  }
                  save();
                }}
                onSelect= {(value) => {
                  if (dataIndex == 'in') {
                      form.setFieldsValue({ ['in']: value });
                  }
                }}
                onOk={(value) => {
                  if (dataIndex == 'in') {
                    if (record.out.time && value) {
                      let outTimeObj = moment(record.out.time, 'hh:mm A');

                      if (!outTimeObj.isAfter(value)) {
                        let key = 'Error';
                        message.error({
                          content: 'In time must be less than out time',
                          key,
                        });
                        form.setFieldsValue({ in: null });
                        inputRef.current!.focus();
                        return;
                      }
                    }
                  }
                }}
              />
            ) : dataIndex == 'shift' ? (
              <Select
                name="select"
                size="small"
                style={{ fontSize: 10 }}
                showSearch
                options={workShiftList}
                ref={inputRef}
                onBlur={save}
              />
            ) : (
              <Input
                ref={inputRef}
                style={{ borderRadius: 6, fontSize: 10 }}
                onPressEnter={save}
                onBlur={save}
              />
            )}
          </Form.Item>
        }
      </>
    ) : (
      <div
        className={styles.editableCellValueWrap}
        style={{ paddingRight: 24 }}
        onClick={toggleEdit}
      >
        {children}
      </div>
    );
  }

  return <td {...restProps}>{childNode}</td>;
};

type EditableTableProps = Parameters<typeof Table>[0];

interface DataType {
  key: React.Key;
  name: string;
  age: string;
  address: string;
}

type ColumnTypes = Exclude<EditableTableProps['columns'], undefined>;

export type TableViewProps = {
  employeeId?: number;
  others?: boolean;
  nonEditModel?: boolean;
  adminView?: boolean;
  accessLevel?: string;
};

type AttendanceItem = {
  id: number;
  date: string;
  employeeIdNo: number;
  name: string;
  shiftId: number;
  summaryId: number;
  timeZone: string;
  requestedTimeChangeId: number | null;
  shift: string;
  day: {
    isWorked: number;
    dayType: string;
  };
  leave: {
    name: string;
    color: string;
  }[];
  in: {
    time: string;
    late: boolean;
    date?: string;
  };
  out: {
    time: string;
    early: boolean;
    date?: string;
    isDifferentOutDate: boolean;
  };
  duration: {
    worked: string;
    breaks: string;
  };
};

const AttendanceAdminTableView: React.FC<TableViewProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const [actionIds, setActionIds] = useState<string | null>(null);
  const [workflowId, setWorkflowId] = useState<string | null>(null);
  const [workflowInstanceId, setworkflowInstanceId] = useState<string | null>(null);
  const [contextId, setContextId] = useState<string | null>(null);
  const actionRef = useRef<ActionType>();
  const [selectorEmployees, setSelectorEmployees] = useState([]);
  const [payTypes, setPayTypes] = useState([]);
  const [attendanceSheetData, setAttendanceSheetData] = useState([]);
  const [dayTypesData, setDayTypesData] = useState([]);
  const [intialData, setIntialData] = useState<any>([]);
  const [dataCount, setDataCount] = useState(0);
  const [tableState, setTableState] = useState<any>({});
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();
  const [selectedEmployee, setSelectedEmployee] = useState(props.employeeId ?? undefined);
  const [selectedRawEmployeeId, setSelectedRawEmployeeId] = useState(null);
  const [othersView, setOthersView] = useState(props.others ?? false);
  const [adminView, setAdminView] = useState(props.adminView ?? false);
  const [loading, setLoading] = useState(false);
  const [actions, setActions] = useState<any>([]);
  const [timeChangeDataSet, setTimeChangeDataSet] = useState({});

  const [isModalVisible, setIsModalVisible] = useState(false);
  const [loadingModel, setLoadingModel] = useState(false);
  const [disableModelButtons, setDisableModelButtons] = useState(false);
  const [showDataTable, setShowDataTable] = useState<boolean>(false);
  const [loadingModelRequest, setLoadingModelRequest] = useState(false);
  const [loadingModelReject, setLoadingModelReject] = useState(false);
  const [loadingModelApprove, setLoadingModelApprove] = useState(false);
  const [isMaintainOt, setIsMaintainOt] = useState(false);
  const [loadingExcelDownload, setLoadingExcelDownload] = useState(false);
  const [editStatusModel, setEditStatusModel] = useState(!props.nonEditModel);
  const [dateModel, setDateModel] = useState('');
  const [currentEditingRow, setCurrentEditingRow] = useState(null);
  const [reasonModel, setReasonModel] = useState('');
  const [shiftIdModel, setShiftIdModel] = useState<number>();
  const [timeChangeIdModel, setTimeChangeIdModel] = useState<number>();
  const [summaryIdModel, setSummaryIdModel] = useState<number>();
  const [employeeIdModel, setEmployeeIdModel] = useState<number>();
  const [employeeName, setEmployeeName] = useState<string | null>(null);

  const [inDateModel, setInDateModel] = useState<moment.Moment>();
  const [validatedStatusInDate, setValidateStatusInDate] = useState<'' | 'error'>('');
  const [helpInDate, setHelpInDate] = useState('');

  const [outDateModel, setOutDateModel] = useState<moment.Moment>();
  const [validateStatusOutDate, setValidateStatusOutDate] = useState<'' | 'error'>('');
  const [helpOutDate, setHelpOutDate] = useState('');

  const [inTimeModel, setInTimeModel] = useState<moment.Moment>();
  const [validateStatusInTime, setValidateStatusInTime] = useState<'' | 'error'>('');
  const [helpInTime, setHelpInTime] = useState('');
  const [shiftDate, setShiftDate] = useState('');
  const [shift, setShift] = useState('');
  const [totalBreake, setTotalBreake] = useState('');

  const [outTimeModel, setOutTimeModel] = useState<moment.Moment>();
  const [validateStatusOutTime, setValidateStatusOutTime] = useState<'' | 'error'>('');
  const [helpOutTime, setHelpOutTime] = useState(' ');
  const key = 'saving';
  const summaryUrl = '/attendance-manager/summary';
  const [relateScope, setRelateScope] = useState<string | null>(null);
  const { RangePicker } = DatePicker;
  const [form] = Form.useForm();
  const [searchForm] = Form.useForm();

  const [count, setCount] = useState(2);

  const handleSave = (row: DataType) => {
    const newData = [...attendanceSheetData];
    const index = newData.findIndex((item) => row.id === item.id);
    // console.log(row);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });

    setAttendanceSheetData([...newData]);
    setCurrentEditingRow(null);
  };

  const handleIntialData = (row: DataType) => {
    const newData = [...intialData];
    const index = newData.findIndex((item) => row.id === item.id);
    console.log(index);
    console.log(row);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });

    setIntialData([...newData]);
  };

  const checkHasChanges = (editedRow) => {
    const index = intialData.findIndex((item) => editedRow.id === item.id);
    if (index == -1) {
      return false;
    }

    let orginalRecord = intialData[index];

    //check whether in time is changed
    if (orginalRecord.in !== editedRow.in.time) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.out !== editedRow.out.time) {
      return true;
    }

    //check whether in date is changed
    if (orginalRecord.inDate !== editedRow.in.date) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.outDate !== editedRow.outDate) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.shiftId !== editedRow.shiftId) {
      return true;
    }

    return false;
  };

  const components = {
    body: {
      row: EditableRow,
      cell: EditableCell,
    },
  };

  var columns: ProColumns<AttendanceItem>[] = [
    {
      title: '',
      width: 10,
      fixed: 'left',
      hideInSearch: true,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <div className='iconSpace'>
              {
                <Badge style={{marginRight: 8}} color={record.day.dayTypeColor}/>
              }
              {
                record.shiftId != null && record.isExpectedToPresent &&
                  record.day.isWorked === 0 &&
                  record.leave.length == 0 ? (
                    <Badge color="#44A4ED"/>
                ) :  (record.shiftId != null && record.isExpectedToPresent && record.day.isWorked === 1 && record.in.late || record.shiftId != null && record.isExpectedToPresent && record.day.isWorked === 1 && record.out.early) ? (
                    <Badge color="#ED4444"/>
                ) : <></>
              }
            </div>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.date" defaultMessage={intl.formatMessage({
        id: 'date',
        defaultMessage: 'Date',
      })} />,
      dataIndex: 'date',
      fixed: 'left',
      width: 80,
      sorter: true,
      hideInSearch: true,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { height: 75, background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <>
              {record.leave.length > 0 ? (
                <Row>
                  <Space>
                    {record.leave[0].typeString !== null &&
                    record.leave[0].typeString.length <= 14 ? (
                      <p className={styles.leaves}>{record.leave[0].typeString}</p>
                    ) : record.leave[0].typeString !== null &&
                      record.leave[0].typeString.length > 14 ? (
                      <Tooltip title={record.leave[0].typeString}>
                        <p className={styles.leaves}>
                          {record.leave[0].typeString.substring(0, 14 - 3) + '...'}{' '}
                        </p>
                      </Tooltip>
                    ) : (
                      <></>
                    )}
                  </Space>
                </Row>
              ) : (
                <></>
              )}
              <Row className={record.leave.length > 0 ? styles.dateWithLeave : styles.date}>
                <Space>
                  {moment(record.date, 'YYYY-MM-DD').isValid()
                    ? moment(record.date).format('DD-MM-YYYY')
                    : null
                  }
                </Space>
              </Row>
            </>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.shift" defaultMessage= {intl.formatMessage({
        id: 'shiftName',
        defaultMessage: "Shift Name",
      })} />,
      dataIndex: 'shift',
      width: 150,
      editable: true,
      fixed: 'left',
      hideInSearch: true,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              {record.shift !== null && record.shift.length <= 16 ? (
                <span>{record.shift}</span>
              ) : record.shift !== null && record.shift.length > 16 ? (
                <Tooltip title={record.shift}>{record.shift.substring(0, 16 - 3) + '...'}</Tooltip>
              ) : (
                <>-</>
              )}
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.inTime" defaultMessage={intl.formatMessage({
        id: 'inTime',
        defaultMessage: "In",
      })} />,
      dataIndex: 'inDateAndTime',
      search: false,
      fixed: 'left',
      width: 190,
      editable: true,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <Space className={styles.dayTypeTable}>
                <div>
                  <Row style={{ display: 'flex' }}>
                    <p className={styles.time}>{
                    moment(record.in.date, 'YYYY-MM-DD').isValid() && moment(record.in.time, 'hh:mm A').isValid()  ? moment(record.in.date + ' ' +  moment(record.in.time, 'hh:mm A').format('HH:mm')).format('DD-MM-YYYY  HH:mm:ss') : '-'
                    }</p>
                    {record.in.late ? (
                      <p className={styles.dayTypeTableIcon}>
                        <Image src={LateIcon} preview={false} />
                      </p>
                    ) : (
                      <></>
                    )}
                  </Row>
                </div>
              </Space>
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.date" defaultMessage={intl.formatMessage({
        id: 'outDateTime',
        defaultMessage: "Out",
      })} />,
      dataIndex: 'outDateAndTime',
      fixed: 'left',
      editable: true,
      width: 190,
      hideInSearch: true,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <Space className={styles.dayTypeTable}>
                <div>
                  <Row style={{ display: 'flex' }}>
                    <p className={styles.time}>{moment(record.outDate, 'YYYY-MM-DD').isValid() &&
              moment(record.out.time, 'hh:mm A').isValid()
                ? moment(record.outDate + ' ' +  moment(record.out.time, 'hh:mm A').format('HH:mm')).format('DD-MM-YYYY  HH:mm:ss')
                : '-'}</p>
                    {record.out.early ? (
                      <p className={styles.dayTypeTableIcon}>
                        <Image src={EarlyIcon} preview={false} />
                      </p>
                    ) : (
                      <></>
                    )}
                  </Row>
                </div>
              </Space>
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.OtHours" defaultMessage={intl.formatMessage({
        id: 'inLate',
        defaultMessage: "In Late",
      })} />,
      dataIndex: 'inLate',
      width : !isMaintainOt ? 'auto' : undefined,
      fixed : !isMaintainOt ? 'left' : false,
      hideInTable:  false,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <p className={styles.time}>{record.in.late ? record.in.late : '00:00'}</p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.OtHours" defaultMessage={intl.formatMessage({
        id: 'earlyDepature',
        defaultMessage: "Early Depature",
      })} />,
      dataIndex: 'earlyDepature',
      width : !isMaintainOt ? 'auto' : undefined,
      fixed : !isMaintainOt ? 'left' : false,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <p className={styles.time}>{record.out.early ? record.out.early : '00:00'}</p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.OtHours" defaultMessage={intl.formatMessage({
        id: 'totalLate',
        defaultMessage: "Total Late",
      })}/>,
      dataIndex: 'totalLate',
      width : !isMaintainOt ? 'auto' : undefined,
      fixed : !isMaintainOt ? 'left' : false,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <p className={styles.time}>{record.totalLate ? record.totalLate : '00:00'}</p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.OtHours" defaultMessage={intl.formatMessage({
        id: 'totalOt',
        defaultMessage: "Total OT",
      })} />,
      dataIndex: 'totalOT',
      hideInTable: !isMaintainOt ? true : false,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <p className={styles.time}>
                      {record.otData.totalOtHours ? record.otData.totalOtHours : '00:00'}
                    </p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.OtHours" defaultMessage={intl.formatMessage({
        id: 'totalApprovedOt',
        defaultMessage: "Total Approved OT",
      })}  />,
      dataIndex: 'totalApproveOt',
      hideInTable: !isMaintainOt ? true : false,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <p className={styles.time}>
                      {record.otData.totalApprovedOtHours ? record.otData.totalApprovedOtHours : '00:00'}
                    </p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    },
  ];

  useEffect(() => {
    if (othersView) {
      callGetEmployeeData();
      callGetOTPayTypesData();
    }
  }, []);

  useEffect(() => {
    if (isModalVisible) {
      getRelatedBreakDetails();
    }
  }, [isModalVisible]);

  useEffect(() => {
    if (outDateModel == undefined || outTimeModel || undefined || inTimeModel == undefined) {
      form.setFieldsValue({ relatedBreaksDetails: null });
    }
  }, [inDateModel, inTimeModel, outDateModel, outTimeModel]);

  const getRelatedBreakDetails = async () => {
    let realtedBreaks = await getRelatedBreakes({ summeryId: summaryIdModel });
    const breakeData = realtedBreaks.data.map((col) => {
      return {
        id: col.id,
        breakInTime: moment(col.breakInTime, 'hh:mm A'),
        breakInDate: moment(col.breakInDate, 'YYYY-MM-DD'),
        breakOutTime: moment(col.breakOutTime, 'hh:mm A'),
        breakOutDate: moment(col.breakOutDate, 'YYYY-MM-DD'),
      };
    });

    await form.setFieldsValue({ relatedBreaksDetails: breakeData });
    setLoading(false);
  };

  const getViewType = () => {
    if (adminView && othersView) {
      return 'adminView';
    } else if (othersView) {
      return 'managerView';
    } else {
      return 'myView';
    }
  };

  const processedCols = () => {
    let colSet = columns;


    if (isMaintainOt) {

      payTypes.forEach((payType) => {
        let colName = payType.name;
        let approvedcolName = 'Approved' + ' ' + payType.name;
        let tempOTCol = {
          title: <FormattedMessage id={payType.code} defaultMessage={payType.name} />,
          dataIndex: payType.name,
          search: false,
          render: (_, record) => {
            return {
              props: {
                style:
                  record.leave.length > 0
                    ? { background: '#FFF7E6' }
                    : record.id === currentEditingRow
                    ? { background: '#f2fced' }
                    : record.incompleUpdate
                    ? { background: '#FCFEF1' }
                    : {},
              },
              children: (
                <Space>
                  <div>
                    <Row>
                      <Space>
                        <p className={styles.time}>
                          {record.otData.otDetails[payType.code]
                            ? record.otData.otDetails[payType.code]
                            : '00:00'}
                        </p>
                      </Space>
                    </Row>
                  </div>
                </Space>
              ),
            };
          },
        };
  
        let tempOTApprovedCol = {
          title: <FormattedMessage id={approvedcolName} defaultMessage={approvedcolName} />,
          dataIndex: 'approved'+payType.code,
          search: false,
          render: (_, record) => {
            return {
              props: {
                style:
                  record.leave.length > 0
                    ? { background: '#FFF7E6' }
                    : record.id === currentEditingRow
                    ? { background: '#f2fced' }
                    : record.incompleUpdate
                    ? { background: '#FCFEF1' }
                    : {},
              },
              children: (
                <Space>
                  <div>
                    <Row>
                      <Space>
                        <p className={styles.time}>
                          {record.otData.approvedOtDetails[payType.code]
                            ? record.otData.approvedOtDetails[payType.code]
                            : '00:00'}
                        </p>
                      </Space>
                    </Row>
                  </div>
                </Space>
              ),
            };
          },
        };
  
        colSet.push(tempOTCol);
        colSet.push(tempOTApprovedCol);
      });
    }

    let workedHours = {
      title: <FormattedMessage id="Attendance.workHours" defaultMessage={intl.formatMessage({
        id: 'workedHours',
        defaultMessage: "Worked Hours",
      })} />,
      dataIndex: 'workedHours',
      fixed: 'right',
      width: 70,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <p className={styles.time}>
                      {record.duration.worked ? record.duration.worked : '00:00'}
                    </p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    };
    let breakeTimeCol = {
      title: <FormattedMessage id="Attendance.workHours" defaultMessage={intl.formatMessage({
        id: 'totalBreakTime',
        defaultMessage: "Total Breake Time",
      })} />,
      dataIndex: 'breakDuration',
      fixed: 'right',
      width: 70,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style:
              record.leave.length > 0
                ? { background: '#FFF7E6' }
                : record.id === currentEditingRow
                ? { background: '#f2fced' }
                : record.incompleUpdate
                ? { background: '#FCFEF1' }
                : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    <a
                      onClick={async () => {
                        if (
                          !record.date ||
                          !record.out.date ||
                          !record.in.time ||
                          !record.out.time ||
                          record.incompleUpdate
                        ) {
                          message.error({
                            content: intl.formatMessage({
                              id: 'cannotAddBreakes',
                              defaultMessage:
                                'Please update in time and out time for attendance before you update breaks.',
                            }),
                            key,
                          });
                          return;
                        }
                        setLoading(true);

                        const inDateTime = record.date + ' ' + record.in.time;
                        const outDateTime = record.out.date + ' ' + record.out.time;
                        const inDateTimeMoment = moment(inDateTime, 'YYYY-MM-DD hh:mm:ss A');
                        const outDateTimeMoment = moment(outDateTime, 'YYYY-MM-DD hh:mm:ss A');
                        const outDateRecord = record.out.date
                          ? moment(record.out.date, 'YYYY-MM-DD')
                          : undefined;
                        const inTimeRecord = record.in.time ? inDateTimeMoment : undefined;
                        const outTimeRecord =
                          record.out.date && record.out.time ? outDateTimeMoment : undefined;

                        resetStates(record, outDateRecord, inTimeRecord, outTimeRecord);

                        setReasonModel('');
                        setLoadingModelRequest(false);
                      }}
                      className={styles.time}
                    >
                      {record.duration.breaks ? record.duration.breaks : '00:00'}
                    </a>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    };

    let actionCol = {
      title: '',
      dataIndex: 'action',
      fixed: 'right',
      width: 70,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style: record.leave.length > 0 ? { background: '#FFF7E6' } : {},
          },
          children: record.isEdited ? (
            <Button
              type="text"
              icon={<Image src={ManagerRequestIcon} preview={false} />}
              onClick={async () => {
                console.log(record);
              }}
            />
          ) : (
            <></>
          ),
        };
      },
    };
    colSet.push(breakeTimeCol);
    colSet.push(workedHours);
    return colSet;
  };

  const cols = processedCols().map((col) => {
    if (!col.editable) {
      return col;
    }
    return {
      ...col,
      onCell: (record: DataType) => ({
        record,
        editable: col.editable,
        dataIndex: col.dataIndex,
        title: col.title,
        handleSave,
        handleIntialData,
        currentEditingRow: currentEditingRow,
        setCurrentEditingRow: setCurrentEditingRow,
        checkHasChanges,
        setLoading,
      }),
    };
  });

  function resetStates(
    record: AttendanceItem,
    outDateRecord: moment.Moment | undefined,
    inTimeRecord: moment.Moment | undefined,
    outTimeRecord: moment.Moment | undefined,
  ) {
    const inDateRecord = moment(record.date, 'YYYY-MM-DD');
    setShiftDate(record.date);
    setShift(record.shift);
    setTotalBreake(record.duration.breaks ? record.duration.breaks : '00:00');

    setDateModel(record.date);
    setEmployeeIdModel(record.employeeIdNo);
    setInDateModel(inDateRecord);
    setOutDateModel(outDateRecord);
    setInTimeModel(inTimeRecord);
    setOutTimeModel(outTimeRecord);
    setShiftIdModel(record.shiftId);
    setSummaryIdModel(record.summaryId);
    setDisableModelButtons(false);
    setIsModalVisible(true);

    setValidateStatusInDate('');
    setHelpInDate('');
    setValidateStatusOutDate('');
    setHelpOutDate('');
    setValidateStatusInTime('');
    setHelpInTime('');
    setValidateStatusOutTime('');
    setHelpOutTime('');
  }

  async function callGetEmployeeData() {
    let scope = 'EMPLOYEE';
    if (adminView) {
      scope = 'ADMIN';
    } else if (othersView) {
      scope = 'MANAGER';
    }
    try {
      const response = await getEmployeeList(scope);
      const { data } = response;
      const selectorEmployees = data.map((employee: any) => {
        return {
          label: employee.employeeNumber+' | '+employee.employeeName,
          value: employee.id,
        };
      });
      setSelectorEmployees(selectorEmployees);
    } catch (err) {
      console.log(err);
    }
  }

  async function callGetOTPayTypesData() {
    let scope = 'EMPLOYEE';
    if (adminView) {
      scope = 'ADMIN';
    } else if (othersView) {
      scope = 'MANAGER';
    }
    try {
      const response = await getOtPayTypeList(scope);
      setPayTypes(response.data);
    } catch (err) {
      console.log(err);
    }
  }

  async function callGetAttendanceSheetData(
    pageNo?: number,
    pageCount?: number,
    sort = { name: 'date', order: 'DESC' },
  ) {
    setLoading(true);
    const params = {
      employee: selectedEmployee,
      fromDate: fromDate,
      toDate: toDate,
      // pageNo: pageNo,
      // pageCount: pageCount,
      sort: sort,
    };

    setAttendanceSheetData([]);
    setIntialData([]);
    setDataCount(0);
    if (hasPermitted('attendance-admin-access') && adminView) {
      await getAttendanceSheetAdminData(params)
        .then((response: any) => {
          if (response) {
            // const intialData = response.data.sheets;
            setAttendanceSheetData(response.data.sheets);
            setDayTypesData(response.data.relatedDayTypes);
            setCurrentEditingRow(null);

            let orgData = [];
            response.data.sheets.map((sheet) => {
              let tempObj = {
                in: sheet.in.time,
                id: sheet.id,
                out: sheet.out.time,
                outDate: sheet.outDate,
                inDate: sheet.in.date,
                shiftId: sheet.shiftId,
                shift: sheet.shift,
              };
              orgData.push(tempObj);
            });

            setIntialData([...orgData]);
            setDataCount(response.data.count);
            setIsMaintainOt(response.data.isMaintainOt);
          }
          setLoading(false);
        })
        .catch(() => {
          setLoading(false);
        });
    } else if (hasPermitted('attendance-manager-access') && othersView) {
      await getAttendanceSheetManagerData(params)
        .then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setDataCount(response.data.count);
            setIsMaintainOt(response.data.isMaintainOt);
          }
          setLoading(false);
        })
        .catch(() => {
          setLoading(false);
        });
    } else {
      await getAttendanceSheetEmployeeData(params)
        .then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setDataCount(response.data.count);
            setIsMaintainOt(response.data.isMaintainOt);
          }
          setLoading(false);
        })
        .catch(() => {
          setLoading(false);
        });
    }
    setLoading(false);
  }

  async function callDownloadTeamAttendance() {
    setLoadingExcelDownload(true);
    const params = {
      employee: selectedEmployee,
      fromDate: fromDate,
      toDate: toDate,
    };

    if (hasPermitted('attendance-admin-access') && adminView) {
      await downloadAdminAttendanceView(params)
        .then((response: any) => {
          setLoadingExcelDownload(false);
          if (response.data) {
            downloadBase64File(
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              response.data,
              'attendanceSheetData.xlsx',
            );
          }
        })
        .catch((error: APIResponse) => {
          setLoadingExcelDownload(false);
        });
    } else if (hasPermitted('attendance-manager-access') && othersView) {
      await downloadManagerAttendanceView(params)
        .then((response: any) => {
          setLoadingExcelDownload(false);
          if (response.data) {
            downloadBase64File(
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              response.data,
              'attendanceSheetData.xlsx',
            );
          }
        })
        .catch((error: APIResponse) => {
          setLoadingExcelDownload(false);
        });
    }
  }

  function onChange(dates: any, dateStrings: any) {
    if (dates) {
      setFromDate(moment(dateStrings[0], 'DD-MM-YYYY').format('YYYY-MM-DD'));
      setToDate(moment(dateStrings[1], 'DD-MM-YYYY').format('YYYY-MM-DD'));
    } else {
      setFromDate(undefined);
      setToDate(undefined);
    }
  }

  const handleCancel = () => {
    setIsModalVisible(false);
  };

  const handleOk = (breakList: any) => {
    clearModelValidationStates();

    const params = {
      shiftId: shiftIdModel,
      summaryId: summaryIdModel,
      employeeId: employeeIdModel,
      shiftDate: dateModel,
      inDate: inDateModel?.format('YYYY-MM-DD'),
      outDate: outDateModel?.format('YYYY-MM-DD'),
      inTime: inTimeModel?.format('HH:mm:ss'),
      outTime: outTimeModel?.format('HH:mm:ss'),
      reason: reasonModel,
      breakDetails: breakList,
    };

    const invalidParams = validatedDates(params);

    if (!invalidParams) {
      approveTimeChangeAdmin(params)
        .then((response: any) => {
          setLoadingModelRequest(false);
          setDisableModelButtons(false);

          if (response.data) {
            callGetAttendanceSheetData(
              tableState.current,
              tableState.pageSize,
              tableState.sortValue,
            );
            handleCancel();
            message.success({
              content: intl.formatMessage({
                id: 'updatedTimeChange',
                defaultMessage: 'Saved the time change.',
              }),
              key,
            });
          }

          if (response.error) {
            message.error({
              content: intl.formatMessage({
                id: 'rejectedTimeChange',
                defaultMessage: 'Failed to save the time change.',
              }),
              key,
            });
          }
        })
        .catch((error: APIResponse) => {
          setLoadingModelRequest(false);
          setDisableModelButtons(false);

          message.error({
            content:
              error.message ??
              intl.formatMessage({
                id: 'rejectedTimeChange',
                defaultMessage: 'Failed to save the time change.',
              }),
            key,
          });
        });
    }
  };

  const clearModelValidationStates = () => {
    setLoadingModelRequest(true);
    setDisableModelButtons(true);
    setValidateStatusInDate('');
    setHelpInDate('');
    setValidateStatusOutDate('');
    setHelpOutDate('');
    setValidateStatusInTime('');
    setHelpInTime('');
    setValidateStatusOutTime('');
    setHelpOutTime('');
  };

  const onFinish = async () => {
    let fieldWiseDynamicErrors = form.getFieldsError();
    let errCount = 0;
    if (fieldWiseDynamicErrors != undefined) {
      fieldWiseDynamicErrors.map((fieldObj) => {
        if (fieldObj.errors.length > 0) {
          errCount++;
        }
      });
    }

    if (errCount > 0) {
      return;
    }

    let breakArray =
      form.getFieldValue('relatedBreaksDetails') != undefined
        ? form.getFieldValue('relatedBreaksDetails')
        : [];

    if (breakArray.length > 0) {
      await form.validateFields();
    }
    let breakList = breakArray.map((el) => {
      let formattedBreak = {
        breakInDate: el.breakInDate?.format('YYYY-MM-DD'),
        breakInTime: el.breakInTime?.format('HH:mm:ss'),
        breakOutDate: el.breakOutDate?.format('YYYY-MM-DD'),
        breakOutTime: el.breakOutTime?.format('HH:mm:ss'),
      };
      return formattedBreak;
    });

    if (props.accessLevel == 'admin') {
      handleOk(breakList);
    } else if (props.accessLevel == 'employee') {
      handleChange(breakList);
    }
  };

  const createBreake = async () => {
    let breakArray =
      form.getFieldValue('relatedBreaksDetails') != undefined
        ? form.getFieldValue('relatedBreaksDetails')
        : [];

    if (breakArray.length > 0) {
      await form.validateFields();
    }

    if (breakArray.length == 0) {
      message.error({
        content: intl.formatMessage({
          id: 'breakeEmpty',
          defaultMessage: 'Add atlease one breake before save',
        }),
        key,
      });
      return;
    }

    let conflictBrakesCount = 0;
    let dublicateBrakesCount = 0;
    let errorCount = 0;
    breakArray.forEach((el, index) => {
      let inCompleteDate =
        inDateModel?.format('YYYY-MM-DD') + ' ' + inTimeModel?.format('HH:mm:ss');
      let outCompleteDate =
        outDateModel?.format('YYYY-MM-DD') + ' ' + outTimeModel?.format('HH:mm:ss');
      let startDate = moment(inCompleteDate, 'YYYY-MM-DD hh:mm:ss A');
      let endDate = moment(outCompleteDate, 'YYYY-MM-DD hh:mm:ss A');

      let breakInDateTime =
        el.breakInDate?.format('YYYY-MM-DD') + ' ' + el.breakInTime?.format('HH:mm:ss');
      let compareBreakeInDate = moment(breakInDateTime, 'YYYY-MM-DD hh:mm:ss A');

      let breakeOutDateTime =
        el.breakOutDate?.format('YYYY-MM-DD') + ' ' + el.breakOutTime?.format('HH:mm:ss');
      let compareBreakOutDate = moment(breakeOutDateTime, 'YYYY-MM-DD hh:mm:ss A');

      if (compareBreakOutDate.isAfter(compareBreakeInDate)) {
        if (el.breakInTime != undefined && el.breakInDate != undefined) {
          if (
            compareBreakeInDate.isBetween(startDate, endDate) ||
            compareBreakeInDate.isSame(startDate) ||
            compareBreakeInDate.isSame(endDate)
          ) {
            form.setFields([
              {
                name: ['relatedBreaksDetails', key, 'breakInTime'],
                errors: [],
              },
            ]);
          } else {
            errorCount++;
            form.setFields([
              {
                name: ['relatedBreaksDetails', index, 'breakInTime'],
                errors: ['Invalid break in time'],
              },
            ]);
          }
        }

        if (el.breakOutTime != undefined && el.breakOutDate != undefined) {
          if (
            compareBreakOutDate.isBetween(startDate, endDate) ||
            compareBreakOutDate.isSame(startDate) ||
            compareBreakOutDate.isSame(endDate)
          ) {
            // form.setFieldsValue({
            //   relatedBreaksDetails: breakArr,
            // });
            form.setFields([
              {
                name: ['relatedBreaksDetails', key, 'breakOutTime'],
                errors: [],
              },
            ]);
          } else {
            errorCount++;
            form.setFields([
              {
                name: ['relatedBreaksDetails', index, 'breakOutTime'],
                errors: ['Invalid break out time'],
              },
            ]);
          }
        }
      } else {
        form.setFields([
          {
            name: ['relatedBreaksDetails', index, 'breakInTime'],
            errors: ['Should less than out time'],
          },
        ]);

        form.setFields([
          {
            name: ['relatedBreaksDetails', index, 'breakOutTime'],
            errors: ['Should greater than in time'],
          },
        ]);
        errorCount++;
      }

      breakArray.forEach((element: any, elementIndex) => {
        if (index != elementIndex) {
          let compareInDateTime =
            element.breakInDate?.format('YYYY-MM-DD') +
            ' ' +
            element.breakInTime?.format('HH:mm:ss');
          let compareInDate = moment(compareInDateTime, 'YYYY-MM-DD hh:mm:ss A');

          let compareOutDateTime =
            element.breakOutDate?.format('YYYY-MM-DD') +
            ' ' +
            element.breakOutTime?.format('HH:mm:ss');
          let compareOutDate = moment(compareOutDateTime, 'YYYY-MM-DD hh:mm:ss A');

          if (
            compareBreakOutDate.isBetween(compareInDate, compareOutDate) ||
            compareBreakeInDate.isBetween(compareInDate, compareOutDate)
          ) {
            conflictBrakesCount++;
          }

          if (
            compareBreakOutDate.isSame(compareOutDate) &&
            compareBreakeInDate.isSame(compareInDate)
          ) {
            dublicateBrakesCount++;
          }
        }
      });
    });

    if (errorCount > 0) {
      return;
    }

    if (conflictBrakesCount > 0) {
      message.error({
        content: intl.formatMessage({
          id: 'breakeConflicts',
          defaultMessage:
            'Added break records have some time conflicts please resolve them before save',
        }),
        key,
      });
      return;
    }

    if (dublicateBrakesCount > 0) {
      message.error({
        content: intl.formatMessage({
          id: 'breakeDuplocates',
          defaultMessage:
            'Added break records have some duplicates please resolve them before save',
        }),
        key,
      });
      return;
    }

    let breakList = breakArray.map((el) => {
      let formattedBreak = {
        id: el.id,
        breakInDate: el.breakInDate?.format('YYYY-MM-DD'),
        breakInTime: el.breakInTime?.format('HH:mm:ss'),
        breakOutDate: el.breakOutDate?.format('YYYY-MM-DD'),
        breakOutTime: el.breakOutTime?.format('HH:mm:ss'),
      };
      return formattedBreak;
    });
    setLoadingModelRequest(true);
    updateBreakeRecords(breakList);
  };

  const handleChange = async (breakList: any) => {
    clearModelValidationStates();

    const params = {
      shiftId: shiftIdModel,
      summaryId: summaryIdModel,
      shiftDate: dateModel,
      inDate: inDateModel?.format('YYYY-MM-DD'),
      outDate: outDateModel?.format('YYYY-MM-DD'),
      inTime: inTimeModel?.format('HH:mm:ss'),
      outTime: outTimeModel?.format('HH:mm:ss'),
      reason: reasonModel,
      breakDetails: breakList,
    };

    const invalidParams = validatedDates(params);

    if (!invalidParams) {
      requestTimeChange(params)
        .then((response: any) => {
          setLoadingModelRequest(false);
          setDisableModelButtons(false);

          if (response.data) {
            callGetAttendanceSheetData(
              tableState.current,
              tableState.pageSize,
              tableState.sortValue,
            );
            handleCancel();
            message.success({
              content: intl.formatMessage({
                id: 'updatedTimeChange',
                defaultMessage: 'Your request has been submitted.',
              }),
              key,
            });
          }

          if (response.error) {
            message.error({
              content: intl.formatMessage({
                id: 'rejectedTimeChange',
                defaultMessage: 'Failed to save the time change.',
              }),
              key,
            });
          }
        })
        .catch((error: APIResponse) => {
          setLoadingModelRequest(false);
          setDisableModelButtons(false);
          message.error({
            content:
              error.message ??
              intl.formatMessage({
                id: 'rejectedTimeChange',
                defaultMessage: 'Failed to save the time change.',
              }),
            key,
          });
        });
    }
  };

  const updateBreakeRecords = async (breakList: any) => {
    clearModelValidationStates();
    setLoading(true);
    const params = {
      shiftId: shiftIdModel,
      summaryId: summaryIdModel,
      shiftDate: dateModel,
      breakDetails: breakList,
      employeeId: employeeIdModel,
    };

    requestUpdateBreaks(params)
      .then((response: any) => {
        setLoadingModelRequest(false);
        setDisableModelButtons(false);

        if (response.data) {
          setIsModalVisible(false);
          handleSave({ ...response.data });
          let tempObj = {
            in: response.data.in.time,
            id: response.data.id,
            out: response.data.out.time,
            outDate: response.data.outDate,
            shiftId: response.data.shiftId,
            shift: response.data.shift,
          };
          handleIntialData({ ...tempObj });
          
          message.success({
            content: intl.formatMessage({
              id: 'updatedTimeChange',
              defaultMessage: 'Your break records successfully submitted.',
            }),
            key,
          });
        
        }

        if (response.error) {
          message.error({
            content: intl.formatMessage({
              id: 'rejectedTimeChange',
              defaultMessage: 'Failed to save the time change.',
            }),
            key,
          });
        }
        setLoading(false);
      })
      .catch((error: APIResponse) => {
        setLoading(false);
        setLoadingModelRequest(false);
        setDisableModelButtons(false);
        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'rejectedTimeChange',
              defaultMessage: 'Failed to save the time change.',
            }),
          key,
        });
      });
  };

  const disableDates = (current: any) => {
    let firstDate = moment(dateModel).subtract(0, 'd').format('YYYY-MM-DD');
    let secondDate = moment(dateModel).add(1, 'd').format('YYYY-MM-DD');
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');

    const isNextDay = moment(compareDate, 'YYYY-MM-DD') >= moment(firstDate, 'YYYY-MM-DD');
    const isPreviousDay = moment(compareDate, 'YYYY-MM-DD') <= moment(secondDate, 'YYYY-MM-DD');
    const isValidDate = isNextDay && isPreviousDay;

    return !isValidDate;
  };

  const disableDatesForBreakOutDate = (current: any) => {
    let firstDate = moment(dateModel).subtract(0, 'd').format('YYYY-MM-DD');
    let secondDate = moment(dateModel).add(1, 'd').format('YYYY-MM-DD');
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');

    let outDate = moment(outDateModel).format('YYYY-MM-DD');

    const isNextDay = moment(compareDate, 'YYYY-MM-DD') >= moment(firstDate, 'YYYY-MM-DD');
    const isPreviousDay = moment(compareDate, 'YYYY-MM-DD') <= moment(secondDate, 'YYYY-MM-DD');
    const isEqualToOutDate = moment(compareDate, 'YYYY-MM-DD').isSame(outDate);

    if (isNextDay && isPreviousDay) {
      if (moment(outDate, 'YYYY-MM-DD').isSame(secondDate)) {
        const isValidDate = isNextDay && isPreviousDay;
        return !isValidDate;
      }

      if (moment(outDate, 'YYYY-MM-DD').isSame(firstDate)) {
        const isValidDate = isNextDay && isPreviousDay && isEqualToOutDate;
        return !isValidDate;
      }
    } else {
      const isValidDate = isNextDay && isPreviousDay;
      return !isValidDate;
    }
  };

  const validatedDates = (params: any): boolean => {
    var invalidParams: boolean = false;
    const inDateTimeString = params.inDate + ' ' + params.inTime;
    const outDateTimeString = params.outDate + ' ' + params.outTime;
    const inDateTime = new Date(inDateTimeString);
    const outDateTime = new Date(outDateTimeString);

    if (!params.inDate) {
      setValidateStatusInDate('error');
      setHelpInDate('Please set valid in date');
      invalidParams = true;
      setLoadingModelRequest(false);
      setDisableModelButtons(false);
    }

    if (!params.outDate) {
      setValidateStatusOutDate('error');
      setHelpOutDate('Please set valid out date');
      invalidParams = true;
      setLoadingModelRequest(false);
      setDisableModelButtons(false);
    }

    if (params.inTime === 'Invalid date' || !params.inTime) {
      setValidateStatusInTime('error');
      setHelpInTime('Please set valid in time');
      invalidParams = true;
      setLoadingModelRequest(false);
      setDisableModelButtons(false);
    }

    if (params.outTime === 'Invalid date' || !params.outTime) {
      setValidateStatusOutTime('error');
      setHelpOutTime('Please set valid out time');
      invalidParams = true;
      setLoadingModelRequest(false);
      setDisableModelButtons(false);
    }

    if (inDateTime >= outDateTime) {
      message.error({
        content: intl.formatMessage({
          id: 'incorrectInOutDateTime',
          defaultMessage: 'Out date time need be greater than in date time.',
        }),
        key,
      });
      invalidParams = true;
      setLoadingModelRequest(false);
      setDisableModelButtons(false);
    }

    return invalidParams;
  };

  return (
    <ProCard direction="column" ghost gutter={[0, 16]} style={{ padding: 0, margin: 0 }}>
      <Row style={{ width: '100%' }}>
        <Col className={'attendanceSearchArea'} style={{ width: '100%' }}>
          <ProForm
            id={'searchForm'}
            style={{marginTop: 10 }}
            layout="inline"
            form={searchForm}
            submitter={{
              resetButtonProps: {
                style: {
                  display: 'none',
                },
              },
              render: (props, doms) => {
                return [
                  <>
                    <Col style={{marginLeft: 10}}>
                      <Tooltip
                        title={intl.formatMessage({
                          id: 'tooltip.search',
                          defaultMessage: 'search',
                        })}
                      >
                        <Button
                          type="primary"
                          icon={<SearchOutlined />}
                          size="middle"
                          onClick={async () => {
                            await searchForm.validateFields();

                            setLoading(true);
                            setShowDataTable(true);

                            if (selectedEmployee && fromDate && toDate) {
                              callGetAttendanceSheetData(1, 100).then(() => {});
                            }
                          }}
                        />
                      </Tooltip>
                    </Col>
                    <Access
                      accessible={
                        (hasPermitted('attendance-manager-access') ||
                          hasPermitted('attendance-admin-access')) &&
                        othersView
                      }
                    >
                      <Col className={styles.excelCol}>
                        <Tooltip
                          title={intl.formatMessage({
                            id: 'tooltip.excel',
                            defaultMessage: 'Download Excel',
                          })}
                        >
                          <Button
                            type="primary"
                            icon={<DownloadOutlined />}
                            size="middle"
                            loading={loadingExcelDownload}
                            onClick={async () => {
                              await searchForm.validateFields();
                              callDownloadTeamAttendance();
                            }}
                          />
                        </Tooltip>
                      </Col>
                    </Access>
                  </>
                ];
              },
            }}
          >
            <Row >
              <Col span={12}>
                <ProFormDateRangePicker
                  name="searchDateRange"
                  className={styles.rangePicker}
                  ranges={{
                    Today: [moment(), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                  }}
                  format="DD-MM-YYYY"
                  onChange={onChange}
                  placeholder={[intl.formatMessage({
                    id :'attendance.startDate',
                    defaultMessage :'Start Date'
                  }), intl.formatMessage({
                    id :'attendance.endDate',
                    defaultMessage :'End Date'
                  })]}
                  rules={
                    [
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'employee.attendance.search.dateRange.required',
                          defaultMessage: 'Required',
                        })
                      }
                    ]
                  }
                />
              </Col>

              <Access
                accessible={
                  (hasPermitted('attendance-manager-access') ||
                    hasPermitted('attendance-admin-access')) &&
                  othersView
                }
              >
                <Col className={styles.employeeCol} span={12}>
                  <ProFormSelect
                    name="select"
                    placeholder={intl.formatMessage({
                      id: 'employee.placeholder',
                      defaultMessage: 'Search Employee',
                    })}
                    showSearch
                    options={selectorEmployees}
                    fieldProps={{
                      optionItemRender(item) {
                        return item.label;
                      },
                      onChange: (value) => {
                        setSelectedEmployee(value);
                        setAttendanceSheetData([]);
                      },
                    }}
                    rules={
                      [
                        {
                          required: true,
                          message: intl.formatMessage({
                            id: 'employee.attendance.search.employee.required',
                            defaultMessage: 'Required',
                          })
                        }
                      ]
                    }
                  />
                </Col>
              </Access>
            </Row>
          </ProForm>
          <Card style={{ marginTop: 20 ,width: '100%', height: showDataTable ? 690 : 575 }}>
            <ConfigProvider locale={en_US}>
              <Space direction="vertical" size={25} style={{ width: '100%' }}>
                {showDataTable ? (
                  <>
                    {
                      <Row>
                        <Col span={24}>
                          <div className={styles.spinCol}>
                            <Spin size="large" spinning={loading}>
                              <Row className={'adminAttendanceTable'}>
                                <ProTable<AttendanceItem>
                                  columns={cols as ColumnTypes}
                                  scroll={ !isMaintainOt && attendanceSheetData.length > 10 ?  {y: 500 } : !isMaintainOt && attendanceSheetData.length < 10 ? undefined : isMaintainOt && attendanceSheetData.length > 10 ? { x: '100vw', y: 500 } : isMaintainOt && attendanceSheetData.length < 10 ? { x: '100vw'} : undefined}
                                  components={components}
                                  rowClassName={() => 'editableRow'}
                                  actionRef={actionRef}
                                  dataSource={attendanceSheetData}
                                  request={async (params = { current: 1, pageSize: 100 }, sort, filter) => {
                                    const sortValue = sort?.name
                                      ? { name: 'name', order: sort?.name === 'ascend' ? 'ASC' : 'DESC' }
                                      : { name: 'date', order: sort?.date === 'ascend' ? 'ASC' : 'DESC' };
                    
                                    const tableParams = {
                                      current: params?.current,
                                      pageSize: params?.pageSize,
                                      sortValue: sortValue,
                                    };
                                    setTableState(tableParams);
                    
                                    await callGetAttendanceSheetData(params?.current, params?.pageSize, sortValue);
                                    return attendanceSheetData;
                                  }}
                                  headerTitle={
                                    <Row className='attendanceHeaderTitle'>
                                      <Space size={[8, 16]} wrap>
                                        <>
                                          <p className={styles.dayTypeIcon}>
                                            <Image src={LateIcon} preview={false} height={15} />
                                          </p>
                                          <p className={styles.dayTypeContent}>Late In</p>
                                        </>
                                        <>
                                          <p className={styles.dayTypeIcon}>
                                            <Image src={EarlyIcon} height={15} preview={false} />
                                          </p>
                                          <p className={styles.dayTypeContent}>Early Departure</p>
                                        </>
                                        <>
                                          <Badge color="#44A4ED" text={'Absent'}/>
                                        </>
                                        <>
                                          <Badge color="#ED4444" text={'Late'}/>
                                        </>

                                        {dayTypesData.map((item) => (
                                          <>
                                            <Badge color={item.typeColor} text={item.name}/>
                                          </>
                                        ))}
                                      </Space>
                                    </Row>
                                  }
                                  pagination={false}
                                  search={false}
                                  style={{ width: '100%', fontSize: 10 }}
                                />
                              </Row>
                            </Spin>
                          </div>
                        </Col>
                      </Row>
                    }
                  </>
                ) : (
                  <Row style={{ marginTop: 100 }} justify={'center'}>
                    <Result
                      title={
                        <span style={{ color: '#C8C8C8' }}>
                          {'Search Attendance By Date And Employee'}
                        </span>
                      }
                      icon={<InfoCircleFilled style={{ color: '#C8C8C8' }} />}
                    />
                  </Row>
                )}

                <Modal
                  title={<FormattedMessage id="break_details" defaultMessage="Break Details" />}
                  visible={isModalVisible}
                  className={'breakModal'}
                  width={
                    hasPermitted('attendance-manager-access') && othersView && !adminView
                      ? 600
                      : 800
                  }
                  onCancel={handleCancel}
                  centered
                  footer={[
                    <>
                      <Button key="back" onClick={handleCancel} disabled={disableModelButtons}>
                        Cancel
                      </Button>
                      <Button
                        key="submit"
                        type="primary"
                        loading={loadingModelRequest}
                        disabled={disableModelButtons}
                        onClick={createBreake}
                      >
                        <FormattedMessage id="Change" defaultMessage="Change" />
                      </Button>
                    </>,
                  ]}
                >
                  {loadingModel ? (
                    <Spin size="large" spinning={loadingModel} />
                  ) : (
                    <>
                      <Access
                        accessible={props.accessLevel == 'admin' || props.accessLevel == 'employee'}
                      >
                        <Form form={form} layout="vertical" style={{ width: '100%' }}>
                          <Row>
                            <Col span={4} style={{ marginBottom: 10 }}>
                              Date
                            </Col>
                            <Col span={8}>{shiftDate}</Col>
                          </Row>
                          <Row style={{ marginBottom: 10 }}>
                            <Col span={4}>Shift</Col>
                            <Col span={8}>{shift}</Col>
                          </Row>
                          <Row style={{ marginBottom: 40 }}>
                            <Col span={4}>Total Breaks</Col>
                            <Col span={8}>
                              <a href="">{totalBreake}</a>
                            </Col>
                          </Row>
                          <Row style={{ marginBottom: 5 }}>
                            <Col style={{ paddingBottom: 8, fontSize: 16, fontWeight: 'bold' }}>
                              <FormattedMessage id="breakDetails" defaultMessage="Breaks" />
                            </Col>
                          </Row>
                          <Row>
                            <Row style={{ width: '100%', marginBottom: 50 }}>
                              <Form.List name="relatedBreaksDetails">
                                {(fields, { add, remove }) => (
                                  <>
                                    <div
                                      style={{
                                        overflowY: 'auto',
                                        maxHeight: 280,
                                        marginBottom: 20,
                                      }}
                                    >
                                      {fields.map(({ key, name, ...restField }) => (
                                        <Space
                                          key={key}
                                          style={{ display: 'flex', marginBottom: 8 }}
                                          align="baseline"
                                        >
                                          <Row>
                                            <Col style={{ marginRight: 30 }}>
                                              <Form.Item
                                                name={[name, 'breakInDate']}
                                                label="Break Start"
                                                rules={[{ required: true, message: 'Required' }]}
                                              >
                                                <DatePicker
                                                  disabled
                                                  value={inDateModel}
                                                  onChange={(date, dateString) => {}}
                                                  disabledDate={disableDates}
                                                />
                                              </Form.Item>
                                            </Col>
                                            <Col style={{ marginRight: 20, marginTop: 30 }}>
                                              <Form.Item
                                                name={[name, 'breakInTime']}
                                                rules={[{ required: true, message: 'Required' }]}
                                              >
                                                <TimePicker
                                                  disabled={
                                                    inDateModel &&
                                                    inTimeModel &&
                                                    outDateModel &&
                                                    outTimeModel &&
                                                    form.getFieldValue([
                                                      'relatedBreaksDetails',
                                                      key,
                                                      'id',
                                                    ]) == 'new'
                                                      ? false
                                                      : true
                                                  }
                                                  // use12Hours
                                                  format="HH:mm"
                                                  // value={inTimeModel}
                                                  onSelect={(timeString) => {
                                                    let breakArr =
                                                      form.getFieldValue('relatedBreaksDetails');
                                                    breakArr[key]['breakInTime'] = moment(
                                                      timeString,
                                                      'hh:mm:ss A',
                                                    );

                                                    let inCompleteDate =
                                                      inDateModel?.format('YYYY-MM-DD') +
                                                      ' ' +
                                                      inTimeModel?.format('HH:mm:ss');
                                                    let outCompleteDate =
                                                      outDateModel?.format('YYYY-MM-DD') +
                                                      ' ' +
                                                      outTimeModel?.format('HH:mm:ss');

                                                    if (
                                                      breakArr[key]['breakInTime'] != undefined &&
                                                      breakArr[key]['breakInDate'] != undefined
                                                    ) {
                                                      let compareDateTime =
                                                        breakArr[key]['breakInDate']?.format(
                                                          'YYYY-MM-DD',
                                                        ) +
                                                        ' ' +
                                                        breakArr[key]['breakInTime']?.format(
                                                          'HH:mm:ss',
                                                        );

                                                      var compareDate = moment(
                                                        compareDateTime,
                                                        'YYYY-MM-DD hh:mm:ss A',
                                                      );
                                                      var startDate = moment(
                                                        inCompleteDate,
                                                        'YYYY-MM-DD hh:mm:ss A',
                                                      );
                                                      var endDate = moment(
                                                        outCompleteDate,
                                                        'YYYY-MM-DD hh:mm:ss A',
                                                      );

                                                      if (
                                                        compareDate.isBetween(startDate, endDate) ||
                                                        compareDate.isSame(startDate) ||
                                                        compareDate.isSame(endDate)
                                                      ) {
                                                        form.setFieldsValue({
                                                          relatedBreaksDetails: breakArr,
                                                        });
                                                        form.setFields([
                                                          {
                                                            name: [
                                                              'relatedBreaksDetails',
                                                              key,
                                                              'breakInTime',
                                                            ],
                                                            errors: [],
                                                          },
                                                        ]);

                                                        if (breakArr[key]['breakOutDate'] != undefined &&
                                                        breakArr[key]['breakOutTime'] != undefined) {
                                                          let compareOutDateTime =
                                                          breakArr[key]['breakOutDate']?.format(
                                                            'YYYY-MM-DD',
                                                          ) +
                                                          ' ' +
                                                          breakArr[key]['breakOutTime']?.format(
                                                            'HH:mm:ss',
                                                          );

                                                          let compareOutDate = moment(
                                                            compareOutDateTime,
                                                            'YYYY-MM-DD hh:mm:ss A',
                                                          );

                                                          if (compareOutDate.isAfter(compareDate)) {
                                                            form.setFields([
                                                              {
                                                                name: [
                                                                  'relatedBreaksDetails',
                                                                  key,
                                                                  'breakOutTime',
                                                                ],
                                                                errors: [],
                                                              },
                                                            ]);

                                                            form.setFields([
                                                              {
                                                                name: [
                                                                  'relatedBreaksDetails',
                                                                  key,
                                                                  'breakOutDate',
                                                                ],
                                                                errors: [],
                                                              },
                                                            ]);
                                                          }

                                                        }
                                                      } else {
                                                        form.setFields([
                                                          {
                                                            name: [
                                                              'relatedBreaksDetails',
                                                              key,
                                                              'breakInTime',
                                                            ],
                                                            errors: ['Invalid break in time'],
                                                          },
                                                        ]);
                                                      }
                                                    } else {
                                                      if (
                                                        breakArr[key]['breakOutDate'] == undefined
                                                      ) {
                                                        form.setFields([
                                                          {
                                                            name: [
                                                              'relatedBreaksDetails',
                                                              key,
                                                              'breakInDate',
                                                            ],
                                                            errors: ['Required'],
                                                          },
                                                        ]);
                                                      }
                                                    }
                                                  }}
                                                />
                                              </Form.Item>
                                            </Col>
                                            <Col
                                              style={{
                                                paddingRight: 20,
                                                marginLeft: 0,
                                                marginTop: 30,
                                              }}
                                            >
                                              -
                                            </Col>
                                            <Col style={{ marginRight: 30 }}>
                                              <Form.Item
                                                name={[name, 'breakOutDate']}
                                                label="Break End"
                                                rules={[{ required: true, message: 'Required' }]}
                                              >
                                                <DatePicker
                                                  disabled={
                                                    inDateModel &&
                                                    inTimeModel &&
                                                    outDateModel &&
                                                    outTimeModel &&
                                                    form.getFieldValue([
                                                      'relatedBreaksDetails',
                                                      key,
                                                      'id',
                                                    ]) == 'new'
                                                      ? false
                                                      : true
                                                  }
                                                  onSelect={(dateString) => {}}
                                                  disabledDate={disableDatesForBreakOutDate}
                                                />
                                              </Form.Item>
                                            </Col>
                                            <Col style={{ marginTop: 30 }}>
                                              <Form.Item
                                                name={[name, 'breakOutTime']}
                                                rules={[{ required: true, message: 'Required' }]}
                                              >
                                                <TimePicker
                                                  disabled={
                                                    inDateModel &&
                                                    inTimeModel &&
                                                    outDateModel &&
                                                    outTimeModel &&
                                                    form.getFieldValue([
                                                      'relatedBreaksDetails',
                                                      key,
                                                      'id',
                                                    ]) == 'new'
                                                      ? false
                                                      : true
                                                  }
                                                  // use12Hours
                                                  format="HH:mm"
                                                  // value={inTimeModel}
                                                  onSelect={(timeString) => {
                                                    let breakArr =
                                                      form.getFieldValue('relatedBreaksDetails');
                                                    breakArr[key]['breakOutTime'] = moment(
                                                      timeString,
                                                      'hh:mm:ss A',
                                                    );

                                                    let inCompleteDate =
                                                      inDateModel?.format('YYYY-MM-DD') +
                                                      ' ' +
                                                      inTimeModel?.format('HH:mm:ss');
                                                    let outCompleteDate =
                                                      outDateModel?.format('YYYY-MM-DD') +
                                                      ' ' +
                                                      outTimeModel?.format('HH:mm:ss');

                                                    // let compareDateTime = null;
                                                    if (
                                                      breakArr[key]['breakOutTime'] != undefined &&
                                                      breakArr[key]['breakOutDate'] != undefined
                                                    ) {
                                                      let compareDateTime =
                                                        breakArr[key]['breakOutDate']?.format(
                                                          'YYYY-MM-DD',
                                                        ) +
                                                        ' ' +
                                                        breakArr[key]['breakOutTime']?.format(
                                                          'HH:mm:ss',
                                                        );

                                                      var compareDate = moment(
                                                        compareDateTime,
                                                        'YYYY-MM-DD hh:mm:ss A',
                                                      );
                                                      var startDate = moment(
                                                        inCompleteDate,
                                                        'YYYY-MM-DD hh:mm:ss A',
                                                      );
                                                      var endDate = moment(
                                                        outCompleteDate,
                                                        'YYYY-MM-DD hh:mm:ss A',
                                                      );

                                                      if (
                                                        compareDate.isBetween(startDate, endDate) ||
                                                        compareDate.isSame(startDate) ||
                                                        compareDate.isSame(endDate)
                                                      ) {
                                                        form.setFieldsValue({
                                                          relatedBreaksDetails: breakArr,
                                                        });
                                                        form.setFields([
                                                          {
                                                            name: [
                                                              'relatedBreaksDetails',
                                                              key,
                                                              'breakOutTime',
                                                            ],
                                                            errors: [],
                                                          },
                                                        ]);

                                                        if (breakArr[key]['breakInDate'] != undefined &&
                                                        breakArr[key]['breakInTime'] != undefined) {
                                                          let compareInDateTime =
                                                          breakArr[key]['breakInDate']?.format(
                                                            'YYYY-MM-DD',
                                                          ) +
                                                          ' ' +
                                                          breakArr[key]['breakInTime']?.format(
                                                            'HH:mm:ss',
                                                          );

                                                          let compareInDate = moment(
                                                            compareInDateTime,
                                                            'YYYY-MM-DD hh:mm:ss A',
                                                          );

                                                          if (compareDate.isAfter(compareInDateTime)) {
                                                            form.setFields([
                                                              {
                                                                name: [
                                                                  'relatedBreaksDetails',
                                                                  key,
                                                                  'breakInTime',
                                                                ],
                                                                errors: [],
                                                              },
                                                            ]);

                                                            form.setFields([
                                                              {
                                                                name: [
                                                                  'relatedBreaksDetails',
                                                                  key,
                                                                  'breakInDate',
                                                                ],
                                                                errors: [],
                                                              },
                                                            ]);
                                                          }

                                                        }
                                                      } else {
                                                        form.setFields([
                                                          {
                                                            name: [
                                                              'relatedBreaksDetails',
                                                              key,
                                                              'breakOutTime',
                                                            ],
                                                            errors: ['Invalid break out time'],
                                                          },
                                                        ]);
                                                      }
                                                    } else {
                                                      if (
                                                        breakArr[key]['breakOutDate'] == undefined
                                                      ) {
                                                        form.setFields([
                                                          {
                                                            name: [
                                                              'relatedBreaksDetails',
                                                              key,
                                                              'breakOutDate',
                                                            ],
                                                            errors: ['Required'],
                                                          },
                                                        ]);
                                                      }
                                                    }
                                                  }}
                                                />
                                              </Form.Item>
                                            </Col>
                                            <Col style={{ marginLeft: 10, marginTop: 35 }}>
                                              {form.getFieldValue([
                                                'relatedBreaksDetails',
                                                key,
                                                'id',
                                              ]) == 'new' ? (
                                                <MinusCircleOutlined
                                                  onClick={() => {
                                                    // remove(name)
                                                    let newBreaks = [];
                                                    let breaks = form.getFieldValue(
                                                      'relatedBreaksDetails',
                                                    )
                                                      ? form.getFieldValue('relatedBreaksDetails')
                                                      : [];

                                                    breaks.map((el, index) => {
                                                      if (index != key) {
                                                        newBreaks.push(el);
                                                      }
                                                    });
                                                    form.setFieldsValue({
                                                      relatedBreaksDetails: newBreaks,
                                                    });
                                                  }}
                                                />
                                              ) : (
                                                <></>
                                              )}
                                            </Col>
                                          </Row>
                                        </Space>
                                      ))}
                                    </div>
                                    <Row>
                                      <Col style={{ width: 750 }}>
                                        <Button
                                          disabled={
                                            inDateModel &&
                                            inTimeModel &&
                                            outDateModel &&
                                            outTimeModel
                                              ? false
                                              : true
                                          }
                                          type="dashed"
                                          style={{
                                            backgroundColor: '#E4eff1',
                                            borderColor: '#E4eff1',
                                            borderRadius: 6,
                                          }}
                                          onClick={() => {
                                            // add();
                                            let breaks = form.getFieldValue('relatedBreaksDetails')
                                              ? form.getFieldValue('relatedBreaksDetails')
                                              : [];
                                            let tempObj = {
                                              id: 'new',
                                              breakInDate: inDateModel,
                                              breakInTime: null,
                                              breakOutDate: null,
                                              breakOutTime: null,
                                            };
                                            breaks.push(tempObj);

                                            form.setFieldsValue({ relatedBreaksDetails: breaks });
                                          }}
                                          block
                                          icon={<PlusOutlined />}
                                        >
                                          Add Break
                                        </Button>
                                      </Col>
                                    </Row>
                                  </>
                                )}
                              </Form.List>
                            </Row>
                          </Row>
                          <Row></Row>
                        </Form>
                      </Access>
                      <Access
                        accessible={
                          props.accessLevel == 'manager' &&
                          othersView &&
                          !loadingModel &&
                          !adminView
                        }
                      >
                        <TimeChangeRequest
                          scope={relateScope}
                          employeeId={selectedRawEmployeeId}
                          employeeFullName={employeeName}
                          timeChangeRequestData={timeChangeDataSet}
                        ></TimeChangeRequest>
                      </Access>
                    </>
                  )}
                </Modal>
              </Space>
            </ConfigProvider>
          </Card>
        </Col>
      </Row>
    </ProCard>
  );
};

export default AttendanceAdminTableView;
