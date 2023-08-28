import React, { useEffect, useRef, useState, useContext } from 'react';
import { InputRef } from 'antd';
import {
  Button,
  Tag,
  Space,
  Image,
  Row,
  Col,
  Tooltip,
  Spin,
  DatePicker,
  Form,
  message,
  ConfigProvider,
  Card,
  Table,
  Input,
  Badge,
} from 'antd';
// import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import moment from 'moment';
import { FormattedMessage, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import _ from 'lodash';
const { TextArea } = Input;

import {
  getAttendanceSheetForEmployeePostOtRequest,
  getRelatedBreakes,
  createPostOtRequest,
  checkOtAccessability,
} from '@/services/attendance';
import LateIcon from '../../assets/attendance/icon-clock-red.svg';
import EarlyIcon from '../../assets/attendance/icon-clock-orange.svg';
import ManagerRequestIcon from '../../assets/attendance/Time-change-notification-manager.svg';
import { APIResponse } from '@/utils/request';
import { getEmployeeList, getOtPayTypeList } from '@/services/dropdown';
import { getAllWorkShifts } from '@/services/workShift';
import en_US from 'antd/lib/locale-provider/en_US';
import styles from '../../components/Attendance/attendance.less';
import ProCard from '@ant-design/pro-card';
import type { FormInstance } from 'antd/es/form';
import '../../components/Attendance/index.css';
import PermissionDeniedPage from '../403';

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

      if (record.otApprovedStatus == 'PENDING') {
        let key = 'Error';
        message.error({
          content: 'Can not change records in pending state',
          key,
        });
        setCurrentEditingRow(null);
        return;
      }

      if (record.otApprovedStatus == 'APPROVED') {
        let key = 'Error';
        message.error({
          content: 'Can not change already ot aprroved records',
          key,
        });
        setCurrentEditingRow(null);
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

      if (dataIndex != 'reason') {
        let arr = dataIndex.split('approved');
        let payCode = arr[1];

        if (record.otData.otDetails[payCode] == '00:00') {
          let key = 'Error';
          message.error({
            content: 'Can not set requested ot value for this pay type',
            key,
          });
          setCurrentEditingRow(null);
          return;
        }
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

    if (dataIndex == 'reason') {
      form.setFieldsValue({ [dataIndex]: record.reason });
    } else {
      let arr = dataIndex.split('approved');
      let payCode = arr[1];
      let otDuration = moment(record.otData.requestedOtDetails[payCode], 'HH:mm').isValid()
        ? record.otData.requestedOtDetails[payCode]
        : null;
      form.setFieldsValue({ [dataIndex]: otDuration });
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
        otData: record.otData,
        reason: record.reason ? record.reason : null,
        incompleUpdate: record.incompleUpdate ? record.incompleUpdate : false,
        isChanged: record.isChanged ? record.isChanged : false,
      };

      if (dataIndex != 'reason' && values.hasOwnProperty(dataIndex)) {
        let dataIndexArr = dataIndex.split('approved');
        let payCode = dataIndexArr[1];
        if (values[dataIndex]) {
          tempArr.otData.requestedOtDetails[payCode] = values[dataIndex];
        } else {
          tempArr.otData.requestedOtDetails[payCode] = '00:00';
        }
      }

      if (dataIndex == 'reason') {
        if (values[dataIndex]) {
          tempArr.reason = values[dataIndex];
        } else {
          tempArr.reason = null;
        }
      }

      console.log(tempArr);

      let hasRecordChange = await checkHasChanges(tempArr);

      if (hasRecordChange) {
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
                    : dataIndex == 'outDateAndTime'
                      ? 150
                      : dataIndex == 'reason'
                        ? 220
                        : 100,
            }}
            name={dataIndex}
            rules={getRules(dataIndex)}
          >
            {dataIndex == 'reason' ? (
              <TextArea
                ref={inputRef}
                style={{ borderRadius: 6, fontSize: 10 }}
                onPressEnter={save}
                rows={1}
                onBlur={save}
              />
            ) : (
              <Input
                ref={inputRef}
                style={{ borderRadius: 6, fontSize: 10 }}
                onBlur={(value) => {
                  let reg = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;

                  if (!reg.test(value.target.value)) {
                    let key = 'Error';
                    message.error({
                      content: 'Invalid hour and minutes format',
                      key,
                    });
                    inputRef.current!.focus();
                    return;
                  }

                  let dataIndexArr = dataIndex.split('approved');
                  let payCode = dataIndexArr[1];
                  let actualOtCountString = record.otData.otDetails[payCode];
                  let actualOtCountArr = actualOtCountString.split(':');
                  let actualWorkOtHours = parseInt(actualOtCountArr[0]);
                  let actualWorkOtMiniutes = parseInt(actualOtCountArr[1]);
                  let actualWorkOtHoursInMinutes = actualWorkOtHours * 60;
                  let totalActualWorkOtInMinutes =
                    actualWorkOtHoursInMinutes + actualWorkOtMiniutes;

                  let requestOtCountString = value.target.value;
                  let requestOtCountArr = requestOtCountString.split(':');
                  let requestWorkOtHours = parseInt(requestOtCountArr[0]);
                  let requestWorkOtMiniutes = parseInt(requestOtCountArr[1]);
                  let requestWorkOtHoursInMinutes = requestWorkOtHours * 60;
                  let totalRequestWorkOtInMinutes =
                    requestWorkOtHoursInMinutes + requestWorkOtMiniutes;

                  if (totalRequestWorkOtInMinutes > totalActualWorkOtInMinutes) {
                    let key = 'Error';
                    message.error({
                      content: 'Requested Overtime Exceeds Limit',
                      key,
                    });
                    inputRef.current!.focus();
                    return;
                  }
                  save();
                }}
                onPressEnter={(value) => {
                  let reg = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;

                  if (!reg.test(value.target.value)) {
                    let key = 'Error';
                    message.error({
                      content: 'Invalid hour and minutes format',
                      key,
                    });
                    inputRef.current!.focus();
                    return;
                  }

                  let dataIndexArr = dataIndex.split('approved');
                  let payCode = dataIndexArr[1];
                  let actualOtCountString = record.otData.otDetails[payCode];
                  let actualOtCountArr = actualOtCountString.split(':');
                  let actualWorkOtHours = parseInt(actualOtCountArr[0]);
                  let actualWorkOtMiniutes = parseInt(actualOtCountArr[1]);
                  let actualWorkOtHoursInMinutes = actualWorkOtHours * 60;
                  let totalActualWorkOtInMinutes =
                    actualWorkOtHoursInMinutes + actualWorkOtMiniutes;

                  let requestOtCountString = value.target.value;
                  let requestOtCountArr = requestOtCountString.split(':');
                  let requestWorkOtHours = parseInt(requestOtCountArr[0]);
                  let requestWorkOtMiniutes = parseInt(requestOtCountArr[1]);
                  let requestWorkOtHoursInMinutes = requestWorkOtHours * 60;
                  let totalRequestWorkOtInMinutes =
                    requestWorkOtHoursInMinutes + requestWorkOtMiniutes;

                  if (totalRequestWorkOtInMinutes > totalActualWorkOtInMinutes) {
                    let key = 'Error';
                    message.error({
                      content: 'Requested Overtime Exceeds Limit',
                      key,
                    });
                    inputRef.current!.focus();
                    return;
                  }
                  save();
                }}
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
  } else {
    childNode = (
      <div className={styles.nonEditableCellValueWrap} style={{ paddingRight: 24 }}>
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

const MyPostOtRequestTableView: React.FC<TableViewProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const actionRef = useRef<ActionType>();
  const [payTypes, setPayTypes] = useState([]);
  const [attendanceSheetData, setAttendanceSheetData] = useState([]);
  const [dayTypesData, setDayTypesData] = useState([]);
  const [intialData, setIntialData] = useState<any>([]);
  const [thisMonthOtLabel, setThisMonthOTLabel] = useState<any>('00:00');
  const [lastMonthOtLabel, setLastMonthOTLabel] = useState<any>('00:00');
  const [dataCount, setDataCount] = useState(0);
  const [tableState, setTableState] = useState<any>({});
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();
  const [selectedEmployee, setSelectedEmployee] = useState(props.employeeId ?? undefined);
  const [othersView, setOthersView] = useState(props.others ?? false);
  const [adminView, setAdminView] = useState(props.adminView ?? false);
  const [filterMonth, setFilterMonth] = useState<moment.Moment>();
  const [currentMonth, setCurrentMonth] = useState<moment.Moment>(moment());
  const [loading, setLoading] = useState(false);

  const [isModalVisible, setIsModalVisible] = useState(false);
  const [disableModelButtons, setDisableModelButtons] = useState(false);
  const [loadingModelRequest, setLoadingModelRequest] = useState(false);
  const [isMaintainOt, setIsMaintainOt] = useState(true);
  const [loadingExcelDownload, setLoadingExcelDownload] = useState(false);
  const [dateModel, setDateModel] = useState('');
  const [currentEditingRow, setCurrentEditingRow] = useState(null);
  const [shiftIdModel, setShiftIdModel] = useState<number>();
  const [summaryIdModel, setSummaryIdModel] = useState<number>();
  const [employeeIdModel, setEmployeeIdModel] = useState<number>();

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

  useEffect(() => {
    if (filterMonth) {
      callGetAttendanceSheetData(1, 100).then(() => { });
    }
  }, [filterMonth]);

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

  const createPostOtRequestRecord = (breakList: any) => {
    // setLoading(true);
    let openPostOtRecords = [];
    let totalRequestOt = 0;
    attendanceSheetData.map((el, index) => {
      if (el.otApprovedStatus == 'OPEN') {
        let inTimeObj = moment(el.in.time, 'hh:mm A');
        let outTimeObj = moment(el.out.time, 'hh:mm A');
        let inDateObj = moment(el.date, 'YYYY-MM-DD');
        let outDateObj = moment(el.outDate, 'YYYY-MM-DD');

        let recordWiseRequestedOt = 0;
        let recordWiseActualOt = 0;
        let recordWiseApprovedOt = 0;

        for (let index in el.otData.requestedOtDetails) {
          let value = el.otData.requestedOtDetails[index];

          let requestOtCountString = value;
          let requestOtCountArr = requestOtCountString.split(':');
          let requestWorkOtHours = parseInt(requestOtCountArr[0]);
          let requestWorkOtMiniutes = parseInt(requestOtCountArr[1]);
          let requestWorkOtHoursInMinutes = requestWorkOtHours * 60;
          let totalRequestWorkOtInMinutes = requestWorkOtHoursInMinutes + requestWorkOtMiniutes;
          recordWiseRequestedOt += totalRequestWorkOtInMinutes;
          totalRequestOt += totalRequestWorkOtInMinutes;
        }

        let value = el.otData.otDetails[index];

        let actualOtCountString = el.otData.totalOtHours;
        let actualOtCountArr = actualOtCountString.split(':');
        let actualWorkOtHours = parseInt(actualOtCountArr[0]);
        let actualWorkOtMiniutes = parseInt(actualOtCountArr[1]);
        let actualWorkOtHoursInMinutes = actualWorkOtHours * 60;
        let totalactualWorkOtInMinutes = actualWorkOtHoursInMinutes + actualWorkOtMiniutes;
        recordWiseActualOt = totalactualWorkOtInMinutes;
        // recordWiseApprovedOt = recordWiseRequestedOt;
        recordWiseApprovedOt = 0;

        el.otData.approvedOtDetails = el.otData.requestedOtDetails;

        let obj = {
          summaryId: el.id,
          shiftId: el.shiftId,
          date: el.date,
          actualIn: inDateObj?.format('YYYY-MM-DD') + ' ' + inTimeObj?.format('HH:mm:ss'),
          actualOut: outDateObj?.format('YYYY-MM-DD') + ' ' + outTimeObj?.format('HH:mm:ss'),
          workTime: el.duration && el.duration.workedMin ? el.duration.workedMin : 0,
          status: el.otApprovedStatus,
          requestedEmployeeComment: el.reason,
          totalApprovedOtMins: recordWiseApprovedOt,
          totalActualOtMins: recordWiseActualOt,
          totalRequestedOtMins: recordWiseRequestedOt,
          otDataSet: el.otData,
        };

        openPostOtRecords.push(obj);
      }
    });

    if (openPostOtRecords.length == 0) {
      message.error({
        content: intl.formatMessage({
          id: 'noAnyOpenRequest',
          defaultMessage: 'There is no any open attendance records for request.',
        }),
        key,
      });
      return;
    }

    let month = null;
    if (filterMonth) {
      month = filterMonth.format('YYYY/MMMM');
    } else {
      let currenDate = moment();
      month = currenDate.format('YYYY/MMMM');
    }

    let params = {
      openPostOtRecords: JSON.stringify(openPostOtRecords),
      month: month,
      totalRequestedOtMins: totalRequestOt,
    };

    createPostOtRequest(params)
      .then((response: any) => {
        setLoading(false);

        if (response.data) {
          actionRef.current?.reload();
          message.success({
            content: intl.formatMessage({
              id: 'postOtRequestCreateSuccess',
              defaultMessage: 'Post OT request sucessfully created.',
            }),
            key,
          });
        }

        if (response.error) {
          message.error({
            content: intl.formatMessage({
              id: 'postOtRequestCreateFaild',
              defaultMessage: 'Failed to save Post OT request.',
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
              id: 'postOtRequestCreateFaild',
              defaultMessage: 'Failed to save Post OT request.',
            }),
          key,
        });
      });
  };

  const checkHasChanges = (editedRow) => {
    const index = intialData.findIndex((item) => editedRow.id === item.id);
    if (index == -1) {
      return false;
    }

    let orginalRecord = intialData[index];

    //check whether in time is changed
    if (orginalRecord.reason !== editedRow.reason) {
      return true;
    }

    let requestOtChangeCount = 0;
    for (let index in editedRow.otData.requestedOtDetails) {
      let payKey = 'approved' + index;
      if (orginalRecord[payKey] != editedRow.otData.requestedOtDetails[index]) {
        requestOtChangeCount++;
      }
    }

    if (requestOtChangeCount > 0) {
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
            <div className="iconSpace">
              {<Badge style={{ marginRight: 8 }} color={record.day.dayTypeColor} />}
              {record.shiftId != null &&
                record.isExpectedToPresent &&
                record.day.isWorked === 0 &&
                record.leave.length == 0 ? (
                <Badge color="#44A4ED" />
              ) : (record.shiftId != null &&
                record.isExpectedToPresent &&
                record.day.isWorked === 1 &&
                record.in.late) ||
                (record.shiftId != null &&
                  record.isExpectedToPresent &&
                  record.day.isWorked === 1 &&
                  record.out.early) ? (
                <Badge color="#ED4444" />
              ) : (
                <></>
              )}
            </div>
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
      fixed: 'left',
      width: 120,
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
                    : null}
                </Space>
              </Row>
            </>
          ),
        };
      },
    },
    {
      title: (
        <FormattedMessage
          id="Attendance.shift"
          defaultMessage={intl.formatMessage({
            id: 'shiftName',
            defaultMessage: 'Shift Name',
          })}
        />
      ),
      dataIndex: 'shift',
      width: 130,
      //   editable: true,
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
      title: (
        <FormattedMessage
          id="Attendance.inTime"
          defaultMessage={intl.formatMessage({
            id: 'inTime',
            defaultMessage: 'In',
          })}
        />
      ),
      dataIndex: 'in',
      search: false,
      fixed: 'left',
      width: 170,
      //   editable: true,
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
                    {/* <p className={styles.time}>
                      {moment(record.in.time, 'hh:mm A').isValid()
                        ? moment(record.in.time, 'hh:mm A').format('HH:mm:ss')
                        : '-'}
                    </p> */}

                    <p className={styles.time}>
                      {moment(record.in.date, 'YYYY-MM-DD').isValid() &&
                        moment(record.in.time, 'hh:mm A').isValid()
                        ? moment(
                          record.in.date +
                          ' ' +
                          moment(record.in.time, 'hh:mm A').format('HH:mm'),
                        ).format('DD-MM-YYYY  HH:mm:ss')
                        : '-'}
                    </p>
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
      title: (
        <FormattedMessage
          id="Attendance.date"
          defaultMessage={intl.formatMessage({
            id: 'outDateTime',
            defaultMessage: 'Out',
          })}
        />
      ),
      dataIndex: 'outDateAndTime',
      fixed: 'left',
      //   editable: true,
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
                    <p className={styles.time}>
                      {moment(record.outDate, 'YYYY-MM-DD').isValid() &&
                        moment(record.out.time, 'hh:mm A').isValid()
                        ? moment(
                          record.outDate +
                          ' ' +
                          moment(record.out.time, 'hh:mm A').format('HH:mm'),
                        ).format('DD-MM-YYYY  HH:mm:ss')
                        : '-'}
                    </p>
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
      title: (
        <FormattedMessage
          id="Attendance.workHours"
          defaultMessage={intl.formatMessage({
            id: 'workedHours',
            defaultMessage: 'Worked Hours',
          })}
        />
      ),
      dataIndex: 'workedHours',
      fixed: 'left',
      width: 80,
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
    },
  ];

  useEffect(() => {
    callGetOTPayTypesData();
    checkHaveAccessToMaintainOT();
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
        let requestedOtColName = 'Requested' + ' ' + payType.name;
        let tempOTCol = {
          title: <FormattedMessage id={payType.code} defaultMessage={payType.name} />,
          dataIndex: payType.name,
          search: false,
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
          title: <FormattedMessage id={requestedOtColName} defaultMessage={requestedOtColName} />,
          dataIndex: 'approved' + payType.code,
          editable: true,
          width: 120,
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
                          {record.otData.requestedOtDetails[payType.code]
                            ? record.otData.requestedOtDetails[payType.code]
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

    let reasonCol = {
      title: (
        <FormattedMessage
          id="Attendance.workHours"
          defaultMessage={intl.formatMessage({
            id: 'reason',
            defaultMessage: 'Reason',
          })}
        />
      ),
      dataIndex: 'reason',
      fixed: 'right',
      width: 250,
      editable: true,
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
                      {record.reason && record.reason !== null && record.reason.length <= 25 ? (
                        <span>{record.reason}</span>
                      ) : record.reason && record.reason !== null && record.reason.length > 25 ? (
                        <Tooltip title={record.reason}>
                          {record.reason.substring(0, 25 - 3) + '...'}
                        </Tooltip>
                      ) : (
                        <>-</>
                      )}
                    </p>
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    };
    let statusCol = {
      title: (
        <FormattedMessage
          id="Attendance.workHours"
          defaultMessage={intl.formatMessage({
            id: 'status',
            defaultMessage: 'Status',
          })}
        />
      ),
      dataIndex: 'state',
      fixed: 'right',
      width: 100,
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
                  <Tag
                    style={{
                      borderRadius: 20,
                      paddingRight: 20,
                      paddingLeft: 20,
                      paddingTop: 2,
                      paddingBottom: 2,
                      border: 0,
                    }}
                    color={
                      record.otApprovedStatus == 'OPEN'
                        ? 'geekblue'
                        : record.otApprovedStatus == 'PENDING'
                          ? 'orange'
                          : record.otApprovedStatus == 'APPROVED'
                            ? 'green'
                            : ''
                    }
                  >
                    {record.otApprovedStatus == 'OPEN'
                      ? 'Open'
                      : record.otApprovedStatus == 'PENDING'
                        ? 'Pending'
                        : record.otApprovedStatus == 'APPROVED'
                          ? 'Approved'
                          : ''}
                  </Tag>
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
    colSet.push(reasonCol);
    colSet.push(statusCol);
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
        editable:
          record.otApprovedStatus == 'APPROVED' || record.otApprovedStatus == 'PENDING'
            ? false
            : col.editable,
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

  async function callGetOTPayTypesData() {
    let scope = 'EMPLOYEE';
    try {
      const response = await getOtPayTypeList(scope);
      setPayTypes(response.data);
      setIsMaintainOt(false);
    } catch (err) {
      console.log(err);
    }
  }
  async function checkHaveAccessToMaintainOT() {
    let scope = 'EMPLOYEE';
    try {
      const response = await checkOtAccessability(scope);
      setIsMaintainOt(response.data.isMaintainOt);
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
    if (filterMonth) {
      var startOfMonth = filterMonth.startOf('month').format('YYYY-MM-DD');
      var endOfMonth = filterMonth.endOf('month').format('YYYY-MM-DD');
    } else {
      var startOfMonth = moment().startOf('month').format('YYYY-MM-DD');
      var endOfMonth = moment().endOf('month').format('YYYY-MM-DD');
    }

    //previous month
    let preMonth = moment(startOfMonth).subtract(1, 'months');
    let startOfPreMonth = preMonth.startOf('month').format('YYYY-MM-DD');
    let endOfPreMonth = preMonth.endOf('month').format('YYYY-MM-DD');

    const params = {
      employee: selectedEmployee,
      fromDate: startOfMonth,
      toDate: endOfMonth,
      preMonthFromDate: startOfPreMonth,
      preMonthToDate: endOfPreMonth,
      sort: sort,
    };
    setAttendanceSheetData([]);
    setIntialData([]);
    setDataCount(0);

    await getAttendanceSheetForEmployeePostOtRequest(params)
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
              shiftId: sheet.shiftId,
              shift: sheet.shift,
              reason: sheet.reason ? sheet.reason : null,
            };

            for (let index in sheet.otData.requestedOtDetails) {
              let payKey = 'approved' + index;
              tempObj[payKey] = sheet.otData.requestedOtDetails[index];
            }

            orgData.push(tempObj);
          });

          let lastMonthOtLabel = '00:00';
          let thisMonthOtLabel = '00:00';
          if (response.data.previousMonthTotalOt && response.data.previousMonthTotalOt > 0) {
            let hour =
              Math.floor(response.data.previousMonthTotalOt / 60) >= 10
                ? Math.floor(response.data.previousMonthTotalOt / 60)
                : '0' + Math.floor(response.data.previousMonthTotalOt / 60);
            let min =
              response.data.previousMonthTotalOt % 60 >= 10
                ? response.data.previousMonthTotalOt % 60
                : '0' + (response.data.previousMonthTotalOt % 60);
            lastMonthOtLabel = hour + ':' + min;
          }

          if (response.data.currentMonthTotalOt && response.data.currentMonthTotalOt > 0) {
            let curHour =
              Math.floor(response.data.currentMonthTotalOt / 60) >= 10
                ? Math.floor(response.data.currentMonthTotalOt / 60)
                : '0' + Math.floor(response.data.currentMonthTotalOt / 60);
            let curMin =
              response.data.currentMonthTotalOt % 60 >= 10
                ? response.data.currentMonthTotalOt % 60
                : '0' + (response.data.currentMonthTotalOt % 60);
            thisMonthOtLabel = curHour + ':' + curMin;
          }

          setLastMonthOTLabel(lastMonthOtLabel);
          setThisMonthOTLabel(thisMonthOtLabel);
          setIntialData([...orgData]);
          setDataCount(response.data.count);
          //   setIsMaintainOt(response.data.isMaintainOt);
        }
        setLoading(false);
      })
      .catch(() => {
        setLoading(false);
      });
    setLoading(false);
  }

  const handleRefresh = () => {
    actionRef.current?.reload();
  };

  return (
    // <Access accessible={isMaintainOt} fallback={<PermissionDeniedPage />}>
    <ProCard direction="column" ghost gutter={[0, 16]} style={{ padding: 0, margin: 0 }}>
      <Row style={{ width: '100%' }}>
        <Col className={'attendanceSearchArea'} style={{ width: '100%' }}>
          <Row className={styles.attendanceSearchArea}>
            {
              <>
                <Col span={3} offset={21}>
                  <DatePicker
                    style={{ borderRadius: 6, marginLeft: 55 }}
                    onChange={(date) => {
                      if (date) {
                        setFilterMonth(date);
                      } else {
                        setFilterMonth(undefined);
                      }
                    }}
                    picker="month"
                    defaultValue={moment()}
                    format={'YYYY/MMMM'}
                  />
                </Col>
              </>
            }
          </Row>
          <Card style={{ marginTop: 20, width: '100%', height: 700 }}>
            <ConfigProvider locale={en_US}>
              <Space direction="vertical" size={2} style={{ width: '100%' }}>
                {/* {showDataTable ? (
                  <> */}
                {
                  <>
                    <Row style={{ marginLeft: 30 }}>
                      <Col span={3}>
                        <Row>
                          <Col span={24} style={{ marginLeft: 5, fontSize: 12 }}>
                            LAST MONTH OT
                          </Col>
                          <Col span={24} style={{ fontSize: 45, color: 'gray', marginTop: -16 }}>
                            {lastMonthOtLabel}
                          </Col>
                        </Row>
                      </Col>
                      <Col span={3}>
                        <Row>
                          <Col span={24} style={{ marginLeft: 5, fontSize: 12 }}>
                            THIS MONTH OT
                          </Col>
                          <Col span={24} style={{ fontSize: 45, color: 'gray', marginTop: -16 }}>
                            {thisMonthOtLabel}
                          </Col>
                        </Row>
                      </Col>
                    </Row>
                    <Row>
                      <Col span={24}>
                        <div className={styles.spinCol}>
                          <Spin size="large" spinning={loading}>
                            <Row className={'adminAttendanceTable'}>
                              <ProTable<AttendanceItem>
                                columns={cols as ColumnTypes}
                                scroll={
                                  !isMaintainOt && attendanceSheetData.length > 10
                                    ? { y: 500 }
                                    : !isMaintainOt && attendanceSheetData.length < 10
                                      ? undefined
                                      : isMaintainOt && attendanceSheetData.length > 10
                                        ? { x: '100vw', y: 500 }
                                        : isMaintainOt && attendanceSheetData.length < 10
                                          ? { x: '100vw' }
                                          : undefined
                                }
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

                                  await callGetAttendanceSheetData(
                                    params?.current,
                                    params?.pageSize,
                                    sortValue,
                                  );
                                  return attendanceSheetData;
                                }}
                                headerTitle={
                                  <Row className="attendanceHeaderTitle">
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
                                        <Badge color="#44A4ED" text={'Absent'} />
                                      </>
                                      <>
                                        <Badge color="#ED4444" text={'Late'} />
                                      </>

                                      {dayTypesData.map((item) => (
                                        <>
                                          <Badge color={item.typeColor} text={item.name} />
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
                      <Col span={24}>
                        <Row
                          className={'invalid-attendance-btn-section'}
                          style={{ marginTop: 20, float: 'right', marginRight: 28 }}
                        >
                          <Button key="back" style={{ marginRight: 10 }} onClick={handleRefresh}>
                            Refresh
                          </Button>
                          <Button
                            key="submit"
                            type="primary"
                            loading={loadingModelRequest}
                            disabled={
                              filterMonth &&
                                filterMonth.format('YYYY/MMMM') == currentMonth.format('YYYY/MMMM')
                                ? true
                                : filterMonth &&
                                  filterMonth.format('YYYY/MMMM') !=
                                  currentMonth.format('YYYY/MMMM')
                                  ? false
                                  : true
                            }
                            onClick={createPostOtRequestRecord}
                          >
                            <FormattedMessage id="sendRequest" defaultMessage="Send Request" />
                          </Button>
                        </Row>
                      </Col>
                    </Row>
                  </>
                }
              </Space>
            </ConfigProvider>
          </Card>
        </Col>
      </Row>
    </ProCard>
    // </Access>
  );
};

export default MyPostOtRequestTableView;
