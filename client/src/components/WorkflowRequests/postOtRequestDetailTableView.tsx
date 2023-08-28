import React, { useEffect, useRef, useState, useContext } from 'react';
import { InputRef, Select, List } from 'antd';
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
  Badge,
} from 'antd';
// import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { ProFormSelect } from '@ant-design/pro-form';
import ProForm, { ProFormDateRangePicker } from '@ant-design/pro-form';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import { ReactComponent as Comment } from '../../assets/attendance/Comment.svg';
import ProTable from '@ant-design/pro-table';
import _, { cond } from 'lodash';
const { TextArea } = Input;

import {
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
// import TimeChangeRequest from '../WorkflowRequests/timeChangeRequest';
import { getEmployeeList, getOtPayTypeList } from '@/services/dropdown';
import { getAllWorkShifts } from '@/services/workShift';
import en_US from 'antd/lib/locale-provider/en_US';
import { MinusCircleOutlined, PlusOutlined } from '@ant-design/icons';
import styles from '../../components/Attendance/attendance.less';
import ProCard from '@ant-design/pro-card';
import type { FormInstance } from 'antd/es/form';
import '../../components/Attendance/index.css';

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

      if (dataIndex != 'approveUserComment') {
        let arr = dataIndex.split('approved');
        let payCode = arr[1];

        if (record.otData.requestedOtDetails[payCode] == '00:00') {
          let key = 'Error';
          message.error({
            content: 'Can not set approved ot value for this pay type',
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

    if (dataIndex == 'approveUserComment') {
      form.setFieldsValue({ [dataIndex]: record.approveUserComment });
    } else {
      let arr = dataIndex.split('approved');
      let payCode = arr[1];
      let otDuration = moment(record.otData.approvedOtDetails[payCode], 'HH:mm').isValid()
        ? record.otData.approvedOtDetails[payCode]
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
        approveUserComment: record.approveUserComment ? record.approveUserComment : null,
        incompleUpdate: record.incompleUpdate ? record.incompleUpdate : false,
        isChanged: record.isChanged ? record.isChanged : false,
      };

      if (dataIndex != 'approveUserComment' && values.hasOwnProperty(dataIndex)) {
        let dataIndexArr = dataIndex.split('approved');
        let payCode = dataIndexArr[1];
        if (values[dataIndex]) {
          tempArr.otData.approvedOtDetails[payCode] = values[dataIndex];
        } else {
          tempArr.otData.approvedOtDetails[payCode] = '00:00';
        }
      }

      if (dataIndex == 'approveUserComment') {
        if (values[dataIndex]) {
          tempArr.approveUserComment = values[dataIndex];
        } else {
          tempArr.approveUserComment = null;
        }
      }

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
                  : dataIndex == 'approveUserComment'
                  ? 230
                  : 100,
            }}
            name={dataIndex}
            rules={getRules(dataIndex)}
          >
            {dataIndex == 'approveUserComment' ? (
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

                  let approvedOtCountString = value.target.value;
                  let approvedOtCountArr = approvedOtCountString.split(':');
                  let approvedWorkOtHours = parseInt(approvedOtCountArr[0]);
                  let approvedWorkOtMiniutes = parseInt(approvedOtCountArr[1]);
                  let approvedWorkOtHoursInMinutes = approvedWorkOtHours * 60;
                  let totalApprovedWorkOtInMinutes =
                    approvedWorkOtHoursInMinutes + approvedWorkOtMiniutes;

                  let requestOtCountString = record.otData.requestedOtDetails[payCode];
                  let requestOtCountArr = requestOtCountString.split(':');
                  let requestWorkOtHours = parseInt(requestOtCountArr[0]);
                  let requestWorkOtMiniutes = parseInt(requestOtCountArr[1]);
                  let requestWorkOtHoursInMinutes = requestWorkOtHours * 60;
                  let totalRequestWorkOtInMinutes =
                    requestWorkOtHoursInMinutes + requestWorkOtMiniutes;

                  if (totalApprovedWorkOtInMinutes > totalRequestWorkOtInMinutes) {
                    let key = 'Error';
                    message.error({
                      content: 'Approved Overtime Exceeds Limit',
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

                  let approvedOtCountString = value.target.value;
                  let approvedOtCountArr = approvedOtCountString.split(':');
                  let approvedWorkOtHours = parseInt(approvedOtCountArr[0]);
                  let approvedWorkOtMiniutes = parseInt(approvedOtCountArr[1]);
                  let approvedWorkOtHoursInMinutes = approvedWorkOtHours * 60;
                  let totalApprovedWorkOtInMinutes =
                    approvedWorkOtHoursInMinutes + approvedWorkOtMiniutes;

                  let requestOtCountString = record.otData.requestedOtDetails[payCode];
                  let requestOtCountArr = requestOtCountString.split(':');
                  let requestWorkOtHours = parseInt(requestOtCountArr[0]);
                  let requestWorkOtMiniutes = parseInt(requestOtCountArr[1]);
                  let requestWorkOtHoursInMinutes = requestWorkOtHours * 60;
                  let totalRequestWorkOtInMinutes =
                    requestWorkOtHoursInMinutes + requestWorkOtMiniutes;

                  if (totalApprovedWorkOtInMinutes > totalRequestWorkOtInMinutes) {
                    let key = 'Error';
                    message.error({
                      content: 'Approved Overtime Exceeds Limit',
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
      <div
        className={styles.nonEditableCellValueWrapInPostOtRequest}
        // style={{ paddingRight: 24 }}
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
  scope?: any;
  postOtRequestData?: any;
  accessLevel?: string;
  attendanceSheetData?: any;
  setAttendanceSheetData?: any;
  intialData?: any;
  requestState?: any;
  isApproveActionAvailable?: any;
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
    const newData = [...props.attendanceSheetData];
    const index = newData.findIndex((item) => row.id === item.id);
    // console.log(row);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });

    props.setAttendanceSheetData([...newData]);
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
    const index = props.intialData.findIndex((item) => editedRow.id === item.id);
    if (index == -1) {
      return false;
    }

    let orginalRecord = props.intialData[index];

    //check whether in time is changed
    if (orginalRecord.approveUserComment !== editedRow.approveUserComment) {
      return true;
    }

    let approvedOtChangeCount = 0;
    for (let index in editedRow.otData.approvedOtDetails) {
      let payKey = 'approved' + index;
      if (orginalRecord[payKey] != editedRow.otData.approvedOtDetails[index]) {
        approvedOtChangeCount++;
      }
    }

    if (approvedOtChangeCount > 0) {
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
      width: 120,
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
      width: 150,
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
      width: 150,
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
      width: 90,
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
    if (props.postOtRequestData.id) {
      actionRef.current?.reload();
    }
  }, []);


  useEffect(() => {
    if (outDateModel == undefined || outTimeModel || undefined || inTimeModel == undefined) {
      form.setFieldsValue({ relatedBreaksDetails: null });
    }
  }, [inDateModel, inTimeModel, outDateModel, outTimeModel]);


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
        let approvedOtColName = 'Approved' + ' ' + payType.name;
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

        let OTRequestedCol = {
          title: <FormattedMessage id={requestedOtColName} defaultMessage={requestedOtColName} />,
          dataIndex: 'requested' + payType.code,
          //   editable : true,
          width: 90,
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

        let OTApprovedCol = {
          title: <FormattedMessage id={approvedOtColName} defaultMessage={approvedOtColName} />,
          dataIndex: 'approved' + payType.code,
          editable: props.scope == 'EMPLOYEE' ? false : true,
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
        colSet.push(OTRequestedCol);
        colSet.push(OTApprovedCol);
      });
    }

    let approveUserCommentCol = {
      title: (
        <FormattedMessage
          id="Attendance.appComments"
          defaultMessage={intl.formatMessage({
            id: 'appComments',
            defaultMessage: 'App.Comments',
          })}
        />
      ),
      dataIndex: 'approveUserComment',
      fixed: 'right',
      width: 250,
      editable: props.scope == 'EMPLOYEE' ? false : true,
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
                      {record.approveUserComment &&
                      record.approveUserComment !== null &&
                      record.approveUserComment.length <= 25 ? (
                        <span>{record.approveUserComment}</span>
                      ) : record.approveUserComment &&
                        record.approveUserComment !== null &&
                        record.approveUserComment.length > 25 ? (
                        <Tooltip title={record.approveUserComment}>
                          {record.approveUserComment.substring(0, 25 - 3) + '...'}
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

    let approveUserCommentHistoryCol = {
      title:
        props.requestState != 2 &&
        props.requestState != 3 &&
        props.requestState != 4 &&
        props.isApproveActionAvailable
          ? ''
          : 'Approver Comments',
      dataIndex: '',
      fixed: 'right',
      width:
        props.requestState != 2 &&
        props.requestState != 3 &&
        props.requestState != 4 &&
        props.isApproveActionAvailable
          ? 10
          : 100,
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
                    {/* <Tooltip title={'Approver Comments'}> */}
                    <Popover
                      content={
                        <Row style={{ width: 400 }}>
                          <List
                            itemLayout="horizontal"
                            dataSource={record.approveUserCommentList}
                            style={
                              record.approveUserCommentList > 3
                                ? { overflowY: 'scroll', height: 150, width: '100%' }
                                : { width: '100%' }
                            }
                            renderItem={(item, index) => (
                              <List.Item key={index}>
                                <List.Item.Meta
                                  // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                                  title={
                                    <Row>
                                      <p
                                        key="commentedUserName"
                                        style={{
                                          fontSize: 15,
                                          fontWeight: 500,
                                          marginBottom: 0,
                                          marginRight: 10,
                                        }}
                                      >
                                        {item.level}
                                      </p>
                                      <p
                                        key="commentDateTime"
                                        style={{
                                          fontSize: 13,
                                          marginBottom: 0,
                                          fontWeight: 400,
                                          marginRight: 10,
                                          paddingTop: 2,
                                          color: '#626D6C',
                                        }}
                                      >
                                        {'(' + item.performBy + ')'}
                                      </p>
                                    </Row>
                                  }
                                  description={item.comment ? item.comment : 'No Comments'}
                                />
                              </List.Item>
                            )}
                          />
                        </Row>
                      }
                      placement="left"
                      title="Approver Comments"
                      trigger="click"
                    >
                      <Badge
                        size="small"
                        style={
                          record.approveUserCommentList.length > 0
                            ? { fontSize: 10 }
                            : { fontSize: 10, display: 'none' }
                        }
                        color="green"
                        count={record.approveUserCommentList.length}
                      >
                        <Comment height={20} width={20} style={{ cursor: 'pointer' }}></Comment>
                      </Badge>
                    </Popover>

                    {/* </Tooltip> */}
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    };

    let requestedEmployeeCommentCol = {
      title: (
        <FormattedMessage
          id="Attendance.workHours"
          defaultMessage={intl.formatMessage({
            id: 'empComments',
            defaultMessage: 'Emp.Comments',
          })}
        />
      ),
      dataIndex: 'requestedEmployeeComment',
      fixed: 'right',
      width: 150,
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
                      {record.requestedEmployeeComment &&
                      record.requestedEmployeeComment !== null &&
                      record.requestedEmployeeComment.length <= 14 ? (
                        <span>{record.requestedEmployeeComment}</span>
                      ) : record.requestedEmployeeComment &&
                        record.requestedEmployeeComment !== null &&
                        record.requestedEmployeeComment.length > 14 ? (
                        <Tooltip title={record.requestedEmployeeComment}>
                          {record.requestedEmployeeComment.substring(0, 14 - 3) + '...'}
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

    if (
      props.requestState != 2 &&
      props.requestState != 3 &&
      props.requestState != 4 &&
      props.isApproveActionAvailable
    ) {
      colSet.push(approveUserCommentCol);
    }

    colSet.push(approveUserCommentHistoryCol);
    colSet.push(requestedEmployeeCommentCol);
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
          props.requestState != 2 &&
          props.requestState != 3 &&
          props.requestState != 4 &&
          props.isApproveActionAvailable
            ? col.editable
            : false,
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
    } catch (err) {
      console.log(err);
    }
  }

  return (
    <ProCard direction="column" ghost gutter={[0, 16]} style={{ padding: 0, margin: 0 }}>
      <Row style={{ width: '100%', height: 'auto' }}>
        <Col className={'attendanceSearchArea'} style={{ width: '100%' }}>
          {/* <Card style={{ marginTop: 20 ,width: '100%', height: 500}}> */}
          <ConfigProvider locale={en_US}>
            <Space direction="vertical" size={25} style={{ width: '100%' }}>
              {/* {showDataTable ? (
                  <> */}
              {
                <Row>
                  <Col span={24}>
                    <div className={styles.spinCol}>
                      <Spin size="large" spinning={loading}>
                        <Row className={'adminAttendanceTable'}>
                          <ProTable<AttendanceItem>
                            columns={cols as ColumnTypes}
                            scroll={
                              !isMaintainOt && props.attendanceSheetData.length > 4
                                ? { y: 500 }
                                : !isMaintainOt && props.attendanceSheetData.length < 4
                                ? undefined
                                : isMaintainOt && props.attendanceSheetData.length > 4
                                ? { x: '100vw', y: 230 }
                                : isMaintainOt && props.attendanceSheetData.length < 4
                                ? { x: '100vw' }
                                : undefined
                            }
                            components={components}
                            rowClassName={() => 'editableRow'}
                            toolBarRender={false}
                            actionRef={actionRef}
                            dataSource={props.attendanceSheetData}
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
              {/* </> */}
            </Space>
          </ConfigProvider>
          {/* </Card> */}
        </Col>
      </Row>
    </ProCard>
  );
};

export default MyPostOtRequestTableView;
