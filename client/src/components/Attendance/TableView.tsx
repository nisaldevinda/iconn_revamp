import React, { useEffect, useRef, useState } from 'react';
import { DownloadOutlined, SearchOutlined } from '@ant-design/icons';
import { Button, Tag, Space, Image, Row, Col, Tooltip, Spin, Modal, Popover, DatePicker, TimePicker, Form, message, Popconfirm, ConfigProvider, InputNumber } from 'antd';
import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import _, { cond } from 'lodash';

import { approveTimeChange, approveTimeChangeAdmin, getAttendanceSheetAdminData, getAttendanceSheetEmployeeData, getAttendanceSheetManagerData, getAttendanceTimeChangeData, requestTimeChange, accessibleWorkflowActions, updateInstance, downloadManagerAttendanceView , downloadAdminAttendanceView } from '@/services/attendance';
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
import { getEmployeeList } from '@/services/dropdown';
import en_US from 'antd/lib/locale-provider/en_US';
import { MinusCircleOutlined, PlusOutlined } from '@ant-design/icons';
import styles from './attendance.less';

moment.locale('en');

export type TableViewProps = {
    employeeId?: number,
    others?: boolean,
    nonEditModel?: boolean,
    adminView?: boolean,
    accessLevel?: string
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

const TableView: React.FC<TableViewProps> = (props) => {
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;

    const [actionIds, setActionIds] = useState<string | null>(null);
    const [workflowId, setWorkflowId] = useState<string | null>(null);
    const [workflowInstanceId, setworkflowInstanceId] = useState<string | null>(null);
    const [contextId, setContextId] = useState<string | null>(null);
    const actionRef = useRef<ActionType>();
    const [selectorEmployees, setSelectorEmployees] = useState([]);
    const [attendanceSheetData, setAttendanceSheetData] = useState([]);
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
    const [loadingModelRequest, setLoadingModelRequest] = useState(false);
    const [loadingModelReject, setLoadingModelReject] = useState(false);
    const [loadingModelApprove, setLoadingModelApprove] = useState(false);
    const [isMaintainOt, setIsMaintainOt] = useState(false);
    const [loadingExcelDownload, setLoadingExcelDownload] = useState(false);
    const [editStatusModel, setEditStatusModel] = useState(!props.nonEditModel);
    const [dateModel, setDateModel] = useState('');
    const [reasonModel, setReasonModel] = useState('');
    const [shiftIdModel, setShiftIdModel] = useState<number>();
    const [timeChangeIdModel, setTimeChangeIdModel] = useState<number>();
    const [summaryIdModel, setSummaryIdModel] = useState<number>();
    const [employeeIdModel, setEmployeeIdModel] = useState<number>();
    const [employeeName, setEmployeeName] = useState<string | null>(null);

    const [inDateModel, setInDateModel] = useState<moment.Moment>();
    const [validatedStatusInDate, setValidateStatusInDate] = useState<"" | "error">("");
    const [helpInDate, setHelpInDate] = useState('');

    const [outDateModel, setOutDateModel] = useState<moment.Moment>();
    const [validateStatusOutDate, setValidateStatusOutDate] = useState<"" | "error">("");
    const [helpOutDate, setHelpOutDate] = useState('');

    const [inTimeModel, setInTimeModel] = useState<moment.Moment>();
    const [validateStatusInTime, setValidateStatusInTime] = useState<"" | "error">("");
    const [helpInTime, setHelpInTime] = useState('');

    const [outTimeModel, setOutTimeModel] = useState<moment.Moment>();
    const [validateStatusOutTime, setValidateStatusOutTime] = useState<"" | "error">("");
    const [helpOutTime, setHelpOutTime] = useState(' ');
    const key = 'saving';
    const summaryUrl = '/attendance-manager/summary';
    const [relateScope, setRelateScope] = useState<string | null>(null);
    const { RangePicker } = DatePicker;
    const [form] = Form.useForm();

    useEffect(() => {
        if (othersView) {
            callGetEmployeeData();
        }
    }, []);


    useEffect(() => {
      if (outDateModel == undefined || outTimeModel || undefined || inTimeModel == undefined) {
        form.setFieldsValue({relatedBreaksDetails: null});
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
    }

    const updateWorkflowInstance = (actionId, instanceId, workflowId, contextId) => {
        try {
            updateInstance({
                actionId,
                instanceId,
                workflowId,
                contextId,
                relateScope
            }).then((res) => {
              setIsModalVisible(false);
              actionRef.current?.reload();
              message.success('Updated Successfully');
            }).catch((error: APIResponse) => {
              console.log(error.message);
            });
        } catch (err) {
            console.log(err);
        }
    };


    const convertMiniutesToHhmm = (numberOfMinutes:any) => {
        //create duration object from moment.duration  
        var duration = moment.duration(numberOfMinutes, 'minutes');
        
        //calculate hours
        var hh = (duration.years()*(365*24)) + (duration.months()*(30*24)) + (duration.days()*24) + (duration.hours());
        hh = (hh < 10) ? '0'+hh : hh;
        
        //get minutes
        var mm = duration.minutes();
        mm =  (mm < 10) ? '0'+mm : mm
       
        //return total time in hh:mm format
        return hh+':'+mm;
    };


    const columns: ProColumns<AttendanceItem>[] = [
      {
        title: '',
        dataIndex: 'day',
        render: (_, record) => (
          <Space>
            <Space className={styles.dayTypeTable}>
              <div>
                <Row>
                  {record.day.dayType === 'Non Working Day' ?
                    (
                      <p className={styles.dayTypeTableIcon}>
                        <Image src={NonWorkingDayIcon} preview={false} />
                      </p>
                    )
                    :
                    record.day.dayType === 'Holiday' ?
                      (
                        <p className={styles.dayTypeTableIcon}>
                          <Image src={HolidayIcon} preview={false} />
                        </p>
                      )
                      :
                      record.day.dayType === 'Working Day' && record.day.isWorked === 0 ?
                        (
                          <p className={styles.dayTypeTableIcon}>
                            <Image src={AbsentIcon} preview={false} />
                          </p>
                        )
                        :
                        (
                          <></>
                        )
                  }
                </Row>
              </div>
            </Space>
          </Space>
        )
      },
      {
        title:   <FormattedMessage id="Attendance.date" defaultMessage="Date"/>,
        dataIndex: 'date',
        sorter: true,
        hideInSearch: true,
        render: (_, record) => (
            <div 
              className={styles.date}
            >
                {moment(record.date,"YYYY-MM-DD").isValid() ? moment(record.date).format("DD-MM-YYYY") : null}
            </div>
        )
      },
      {
        title:  <FormattedMessage id="Attendance.name" defaultMessage="Name"/>,
        dataIndex: 'name',
        sorter: true,
        hideInSearch: true,
        hideInTable: selectedEmployee || !othersView ? true : false,
      },
      {
        title:  <FormattedMessage id="Attendance.shift" defaultMessage="Shift"/>,
        dataIndex: 'shift',
        hideInSearch: true,
      },
      {
        title: <FormattedMessage id="Attendance.leave" defaultMessage="Leave"/>,
        dataIndex: 'leave',
        search: false,
        render: (_, record) => {
          return record.leave.map((leave) => {
            return (
              <Space>
                {/* <Tag color={leave.color} key={leave.name}>
                  {leave.name}
                </Tag> */}
                {leave.typeString}
              </Space>
            )
          })
        }
      },
      {
        title: <FormattedMessage id="Attendance.inTime" defaultMessage="In Time"/> ,
        dataIndex: 'in',
        search: false,
        render: (_, record) => (
          <Space>
            <Space className={styles.dayTypeTable}>
              <div>
                <Row>
                  <p className={styles.time}>{record.in.time}</p>
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
      },
      {
        title: <FormattedMessage id="Attendance.outTime" defaultMessage="Out Time"/> ,
        dataIndex: 'out',
        search: false,
        render: (_, record) => (
          <Space>
            <Space className={styles.dayTypeTable}>
              <div>
                <Row>
                  <p className={styles.time}>{record.out.time}</p>
                  {record.out.early ? (
                    <p className={styles.dayTypeTableIcon}>
                      <Image src={EarlyIcon} preview={false} />
                    </p>
                  ) : (
                    <></>
                  )}
                </Row>

                {record.out.isDifferentOutDate ? (
                  <Row>
                    <Space className={styles.hoursCol}>
                      <p className={styles.hours }>
                        {record.out.date}
                      </p>
                    </Space>
                  </Row>
                ) : (
                  <></>
                )}
              </div>
            </Space>
          </Space>
        ),
      },
      {
        title: <FormattedMessage id="Attendance.workHours" defaultMessage="Worked Hours"/>,
        dataIndex: 'duration',
        search: false,
        render: (_, record) => (
          <Space>
            <div>
              <Row>
                <Space>
                  <p className={styles.time}>{record.duration.worked}</p>
                </Space>
              </Row>
              {record.duration.breaks ? (
                <Row>
                  <Space className={styles.hoursCol}>
                    <p className={styles.hours }>
                      Breaks {record.duration.breaks}
                    </p>
                  </Space>
                </Row>
              ) : (
                <></>
              )}
            </div>
          </Space>
        ),
      },
      {
        title:  <FormattedMessage id="Attendance.OtHours" defaultMessage="OT Hours"/>,
        dataIndex: 'duration',
        hideInTable: !isMaintainOt ? true : false,
        search: false,
        render: (_, record) => (
          <Space>
            <div>
              <Row>
                <Space >
                  <Popover className={styles.popOver } content={
                    <div>
                    {
                      record.otData.otDetails.length > 0 ? (
                        record.otData.otDetails.map((el:object) => {
                          return(
                            <Row>
                              <Col span={9}>
                                <span>{el.code}</span>
                              </Col>
                              <Col span={4}> - </Col>
                              <Col span={10}>
                                <span>{convertMiniutesToHhmm(el.workedTime)}</span>
                              </Col>
                            </Row>
                          )
                        })
                      ) : (
                        <span>--</span>
                      )
                    }
                    
                  </div>
                  } title="OT Details (HH:mm)" trigger="click">
                      <a onClick={() => {
                       
                      }} className={styles.dayTypeIco}>{record.otData.totalOtHours}</a>
                  </Popover>
                </Space>
              </Row>
            </div>
          </Space>
        ),
      },
      {
        title: '',
        dataIndex: '',
        search: false,
        render: (_, record) => (
          <Space>
            <Link
              to={{
                pathname: summaryUrl,
                state: {
                  employeeId: record.employeeIdNo,
                  summaryDate: record.date,
                  viewType: getViewType(),
                },
              }}
            >
              <Button type="text" icon={<Image src={viewIcon} preview={false} />} />
            </Link>

            {(!othersView && !record.requestedTimeChangeId) || adminView ? (
              <Button
                type="text"
                icon={<Image src={RequestIcon} preview={false} />}
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

                  form.setFieldsValue({relatedBreaksDetails: null});

                  resetStates(record, outDateRecord, inTimeRecord, outTimeRecord);

                  setReasonModel('');
                  setLoadingModelRequest(false);
                }}
              />
            ) : (
              <></>
            )}

            <Access
              accessible={hasPermitted('attendance-manager-access') && othersView && !adminView}
            >
              {record.requestedTimeChangeId ? (
                <Button
                  type="text"
                  icon={<Image src={ManagerRequestIcon} preview={false} />}
                  onClick={async () => {
                    setLoadingModel(true);
                    setEmployeeName(record.name);
                    const params = {
                      requestedTimeChangeId: record.requestedTimeChangeId,
                      shiftId: record.shiftId,
                      employeeId: record.employeeIdNo,
                    };
                    setIsModalVisible(true);

                    await getAttendanceTimeChangeData(params)
                      .then((response: any) => {
                        if (response) {
                          setTimeChangeDataSet(response.data);
                          const inDateTimeMoment = moment(
                            response.data.inDateTime,
                            'YYYY-MM-DD hh:mm:ss A',
                          );
                          const outDateTimeMoment = moment(
                            response.data.outDateTime,
                            'YYYY-MM-DD hh:mm:ss A',
                          );
                          const outDateRecord = response.data.outDateTime
                            ? moment(response.data.outDateTime, 'YYYY-MM-DD')
                            : undefined;
                          const inTimeRecord = response.data.inDateTime
                            ? inDateTimeMoment
                            : undefined;
                          // const outTimeRecord = record.out.date && response.data.outDateTime ? outDateTimeMoment : undefined;
                          const outTimeRecord = response.data.outDateTime
                            ? outDateTimeMoment
                            : undefined;

                          resetStates(record, outDateRecord, inTimeRecord, outTimeRecord);
                          setSelectedRawEmployeeId(response.data.employeeId);

                          setReasonModel(response.data.reason);
                          setTimeChangeIdModel(record.requestedTimeChangeId ?? 0);
                          setLoadingModel(false);
                          setLoadingModelReject(false);
                          setLoadingModelApprove(false);
                          setActionIds(response.data.actionIds);
                          setWorkflowId(response.data.workflowId);
                          setContextId(response.data.contextId);
                          setworkflowInstanceId(response.data.workflowInstanceId);
                        }
                        return response.data;
                      })
                      .then((res: any) => {
                        if (res.workflowId != null) {
                          const requestScope = 'MANAGER';
                          accessibleWorkflowActions(
                            res.workflowId,
                            res.employeeId,
                            { scope: requestScope },
                            res.workflowInstanceId,
                          ).then((resData: any) => {
                            const { actions, scope } = resData.data;
                            setActions(actions);
                            setRelateScope(scope);
                          });
                        }
                      });
                  }}
                />
              ) : (
                <></>
              )}
            </Access>
          </Space>
        ),
      },
    ];

    function resetStates(record: AttendanceItem, outDateRecord: moment.Moment | undefined, inTimeRecord: moment.Moment | undefined, outTimeRecord: moment.Moment | undefined) {
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
        let scope = "EMPLOYEE";
        if (adminView) {
            scope = "ADMIN";
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

    async function callGetAttendanceSheetData(pageNo?: number, pageCount?: number, sort = { name: 'date', order: 'DESC' }) {
        setLoading(true);
        const params = {
            employee: selectedEmployee,
            fromDate: fromDate,
            toDate: toDate,
            pageNo: pageNo,
            pageCount: pageCount,
            sort: sort
        };

        setAttendanceSheetData([]);
        setDataCount(0);
      if (hasPermitted('attendance-admin-access') && adminView) {
        await getAttendanceSheetAdminData(params).then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setDataCount(response.data.count);
            setIsMaintainOt(response.data.isMaintainOt);
            
          }
          setLoading(false);
        }).catch(() => {
          setLoading(false);
        });
      } else if (hasPermitted('attendance-manager-access') && othersView) {
        await getAttendanceSheetManagerData(params).then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setDataCount(response.data.count);
            setIsMaintainOt(response.data.isMaintainOt);
            
          }
          setLoading(false);
        }).catch(() => {
          setLoading(false);
        });
      } else {
        await getAttendanceSheetEmployeeData(params).then((response: any) => {
          if (response) {
            setAttendanceSheetData(response.data.sheets);
            setDataCount(response.data.count);
            setIsMaintainOt(response.data.isMaintainOt);
          }
          setLoading(false);
        }).catch(() => {
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
            await downloadAdminAttendanceView(params).then((response: any) => {
                setLoadingExcelDownload(false);
                if (response.data) {
                    downloadBase64File(
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        response.data,
                        'attendanceSheetData.xlsx',
                    );
                }
            }).catch((error: APIResponse) => {
                setLoadingExcelDownload(false);
            });;
        } else if (hasPermitted('attendance-manager-access') && othersView) {
            await downloadManagerAttendanceView(params).then((response: any) => {
                setLoadingExcelDownload(false);
                if (response.data) {
                    downloadBase64File(
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        response.data,
                        'attendanceSheetData.xlsx',
                    );
                }
            }).catch((error: APIResponse) => {
               setLoadingExcelDownload(false);
            });;
        }
       
    }

    // const callGetTableData = () => {
    //     callGetAttendanceSheetData(tableState.current, tableState.pageSize, tableState.sortValue);
    // }

    function onChange(dates: any, dateStrings: any) {
        if (dates) {
            setFromDate(moment(dateStrings[0],'DD-MM-YYYY').format("YYYY-MM-DD"));
            setToDate(moment(dateStrings[1],'DD-MM-YYYY').format("YYYY-MM-DD"));
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
            inDate: inDateModel?.format("YYYY-MM-DD"),
            outDate: outDateModel?.format("YYYY-MM-DD"),
            inTime: inTimeModel?.format("HH:mm:ss"),
            outTime: outTimeModel?.format("HH:mm:ss"),
            reason: reasonModel,
            breakDetails: breakList
        };

        const invalidParams = validatedDates(params);

        if (!invalidParams) {
            approveTimeChangeAdmin(params).then((response: any) => {
                setLoadingModelRequest(false);
                setDisableModelButtons(false);

                if (response.data) {
                    callGetAttendanceSheetData(tableState.current, tableState.pageSize, tableState.sortValue);
                    handleCancel();
                    message.success({
                        content:
                            intl.formatMessage({
                                id: 'updatedTimeChange',
                                defaultMessage: 'Saved the time change.',
                            }),
                        key,
                    });
                }

                if (response.error) {
                    message.error({
                        content:
                            intl.formatMessage({
                                id: 'rejectedTimeChange',
                                defaultMessage: 'Failed to save the time change.',
                            }),
                        key,
                    });
                }
            }).catch((error: APIResponse) => {
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
    }

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



    const onFinish = async() => {
        let fieldWiseDynamicErrors = form.getFieldsError();
        let errCount = 0;
        if (fieldWiseDynamicErrors != undefined) {
          fieldWiseDynamicErrors.map((fieldObj) => {
            if (fieldObj.errors.length > 0) {
              errCount ++;
            }
          });
        }

        if (errCount > 0) {
          return;
        }

        let breakArray = (form.getFieldValue('relatedBreaksDetails') != undefined) ? form.getFieldValue('relatedBreaksDetails') : [];

        if (breakArray.length > 0) {
          await form.validateFields();
        }
        let breakList =  breakArray.map((el) => {
            let formattedBreak = {
              breakInDate : el.breakInDate?.format("YYYY-MM-DD"),
              breakInTime : el.breakInTime?.format("HH:mm:ss"),
              breakOutDate : el.breakOutDate?.format("YYYY-MM-DD"),
              breakOutTime : el.breakOutTime?.format("HH:mm:ss"),
            }
            return formattedBreak;

        });

        if (props.accessLevel == 'admin') {
            handleOk(breakList);
        } else if (props.accessLevel == 'employee') {
            handleChange(breakList);
        }
    }

    const handleChange = async(breakList: any) => {
        clearModelValidationStates();

        const params = {
            shiftId: shiftIdModel,
            summaryId: summaryIdModel,
            shiftDate: dateModel,
            inDate: inDateModel?.format("YYYY-MM-DD"),
            outDate: outDateModel?.format("YYYY-MM-DD"),
            inTime: inTimeModel?.format("HH:mm:ss"),
            outTime: outTimeModel?.format("HH:mm:ss"),
            reason: reasonModel,
            breakDetails: breakList
        };

        const invalidParams = validatedDates(params);

        if (!invalidParams) {
            requestTimeChange(params).then((response: any) => {
                setLoadingModelRequest(false);
                setDisableModelButtons(false);

                if (response.data) {
                    callGetAttendanceSheetData(tableState.current, tableState.pageSize, tableState.sortValue);
                    handleCancel();
                    message.success({
                        content:
                            intl.formatMessage({
                                id: 'updatedTimeChange',
                                defaultMessage: 'Your request has been submitted.',
                            }),
                        key,
                    });
                }

                if (response.error) {
                    message.error({
                        content:
                            intl.formatMessage({
                                id: 'rejectedTimeChange',
                                defaultMessage: 'Failed to save the time change.',
                            }),
                        key,
                    });
                }
            }).catch((error: APIResponse) => {
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

    const handleApprove = (approveType: number) => {
        setDisableModelButtons(true);
        approveType === 1 ? setLoadingModelReject(true) : setLoadingModelApprove(true);
        const params = {
            timeChangeId: timeChangeIdModel,
            type: approveType,
            shiftId: 1,
            employeeId: 2,
            summaryId: summaryIdModel,
        }

        approveTimeChange(params).then((response: any) => {
            callGetAttendanceSheetData(tableState.current, tableState.pageSize, tableState.sortValue);
            setLoading(false);
            handleCancel();
            setDisableModelButtons(false);
            setLoadingModelReject(false);
            setLoadingModelApprove(false);

            if (response.data) {
                message.success({
                    content:
                        intl.formatMessage({
                            id: 'updatedTimeChange',
                            defaultMessage: 'Request is accepted.',
                        }),
                    key,
                });
            }
            if (response.error) {
                message.error({
                    content:
                        intl.formatMessage({
                            id: 'rejectedTimeChange',
                            defaultMessage: 'Failed to save the time change.',
                        }),
                    key,
                });
            }
        }).catch((error: APIResponse) => {
            setLoadingModelReject(false);
            setLoadingModelApprove(false);
            message.error({
                content:
                    intl.formatMessage({
                        id: 'rejectedTimeChange',
                        defaultMessage: 'Failed to save the time change.',
                    }),
                key,
            });
        });
    };

    const disableDates = (current: any) => {
        let firstDate = moment(dateModel).subtract(0, 'd').format("YYYY-MM-DD");
        let secondDate = moment(dateModel).add(1, 'd').format("YYYY-MM-DD");
        let compareDate = moment(current, "YYYY-MM-DD").format("YYYY-MM-DD");

        const isNextDay = moment(compareDate, "YYYY-MM-DD") >= moment(firstDate, "YYYY-MM-DD");
        const isPreviousDay = moment(compareDate, "YYYY-MM-DD") <= moment(secondDate, "YYYY-MM-DD");
        const isValidDate = isNextDay && isPreviousDay

        return !isValidDate;
    }

    const disableDatesForBreakOutDate = (current: any) => {
        let firstDate = moment(dateModel).subtract(0, 'd').format("YYYY-MM-DD");
        let secondDate = moment(dateModel).add(1, 'd').format("YYYY-MM-DD");
        let compareDate = moment(current, "YYYY-MM-DD").format("YYYY-MM-DD");

        let outDate = moment(outDateModel).format("YYYY-MM-DD");
        

        const isNextDay = moment(compareDate, "YYYY-MM-DD") >= moment(firstDate, "YYYY-MM-DD");
        const isPreviousDay = moment(compareDate, "YYYY-MM-DD") <= moment(secondDate, "YYYY-MM-DD");
        const isEqualToOutDate = moment(compareDate, "YYYY-MM-DD").isSame(outDate);

        if (isNextDay && isPreviousDay) {
          if (moment(outDate, "YYYY-MM-DD").isSame(secondDate)) {
            const isValidDate = isNextDay && isPreviousDay;
            return !isValidDate;
          } 

          if (moment(outDate, "YYYY-MM-DD").isSame(firstDate)) {
            const isValidDate = isNextDay && isPreviousDay && isEqualToOutDate;
            return !isValidDate;
          } 
        } else {
          const isValidDate = isNextDay && isPreviousDay;
          return !isValidDate;
        }

    }

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
                content:
                    intl.formatMessage({
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
    }

    return (
      <ConfigProvider locale={en_US}>
        <Space direction="vertical" size={25} style={{width : '100%'}} >
          <Row className={styles.attendanceView}>
            <Col
              className={styles.rangePicker}
              span={5}
            >
              <RangePicker
                ranges={{
                  Today: [moment(), moment()],
                  'This Month': [moment().startOf('month'), moment().endOf('month')],
                }}
                format="DD-MM-YYYY"
                onChange={onChange}
              />
            </Col>

            <Access
              accessible={
                (hasPermitted('attendance-manager-access') ||
                hasPermitted('attendance-admin-access')) &&
                othersView
              }
            >
              <Col
                className={styles.employeeCol}
                span={5}
              >
                <ProFormSelect
                  name="select"
                  placeholder=  {intl.formatMessage({
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
                />
              </Col>
            </Access>
              <Col
                className={styles.searchCol}
                span={1}
              >
                <Tooltip 
                  title= {intl.formatMessage({
                    id: 'tooltip.search',
                    defaultMessage: 'search',
                  })}
                >
                  <Button
                    type="primary"
                    icon={<SearchOutlined />}
                    size="middle"
                    onClick={async () => {
                    setLoading(true);
                      callGetAttendanceSheetData(1, 100);
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
                <Col
                  className={styles.excelCol}
                  span={1}
                >
                  <Tooltip  
                    title= {intl.formatMessage({
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
                        callDownloadTeamAttendance();
                      }}
                    />
                  </Tooltip>
                </Col>
              </Access>
              <Col>
                <div
                  className={styles.spinCol}
                >
                  <Spin size="large" spinning={loading} />
                </div>
              </Col>
            <Col span={8} 
              className={styles.dayTypeCol}
            >
              <Space className={styles.dayType}>
                <p className={styles.dayTypeIcon}>
                  <Image src={LateIcon} preview={false} height={15} />
                </p>
                <p className={styles.dayTypeContent }>
                  Late In
                </p>
              </Space>
              <Space className={styles.dayType}>
                <p className={styles.dayTypeIcon}>
                  <Image src={EarlyIcon} preview={false} height={15} />
                </p>
                <p className={styles.dayTypeContent }>
                   Early Departure
                </p>
              </Space>
              <Space className={styles.dayType}>
                  <p className={styles.dayTypeIcon}>
                    <Image src={NonWorkingDayIcon} preview={false} height={15} />
                  </p>
                  <p className={styles.dayTypeContent }>
                    Non Working Day
                  </p>
              </Space>    
              <Space className={styles.dayType}>
                <p className={styles.dayTypeIcon}>
                    <Image src={HolidayIcon} preview={false} height={15} />
                </p>
                <p className={styles.dayTypeContent }>
                    Holiday
                 </p>
              </Space>
              <Space className={styles.dayType}>
                <p className={styles.dayTypeIcon}>
                    <Image src={AbsentIcon} preview={false} height={15} />
                </p>
                <p className={styles.dayTypeContent }>
                    Absent
                </p>
              </Space>
              
            </Col>
          </Row>

          <Row
             className={styles.attendanceTable}
          >
            <ProTable<AttendanceItem>
              columns={columns}
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
              pagination={{ pageSize: 100, total: dataCount, hideOnSinglePage: true }}
              toolBarRender={false}
              search={false}
              style={{ width: '100%' }}
            />
          </Row>

          <Modal
            title={
              adminView ? (
                <FormattedMessage
                  id="Attendance_Change_Request"
                  defaultMessage="Time Change Request"
                />
              ) : (
                <FormattedMessage id="Time_Change_Request" defaultMessage="Time Change Request" />
              )
            }
            visible={isModalVisible}
            width={(hasPermitted('attendance-manager-access') && othersView && !adminView) ? 600 : 800}
            // onOk={handleOk}
            onCancel={handleCancel}
            centered
            footer={[
              <Access accessible={hasPermitted('attendance-employee-access') && !othersView}>
                <Button key="back" onClick={handleCancel} disabled={disableModelButtons}>
                  Cancel
                </Button>
              </Access>,
              <Access accessible={hasPermitted('attendance-employee-access') && !othersView}>
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
              <Access
                accessible={
                  hasPermitted('attendance-admin-access') &&
                  othersView &&
                  !loadingModel &&
                  adminView
                }
              >
                <Button
                  key="submit"
                  type="primary"
                  loading={loadingModelRequest}
                  disabled={disableModelButtons}
                  onClick={onFinish}
                >
                  <FormattedMessage id="Change" defaultMessage="Change" />
                </Button>
              </Access>,
              <Access
                accessible={
                  hasPermitted('attendance-manager-access') &&
                  othersView &&
                  !loadingModel &&
                  !adminView
                }
              >
                {actions.map((element) => {
                  if (_.get(element, 'actionName', false)) {
                    return (
                      <Popconfirm
                        title="Are sure you want perform this action?"
                        placement="top"
                        onConfirm={() => {
                          // updateWorkflowInstance(
                          //   element.id,
                          //   selectedRow.id,
                          //   selectedRow.workflowId,
                          //   selectedRow.contextId,
                          // );
                          updateWorkflowInstance(
                            element.id,
                            workflowInstanceId,
                            workflowId,
                            contextId,
                          );
                        }}
                        okText="Yes"
                        cancelText="No"
                      >
                        <Button
                          key={element.id}
                          onClick={() => {}}
                          type={element.isPrimary ? 'primary' : 'default'}
                        >
                          {element.label}
                        </Button>
                      </Popconfirm>
                    );
                  }
                })}

                {/* <Access accessible={hasPermitted('attendance-manager-access') && othersView && !loadingModel && !adminView}>
                                <Button key="submit" loading={loadingModelReject} disabled={disableModelButtons}
                                    onClick={() => handleApprove(1)}
                                >
                                    <FormattedMessage id="Decline" defaultMessage="Decline" />
                                </Button>
                            </Access>,
                            <Access accessible={hasPermitted('attendance-manager-access') && othersView && !loadingModel && !adminView}>
                                <Button key="submit" type="primary" loading={loadingModelApprove} disabled={disableModelButtons}
                                    onClick={() => handleApprove(2)}
                                >
                                    <FormattedMessage id="Accept" defaultMessage="Accept" />
                                </Button>
                            </Access> */}
              </Access>,
            ]}
          >
            {loadingModel ? (
              <Spin size="large" spinning={loadingModel} />
            ) : (
              <>
              <Access accessible={props.accessLevel == 'admin' || props.accessLevel == 'employee'}>
              <Form form={form} layout="vertical" style={{width: '100%'}}>
                <Row>
                  <Col span={6}>
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
                  <Col span={6}>
                    <Form.Item
                      className="pro-field pro-field-md"
                      validateStatus={validateStatusInTime}
                      help={helpInTime}
                      label={<FormattedMessage id="In_Time" defaultMessage="In Time" />}
                      required={editStatusModel}
                    >
                      <TimePicker
                        disabled={!editStatusModel}
                        use12Hours
                        format="h:mm A"
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
                  <Col span={6}>
                    <Form.Item
                      className="pro-field pro-field-md"
                      validateStatus={validateStatusOutDate}
                      help={helpOutDate}
                      label={<FormattedMessage id="Out_Date" defaultMessage="Out Date" />}
                      required={editStatusModel}
                      style={{ margin: 0 }}
                    >
                      <DatePicker
                        disabled={!editStatusModel}
                        value={outDateModel}
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
                  <Col span={6}>
                    <Form.Item
                      className="pro-field pro-field-md"
                      validateStatus={validateStatusOutTime}
                      help={helpOutTime}
                      label={<FormattedMessage id="Out_Time" defaultMessage="Out Time" />}
                      required={editStatusModel}
                    >
                      <TimePicker
                        disabled={!editStatusModel}
                        use12Hours
                        format="h:mm A"
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
                <Row style={{marginBottom: 5}}>
                  <Col style={{paddingBottom: 8,  fontSize: 16, fontWeight: 'bold'}}>
                    <FormattedMessage id="breakDetails" defaultMessage="Break Details" />
                  </Col>
                </Row>
                <Row style={{width: '100%', marginBottom: 50}}>
                
                  <Form.List name="relatedBreaksDetails" >
                        {(fields, { add, remove }) => (
                        <>
                            {fields.map(({ key, name, ...restField }) => (
                              <Space key={key} style={{ display: 'flex', marginBottom: 8 }} align="baseline">
                                <Row>
                                  <Col style={{marginRight: 30}}>
                                      <Form.Item  name={[name, 'breakInDate']} 
                                      label="Break Start"
                                      rules={[{ required: true, message: 'Required' }]}
                                      >    
                                          <DatePicker
                                            disabled
                                            value={inDateModel}
                                            onChange={(date, dateString) => {
                                            }}
                                            disabledDate={disableDates}
                                          />
                                      </Form.Item>
                                  </Col>
                                  <Col style={{marginRight: 20, marginTop: 30}}>
                                    <Form.Item  name={[name, 'breakInTime']}   
                                      rules={[{ required: true, message: 'Required' }]}
                                      >    
                                        <TimePicker
                                          disabled = {(inDateModel && inTimeModel && outDateModel && outTimeModel) ? false : true}
                                          use12Hours
                                          format="h:mm A"
                                          // value={inTimeModel}
                                          onSelect={(timeString) => {
                                            
                                            let breakArr = form.getFieldValue('relatedBreaksDetails');
                                            breakArr[key]['breakInTime'] = moment(timeString, 'hh:mm:ss A');


                                            let inCompleteDate = inDateModel?.format("YYYY-MM-DD") +' '+ inTimeModel?.format("HH:mm:ss");
                                            let outCompleteDate = outDateModel?.format("YYYY-MM-DD") +' '+ outTimeModel?.format("HH:mm:ss");

                                            if (breakArr[key]['breakInTime'] != undefined && breakArr[key]['breakInDate'] != undefined) {
                                              let compareDateTime = breakArr[key]['breakInDate']?.format("YYYY-MM-DD") + ' '+ breakArr[key]['breakInTime']?.format("HH:mm:ss");
                                              
                                              var compareDate = moment(compareDateTime, "YYYY-MM-DD hh:mm:ss A");
                                              var startDate   = moment(inCompleteDate, "YYYY-MM-DD hh:mm:ss A");
                                              var endDate     = moment(outCompleteDate, "YYYY-MM-DD hh:mm:ss A");

                                              if (compareDate.isBetween(startDate, endDate) || compareDate.isSame(startDate) || compareDate.isSame(endDate)) {
                                                form.setFieldsValue({relatedBreaksDetails: breakArr});
                                                form.setFields([{
                                                  name: ['relatedBreaksDetails', key, 'breakInTime'],
                                                        errors: [] 
                                                    }
                                                ]);
                                              } else {
                                                form.setFields([{
                                                        name: ['relatedBreaksDetails', key, 'breakInTime'],
                                                        errors: ['Invalid break in time'] 
                                                    }
                                                ]);
                                              }
                                            } else {

                                              if (breakArr[key]['breakOutDate'] == undefined) {
                                                form.setFields([{
                                                  name: ['relatedBreaksDetails', key, 'breakInDate'],
                                                        errors: ['Required'] 
                                                    }
                                                ]);
                                              }
                                            }
                                          }}
                                        />
                                    </Form.Item>
                                  </Col>
                                  <Col style={{paddingRight: 20, marginLeft: 0, marginTop: 30}}>-</Col>
                                  <Col style={{marginRight: 30}} >
                                      <Form.Item  name={[name, 'breakOutDate']} 
                                      label="Break End"
                                      rules={[{ required: true, message: 'Required' }]}
                                      >    
                                          <DatePicker
                                            disabled = {(inDateModel && inTimeModel && outDateModel && outTimeModel) ? false : true}
                                            onSelect={(dateString) => {
                                            }}
                                            disabledDate={disableDatesForBreakOutDate}
                                          />
                                      </Form.Item>
                                  </Col>
                                  <Col style={{marginTop: 30}}>
                                    <Form.Item  name={[name, 'breakOutTime']}   
                                      rules={[{ required: true, message: 'Required' }]}
                                      >    
                                        <TimePicker
                                          disabled = {(inDateModel && inTimeModel && outDateModel && outTimeModel) ? false : true}
                                          use12Hours
                                          format="h:mm A"
                                          // value={inTimeModel}
                                          onSelect={(timeString) => {
                                            let breakArr = form.getFieldValue('relatedBreaksDetails');
                                            breakArr[key]['breakOutTime'] = moment(timeString, 'hh:mm:ss A');


                                            let inCompleteDate = inDateModel?.format("YYYY-MM-DD") +' '+ inTimeModel?.format("HH:mm:ss");
                                            let outCompleteDate = outDateModel?.format("YYYY-MM-DD") +' '+ outTimeModel?.format("HH:mm:ss");

                                            // let compareDateTime = null;
                                            if (breakArr[key]['breakOutTime'] != undefined && breakArr[key]['breakOutDate'] != undefined) {
                                              let compareDateTime = breakArr[key]['breakOutDate']?.format("YYYY-MM-DD") + ' '+ breakArr[key]['breakOutTime']?.format("HH:mm:ss");
                                              
                                              var compareDate = moment(compareDateTime, "YYYY-MM-DD hh:mm:ss A");
                                              var startDate   = moment(inCompleteDate, "YYYY-MM-DD hh:mm:ss A");
                                              var endDate     = moment(outCompleteDate, "YYYY-MM-DD hh:mm:ss A");

                                              if (compareDate.isBetween(startDate, endDate) || compareDate.isSame(startDate) || compareDate.isSame(endDate)) {
                                                form.setFieldsValue({relatedBreaksDetails: breakArr});
                                                form.setFields([{
                                                  name: ['relatedBreaksDetails', key, 'breakOutTime'],
                                                        errors: [] 
                                                    }
                                                ]);
                                              } else {
                                                form.setFields([{
                                                        name: ['relatedBreaksDetails', key, 'breakOutTime'],
                                                        errors: ['Invalid break out time'] 
                                                    }
                                                ]);
                                              }
                                            } else {

                                              if (breakArr[key]['breakOutDate'] == undefined) {
                                                form.setFields([{
                                                  name: ['relatedBreaksDetails', key, 'breakOutDate'],
                                                        errors: ['Required'] 
                                                    }
                                                ]);
                                              }
                                            }
                                            
                                          }}
                                        />
                                    </Form.Item>
                                  </Col>
                                  <Col style={{marginLeft: 30, marginTop: 35}}>
                                      <MinusCircleOutlined onClick={() => {
                                          // remove(name)
                                          let newBreaks = [];
                                          let breaks = (form.getFieldValue('relatedBreaksDetails')) ? form.getFieldValue('relatedBreaksDetails') : [];

                                          breaks.map((el, index) => {
                                            if (index != key) {
                                              newBreaks.push(el);
                                            }
                                          })                                          
                                          form.setFieldsValue({relatedBreaksDetails: newBreaks});
                                      }} />
                                      
                                  </Col>
                                </Row>
                            </Space>
                            ))}
                            <Row>
                                <Col style={{width: 710}}>
                                    <Button disabled = {(inDateModel && inTimeModel && outDateModel && outTimeModel) ? false : true}  type="dashed" style={{ backgroundColor: '#E4eff1', borderColor: '#E4eff1', borderRadius: 6 }} onClick={() => {
                                        // add();
                                        let breaks = (form.getFieldValue('relatedBreaksDetails')) ? form.getFieldValue('relatedBreaksDetails') : [];
                                        let tempObj = {
                                            breakInDate : inDateModel,
                                            breakInTime : null,
                                            breakOutDate : null,
                                            breakOutTime : null
                                        }
                                        breaks.push(tempObj);

                                        form.setFieldsValue({relatedBreaksDetails: breaks});
                                    } } block icon={<PlusOutlined />}>
                                        Add Break
                                    </Button>
                                </Col>
                            </Row>
                        </>
                        )}
                    </Form.List>
                  </Row>
                <Row>
                  
                </Row>
                <Row style={{ marginTop: 0 }}>
                  <Col span={10}>
                    <FormattedMessage id="Reason" defaultMessage="Reason" />
                  </Col>
                </Row>
                <Row style={{ marginTop: 5 }}>
                  <Col span={20}>
                    <TextArea
                      disabled={!editStatusModel}
                      rows={4}
                      value={reasonModel}
                      onChange={(e) => {
                        setReasonModel(e.target.value);
                      }}
                    />
                  </Col>
                </Row>
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
                <TimeChangeRequest scope={relateScope} employeeId={selectedRawEmployeeId} employeeFullName = {employeeName} timeChangeRequestData={timeChangeDataSet}  ></TimeChangeRequest>
              </Access>
            </>
            )}
          </Modal>
        </Space>
      </ConfigProvider>
    );
};

export default TableView;