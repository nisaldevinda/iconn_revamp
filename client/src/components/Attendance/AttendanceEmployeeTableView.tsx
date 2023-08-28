import React, { useEffect, useRef, useState } from 'react';
import {
  Button,
  Space,
  Image,
  Row,
  Col,
  Tooltip,
  Spin,
  Modal,
  DatePicker,
  TimePicker,
  Form,
  message,
  ConfigProvider,
  Card,
  Table,
  Switch,
  Alert,
  Badge
} from 'antd';
import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType, ColumnsState } from '@ant-design/pro-table';
import moment from 'moment';
import { Access, FormattedMessage, useAccess, useIntl, history } from 'umi';
import ProTable from '@ant-design/pro-table';
import _ from 'lodash';

import {
  getAttendanceSheetAdminData,
  getAttendanceSheetEmployeeData,
  getAttendanceSheetManagerData,
  requestTimeChange,
  downloadManagerAttendanceView,
  downloadAdminAttendanceView,
  getRelatedBreakes,
} from '@/services/attendance';
import LateIcon from '../../assets/attendance/icon-clock-red.svg';
import EarlyIcon from '../../assets/attendance/icon-clock-orange.svg';
import RequestIcon from '../../assets/attendance/Time-change-request.svg';
import LeaveIcon from '../../assets/attendance/Quote_request.svg';
import { APIResponse } from '@/utils/request';
import { downloadBase64File } from '@/utils/utils';
import { getEmployeeList, getOtPayTypeList } from '@/services/dropdown';
import en_US from 'antd/lib/locale-provider/en_US';
import { MinusCircleOutlined, PlusOutlined } from '@ant-design/icons';
import styles from './attendance.less';
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

