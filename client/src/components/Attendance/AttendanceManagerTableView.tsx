import React, { useEffect, useRef, useState, useContext } from 'react';
import { InputRef, Select } from 'antd';
import type { RadioChangeEvent } from 'antd';
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
  Radio,
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
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import _, { cond } from 'lodash';
import ProForm, { ProFormDateRangePicker } from '@ant-design/pro-form';

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
import employee from '@/services/employee';

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

const AttendanceManagerTableView: React.FC<TableViewProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const [actionIds, setActionIds] = useState<string | null>(null);
  const [workflowId, setWorkflowId] = useState<string | null>(null);
  const [workflowInstanceId, setworkflowInstanceId] = useState<string | null>(null);
  const [contextId, setContextId] = useState<string | null>(null);
  const actionRef = useRef<ActionType>();
  const [selectorEmployees, setSelectorEmployees] = useState([]);
  const [employeeList, setEmployeeList] = useState([]);
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
  const [relatedBreaksDetails, setRelatedBreaksDetails] = useState([]);

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
  const [filteredShiftDay, setFilteredShiftDay] = useState<moment.Moment>();
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
  const [tableMode, setTableMode] = useState('week');

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

  const tableFilterOptions = [
    { label: 'Week', value: 'week' },
    { label: 'Day', value: 'day' },
  ];

  const changeTableModeFilter = async ({ target: { value } }: RadioChangeEvent) => {
    setTableMode(value);
    if (value == 'day') {
      setShowDataTable(true);
      setFilteredShiftDay(moment());

      const selectorEmployees = employeeList.map((employee: any) => {
        return {
          label: employee.employeeNumber+' | '+employee.employeeName,
          value: employee.id,
          disabled: true,
        };
      });
      setSelectorEmployees(selectorEmployees);

      // setSelectedEmployee(undefined);
    } else {
      setShowDataTable(false);
      setFilteredShiftDay(null);
      // setSelectedEmployee(undefined);
      const selectorEmployees = employeeList.map((employee: any) => {
        return {
          label: employee.employeeNumber+' | '+employee.employeeName,
          value: employee.id,
          disabled: false,
        };
      });
      setSelectorEmployees(selectorEmployees);
    }
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
      title: <FormattedMessage id="Attendance.name" defaultMessage="Employee Name" />,
      dataIndex: 'name',
      fixed: 'left',
      // hideInTable: tableMode == 'day' ? false : true,
      sorter: true,
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
          id="Attendance.shift"
          defaultMessage={intl.formatMessage({
            id: 'shiftName',
            defaultMessage: 'Shift Name',
          })}
        />
      ),
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
      title: (
        <FormattedMessage
          id="Attendance.inTime"
          defaultMessage={intl.formatMessage({
            id: 'inDateAndTime',
            defaultMessage: 'In',
          })}
        />
      ),
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
                    <p className={styles.time}>
                      {moment(record.in.date, 'YYYY-MM-DD').isValid() &&
                      moment(record.in.time, 'hh:mm A').isValid()
                        ? moment(record.in.date + ' ' + moment(record.in.time, 'hh:mm A').format('HH:mm')).format(
                            'DD-MM-YYYY HH:mm:ss',
                          )
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
                    <p className={styles.time}>
                      {moment(record.outDate, 'YYYY-MM-DD').isValid() &&
                      moment(record.out.time, 'hh:mm A').isValid()
                        ? moment(record.outDate + ' ' + moment(record.out.time, 'hh:mm A').format('HH:mm')).format(
                            'DD-MM-YYYY HH:mm:ss',
                          )
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
          id="Attendance.OtHours"
          defaultMessage={intl.formatMessage({
            id: 'inLate',
            defaultMessage: 'In Late',
          })}
        />
      ),
      dataIndex: 'inLate',
      fixed : !isMaintainOt ? 'left' : false,
      width : !isMaintainOt ? 'auto' : undefined,
      hideInTable: false,
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
      title: (
        <FormattedMessage
          id="Attendance.OtHours"
          defaultMessage={intl.formatMessage({
            id: 'earlyDepature',
            defaultMessage: 'Early Depature',
          })}
        />
      ),
      dataIndex: 'earlyDepature',
      fixed : !isMaintainOt ? 'left' : false,
      width : !isMaintainOt ? 'auto' : undefined,
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
      title: (
        <FormattedMessage
          id="Attendance.OtHours"
          defaultMessage={intl.formatMessage({
            id: 'totalLate',
            defaultMessage: 'Total Late',
          })}
        />
      ),
      dataIndex: 'totalLate',
      search: false,
      fixed : !isMaintainOt ? 'left' : false,
      width : !isMaintainOt ? 'auto' : undefined,
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
      title: (
        <FormattedMessage
          id="Attendance.OtHours"
          defaultMessage={intl.formatMessage({
            id: 'totalOt',
            defaultMessage: 'Total OT',
          })}
        />
      ),
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
      title: (
        <FormattedMessage
          id="Attendance.OtHours"
          defaultMessage={intl.formatMessage({
            id: 'totalApprovedOt',
            defaultMessage: 'Total Approved OT',
          })}
        />
      ),
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
    if (filteredShiftDay) {
      callGetAttendanceSheetData(1, 100).then(() => {});
    }
  }, [filteredShiftDay]);

  useEffect(() => {
    if (outDateModel == undefined || outTimeModel || undefined || inTimeModel == undefined) {
      form.setFieldsValue({ relatedBreaksDetails: null });
    }
  }, [inDateModel, inTimeModel, outDateModel, outTimeModel]);

  const processedCols = () => {
    let colSet = columns;

    if (isMaintainOt) {
      payTypes.forEach((payType) => {
        let colName = payType.name;
        let approvedcolName = 'Approved' + ' ' + payType.name;
        let tempOTCol = {
          title: <FormattedMessage id={payType.name} defaultMessage={payType.name} />,
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
          dataIndex: 'approved' + payType.code,
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
      title: (
        <FormattedMessage
          id="Attendance.workHours"
          defaultMessage={intl.formatMessage({
            id: 'totalBreakTime',
            defaultMessage: 'Total Breake Time',
          })}
        />
      ),
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

                        let realtedBreaks = await getRelatedBreakes({ summeryId: record.id });

                        const breakeData = realtedBreaks.data.map((col) => {
                          return {
                            id: col.id,
                            breakInTime: moment(col.breakInTime, 'hh:mm A').format('hh:mm A'),
                            breakInDate: moment(col.breakInDate, 'YYYY-MM-DD').format('YYYY-MM-DD'),
                            breakOutTime: moment(col.breakOutTime, 'hh:mm A').format('hh:mm A'),
                            breakOutDate: moment(col.breakOutDate, 'YYYY-MM-DD').format(
                              'YYYY-MM-DD',
                            ),
                          };
                        });
                        await setRelatedBreaksDetails(breakeData);

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
    return col;
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
    setIsModalVisible(true);
    setLoading(false);
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
      setEmployeeList(data);
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
    sort = { name: 'name', order: 'DESC' },
  ) {
    setLoading(true);
    let shiftDate = filteredShiftDay ? filteredShiftDay.format('YYYY-MM-DD') : null;

    const params = {
      employee: JSON.stringify(selectedEmployee),
      fromDate: tableMode == 'day' ? shiftDate : fromDate,
      toDate: tableMode == 'day' ? shiftDate : toDate,
      pageNo: pageNo,
      pageCount: pageCount,
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
            setDayTypesData(response.data.relatedDayTypes);
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
            setDayTypesData(response.data.relatedDayTypes);
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
    <ProCard
      className="attendanceManagerCard"
      direction="column"
      ghost
      gutter={[0, 16]}
      style={{ padding: 0, margin: 0 }}
    >
      <Row style={{ width: '100%' }}>
        <Col style={{ width: '100%' }}>
          <Row className={styles.attendanceSearchArea}>
            {tableMode == 'week' ? (
              <>
                <Col className={'attendanceSearchArea'} span={14}>
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
                              callGetAttendanceSheetData(1, 100);
                              console.log(dayTypesData);
                            }
                          }}
                        />
                      </Tooltip>
                    </Col>
                  </>
                ];
              },
            }}
          >
            <Row  style={{width: '80%'}}>
              <Col span={7}>
                <ProFormDateRangePicker
                  name="searchDateRange"
                  // className={styles.rangePicker}
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
                <Col className={styles.employeeCol} span={17}>
                  <ProFormSelect
                    name="select"
                    placeholder={intl.formatMessage({
                      id: 'employee.placeholder',
                      defaultMessage: 'Search Employee',
                    })}
                    showSearch
                    mode = {'multiple'}
                    options={selectorEmployees}
                    fieldProps={{
                      optionItemRender(item) {
                        return item.label;
                      },
                      maxTagCount : 2,
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
                
                </Col>
              </>
            ) : (
              <Col className={'attendanceSearchArea'} span={12}>
                <Row>
                  <Col span={5}>
                    <DatePicker
                      onChange={(date, dateString) => {
                        if (date) {
                          setFilteredShiftDay(date);
                        } else {
                          setFilteredShiftDay(moment());
                        }
                      }}
                      format="DD-MM-YYYY"
                      value={filteredShiftDay}
                      style={{ borderRadius: 6 }}
                    />
                  </Col>
                  <Col className={styles.employeeCol} span={19}>
                    <Select
                      name="select"
                      placeholder={intl.formatMessage({
                        id: 'employee.placeholder',
                        defaultMessage: 'Search Employee',
                      })}
                      style={{width: '100%'}}
                      mode = {'multiple'}
                      showSearch
                      maxTagCount={2}
                      options={selectorEmployees}
                      defaultValue={selectedEmployee}
                      // fieldProps={{
                      //   optionItemRender(item) {
                      //     return item.label;
                      //   },
                      //   onChange: (value) => {
                      //     setSelectedEmployee(value);
                      //     setAttendanceSheetData([]);
                      //   },
                      // }}
                      // rules={
                      //   [
                      //     {
                      //       required: true,
                      //       message: intl.formatMessage({
                      //         id: 'employee.attendance.search.employee.required',
                      //         defaultMessage: 'Required',
                      //       })
                      //     }
                      //   ]
                      // }
                    />
                  </Col>
                </Row>
              </Col>

            )}
            <Access accessible={hasPermitted('attendance-manager-access') || othersView}>
              <Col style={{ marginBottom: 16 }} span={ tableMode == 'week' ? 10 : 12}>
                <Radio.Group
                  style={{ float: 'right' }}
                  options={tableFilterOptions}
                  onChange={changeTableModeFilter}
                  value={tableMode}
                  optionType="button"
                />
              </Col>
            </Access>
          </Row>
          <Card style={{ width: '100%', height: 'auto' }}>
            <ConfigProvider locale={en_US}>
              <Space direction="vertical" size={25} style={{ width: '100%' }}>
                {showDataTable ? (
                  <>
                    {
                      <Row>
                        <Col span={24}>
                          <div className={styles.spinCol}>
                            <Spin size="large" spinning={loading}>
                              <Row className={'managerAttendanceTable'}>
                                <ProTable<AttendanceItem>
                                  columns={cols as ColumnTypes}
                                  scroll={ !isMaintainOt && attendanceSheetData.length > 10 ?  {y: 500 } : !isMaintainOt && attendanceSheetData.length < 10 ? undefined : isMaintainOt && attendanceSheetData.length > 10 ? { x: '100vw', y: 500 } : isMaintainOt && attendanceSheetData.length < 10 ? { x: '100vw'} : undefined}
                                  //   components={components}
                                  rowClassName={() => 'managerTableRow'}
                                  actionRef={actionRef}
                                  dataSource={attendanceSheetData}
                                  request={async (
                                    params = { current: 1, pageSize: 100 },
                                    sort,
                                    filter,
                                  ) => {
                                    const sortValue = sort?.date
                                      ?  {
                                        name: 'date',
                                        order: sort?.date === 'ascend' ? 'ASC' : 'DESC',
                                        }
                                      : {
                                        name: 'name',
                                        order: sort?.name === 'ascend' ? 'ASC' : 'DESC',
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
                                          // eslint-disable-next-line react/no-array-index-key
                                          <>
                                            <Badge color={item.typeColor} text={item.name}/>
                                          </>
                                        ))}
                                      </Space>
                                    </Row>
                                  }
                                  pagination={{
                                    pageSize: 100,
                                    total: dataCount,
                                    hideOnSinglePage: true,
                                  }}
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
                  width={500}
                  onCancel={handleCancel}
                  centered
                  footer={[
                    <>
                      <Button key="back" onClick={handleCancel} disabled={disableModelButtons}>
                        Cancel
                      </Button>
                    </>,
                  ]}
                >
                  {loadingModel ? (
                    <Spin size="large" spinning={loadingModel} />
                  ) : (
                    <>
                      <Access
                        accessible={props.accessLevel == 'admin' || props.accessLevel == 'manager'}
                      >
                        <Form form={form} layout="vertical" style={{ width: '100%' }}>
                          <Row>
                            <Col span={4} style={{ marginBottom: 5 }}>
                              <p style={{ fontWeight: 'bold' }}>{'Date'}</p>
                            </Col>
                            <Col span={8} style={{ paddingLeft: 25}}>{shiftDate}</Col>
                          </Row>
                          <Row style={{ marginBottom: 5 }}>
                            <Col span={4}>
                              <p style={{ fontWeight: 'bold' }}>{'Shift'}</p>
                            </Col>
                            <Col span={8} style={{ paddingLeft: 25}}>{shift}</Col>
                          </Row>
                          <Row style={{ marginBottom: 40 }}>
                            <Col span={5}>
                              <p style={{ fontWeight: 'bold' }}>{'Total Breaks'}</p>
                            </Col>
                            <Col span={8} style={{ paddingLeft:5}}>
                              <a>{totalBreake}</a>
                            </Col>
                          </Row>
                          <Row style={{ marginBottom: 5 }}>
                            <Col style={{ paddingBottom: 8, fontSize: 16, fontWeight: 'bold' }}>
                              <FormattedMessage id="breakDetails" defaultMessage="Breaks" />
                            </Col>
                          </Row>
                          {relatedBreaksDetails.length > 0 ? (
                            <>
                              <Row style={{ marginBottom: 10}}>
                                <Col span={10} style={{ fontWeight: 'bold' }}>Break Start</Col>
                                <Col span={2}></Col>
                                <Col span={11} style={{ fontWeight: 'bold' }}>Break End</Col>
                              </Row>
                              <Row>
                                {relatedBreaksDetails.map((el) => {
                                  return (
                                    <>
                                      <Col span={10}>
                                        <div style={{ display: 'flex' }}>
                                          <p style={{ marginRight: 10 }}>{el.breakInDate}</p>
                                          <p style={{ fontWeight: 'bold' }}>{el.breakInTime}</p>
                                        </div>{' '}
                                      </Col>
                                      <Col span={2}>-</Col>
                                      <Col span={11}>
                                        <div style={{ display: 'flex' }}>
                                          <p style={{ marginRight: 10 }}>{el.breakOutDate}</p>
                                          <p style={{ fontWeight: 'bold' }}>{el.breakOutTime}</p>
                                        </div>{' '}
                                      </Col>
                                    </>
                                  );
                                })}
                              </Row>
                            </>
                          ) : (
                            <Row style={{ color: 'grey' }}>{'No Breake Records Available'}</Row>
                          )}
                        </Form>
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

export default AttendanceManagerTableView;
