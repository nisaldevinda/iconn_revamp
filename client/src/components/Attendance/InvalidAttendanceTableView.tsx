import React, { useEffect, useRef, useState, useContext } from 'react';
import { InputRef, Select } from 'antd';
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
} from 'antd';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import _, { cond } from 'lodash';

import {
  updateInvalidAttendences,
  getInvalidAttendanceSheetAdminData,
  getRelatedBreakes,
} from '@/services/attendance';
import LateIcon from '../../assets/attendance/icon-clock-red.svg';
import EarlyIcon from '../../assets/attendance/icon-clock-orange.svg';
import NonWorkingDayIcon from '../../assets/attendance/icon-circle-black.svg';
import HolidayIcon from '../../assets/attendance/icon-circle-orange.svg';
import AbsentIcon from '../../assets/attendance/icon-circle-red.svg';
import { APIResponse } from '@/utils/request';
import { getEmployeeList, getOtPayTypeList } from '@/services/dropdown';
import { getAllWorkShifts } from '@/services/workShift';
import en_US from 'antd/lib/locale-provider/en_US';
import styles from './attendance.less';
import './index.css';
import ProCard from '@ant-design/pro-card';
import type { FormInstance } from 'antd/es/form';

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
  setChangedRecordIds: any;
  changedRecordIds: any;
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
  setChangedRecordIds,
  changedRecordIds,
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
      const { data } = response.data;
      const workShiftsArray = data.map((workshift: any) => {
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
        return;
      }

      if (dataIndex == 'outDateAndTimeTwo' && !record.slot1.outDate && !record.slot1.outTime) {
        let key = 'Error';
        message.error({
          content: 'Please add out date & time of slot one before edit out date & time of slot two',
          key,
          duration: 8,
        });
        setCurrentEditingRow(null);
        return;
      }

      if (dataIndex == 'outDateAndTimeTwo' && !record.slot2.inDate && !record.slot2.inTime) {
        let key = 'Error';
        message.error({
          content: 'Please add in date & time of slot two before edit out date & time of slot two',
          key,
          duration: 8,
        });
        setCurrentEditingRow(null);
        return;
      }

      if (dataIndex == 'inDateAndTimeTwo' && !record.slot1.outDate && !record.slot1.outTime) {
        let key = 'Error';
        message.error({
          content: 'Please add out date & time of slot one before edit in date & time of slot two',
          key,
          duration: 8,
        });
        setCurrentEditingRow(null);
        return;
      }

      if (dataIndex == 'outDateAndTimeOne' && !record.slot1.inDate && !record.slot1.inTime) {
        let key = 'Error';
        message.error({
          content: 'Please add in date & time of slot one before edit out date & time of slot one',
          key,
          duration: 8,
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
      case 'outDateAndTimeOne':
        let outDateAndTimeOne =
          moment(record.slot1.outDate, 'YYYY-MM-DD').isValid() &&
          moment(record.slot1.outTime, 'hh:mm A').isValid()
            ? moment(record.slot1.outDate + ' ' + record.slot1.outTime, 'YYYY-MM-DD hh:mm A')
            : null;
        form.setFieldsValue({ [dataIndex]: outDateAndTimeOne });
        break;
      case 'outDateAndTimeTwo':
        let outDateAndTimeTwo =
          moment(record.slot2.outDate, 'YYYY-MM-DD').isValid() &&
          moment(record.slot2.outTime, 'hh:mm A').isValid()
            ? moment(record.slot2.outDate + ' ' + record.slot2.outTime, 'YYYY-MM-DD hh:mm A')
            : null;
        form.setFieldsValue({ [dataIndex]: outDateAndTimeTwo });
        break;
      case 'inDateAndTimeOne':
        let inDateAndTimeOne =
          moment(record.slot1.inDate, 'YYYY-MM-DD').isValid() &&
          moment(record.slot1.inTime, 'hh:mm A').isValid()
            ? moment(record.slot1.inDate + ' ' + record.slot1.inTime, 'YYYY-MM-DD hh:mm A')
            : null;
        form.setFieldsValue({ [dataIndex]: inDateAndTimeOne });
        break;
      case 'inDateAndTimeTwo':
        let inDateAndTimeTwo =
          moment(record.slot2.inDate, 'YYYY-MM-DD').isValid() &&
          moment(record.slot2.inTime, 'hh:mm A').isValid()
            ? moment(record.slot2.inDate + ' ' + record.slot2.inTime, 'YYYY-MM-DD hh:mm A')
            : null;
        form.setFieldsValue({ [dataIndex]: inDateAndTimeTwo });
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

  const disableDatesForEditableInDateTime = (current: any) => {
    let firstDate = moment(recordInDateModel).subtract(0, 'd').format('YYYY-MM-DD');
    let secondDate = moment(recordInDateModel).add(1, 'd').format('YYYY-MM-DD');
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    const isEqualToOutDate = moment(compareDate, 'YYYY-MM-DD').isSame(recordInDateModel);

    const isNextDay = moment(compareDate, 'YYYY-MM-DD') >= moment(firstDate, 'YYYY-MM-DD');
    const isPreviousDay = moment(compareDate, 'YYYY-MM-DD') <= moment(secondDate, 'YYYY-MM-DD');
    const isValidDate = isEqualToOutDate ? true : false;

    return !isValidDate;
  };

  const checkInDateIsValid = (current: any) => {
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    const isEqualToInDate = moment(compareDate, 'YYYY-MM-DD').isSame(recordInDateModel);

    return isEqualToInDate;
  };

  const checkOutDateIsValid = (current: any) => {
    let secondDate = moment(recordInDateModel).add(1, 'd').format('YYYY-MM-DD');
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    const isEqualToInDate = moment(compareDate, 'YYYY-MM-DD').isSame(recordInDateModel);
    const isEqualToNextDate = moment(compareDate, 'YYYY-MM-DD').isSame(secondDate);

    return isEqualToInDate || isEqualToNextDate;
  };

  const validatedRow = (record: any): boolean => {
    let invalidParam = false;
    if (!record.slot1.inTime || !record.slot1.inDate) {
      invalidParam = true;
    } else {
      if (
        !moment(record.slot1.inTime, 'hh:mm A').isValid() ||
        !moment(record.slot1.inDate, 'YYYY-MM-DD').isValid()
      ) {
        invalidParam = true;
      } else {
        if (!record.slot1.outTime || !record.slot1.outDate) {
          invalidParam = true;
        } else {
          if (
            !moment(record.slot1.outTime, 'hh:mm A').isValid() ||
            !moment(record.slot1.outDate, 'YYYY-MM-DD').isValid()
          ) {
            invalidParam = true;
          } else {
            let slot1OutDateTime = moment(
              record.slot1.outDate + ' ' + record.slot1.outTime,
              'YYYY-MM-DD hh:mm A',
            );
            let slot1InDateTime = moment(
              record.slot1.inDate + ' ' + record.slot1.inTime,
              'YYYY-MM-DD hh:mm A',
            );

            if (!slot1OutDateTime.isSameOrAfter(slot1InDateTime)) {
              invalidParam = true;
            } else {
              if (
                !(
                  !record.slot2.inTime &&
                  !record.slot2.inDate &&
                  !record.slot2.outTime &&
                  !record.slot2.outDate
                )
              ) {
                if (!record.slot2.inTime || !record.slot2.inDate) {
                  invalidParam = true;
                } else {
                  if (
                    !moment(record.slot2.inTime, 'hh:mm A').isValid() ||
                    !moment(record.slot2.inDate, 'YYYY-MM-DD').isValid()
                  ) {
                    invalidParam = true;
                  } else {
                    if (!record.slot2.outTime || !record.slot2.outDate) {
                      invalidParam = true;
                    } else {
                      if (
                        !moment(record.slot2.outTime, 'hh:mm A').isValid() ||
                        !moment(record.slot2.outDate, 'YYYY-MM-DD').isValid()
                      ) {
                        invalidParam = true;
                      } else {
                        let slot2OutDateTime = moment(
                          record.slot2.outDate + ' ' + record.slot2.outTime,
                          'YYYY-MM-DD hh:mm A',
                        );
                        let slot2InDateTime = moment(
                          record.slot2.inDate + ' ' + record.slot2.inTime,
                          'YYYY-MM-DD hh:mm A',
                        );

                        if (!slot2OutDateTime.isSameOrAfter(slot2InDateTime)) {
                          invalidParam = true;
                        } else {
                          if (!slot2InDateTime.isSameOrAfter(slot1InDateTime)) {
                            invalidParam = true;
                          } else {
                            if (!slot2OutDateTime.isSameOrAfter(slot1InDateTime)) {
                              invalidParam = true;
                            } else {
                              if (!slot2InDateTime.isSameOrAfter(slot1OutDateTime)) {
                                invalidParam = true;
                              } else {
                                if (!slot2OutDateTime.isSameOrAfter(slot1OutDateTime)) {
                                  invalidParam = true;
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return invalidParam;
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
        isChanged: record.isChanged,
        slot1: record.slot1,
        slot2: record.slot2,
        hasErrors: record.hasErrors,
      };

      if (values.hasOwnProperty('outDateAndTimeOne')) {
        let outDate = values.outDateAndTimeOne
          ? values.outDateAndTimeOne.format('YYYY-MM-DD')
          : null;
        let outTime = values.outDateAndTimeOne ? values.outDateAndTimeOne.format('hh:mm A') : null;
        tempArr.slot1.outDate = outDate;
        tempArr.slot1.outTime = outTime;

        console.log(tempArr);
      }
      if (values.hasOwnProperty('outDateAndTimeTwo')) {
        let outDate = values.outDateAndTimeTwo
          ? values.outDateAndTimeTwo.format('YYYY-MM-DD')
          : null;
        let outTime = values.outDateAndTimeTwo ? values.outDateAndTimeTwo?.format('hh:mm A') : null;
        tempArr.slot2.outDate = outDate;
        tempArr.slot2.outTime = outTime;
      }

      if (values.hasOwnProperty('inDateAndTimeOne')) {
        let inDate = values.inDateAndTimeOne ? values.inDateAndTimeOne.format('YYYY-MM-DD') : null;
        let inTime = values.inDateAndTimeOne ? values.inDateAndTimeOne?.format('hh:mm A') : null;
        tempArr.slot1.inDate = inDate;
        tempArr.slot1.inTime = inTime;
      }
      if (values.hasOwnProperty('inDateAndTimeTwo')) {
        let inDate = values.inDateAndTimeTwo ? values.inDateAndTimeTwo?.format('YYYY-MM-DD') : null;
        let inTime = values.inDateAndTimeTwo ? values.inDateAndTimeTwo?.format('hh:mm A') : null;
        tempArr.slot2.inDate = inDate;
        tempArr.slot2.inTime = inTime;
      }

      let hasRecordChange = await checkHasChanges(tempArr);

      if (hasRecordChange) {
        const invalidParams = validatedRow(tempArr);

        tempArr.hasErrors = invalidParams ? true : false;
        tempArr.incompleUpdate = true;
        tempArr.isChanged = true;
        toggleEdit();
        setIsSaving(false);
        await handleSave({ ...record, ...tempArr });
      } else {
        tempArr.incompleUpdate = false;
        tempArr.isChanged = false;
        toggleEdit();
        setIsSaving(false);
        await handleSave({ ...record, ...tempArr });
      }
    } catch (errInfo) {
      console.log('Save failed:', errInfo);
    }
  };

  const getRules = (dataIndex) => {
    if (dataIndex == 'in') {
      return [{ required: true, message: 'Required' }];
    }
    return [];
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
                dataIndex == 'outDateAndTimeOne' ||
                dataIndex == 'outDateAndTimeTwo' ||
                dataIndex == 'inDateAndTimeOne' ||
                dataIndex == 'inDateAndTimeTwo'
                  ? '100%'
                  : 100,
            }}
            name={dataIndex}
            rules={getRules(dataIndex)}
          >
            {dataIndex == 'outDateAndTimeOne' || dataIndex == 'outDateAndTimeTwo' ? (
              <DatePicker
                onChange={(date, dateString) => {
                  console.log(date);
                }}
                format="DD-MM-YYYY HH:mm"
                placeholder='DD-MM-YYYY HH:mm'
                showTime
                onKeyDown={(e) => {
                  console.log(e.target.value);
                }}
                ref={inputRef}
                onSelect={(value) => {
                  if (dataIndex == 'outDateAndTimeOne') {
                    form.setFieldsValue({ ['outDateAndTimeOne']: value });
                  }
                  if (dataIndex == 'outDateAndTimeTwo') {
                    form.setFieldsValue({ ['outDateAndTimeTwo']: value });
                  }
                }}
                onOk={(value) => {}}
                onBlur={(value) => {
                  if (value.target.value && value.target.value != '') {
                    let isValid = checkOutDateIsValid(moment(value.target.value, 'DD-MM-YYYY'));
                    if (!isValid) {
                      let key = 'Error';
                      message.error({
                        content: 'Invalid Date',
                        key,
                      });

                      if (dataIndex == 'outDateAndTimeOne') {
                        form.setFieldsValue({ outDateAndTimeOne: null });
                      }

                      if (dataIndex == 'outDateAndTimeTwo') {
                        form.setFieldsValue({ outDateAndTimeTwo: null });
                      }

                      inputRef.current!.focus();
                      return;
                    }
                  }

                  save();
                }}
                disabledDate={disableDatesForEditableCells}
              />
            ) : dataIndex == 'inDateAndTimeOne' || dataIndex == 'inDateAndTimeTwo' ? (
              <DatePicker
                onChange={(date, dateString) => {}}
                format="DD-MM-YYYY HH:mm"
                placeholder='DD-MM-YYYY HH:mm'
                showTime
                onKeyDown={(e) => {
                  console.log(e.target.value);
                }}
                ref={inputRef}
                onOk={(value) => {}}
                onSelect={(value) => {
                  if (dataIndex == 'inDateAndTimeOne') {
                    form.setFieldsValue({ ['inDateAndTimeOne']: value });
                  }
                  if (dataIndex == 'inDateAndTimeTwo') {
                    form.setFieldsValue({ ['inDateAndTimeTwo']: value });
                  }
                }}
                onBlur={(value) => {
                  if (value.target.value && value.target.value != '') {
                    let isValid = checkInDateIsValid(moment(value.target.value, 'DD-MM-YYYY'));
                    if (!isValid) {
                      let key = 'Error';
                      message.error({
                        content: 'Invalid Date',
                        key,
                      });

                      if (dataIndex == 'inDateAndTimeOne') {
                        form.setFieldsValue({ inDateAndTimeOne: null });
                      }

                      if (dataIndex == 'inDateAndTimeTwo') {
                        form.setFieldsValue({ inDateAndTimeTwo: null });
                      }

                      inputRef.current!.focus();
                      return;
                    }
                  }

                  save();
                }}
                disabledDate={disableDatesForEditableInDateTime}
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

const InvalidAttendanceTableView: React.FC<TableViewProps> = (props) => {
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
  const [changedRecordIds, setChangedRecordIds] = useState(null);
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
  const [filteredShiftDay, setFilteredShiftDay] = useState<moment.Moment>(
    moment().subtract(1, 'day'),
  );

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

  const [count, setCount] = useState(2);

  const handleSave = (row: DataType) => {
    const newData = [...attendanceSheetData];
    const index = newData.findIndex((item) => row.id === item.id);
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
    if (orginalRecord.slot1InDate !== editedRow.slot1.inDate) {
      return true;
    }

    //check whether in time is changed
    if (orginalRecord.slot1InTime !== editedRow.slot1.inTime) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.slot1OutDate !== editedRow.slot1.outDate) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.slot1OutTime !== editedRow.slot1.outTime) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.slot2InDate !== editedRow.slot2.inDate) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.slot2InTime !== editedRow.slot2.inTime) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.slot2OutDate !== editedRow.slot2.outDate) {
      return true;
    }

    //check whether out time is changed
    if (orginalRecord.slot2OutTime !== editedRow.slot2.outTime) {
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
      dataIndex: 'day',
      width: 20,
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
                  <Row>
                    {record.day.dayType === 'Non Working Day' ? (
                      <p className={styles.dayTypeTableIcon}>
                        <Image src={NonWorkingDayIcon} preview={false} />
                      </p>
                    ) : record.day.dayType === 'Holiday' ? (
                      <p className={styles.dayTypeTableIcon}>
                        <Image src={HolidayIcon} preview={false} />
                      </p>
                    ) : record.day.dayType === 'Working Day' &&
                      record.day.isWorked === 0 &&
                      record.leave.length == 0 ? (
                      <p className={styles.dayTypeTableIcon}>
                        <Image src={AbsentIcon} preview={false} />
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
      title: (
        <FormattedMessage
          id="Attendance.date"
          defaultMessage={intl.formatMessage({
            id: 'date',
            defaultMessage: 'Date',
          })}
        />
      ),
      dataIndex: 'date',
      width: 40,
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
                {moment(record.date, 'YYYY-MM-DD').isValid()
                  ? moment(record.date).format('DD-MM-YYYY')
                  : null}
              </Row>
            </>
          ),
        };
      },
    },
    {
      title: <FormattedMessage id="Attendance.name" defaultMessage="Employee Name" />,
      dataIndex: 'name',
      width: 120,
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
          children: <Space>{record.name}</Space>,
        };
      },
    },
    {
      title: (
        <FormattedMessage
          id="Attendance.inTime"
          defaultMessage={intl.formatMessage({
            id: 'inTime',
            defaultMessage: 'In Date & Time 01',
          })}
        />
      ),
      dataIndex: 'inDateAndTimeOne',
      search: false,
      width: 200,
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
                    <p className={styles.time}>
                      {moment(record.slot1.inDate, 'YYYY-MM-DD').isValid() &&
                      moment(record.slot1.inTime, 'hh:mm A').isValid()
                        ? moment(record.slot1.inDate + ' ' + moment(record.slot1.inTime, 'hh:mm A').format('HH:mm')).format(
                            'DD-MM-YYYY HH:mm:ss',
                          )
                        : '-'}
                    </p>
                    {record.slot1.inLate > 0 ? (
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
      title: (
        <FormattedMessage
          id="Attendance.date"
          defaultMessage={intl.formatMessage({
            id: 'outDateTime',
            defaultMessage: 'Out Date & Time 01',
          })}
        />
      ),
      dataIndex: 'outDateAndTimeOne',
      editable: true,
      width: 200,
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
                    <p className={styles.time}>
                      {moment(record.slot1.outDate, 'YYYY-MM-DD').isValid() &&
                      moment(record.slot1.outTime, 'hh:mm A').isValid()
                        ? moment(record.slot1.outDate + ' ' + moment(record.slot1.outTime, 'hh:mm A').format('HH:mm')).format(
                            'DD-MM-YYYY HH:mm',
                          )
                        : '-'}
                    </p>
                    {record.slot1.earlyOut > 0 ? (
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
      title: (
        <FormattedMessage
          id="Attendance.inTime"
          defaultMessage={intl.formatMessage({
            id: 'inTime',
            defaultMessage: 'In Date & Time 02',
          })}
        />
      ),
      dataIndex: 'inDateAndTimeTwo',
      search: false,
      width: 200,
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
                    <p className={styles.time}>
                      {moment(record.slot2.inDate, 'YYYY-MM-DD').isValid() &&
                      moment(record.slot2.inTime, 'hh:mm A').isValid()
                        ? moment(record.slot2.inDate + ' ' + moment(record.slot2.inTime, 'hh:mm A').format('HH:mm')).format(
                            'DD-MM-YYYY HH:mm:ss',
                          )
                        : '-'}
                    </p>
                    {record.slot2.inLate > 0 ? (
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
      title: (
        <FormattedMessage
          id="Attendance.date"
          defaultMessage={intl.formatMessage({
            id: 'outDateTime',
            defaultMessage: 'Out Date & Time 02',
          })}
        />
      ),
      dataIndex: 'outDateAndTimeTwo',
      editable: true,
      width: 200,
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
                    <p className={styles.time}>
                      {moment(record.slot2.outDate, 'YYYY-MM-DD').isValid() &&
                      moment(record.slot2.outTime, 'hh:mm A').isValid()
                        ? moment(record.slot2.outDate + ' ' + moment(record.slot2.outTime, 'hh:mm A').format('HH:mm')).format(
                            'DD-MM-YYYY HH:mm:ss',
                          )
                        : '-'}
                    </p>
                    {record.slot2.earlyOut > 0 ? (
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
    if (filteredShiftDay) {
      callGetInvalidAttendanceSheetData(1, 100).then(() => {});
    }
  }, [filteredShiftDay]);

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

  const cols = columns.map((col) => {
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
        setChangedRecordIds: setChangedRecordIds,
        changedRecordIds: changedRecordIds,
        checkHasChanges,
        setLoading,
      }),
    };
  });

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
          label: employee.employeeName,
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

  async function callGetInvalidAttendanceSheetData(
    pageNo?: number,
    pageCount?: number,
    sort = { name: 'date', order: 'DESC' },
  ) {
    setLoading(true);
    let shiftDate = filteredShiftDay ? filteredShiftDay.format('YYYY-MM-DD') : null;

    let scope = 'EMPLOYEE';
    if (props.accessLevel == 'admin') {
      scope = 'ADMIN';
    } else if (props.accessLevel == 'manager') {
      scope = 'MANAGER';
    }

    const params = {
      employee: selectedEmployee,
      fromDate: shiftDate,
      toDate: shiftDate,
      sort: sort,
      scope: scope,
    };

    setAttendanceSheetData([]);
    setIntialData([]);
    setDataCount(0);
    if (hasPermitted('invalid-attendance-update-admin-access') && props.accessLevel == 'admin') {
      await getInvalidAttendanceSheetAdminData(params)
        .then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setCurrentEditingRow(null);

            let orgData = [];
            response.data.sheets.map((sheet) => {
              let tempObj = {
                in: sheet.in.time,
                id: sheet.id,
                out: sheet.out.time,
                outDate: sheet.outDate,
                shiftId: sheet.shiftId,
                shift: sheet.shift,
                slot1InDate: sheet.slot1.inDate,
                slot1InTime: sheet.slot1.inTime,
                slot1OutDate: sheet.slot1.outDate,
                slot1OutTime: sheet.slot1.outTime,
                slot2InDate: sheet.slot2.inDate,
                slot2InTime: sheet.slot2.inTime,
                slot2OutDate: sheet.slot2.outDate,
                slot2OutTime: sheet.slot2.outTime,
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
    } else if (
      hasPermitted('invalid-attendance-update-manager-access') &&
      props.accessLevel == 'manager'
    ) {
      await getInvalidAttendanceSheetAdminData(params)
        .then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setCurrentEditingRow(null);

            let orgData = [];
            response.data.sheets.map((sheet) => {
              let tempObj = {
                in: sheet.in.time,
                id: sheet.id,
                out: sheet.out.time,
                outDate: sheet.outDate,
                shiftId: sheet.shiftId,
                shift: sheet.shift,
                slot1InDate: sheet.slot1.inDate,
                slot1InTime: sheet.slot1.inTime,
                slot1OutDate: sheet.slot1.outDate,
                slot1OutTime: sheet.slot1.outTime,
                slot2InDate: sheet.slot2.inDate,
                slot2InTime: sheet.slot2.inTime,
                slot2OutDate: sheet.slot2.outDate,
                slot2OutTime: sheet.slot2.outTime,
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
    }
  }

  const handleCancel = () => {
    setIsModalVisible(false);
  };

  const updateAttendance = (breakList: any) => {
    setLoading(true);
    let changedRecords = [];
    let changedRecordIds = [];
    attendanceSheetData.map((el, index) => {
      if (el.isChanged && !el.hasErrors) {
        let slot1InTimeObj = el.slot1.inTime ? moment(el.slot1.inTime, 'hh:mm A') : null;
        let slot1OutTimeObj = el.slot1.outTime ? moment(el.slot1.outTime, 'hh:mm A') : null;
        let slot1InDateObj = el.slot1.inDate ? moment(el.slot1.inDate, 'YYYY-MM-DD') : null;
        let slot1OutDateObj = el.slot1.outDate ? moment(el.slot1.outDate, 'YYYY-MM-DD') : null;

        let slot2InTimeObj = el.slot2.inTime ? moment(el.slot2.inTime, 'hh:mm A') : null;
        let slot2OutTimeObj = el.slot2.outTime ? moment(el.slot2.outTime, 'hh:mm A') : null;
        let slot2InDateObj = el.slot2.inDate ? moment(el.slot2.inDate, 'YYYY-MM-DD') : null;
        let slot2OutDateObj = el.slot2.outDate ? moment(el.slot2.outDate, 'YYYY-MM-DD') : null;

        el.slot1.inTime = slot1InTimeObj ? slot1InTimeObj.format('HH:mm:ss') : null;
        el.slot1.inDate = slot1InDateObj ? slot1InDateObj.format('YYYY-MM-DD') : null;
        el.slot1.outTime = slot1OutTimeObj ? slot1OutTimeObj.format('HH:mm:ss') : null;
        el.slot1.outDate = slot1OutDateObj ? slot1OutDateObj.format('YYYY-MM-DD') : null;

        el.slot2.inTime = slot2InTimeObj ? slot2InTimeObj.format('HH:mm:ss') : null;
        el.slot2.inDate = slot2InDateObj ? slot2InDateObj.format('YYYY-MM-DD') : null;
        el.slot2.outTime = slot2OutTimeObj ? slot2OutTimeObj.format('HH:mm:ss') : null;
        el.slot2.outDate = slot2OutDateObj ? slot2OutDateObj.format('YYYY-MM-DD') : null;

        let tempEl = {
          date: el.date,
          summaryId: el.summaryId,
          employeeId: el.employeeIdNo,
          timeZone: el.timeZone,
          shiftId: el.shiftId,
          summeryId: el.id,
          expectedIn: el.expectedIn,
          expectedOut: el.expectedOut,
          slot1: el.slot1,
          slot2: el.slot2,
        };

        changedRecords.push(tempEl);
        changedRecordIds.push(el.summaryId);
      }
    });

    let emptyInOutRecord = 0;
    changedRecords.forEach((record) => {
      if (
        !record.slot1.inDate &&
        !record.slot1.inTime &&
        !record.slot1.outDate &&
        !record.slot1.outTime &&
        !record.slot2.inDate &&
        !record.slot2.inTime &&
        !record.slot2.outDate &&
        !record.slot2.outTime
      ) {
        emptyInOutRecord++;
      }
    });

    if (emptyInOutRecord > 0) {
      message.error({
        content: intl.formatMessage({
          id: 'cannotUpdateEmptyAttendance',
          defaultMessage:
            'Some changed records contain fully empty attendance please fill them before save ',
        }),
        key,
        duration: 6,
      });
      return;
    }

    let scope = 'EMPLOYEE';
    if (props.accessLevel == 'admin') {
      scope = 'ADMIN';
    } else if (props.accessLevel == 'manager') {
      scope = 'MANAGER';
    }

    let params = {
      attendanceDetails: JSON.stringify(changedRecords),
      scope: scope,
    };

    updateInvalidAttendences(params)
      .then((response: any) => {
        setLoading(false);

        if (response.data) {
          let newDataCollection = [];
          let newInitialDataCollection = [];

          attendanceSheetData.map((el, index) => {
            if (!changedRecordIds.includes(el.id)) {
              newDataCollection.push(el);
            }
          });

          intialData.map((orgData, orgDataIndex) => {
            if (!changedRecordIds.includes(orgData.id)) {
              newInitialDataCollection.push(orgData);
            }
          });

          setIntialData(newInitialDataCollection);
          setAttendanceSheetData(newDataCollection);

          message.success({
            content: intl.formatMessage({
              id: 'invalidAttendanceUpdated',
              defaultMessage: 'Invalid attendance save successfully.',
            }),
            key,
          });
        }

        if (response.error) {
          message.error({
            content: intl.formatMessage({
              id: 'rejectedInvalidAttendance',
              defaultMessage: 'Failed to save the invalid attendance.',
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
              id: 'rejectedInvalidAttendance',
              defaultMessage: 'Failed to save the invalid attendance.',
            }),
          key,
        });
      });
  };

  const disableDatesForFilter = (current: any) => {
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    const isNextDay = moment(compareDate, 'YYYY-MM-DD') >= moment();
    const isEqualToCurrentDate = moment(compareDate, 'YYYY-MM-DD').isSame(
      moment().format('YYYY-MM-DD'),
    );

    const isDisable = isNextDay || isEqualToCurrentDate;
    return isDisable;
  };

  const isSaveBtnDisable = () => {
    let changedRecords = [];
    attendanceSheetData.map((el, index) => {
      if (el.isChanged && !el.hasErrors) {
        changedRecords.push(el);
      }
    });
    return !(changedRecords.length > 0);
  };

  return (
    <ProCard direction="column" ghost gutter={[0, 16]} style={{ padding: 0, margin: 0 }}>
      <Row style={{ width: '100%' }}>
        <Col style={{ width: '100%' }}>
          <Card style={{ width: '100%', height: showDataTable ? 690 : 680 }}>
            <ConfigProvider locale={en_US}>
              <Space direction="vertical" size={25} style={{ width: '100%' }}>
                <>
                  {
                    <Row>
                      <Col style={{ height: 500 }} span={24}>
                        <div className={styles.spinCol}>
                          <Spin size="large" spinning={loading}>
                            <Row style={{ marginBottom: 20 }}>
                              <Col span={12}>
                                <Row>
                                  <Col span={8} className={styles.dayTypeCol}>
                                    <Space className={styles.dayType}>
                                      <p className={styles.dayTypeIcon}>
                                        <Image src={LateIcon} preview={false} height={15} />
                                      </p>
                                      <p className={styles.dayTypeContent}>Late In</p>
                                    </Space>
                                    <Space className={styles.dayType}>
                                      <p className={styles.dayTypeIcon}>
                                        <Image src={EarlyIcon} preview={false} height={15} />
                                      </p>
                                      <p className={styles.dayTypeContent}>Early Departure</p>
                                    </Space>
                                    <Space className={styles.dayType}>
                                      <p className={styles.dayTypeIcon}>
                                        <Image
                                          src={NonWorkingDayIcon}
                                          preview={false}
                                          height={15}
                                        />
                                      </p>
                                      <p className={styles.dayTypeContent}>Non Working Day</p>
                                    </Space>
                                    <Space className={styles.dayType}>
                                      <p className={styles.dayTypeIcon}>
                                        <Image src={HolidayIcon} preview={false} height={15} />
                                      </p>
                                      <p className={styles.dayTypeContent}>Holiday</p>
                                    </Space>
                                    <Space className={styles.dayType}>
                                      <p className={styles.dayTypeIcon}>
                                        <Image src={AbsentIcon} preview={false} height={15} />
                                      </p>
                                      <p className={styles.dayTypeContent}>Absent</p>
                                    </Space>
                                  </Col>
                                </Row>
                              </Col>
                              <Col span={12}>
                                <DatePicker
                                  onChange={(date, dateString) => {
                                    if (date) {
                                      setFilteredShiftDay(date);
                                    } else {
                                      setFilteredShiftDay(moment().subtract(1, 'day'));
                                    }
                                  }}
                                  format="DD-MM-YYYY"
                                  value={filteredShiftDay}
                                  style={{ borderRadius: 6, float: 'right' }}
                                  disabledDate={disableDatesForFilter}
                                />
                              </Col>
                            </Row>
                            <Row className={'adminAttendanceTable'}>
                              <ProTable<AttendanceItem>
                                columns={cols as ColumnTypes}
                                scroll={{ y: 500 }}
                                components={components}
                                rowClassName={() => 'editableRow'}
                                actionRef={actionRef}
                                dataSource={attendanceSheetData}
                                request={async (
                                  params = { current: 1, pageSize: 100 },
                                  sort,
                                  filter,
                                ) => {
                                  const sortValue = sort?.name
                                    ? {
                                        name: 'name',
                                        order: sort?.name === 'ascend' ? 'ASC' : 'DESC',
                                      }
                                    : {
                                        name: 'date',
                                        order: sort?.date === 'ascend' ? 'ASC' : 'DESC',
                                      };

                                  const tableParams = {
                                    current: params?.current,
                                    pageSize: params?.pageSize,
                                    sortValue: sortValue,
                                  };
                                  setTableState(tableParams);

                                  await callGetInvalidAttendanceSheetData(
                                    params?.current,
                                    params?.pageSize,
                                    sortValue,
                                  );
                                  return attendanceSheetData;
                                }}
                                toolBarRender={false}
                                pagination={false}
                                search={false}
                                style={{ width: '100%', fontSize: 10 }}
                              />
                            </Row>
                          </Spin>
                        </div>
                      </Col>
                      <Col span={24}>
                        <Row
                          className={'invalid-attendance-btn-section'}
                          style={{ marginTop: 100, float: 'right' }}
                        >
                          <Button key="back" style={{ marginRight: 10 }} onClick={handleCancel}>
                            Cancel
                          </Button>
                          <Button
                            key="submit"
                            type="primary"
                            loading={loadingModelRequest}
                            disabled={isSaveBtnDisable()}
                            onClick={updateAttendance}
                          >
                            <FormattedMessage id="save" defaultMessage="Save" />
                          </Button>
                        </Row>
                      </Col>
                    </Row>
                  }
                </>
              </Space>
            </ConfigProvider>
          </Card>
        </Col>
      </Row>
    </ProCard>
  );
};

export default InvalidAttendanceTableView;