const AttendanceEmployeeTableView: React.FC<TableViewProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

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
  const [othersView, setOthersView] = useState(props.others ?? false);
  const [adminView, setAdminView] = useState(props.adminView ?? false);
  const [loading, setLoading] = useState(false);
  const [sugessionText, setSugessionText] = useState(null);

  const [isModalVisible, setIsModalVisible] = useState(false);
  const [loadingModel, setLoadingModel] = useState(false);
  const [disableModelButtons, setDisableModelButtons] = useState(false);
  const [isNeedAddBreaks, setIsNeedAddBreaks] = useState(false);
  const [loadingModelRequest, setLoadingModelRequest] = useState(false);
  const [isMaintainOt, setIsMaintainOt] = useState(false);
  const [loadingExcelDownload, setLoadingExcelDownload] = useState(false);
  const [editStatusModel, setEditStatusModel] = useState(!props.nonEditModel);
  const [dateModel, setDateModel] = useState('');
  const [currentEditingRow, setCurrentEditingRow] = useState(null);
  const [reasonModel, setReasonModel] = useState('');
  const [shiftIdModel, setShiftIdModel] = useState<number>();
  const [summaryIdModel, setSummaryIdModel] = useState<number>();
  const [employeeIdModel, setEmployeeIdModel] = useState<number>();

  const [inDateModel, setInDateModel] = useState<moment.Moment>();
  const [filterMonth, setFilterMonth] = useState<moment.Moment>();
  const [validatedStatusInDate, setValidateStatusInDate] = useState<'' | 'error'>('');
  const [helpInDate, setHelpInDate] = useState('');

  const [outDateModel, setOutDateModel] = useState<moment.Moment>();
  const [validateStatusOutDate, setValidateStatusOutDate] = useState<'' | 'error'>('');
  const [helpOutDate, setHelpOutDate] = useState('');

  const [inTimeModel, setInTimeModel] = useState<moment.Moment>();
  const [validateStatusInTime, setValidateStatusInTime] = useState<'' | 'error'>('');
  const [helpInTime, setHelpInTime] = useState('');
  const [tableMode, setTableMode] = useState('week');

  const [outTimeModel, setOutTimeModel] = useState<moment.Moment>();
  const [validateStatusOutTime, setValidateStatusOutTime] = useState<'' | 'error'>('');
  const [helpOutTime, setHelpOutTime] = useState(' ');
  const key = 'saving';
  const [form] = Form.useForm();

  const [columnsStateMap, setColumnsStateMap] = useState<Record<string, ColumnsState>>({
    // date: {
    //   show: false,
    // },

  });

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
                <Badge style={{ marginRight: 8 }} color={record.day.dayTypeColor} />
              }
              {
                record.shiftId != null && record.isExpectedToPresent &&
                  record.day.isWorked === 0 &&
                  record.leave.length == 0 ? (
                  <Badge color="#44A4ED" />
                ) : (record.shiftId != null && record.isExpectedToPresent && record.day.isWorked === 1 && record.in.late || record.shiftId != null && record.isExpectedToPresent && record.day.isWorked === 1 && record.out.early) ? (
                  <Badge color="#ED4444" />
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
                    : null}

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
      hideInTable: tableMode == 'day' ? false : true,
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
            id: 'inTime',
            defaultMessage: 'In',
          })}
        />
      ),
      dataIndex: 'in',
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
                    {/* <p className={styles.time}>{
                    moment(record.in.time, 'hh:mm A').isValid() ? moment(record.in.time, 'hh:mm A').format('HH:mm:ss') : '-'
                    }</p> */}


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
      fixed: !isMaintainOt ? 'left' : false,
      width: !isMaintainOt ? 'auto' : undefined,
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
      fixed: !isMaintainOt ? 'left' : false,
      width: !isMaintainOt ? 'auto' : undefined,
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
      fixed: !isMaintainOt ? 'left' : false,
      width: !isMaintainOt ? 'auto' : undefined,
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
    callGetOTPayTypesData();
  }, []);

  useEffect(() => {
    if (isModalVisible) {
      getRelatedBreakDetails();
    }
  }, [isModalVisible]);

  useEffect(() => {
    if (filterMonth) {
      callGetAttendanceSheetData(1, 100).then(() => { });
    }
  }, [filterMonth]);

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
    if (realtedBreaks.data.length > 0) {
      setIsNeedAddBreaks(true);
    } else {
      setIsNeedAddBreaks(false);
    }
    setLoading(false);
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
                  <Space>{record.duration.breaks ? record.duration.breaks : '00:00'}</Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    };

    let actionCol = {
      title: 'Actions',
      dataIndex: 'action',
      fixed: 'right',
      width: 70,
      search: false,
      render: (_, record) => {
        return {
          props: {
            style: record.leave.length > 0 ? { background: '#FFF7E6' } : {},
          },
          children: (
            <div style={{ display: 'flex' }}>
              {!record.requestedTimeChangeId ? (
                <Tooltip title={intl.formatMessage({
                  id: 'applyTimeChangeRequest',
                  defaultMessage: 'Time Change Request',
                })}>
                  <Button
                    type="text"
                    icon={<Image style={{ width: 22 }} src={RequestIcon} preview={false} />}
                    onClick={() => {
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

                      form.setFieldsValue({ relatedBreaksDetails: null });

                      resetStates(record, outDateRecord, inTimeRecord, outTimeRecord);

                      setReasonModel('');
                      setLoadingModelRequest(false);
                    }}
                  />
                </Tooltip>
              ) : (
                <div style={{ width: 32 }}></div>
              )}

              {
                record.leave.length == 0 ? (

                  <Tooltip title={intl.formatMessage({
                    id: 'applyLeave',
                    defaultMessage: 'Apply Leave',
                  })}>
                    <Button
                      type="text"
                      icon={<Image style={{ width: 25, height: 15 }} src={LeaveIcon} preview={false} />}
                      onClick={() => {
                        history.push({
                          pathname: '/ess/apply-leave',
                          state: { date: record.date }
                        });
                      }}
                    />
                  </Tooltip>

                ) : (
                  <></>
                )
              }
            </div>
          ),
        };
      },
    };
    colSet.push(breakeTimeCol);
    colSet.push(workedHours);
    colSet.push(actionCol);
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

    const params = {
      employee: selectedEmployee,
      fromDate: startOfMonth,
      toDate: endOfMonth,
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
            setSugessionText(response.data.suggesionParagraph);
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

    handleChange(breakList);
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
            {
              <>
                <Col style={{ marginBottom: 16 }} span={3}>
                  <DatePicker
                    style={{ borderRadius: 6 }}
                    onChange={(date) => {
                      if (date) {
                        setFilterMonth(date);
                      } else {
                        setFilterMonth(undefined);
                      }
                    }}
                    picker="month"
                    format={'YYYY/MM'}
                  />
                </Col>
                {sugessionText ? (
                  <Col className="suggesionParagraph" span={20} offset={1}>
                    <div>
                      {sugessionText !== null && sugessionText.length <= 150 ? (
                        <Alert
                          style={{
                            width: sugessionText.length > 130 ? 730 : 'auto',
                            float: 'right',
                            backgroundColor: '#FFF7E6',
                            color: '#FFD591',
                            borderRadius: 6,
                            fontSize: 12,
                          }}
                          message={sugessionText}
                          type="warning"
                        />
                      ) : sugessionText !== null && sugessionText.length > 130 ? (
                        <Tooltip style={{ width: 800 }} title={sugessionText}><Alert
                          style={{
                            width: sugessionText.length > 130 ? 730 : 'auto',
                            float: 'right',
                            backgroundColor: '#FFF7E6',
                            color: '#FFD591',
                            borderRadius: 6,
                            fontSize: 12,
                          }}
                          message={sugessionText.substring(0, 130 - 3) + '...'}
                          type="warning"
                        /></Tooltip>
                      ) : (
                        <>-</>
                      )}
                    </div>
                  </Col>
                ) : (
                  <></>
                )}
              </>
            }
          </Row>
          <Card style={{ width: '100%', height: 'auto' }}>
            <ConfigProvider locale={en_US}>
              <Space direction="vertical" size={25} style={{ width: '100%' }}>
                {
                  <>
                    {
                      <Row>
                        <Col span={24}>
                          <div className={styles.spinCol}>
                            <Spin size="large" spinning={loading}>
                              <Row className={'managerAttendanceTable'}>
                                <ProTable<AttendanceItem>
                                  columns={cols as ColumnTypes}
                                  scroll={!isMaintainOt && attendanceSheetData.length > 10 ? { y: 500 } : !isMaintainOt && attendanceSheetData.length < 10 ? undefined : isMaintainOt && attendanceSheetData.length > 10 ? { x: '100vw', y: 500 } : isMaintainOt && attendanceSheetData.length < 10 ? { x: '100vw' } : undefined}
                                  //   components={components}
                                  rowClassName={() => 'managerTableRow'}
                                  actionRef={actionRef}
                                  dataSource={attendanceSheetData}
                                  columnsStateMap={columnsStateMap}
                                  onColumnsStateChange={(map) => setColumnsStateMap(map)}
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
                                          <Badge color="#44A4ED" text={'Absent'} />
                                        </>
                                        <>
                                          <Badge color="#ED4444" text={'Late'} />
                                        </>

                                        {dayTypesData.map((item) => (
                                          // eslint-disable-next-line react/no-array-index-key
                                          <>
                                            <Badge color={item.typeColor} text={item.name} />
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
                }

                <Modal
                  title={
                    <FormattedMessage
                      id="Time_Change_Request"
                      defaultMessage="Time Change Request"
                    />
                  }
                  visible={isModalVisible}
                  width={830}
                  // onOk={handleOk}
                  onCancel={handleCancel}
                  centered
                  footer={[
                    <Access accessible={hasPermitted('my-attendance') && !othersView}>
                      <Button key="back" onClick={handleCancel} disabled={disableModelButtons}>
                        Cancel
                      </Button>
                    </Access>,
                    <Access accessible={hasPermitted('my-attendance') && !othersView}>
                      <Button
                        key="submit"
                        type="primary"
                        loading={loadingModelRequest}
                        disabled={disableModelButtons}
                        onClick={onFinish}
                      >
                        <FormattedMessage id="Send" defaultMessage="Send" />
                      </Button>
                    </Access>,
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
                            <Col span={7}>
                              <Form.Item
                                className="pro-field pro-field-md"
                                validateStatus={validatedStatusInDate}
                                help={helpInDate}
                                label={<FormattedMessage id="In_Date" defaultMessage="In Date" />}
                                required={editStatusModel}
                                style={{ margin: 0 }}
                              >
                                <DatePicker
                                  disabled
                                  style={{ width: '80%' }}
                                  value={inDateModel}
                                  onChange={(date, dateString) => {
                                    setValidateStatusInDate('');
                                    setHelpInDate('');

                                    const changedDate = dateString
                                      ? moment(dateString, 'YYYY-MM-DD')
                                      : undefined;
                                    setInDateModel(changedDate);

                                    if (!changedDate) {
                                      setValidateStatusInDate('error');
                                      setHelpInDate('Please set in date');
                                    }
                                  }}
                                  disabledDate={disableDates}
                                />
                              </Form.Item>
                            </Col>
                            <Col span={7}>
                              <Form.Item
                                className="pro-field pro-field-md"
                                validateStatus={validateStatusInTime}
                                help={helpInTime}
                                label={<FormattedMessage id="In_Time" defaultMessage="In Time" />}
                                required={editStatusModel}
                              >
                                <TimePicker
                                  disabled={!editStatusModel}
                                  style={{ width: '80%' }}
                                  // use12Hours
                                  format="HH:mm"
                                  value={inTimeModel}
                                  onSelect={(timeString) => {
                                    setValidateStatusInTime('');
                                    setHelpInTime('');

                                    const changedInTime = timeString
                                      ? moment(timeString, 'hh:mm:ss A')
                                      : undefined;

                                    setInTimeModel(changedInTime);

                                    if (editStatusModel && !timeString) {
                                      setValidateStatusInTime('error');
                                      setHelpInTime('Please set in time');
                                    }
                                  }}
                                />
                              </Form.Item>
                            </Col>
                          </Row>
                          <Row>
                            <Col span={7}>
                              <Form.Item
                                className="pro-field pro-field-md"
                                validateStatus={validateStatusOutDate}
                                help={helpOutDate}
                                label={<FormattedMessage id="Out_Date" defaultMessage="Out Date" />}
                                required={editStatusModel}
                                style={{ marginTop: 0 }}
                              >
                                <DatePicker
                                  disabled={!editStatusModel}
                                  value={outDateModel}
                                  style={{ width: '80%' }}
                                  onChange={(date, dateString) => {
                                    setValidateStatusOutDate('');
                                    setHelpOutDate('');

                                    const changedDate = dateString
                                      ? moment(dateString, 'YYYY-MM-DD')
                                      : undefined;
                                    setOutDateModel(changedDate);

                                    // validatedDates(inDateModel, changedDate);
                                  }}
                                  disabledDate={disableDates}
                                />
                              </Form.Item>
                            </Col>
                            <Col span={7}>
                              <Form.Item
                                className="pro-field pro-field-md"
                                validateStatus={validateStatusOutTime}
                                help={helpOutTime}
                                label={<FormattedMessage id="Out_Time" defaultMessage="Out Time" />}
                                required={editStatusModel}
                                style={{ marginTop: 0 }}
                              >
                                <TimePicker
                                  disabled={!editStatusModel}
                                  // use12Hours
                                  style={{ width: '80%' }}
                                  format="HH:mm"
                                  value={outTimeModel}
                                  onSelect={(timeString) => {
                                    setValidateStatusOutTime('');
                                    setHelpOutTime('');

                                    const changedOutTime = timeString
                                      ? moment(timeString, 'hh:mm:ss A')
                                      : undefined;

                                    setOutTimeModel(changedOutTime);

                                    if (editStatusModel && !timeString) {
                                      setValidateStatusOutTime('error');
                                      setHelpOutTime('Please set out time');
                                    }
                                  }}
                                />
                              </Form.Item>
                            </Col>
                          </Row>
                          <Row style={{ marginTop: 0 }}>
                            <Col span={10}>
                              <FormattedMessage id="Reason" defaultMessage="Reason" />
                            </Col>
                          </Row>
                          <Row style={{ marginTop: 5, marginBottom: 20 }}>
                            <Col span={14}>
                              <TextArea
                                style={{ width: '90%' }}
                                disabled={!editStatusModel}
                                rows={4}
                                value={reasonModel}
                                onChange={(e) => {
                                  setReasonModel(e.target.value);
                                }}
                              />
                            </Col>
                          </Row>

                          <Row style={{ display: 'flex', marginBottom: 20 }}>
                            <span style={{ marginRight: 10 }}>{'Do you need to add breaks ?'}</span>
                            <Switch
                              checkedChildren="Yes"
                              unCheckedChildren="No"
                              checked={isNeedAddBreaks}
                              onChange={(value) => {
                                setIsNeedAddBreaks(value);
                              }}
                            />
                          </Row>

                          {isNeedAddBreaks ? (
                            <>
                              <Row style={{ marginBottom: 5 }}>
                                <Col style={{ paddingBottom: 8, fontSize: 16, fontWeight: 'bold' }}>
                                  <FormattedMessage
                                    id="breakDetails"
                                    defaultMessage="Break Details"
                                  />
                                </Col>
                              </Row>
                              <Row
                                className="breakModal"
                                style={{ width: '100%', marginBottom: 50 }}
                              >
                                <Form.List name="relatedBreaksDetails">
                                  {(fields, { add, remove }) => (
                                    <>
                                      <div
                                        style={{
                                          overflowY: 'auto',
                                          maxHeight: 180,
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
                                                    onChange={(date, dateString) => { }}
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
                                                        outTimeModel
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
                                                          compareDate.isBetween(
                                                            startDate,
                                                            endDate,
                                                          ) ||
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
                                                        outTimeModel
                                                        ? false
                                                        : true
                                                    }
                                                    onSelect={(dateString) => { }}
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
                                                        outTimeModel
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
                                                        breakArr[key]['breakOutTime'] !=
                                                        undefined &&
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
                                                          compareDate.isBetween(
                                                            startDate,
                                                            endDate,
                                                          ) ||
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
                                              <Col
                                                style={{
                                                  marginLeft: 30,
                                                  marginTop: 35,
                                                  marginRight: 8,
                                                }}
                                              >
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
                                              </Col>
                                            </Row>
                                          </Space>
                                        ))}
                                      </div>
                                      <Row>
                                        <Col style={{ width: 710 }}>
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
                                              let breaks = form.getFieldValue(
                                                'relatedBreaksDetails',
                                              )
                                                ? form.getFieldValue('relatedBreaksDetails')
                                                : [];
                                              let tempObj = {
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
                            </>
                          ) : (
                            <></>
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

export default AttendanceEmployeeTableView;
