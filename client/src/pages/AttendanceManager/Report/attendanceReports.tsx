import React, { useRef, useEffect, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Card,
  Col,
  Row,
  Tooltip,
  Button,
  Form,
  Select,
  Space,
  Switch,
  Image,
  Checkbox,
  Typography,
  Empty,
  Spin,
  DatePicker,
  Popover,
} from 'antd';
import ProTable from '@ant-design/pro-table';
import { DragOutlined, SettingOutlined } from '@ant-design/icons';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { Access, useAccess, useIntl, FormattedMessage } from 'umi';
import ExportIcon from '../../../assets/leaveEntitlementUsageReport/export-csv-file-icon.svg';
import TimeLineIcon from '../../../assets/leaveEntitlementUsageReport/icon-show-timeline.svg';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import { getAllDepartment } from '@/services/department';
import { getLeaveEntitlementUsage, getLeaveTypes } from '@/services/leave';
import { getOtPayTypeList } from '@/services/dropdown';
import { getAttendanceReportsData, checkOtAccessabilityForCompany } from '@/services/attendance';
import { downloadBase64File } from '@/utils/utils';
import PermissionDeniedPage from '@/pages/403';
import moment from 'moment';
import { getEmployeeList } from '@/services/dropdown';
import OrgSelector from '@/components/OrgSelector';
import { DndProvider, useDrop, useDrag } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import './index.css';

const AttendanceReports: React.FC = () => {
  const actionRef = useRef<ActionType>();
  const { Option } = Select;
  const [form] = Form.useForm();
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const { Text } = Typography;
  const [reportType, setReportType] = useState('');
  const [tableState, setTableState] = useState<any>({
    current: 1,
    pageSize: 100,
  });
  const [payTypes, setPayTypes] = useState([]);
  const [isMaintainOt, setIsMaintainOt] = useState(false);
  const [employees, setEmployees] = useState([]);
  const [selectedLeaveTypes, setSelectedLeaveTypes] = useState([]);
  const [jobTitles, setJobTitles] = useState([]);
  const [columnKeys, setColumnKeys] = useState([]);
  const [department, setDepartment] = useState([]);
  const [reportDate, setReportDate] = useState<moment.Moment>(null);
  const [location, setLocation] = useState([]);
  const [dataCount, setDataCount] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [reportData, setReportData] = useState({});
  const [employeeAttendanceRecords, setEmployeeAttendanceRecords] = useState([]);
  const [disabled, setDisabled] = useState(false);
  const [isGenerateButtonDisable, setIsGenerateButtonDisable] = useState(false);
  const [loading, setLoading] = useState(false);
  const [attendanceTableColumns, setAttendanceTableColumns] = useState([]);
  const [leaveTypes, setLeaveTypes] = useState([]);
  const [leavePeriod, setLeavePeriod] = useState('current');
  const [entityId, setEntityId] = useState(1);
  const [isWithInactiveEmployees, setIsWithInactiveEmployees] = useState<any>(false);

  useEffect(() => {
    callGetOTPayTypesData();
    checkOtAccessability();
  }, []);

  async function callGetOTPayTypesData() {
    let scope = 'ADMIN';
    try {
      const response = await getOtPayTypeList(scope);
      setPayTypes(response.data);
    } catch (err) {
      console.log(err);
    }
  }

  const checkOtAccessability = async () => {
    try {
      let scope = 'ADMIN';
      const response = await checkOtAccessabilityForCompany(scope);
      setIsMaintainOt(response.data.isMaintainOt);
    } catch (err) {
      console.log(err);
    }
  };

  const type = 'DraggableItem';
  const DraggableItem = ({ index, data, moveRow }) => {
    const ref = useRef();
    const [{ isOver, dropClassName }, drop] = useDrop({
      accept: type,
      collect: (monitor) => {
        const { index: dragIndex } = monitor.getItem() || {};
        if (dragIndex === index) {
          return {};
        }
        return {
          isOver: monitor.isOver(),
          dropClassName: dragIndex < index ? ` drop-over-downward` : ` drop-over-upward`,
        };
      },
      drop: (item) => {
        console.log('drop > ', item);
        console.log('index > ', index);
        moveRow(item.index, index);
        // handleDragAndDrop(item.index, index);
      },
    });

    const [{ isDragging }, drag, preview] = useDrag({
      type,
      item: { index },
      collect: (monitor) => ({
        isDragging: monitor.isDragging(),
      }),
    });

    preview(drop(ref));

    let disabled = false;

    return (
      <div key={index} ref={ref} className={`${isOver ? dropClassName : ''}`}>
        <Row style={{ padding: 5 }}>
          <Col span={4}>
            <Checkbox
              name={index}
              // className={styles.inOvertimeField}
              onChange={(value) => {
                handleColumnVisible(value.target.checked, data.name);
              }}
              checked={data.isShowColumn}
            ></Checkbox>
          </Col>
          <Col span={18}>
            <div
              style={{
                wordWrap: 'break-word',
              }}
            >
              {data.name}
            </div>
          </Col>
          <Col span={2}>
            <div>
              <DragOutlined
                ref={drag}
                onClick={(e) => {
                  e.stopPropagation();
                }}
              />
            </div>
          </Col>
        </Row>
      </div>
    );
  };

  const handleDragAndDrop = (index: number, newIndex: number) => {
    const targetAray = [...columnKeys];
    const currentelement = targetAray[index];
    targetAray.splice(index, 1);
    targetAray.splice(newIndex, 0, currentelement);
    setColumnKeys([...targetAray]);
  };

  const handleColumnVisible = (isChecked: any, colName: any) => {
    const targetAray = [...columnKeys];
    let index = targetAray.findIndex((item) => colName == item.name);
    targetAray[index]['isShowColumn'] = isChecked;
    setColumnKeys([...targetAray]);
  };

  const onFinish = async (pageNo?: number, pageCount?: number) => {
    await form.validateFields();

    setLoading(true);
    setIsGenerateButtonDisable(true);

    await setEmployeeAttendanceRecords([]);
    await setDataCount(0);

    processdColumns();

    const recordData = {};
    recordData.reportType = reportType;
    recordData.dataType = 'table';
    recordData.pageNo = pageNo;
    recordData.pageCount = pageCount;
    recordData.reportDate = reportDate ? reportDate.format('YYYY-MM-DD') : null;
    recordData.entityId = entityId;
    recordData.columnHeaders = JSON.stringify(columnKeys);
    setReportData(recordData);
    const { message, data } = await getAttendanceReportsData(recordData);

    setDataCount(data.count);
    setEmployeeAttendanceRecords(data.sheets);
    //   setDisabled(true);
    setLoading(false);
    setIsGenerateButtonDisable(false);
  };
  const reset = () => {
    form.resetFields();
    setReportType(null);
    setColumnKeys([]);
    setAttendanceTableColumns([]);
    setReportData(null);
    setDataCount(0);
    setEntityId(1);
    setEmployeeAttendanceRecords([]);
  };

  const processdColumns = () => {
    let processedCols: any = [];
    const columnArray = [...columnKeys];

    columnArray.map((columnData: any) => {
      if (columnData.isShowColumn) {
        let tempCol = null;
        switch (columnData.name) {
          case 'Employee Name':
            tempCol = {
              title: <FormattedMessage id="Attendance.name" defaultMessage="Employee Name" />,
              dataIndex: 'name',
              key: 'name',
              render: (_, record) => {
                return {
                  children: <Space>{record.name}</Space>,
                };
              },
            };

            break;
          case 'Employee Number':
            tempCol = {
              title: <FormattedMessage id="Attendance.name" defaultMessage="Employee Number" />,
              dataIndex: 'employeeNumber',
              key: 'employeeNumber',
              render: (_, record) => {
                return {
                  children: <Space>{record.employeeNumber}</Space>,
                };
              },
            };

            break;
          case 'Shift':
            tempCol = {
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
              key: 'shift',
              width: 180,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      {record.shift !== null && record.shift.length <= 20 ? (
                        <span>{record.shift}</span>
                      ) : record.shift !== null && record.shift.length > 20 ? (
                        <Tooltip title={record.shift}>
                          {record.shift.substring(0, 20 - 3) + '...'}
                        </Tooltip>
                      ) : (
                        <>-</>
                      )}
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'In Date':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.inDate"
                  defaultMessage={intl.formatMessage({
                    id: 'inDate',
                    defaultMessage: 'In Date',
                  })}
                />
              ),
              dataIndex: 'inDate',
              key: 'inDate',
              render: (_, record) => {
                return {
                  children: (
                    <>
                      <Space>
                        {moment(record.inDate, 'YYYY-MM-DD').isValid()
                          ? moment(record.inDate).format('DD-MM-YYYY')
                          : null}
                      </Space>
                    </>
                  ),
                };
              },
            };

            break;
          case 'In Time':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.inTime"
                  defaultMessage={intl.formatMessage({
                    id: 'inTime',
                    defaultMessage: 'In Time',
                  })}
                />
              ),
              dataIndex: 'in',
              key: 'inTime',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <Space>
                        <div>
                          <Row style={{ display: 'flex' }}>
                            <Space>
                              {moment(record.inTime, 'hh:mm A').isValid()
                                ? moment(record.inTime, 'hh:mm A').format('HH:mm:ss')
                                : '-'}
                            </Space>
                            {/* {record.in.late ? (
                                        <p className={styles.dayTypeTableIcon}>
                                          <Image src={LateIcon} preview={false} />
                                        </p>
                                      ) : (
                                        <></>
                                      )} */}
                          </Row>
                        </div>
                      </Space>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Out Date':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.outDate"
                  defaultMessage={intl.formatMessage({
                    id: 'outDate',
                    defaultMessage: 'Out Date',
                  })}
                />
              ),
              dataIndex: 'outDate',
              key: 'outDate',
              render: (_, record) => {
                return {
                  children: (
                    <>
                      {/* <Row>
                                </Row> */}
                      <Space>
                        {moment(record.outDate, 'YYYY-MM-DD').isValid()
                          ? moment(record.outDate).format('DD-MM-YYYY')
                          : null}
                      </Space>
                    </>
                  ),
                };
              },
            };

            break;
          case 'Out Time':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.outTime"
                  defaultMessage={intl.formatMessage({
                    id: 'outTime',
                    defaultMessage: 'Out Time',
                  })}
                />
              ),
              dataIndex: 'out',
              key: 'outTime',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row style={{ display: 'flex' }}>
                          <Space>
                            {moment(record.outTime, 'hh:mm A').isValid()
                              ? moment(record.outTime, 'hh:mm A').format('HH:mm:ss')
                              : '-'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Total OT':
            tempCol = {
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
              key: 'totalOT',
              // hideInTable: !isMaintainOt ? true : false,
              search: false,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.totalOtHours ? record.totalOtHours : '00:00'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Total Approved OT':
            tempCol = {
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
              key: 'totalApproveOt',
              // hideInTable: !isMaintainOt ? true : false,
              search: false,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>
                            {record.totalApprovedOtHours ? record.totalApprovedOtHours : '00:00'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'In Late':
            tempCol = {
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
              key: 'inLate',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.inLate ? record.inLate : '00:00'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Early Departure':
            tempCol = {
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
              key: 'earlyDepature',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.outEarly ? record.outEarly : '00:00'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Work Hours':
            tempCol = {
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
              key: 'workedHours',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.workedHours ? record.workedHours : '00:00'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Total Late':
            tempCol = {
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
              key: 'totalLate',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.totalLate ? record.totalLate : '00:00'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Leave Type':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.OtHours"
                  defaultMessage={intl.formatMessage({
                    id: 'totalLate',
                    defaultMessage: 'Leave Type (Count)',
                  })}
                />
              ),
              dataIndex: 'leaveType',
              key: 'leaveType',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <Row>
                        <Space>
                          {record.leave.length > 0 ? (
                            <>
                              {record.leaveDetailString !== null &&
                              record.leaveDetailString.length <= 14 ? (
                                <>{record.leaveDetailString}</>
                              ) : record.leaveDetailString !== null &&
                                record.leaveDetailString.length > 14 ? (
                                <Tooltip title={record.leaveDetailString}>
                                  <>{record.leaveDetailString.substring(0, 14 - 3) + '...'} </>
                                </Tooltip>
                              ) : (
                                <></>
                              )}
                            </>
                          ) : (
                            <>{'-'}</>
                          )}
                        </Space>
                      </Row>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Day Type':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.OtHours"
                  defaultMessage={intl.formatMessage({
                    id: 'dayType',
                    defaultMessage: 'Day Type',
                  })}
                />
              ),
              dataIndex: 'dayType',
              key: 'dayType',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.dayType ? record.dayType : '-'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Expected To Present':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.expectedToPresent"
                  defaultMessage={intl.formatMessage({
                    id: 'expectedToPresent',
                    defaultMessage: 'Expected To Present',
                  })}
                />
              ),
              dataIndex: 'expectedToPresent',
              key: 'expectedToPresent',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.expectedToPresent ? record.expectedToPresent : '-'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Is Present':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.isPresentLabel"
                  defaultMessage={intl.formatMessage({
                    id: 'isPresent',
                    defaultMessage: 'Is Present',
                  })}
                />
              ),
              dataIndex: 'isPresentLabel',
              key: 'isPresentLabel',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>{record.isPresentLabel ? record.isPresentLabel : '-'}</Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'In Date & In Time 1':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.inDateAndTimeSlot1"
                  defaultMessage={intl.formatMessage({
                    id: 'inDateAndTimeSlot1',
                    defaultMessage: 'In Date & In Time 1',
                  })}
                />
              ),
              dataIndex: 'inDateAndTimeSlot1',
              key: 'inDateAndTimeSlot1',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>
                            {moment(record.inDateAndTimeSlot1, 'YYYY-MM-DD hh:mm A').isValid()
                              ? moment(
                                  moment(record.inDateAndTimeSlot1, 'YYYY-MM-DD hh:mm A').format(
                                    'YYYY-MM-DD HH:mm',
                                  ),
                                ).format('DD-MM-YYYY HH:mm:ss')
                              : '-'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Out Date & Out Time 1':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.outDateAndTimeSlot1"
                  defaultMessage={intl.formatMessage({
                    id: 'outDateAndTimeSlot1',
                    defaultMessage: 'Out Date & Out Time 1',
                  })}
                />
              ),
              dataIndex: 'outDateAndTimeSlot1',
              key: 'outDateAndTimeSlot1',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>
                            {moment(record.outDateAndTimeSlot1, 'YYYY-MM-DD hh:mm A').isValid()
                              ? moment(
                                  moment(record.outDateAndTimeSlot1, 'YYYY-MM-DD hh:mm A').format(
                                    'YYYY-MM-DD HH:mm',
                                  ),
                                ).format('DD-MM-YYYY HH:mm:ss')
                              : '-'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'In Date & In Time 2':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.inDateAndTimeSlot2"
                  defaultMessage={intl.formatMessage({
                    id: 'inDateAndTimeSlot2',
                    defaultMessage: 'In Date & In Time 2',
                  })}
                />
              ),
              dataIndex: 'inDateAndTimeSlot2',
              key: 'inDateAndTimeSlot2',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>
                            {moment(record.inDateAndTimeSlot2, 'YYYY-MM-DD hh:mm A').isValid()
                              ? moment(
                                  moment(record.inDateAndTimeSlot2, 'YYYY-MM-DD hh:mm A').format(
                                    'YYYY-MM-DD HH:mm',
                                  ),
                                ).format('DD-MM-YYYY HH:mm:ss')
                              : '-'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;
          case 'Out Date & Out Time 2':
            tempCol = {
              title: (
                <FormattedMessage
                  id="Attendance.outDateAndTimeSlot2"
                  defaultMessage={intl.formatMessage({
                    id: 'outDateAndTimeSlot2',
                    defaultMessage: 'Out Date & Out Time 2',
                  })}
                />
              ),
              dataIndex: 'outDateAndTimeSlot2',
              key: 'outDateAndTimeSlot2',
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>
                            {moment(record.outDateAndTimeSlot2, 'YYYY-MM-DD hh:mm A').isValid()
                              ? moment(
                                  moment(record.outDateAndTimeSlot2, 'YYYY-MM-DD hh:mm A').format(
                                    'YYYY-MM-DD HH:mm',
                                  ),
                                ).format('DD-MM-YYYY HH:mm:ss')
                              : '-'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };

            break;

          default:
            tempCol = {
              title: columnData.name,
              dataIndex: columnData.mappedDataIndex,
              key: columnData.mappedDataIndex,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                      <div>
                        <Row>
                          <Space>
                            {record[columnData.mappedDataIndex]
                              ? record[columnData.mappedDataIndex]
                              : '00:00'}
                          </Space>
                        </Row>
                      </div>
                    </Space>
                  ),
                };
              },
            };
            break;
        }

        processedCols.push(tempCol);
      }
    });
    setAttendanceTableColumns(processedCols);
  };

  return (
    <Access
      accessible={hasPermitted('leave-entitlement-report-access')}
      fallback={<PermissionDeniedPage />}
    >
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
        <PageContainer>
          <Space direction="vertical" size={25} style={{ width: '100%' }}>
            <div
              style={{
                borderRadius: '10px',
                background: '#FFFFFF',
                padding: '32px',
                width: '100%',
              }}
            >
              <Form form={form} autoComplete="off" layout="vertical">
                <Row>
                  <Col
                    style={{
                      //   height: 35,
                      width: 250,
                      paddingLeft: 20,
                    }}
                    span={6}
                  >
                    <Form.Item
                      name="reportType"
                      label={intl.formatMessage({
                        id: 'reportType',
                        defaultMessage: 'Report Type',
                      })}
                      rules={[
                        {
                          required: true,
                          message: intl.formatMessage({
                            id: 'leaveEntitlementReport.reportType',
                            defaultMessage: 'Required',
                          }),
                        },
                      ]}
                    >
                      <Select
                        placeholder={intl.formatMessage({
                          id: 'selectField',
                          defaultMessage: 'Select Field',
                        })}
                        onChange={(value) => {
                          setEmployeeAttendanceRecords([]);
                          form.setFieldsValue({
                            leaveType: value == 'leaveEntitlement' ? [] : null,
                            leavePeriod: 'current',
                          });
                          setEntityId(1);
                          setReportType(value);

                          //change the columns according to the report type
                          let reportCat = [];
                          switch (value) {
                            case 'dailyAttendance':
                              reportCat = [
                                {
                                  name: 'Employee Number',
                                  isShowColumn: true,
                                  mappedDataIndex: 'employeeNumber',
                                },
                                {
                                  name: 'Employee Name',
                                  isShowColumn: true,
                                  mappedDataIndex: 'name',
                                },
                                { name: 'Shift', isShowColumn: true, mappedDataIndex: 'shift' },
                                {
                                  name: 'Leave Type',
                                  isShowColumn: true,
                                  mappedDataIndex: 'leaveDetailString',
                                },
                                { name: 'In Date', isShowColumn: true, mappedDataIndex: 'inDate' },
                                { name: 'In Time', isShowColumn: true, mappedDataIndex: 'inTime' },
                                {
                                  name: 'Out Date',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outDate',
                                },
                                {
                                  name: 'Out Time',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outTime',
                                },
                                {
                                  name: 'Total OT',
                                  isShowColumn: true,
                                  mappedDataIndex: 'totalOtHours',
                                },
                                {
                                  name: 'Total Approved OT',
                                  isShowColumn: true,
                                  mappedDataIndex: 'totalApprovedOtHours',
                                },
                                { name: 'In Late', isShowColumn: true, mappedDataIndex: 'inLate' },
                                {
                                  name: 'Early Departure',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outEarly',
                                },
                                {
                                  name: 'Total Late',
                                  isShowColumn: true,
                                  mappedDataIndex: 'totalLate',
                                },
                                {
                                  name: 'Work Hours',
                                  isShowColumn: true,
                                  mappedDataIndex: 'workedHours',
                                },
                              ];

                              break;
                            case 'dailyLateHours':
                              reportCat = [
                                {
                                  name: 'Employee Number',
                                  isShowColumn: true,
                                  mappedDataIndex: 'employeeNumber',
                                },
                                {
                                  name: 'Employee Name',
                                  isShowColumn: true,
                                  mappedDataIndex: 'name',
                                },
                                { name: 'Shift', isShowColumn: true, mappedDataIndex: 'shift' },
                                { name: 'In Date', isShowColumn: true, mappedDataIndex: 'inDate' },
                                { name: 'In Time', isShowColumn: true, mappedDataIndex: 'inTime' },
                                {
                                  name: 'Out Date',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outDate',
                                },
                                {
                                  name: 'Out Time',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outTime',
                                },
                                { name: 'In Late', isShowColumn: true, mappedDataIndex: 'inLate' },
                                {
                                  name: 'Early Departure',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outEarly',
                                },
                                {
                                  name: 'Total Late',
                                  isShowColumn: true,
                                  mappedDataIndex: 'totalLate',
                                },
                              ];

                              break;
                            case 'dailyAbsentWithoutLeave':
                              reportCat = [
                                {
                                  name: 'Employee Number',
                                  isShowColumn: true,
                                  mappedDataIndex: 'employeeNumber',
                                },
                                {
                                  name: 'Employee Name',
                                  isShowColumn: true,
                                  mappedDataIndex: 'name',
                                },
                                { name: 'Shift', isShowColumn: true, mappedDataIndex: 'shift' },
                                {
                                  name: 'Day Type',
                                  isShowColumn: true,
                                  mappedDataIndex: 'dayType',
                                },
                                {
                                  name: 'Expected To Present',
                                  isShowColumn: true,
                                  mappedDataIndex: 'expectedToPresent',
                                },
                                {
                                  name: 'Is Present',
                                  isShowColumn: true,
                                  mappedDataIndex: 'isPresentLabel',
                                },
                              ];
                              break;
                            case 'dailyAbsentWithLeave':
                              reportCat = [
                                {
                                  name: 'Employee Number',
                                  isShowColumn: true,
                                  mappedDataIndex: 'employeeNumber',
                                },
                                {
                                  name: 'Employee Name',
                                  isShowColumn: true,
                                  mappedDataIndex: 'name',
                                },
                                { name: 'Shift', isShowColumn: true, mappedDataIndex: 'shift' },
                                {
                                  name: 'Expected To Present',
                                  isShowColumn: true,
                                  mappedDataIndex: 'expectedToPresent',
                                },
                                {
                                  name: 'Leave Type',
                                  isShowColumn: true,
                                  mappedDataIndex: 'leaveDetailString',
                                },
                              ];

                              break;
                            case 'dailyInvalidAttendance':
                              reportCat = [
                                {
                                  name: 'Employee Number',
                                  isShowColumn: true,
                                  mappedDataIndex: 'employeeNumber',
                                },
                                {
                                  name: 'Employee Name',
                                  isShowColumn: true,
                                  mappedDataIndex: 'name',
                                },
                                { name: 'Shift', isShowColumn: true, mappedDataIndex: 'shift' },
                                {
                                  name: 'Leave Type',
                                  isShowColumn: true,
                                  mappedDataIndex: 'leaveDetailString',
                                },
                                {
                                  name: 'In Date & In Time 1',
                                  isShowColumn: true,
                                  mappedDataIndex: 'inDateAndTimeSlot1',
                                },
                                {
                                  name: 'Out Date & Out Time 1',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outDateAndTimeSlot1',
                                },
                                {
                                  name: 'In Date & In Time 2',
                                  isShowColumn: true,
                                  mappedDataIndex: 'inDateAndTimeSlot2',
                                },
                                {
                                  name: 'Out Date & Out Time 2',
                                  isShowColumn: true,
                                  mappedDataIndex: 'outDateAndTimeSlot2',
                                },
                              ];
                              break;
                            case 'dailyOT':
                              reportCat = [
                                {
                                  name: 'Employee Number',
                                  isShowColumn: true,
                                  mappedDataIndex: 'employeeNumber',
                                },
                                {
                                  name: 'Employee Name',
                                  isShowColumn: true,
                                  mappedDataIndex: 'name',
                                },
                              ];

                              payTypes.forEach((payType) => {
                                console.log(payType);
                                let tempCol = {
                                  name: payType.name,
                                  isShowColumn: true,
                                  mappedDataIndex: payType.code + '-' + 'count',
                                };

                                reportCat.push(tempCol);

                                //approved column obj
                                let tempApprovedOtCol = {
                                  name: 'Approved ' + payType.name,
                                  isShowColumn: true,
                                  mappedDataIndex: payType.code + '-approved-count',
                                };

                                reportCat.push(tempApprovedOtCol);
                              });

                              //set total ot Column
                              let totalOtCol = {
                                name: 'Total OT',
                                isShowColumn: true,
                                mappedDataIndex: 'totalOtHours',
                              };

                              reportCat.push(totalOtCol);

                              //set total approved ot Column
                              let totalApprovedOtCol = {
                                name: 'Final Total OT',
                                isShowColumn: true,
                                mappedDataIndex: 'totalApprovedOtHours',
                              };

                              reportCat.push(totalApprovedOtCol);
                              break;
                            default:
                              break;
                          }

                          setColumnKeys(reportCat);
                          setAttendanceTableColumns([]);
                          setDataCount(0);
                        }}
                        style={{
                          borderRadius: 6,
                          width: '100%',
                        }}
                        allowClear={true}
                      >
                        <Option value="dailyAttendance">Daily Attendance Report</Option>
                        <Option value="dailyAbsentWithoutLeave">
                          Daily Absent (Without Leave) Report
                        </Option>
                        <Option value="dailyAbsentWithLeave">
                          Daily Absent (With Leave) Report
                        </Option>
                        <Option value="dailyInvalidAttendance">
                          Daily Invalid Attendance Report
                        </Option>

                        {isMaintainOt ? <Option value="dailyOT">Daily OT Report</Option> : <></>}
                        <Option value="dailyLateHours">Daily Late Hours Report</Option>
                        {/* <Option value="leaveType">Leave Type</Option>
                        <Option value="leaveEntitlement">Leave Entitlement Report</Option> */}
                      </Select>
                    </Form.Item>
                  </Col>
                  {(reportType === 'dailyAttendance' ||
                    reportType === 'dailyAbsentWithoutLeave' ||
                    reportType === 'dailyAbsentWithLeave' ||
                    reportType === 'dailyInvalidAttendance' ||
                    reportType === 'dailyOT' ||
                    reportType === 'dailyLateHours') && (
                    <>
                      <Col
                        span={3}
                        style={{
                          //   height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="reportDate"
                          label={intl.formatMessage({
                            id: 'reportDate',
                            defaultMessage: 'Report Date',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.reportDate',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          <DatePicker
                            onChange={(date, dateString) => {
                              if (date) {
                                setReportDate(date);
                              }
                            }}
                            format="DD-MM-YYYY"
                            value={reportDate}
                            style={{ borderRadius: 6, width: '100%' }}
                          />
                        </Form.Item>
                      </Col>
                      <Col
                        span={15}
                        style={{
                          //   height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Popover
                          placement="rightTop"
                          style={{ width: 150 }}
                          title={<h2>Customize Columns</h2>}
                          content={
                            <>
                              <DndProvider backend={HTML5Backend}>
                                {columnKeys.map((columnData, index) => {
                                  return (
                                    <DraggableItem
                                      // index={targetKeys.findIndex((key) => key === item.key)}
                                      index={index}
                                      data={columnData}
                                      moveRow={handleDragAndDrop}
                                    />
                                  );
                                })}
                              </DndProvider>
                            </>
                          }
                          trigger="click"
                        >
                          <Tooltip title={'Customize Columns'}>
                            <Button
                              style={{
                                marginTop: 30,
                                borderRadius: 6,
                                backgroundColor: '#0232AC',
                                color: 'white',
                              }}
                              type="default"
                            >
                              <SettingOutlined
                                style={{ fontSize: 18, marginTop: 2, color: 'white' }}
                              ></SettingOutlined>
                            </Button>
                          </Tooltip>
                        </Popover>
                      </Col>

                      <OrgSelector
                        value={entityId}
                        setValue={(value: number) => setEntityId(value)}
                        span={6}
                        colStyle={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                          paddingTop: 20,
                          paddingBottom: 65,
                        }}
                      />
                    </>
                  )}

                  {(reportType === 'dailyAttendance' ||
                    reportType === 'dailyAbsentWithoutLeave' ||
                    reportType === 'dailyAbsentWithLeave' ||
                    reportType === 'dailyInvalidAttendance' ||
                    reportType === 'dailyOT' ||
                    reportType === 'dailyLateHours') && (
                    <>
                      <Col
                        span={4}
                        style={{
                          height: 35,
                          paddingLeft: 20,
                          paddingTop: 48,
                          paddingBottom: 35,
                        }}
                      >
                        <Space>
                          <Button onClick={reset} type="default">
                            <FormattedMessage id="REPORTRESET" defaultMessage="Reset" />
                          </Button>
                          <Button
                            disabled={isGenerateButtonDisable}
                            onClick={() => {
                              setCurrentPage(1);
                              onFinish(tableState.current, tableState.pageSize);
                            }}
                            type="primary"
                          >
                            <FormattedMessage
                              id="GENERATEREPORT"
                              defaultMessage="Generate Report"
                            />
                          </Button>
                        </Space>
                      </Col>
                    </>
                  )}
                </Row>
              </Form>
              <br />
            </div>
            <br />

            <Spin size="large" spinning={loading}>
              {employeeAttendanceRecords.length >= 0 ? (
                <Card>
                  <Row>
                    {employeeAttendanceRecords.length > 0 ? (
                      <Col span={24} style={{ textAlign: 'right', paddingRight: 25 }}>
                        <Button
                          htmlType="button"
                          style={{
                            background: '#FFFFFF',
                            border: '1px solid #B8B7B7',
                            boxSizing: 'border-box',
                            borderRadius: '6px',
                          }}
                          icon={<Image src={ExportIcon} preview={false} />}
                          onClick={async () => {
                            const excelData = reportData;
                            excelData.dataType = '';
                            excelData.columnHeaders = JSON.stringify(columnKeys);
                            setLoading(true);
                            const { data } = await getAttendanceReportsData(excelData);
                            let reportName = 'attendance_Reprt.xlsx';
                            switch (reportType) {
                              case 'dailyAttendance':
                                reportName =
                                  'Daily_Attendance_Report-' + excelData.reportDate + '.xlsx';
                                break;
                              case 'dailyAbsentWithoutLeave':
                                reportName =
                                  'Daily_Absent_Report(Without_Leave)-' +
                                  excelData.reportDate +
                                  '.xlsx';
                                break;
                              case 'dailyAbsentWithLeave':
                                reportName =
                                  'Daily_Absent_Report(With_Leave)-' +
                                  excelData.reportDate +
                                  '.xlsx';
                                break;
                              case 'dailyInvalidAttendance':
                                reportName =
                                  'Daily_Invalid_Attendance_Report-' +
                                  excelData.reportDate +
                                  '.xlsx';
                                break;
                              case 'dailyOT':
                                reportName = 'Daily_OT_Report-' + excelData.reportDate + '.xlsx';
                                break;
                              case 'dailyLateHours':
                                reportName =
                                  'Daily_Late_Hours_Report-' + excelData.reportDate + '.xlsx';
                                break;

                              default:
                                break;
                            }

                            if (data) {
                              downloadBase64File(
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                data,
                                reportName,
                              );
                            }
                            setLoading(false);
                          }}
                        >
                          <span style={{ verticalAlign: 'top', paddingLeft: '4px' }}> Export</span>
                        </Button>
                      </Col>
                    ) : (
                      <></>
                    )}
                  </Row>
                  <br />
                  <ProTable<any>
                    actionRef={actionRef}
                    rowKey="id"
                    scroll={selectedLeaveTypes.length > 3 ? { x: '130vw' } : {}}
                    search={false}
                    options={false}
                    request={async (params = { current: 1, pageSize: 100 }, sort, filter) => {
                      if (reportType != '' && reportType) {
                        const tableParams = {
                          current: params?.current,
                          pageSize: params?.pageSize,
                        };
                        setTableState(tableParams);
                        await onFinish(params?.current, params?.pageSize);
                        return employeeAttendanceRecords;
                      } else {
                        return [];
                      }
                    }}
                    pagination={{
                      pageSize: 100,
                      current: currentPage,
                      total: dataCount,
                      hideOnSinglePage: true,
                    }}
                    columns={attendanceTableColumns}
                    dataSource={employeeAttendanceRecords}
                    className="custom-table"
                  />
                </Card>
              ) : reportType ? (
                <Card>
                  <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                </Card>
              ) : null}
            </Spin>
          </Space>
        </PageContainer>
      </div>
    </Access>
  );
};

export default AttendanceReports;
