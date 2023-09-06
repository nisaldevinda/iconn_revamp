import React, { useEffect, useRef, useState } from 'react';
import { DownloadOutlined, SearchOutlined } from '@ant-design/icons';
import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { ProFormDateRangePicker, ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';

// import { approveTimeChange, approveTimeChangeAdmin, requestTimeChange, accessibleWorkflowActions, updateInstance } from '@/services/attendance';
import request, { APIResponse } from '@/utils/request';
import { getModel, Models } from '@/services/model';
import _, { trim, values } from 'lodash';
import { CommentOutlined, EyeOutlined } from '@ant-design/icons';
import styles from './index.less';
import { downloadBase64File } from '@/utils/utils';
import LeaveRequest from '../WorkflowRequests/leaveRequest';
import { ReactComponent as Edit } from '../../assets/attendance/Edit.svg';
import { ReactComponent as Comment } from '../../assets/attendance/Comment.svg';
import { UseFetchDataAction } from '@ant-design/pro-table/lib/typing';
import { ProCoreActionType } from '@ant-design/pro-utils';
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
  DatePicker,
  TimePicker,
  Form,
  message,
  List,
  Avatar,
  Switch,
  Typography,
  Popconfirm,
  Statistic,
  Select
} from 'antd';

import {
  getEmployeeRequestAdminData,
  accessibleWorkflowActions,
  updateInstance,
  getEmployeeRequestEmployeeData,
  getEmployeeRequestManagerData,
  getEmployeeData,
  addComment,
  cancelLeave,
  exportLeaveRequestManagerData,
  exportLeaveRequestAdminData,
  cancelCoveringPersonBasedLeaveRequest
} from '@/services/leave';
import { getEmployeeList , getAllEmployeeList } from '@/services/dropdown';
import LeaveComment from './leaveComment';

const { Text } = Typography;

moment.locale('en');

export type TableViewProps = {
  employeeId?: number;
  others?: boolean;
  nonEditModel?: boolean;
  adminView?: boolean;
  accessLevel: string
};

type LeaveRequestItem = {
  id: number;
  date: string;
  employeeName: string;
  name: string;
  numberOfLeaveDates: number;
  StateLabel: string;
};

const LeaveTableView: React.FC<TableViewProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [approverCommentForm] = Form.useForm();
  const [actionIds, setActionIds] = useState<any>(null);
  const [leaveTypes, setLeaveTypes] = useState<any>([{ status: 'dddd', text: 'AAAAA' }]);
  const [relatedLeaveTypes, setRelatedLeaveTypes] = useState<any>([]);
  // const [relatedDepartments, setRelatedDepartments] = useState<any>([]);
  const [relatedAdminLocatons, setRelatedAdminLocatons] = useState<any>([]);
  const [commentContent, setCommentContent] = useState<string | null>(null);
  const [workflowId, setWorkflowId] = useState<string | null>(null);
  const [selectedLeaveId, setSelectedLeaveId] = useState<any>(null);
  const [workflowInstanceId, setworkflowInstanceId] = useState<string | null>(null);
  const [contextId, setContextId] = useState<string | null>(null);
  const actionRef = useRef<ActionType>();
  const [selectorEmployees, setSelectorEmployees] = useState([]);
  const [selectorEmployeesWithInactives, setSelectorEmployeesWithInactives] = useState([]);
  const [selectedRow, setSelectedRow] = useState<any>();
  const [leaveRequestData, setLeaveRequestData] = useState([]);
  const [dataCount, setDataCount] = useState(0);
  const [tableState, setTableState] = useState<any>({});
  const [commentList, setCommentList] = useState<any>([]);
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();
  const [selectedEmployee, setSelectedEmployee] = useState(props.employeeId ?? undefined);
  const [selectedLeaveType, setSelectedLeaveType] = useState<any>(hasPermitted('manager-leave-request-access') && props.accessLevel == 'manager' ? [] : null);
  const [location, setLocation] = useState<any>(null);
  const [department, setDepartment] = useState<any>(null);
  const [selectedStatus, setSelectedStatus] = useState<any>([]);
  const [othersView, setOthersView] = useState(props.others ?? false);
  const [isCommentEnable, setIsCommentEnable] = useState<boolean>(true);
  const [adminView, setAdminView] = useState(props.adminView ?? false);
  const [isWithInactiveEmployees, setIsWithInactiveEmployees] = useState<any>(false);
  const [loading, setLoading] = useState(false);
  const [actions, setActions] = useState<any>([]);
  const [permittedActionIds, setPermittedActionIds] = useState<any>([]);
  const [relatedLeaveStates, setRelatedLeaveStates] = useState<any>([]);
  const [loadingExcelDownload, setLoadingExcelDownload] = useState(false);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [isCommentModalVisible, setIsCommentModalVisible] = useState(false);
  const [loadingModel, setLoadingModel] = useState(false);
  const [loadingCommentListModel, setLoadingCommentListModel] = useState(false);
  const [disableModelButtons, setDisableModelButtons] = useState(false);
  const [loadingModelRequest, setLoadingModelRequest] = useState(false);
  const [leaveDataSet, setleaveDataSet] = useState({});
  const key = 'saving';
  const [employeeName, setEmployeeName] = useState<string | null>(null);
  const [relateScope, setRelateScope] = useState<string | null>(null);
  const [form] = Form.useForm();
  const { RangePicker } = DatePicker;
  const { Option } = Select;
  const [approverComment, setApproverComment] = useState<string | null>(null);

  useEffect(() => {
    callGetEmployeeData();
    // if (!props.others) {
    // }
    getLeaveTypes();
    getLeaveStates();
    // getDepartments();
    getAdminLocations();
  }, []);

  useEffect(() => {
    if (isModalVisible) {
      setApproverComment(null);
      approverCommentForm.setFieldsValue({ approverComment: null });
    }
  }, [isModalVisible]);

  const updateWorkflowInstance = (actionId, instanceId, workflowId, contextId) => {
    try {
      updateInstance({
        actionId,
        instanceId,
        workflowId,
        contextId,
        relateScope,
      }).then((res) => {
        setIsModalVisible(false);
        let sortValue = { name: 'fromDate', order: 'DESC' };
        callGetLeaveRequestData(1, 20, sortValue);
        actionRef.current?.reload();
        message.success(res.message);
      }).catch((error: APIResponse) => {
        message.error(error.message);
      });
    } catch (err) {
      console.log(err);
    }
  };

  const cancelLeaveCoveringRequest = () => {
    try {
      cancelCoveringPersonBasedLeaveRequest(leaveDataSet.id).then((res) => {
        setIsModalVisible(false);
        let sortValue = { name: 'fromDate', order: 'DESC' };
        callGetLeaveRequestData(1, 20, sortValue);
        actionRef.current?.reload();
        message.success(res.message);
      }).catch((error: APIResponse) => {
        message.error(error.message);
      });
    } catch (err) {
      console.log(err);
    }
  };

  const refreshLeaveList = () => {
    actionRef.current?.reload();
  }


  const columns: ProColumns<LeaveRequestItem>[] = [
    {
      title: 'Employee',
      dataIndex: 'employeeName',
      sorter: true,
      hideInSearch: true,
      hideInTable: !othersView ? true : false,
    },
    {
      title: 'Start Date',
      dataIndex: 'fromDate',
      sorter: true,
      hideInSearch: true,
      render: (_, record) => (
        <Space>
          {moment(record.fromDate, 'YYYY-MM-DD').isValid()
            ? moment(record.fromDate).format('DD-MM-YYYY')
            : null}
        </Space>
      ),
    },
    {
      title: 'End Date',
      dataIndex: 'toDate',
      sorter: true,
      hideInSearch: true,
      render: (_, record) => (
        <Space>
          {moment(record.toDate, 'YYYY-MM-DD').isValid()
            ? moment(record.toDate).format('DD-MM-YYYY')
            : null}
        </Space>
      ),
    },
    {
      title: 'LeaveType',
      dataIndex: 'leaveTypeId',
      // hideInSearch: true,
      filters: true,
      onFilter: true,
      valueEnum: leaveTypes,
      render: (_, record) => (
        <Space>
          {/* <Tag
            style={{
              borderRadius: 20,
              paddingRight: 20,
              paddingLeft: 20,
              paddingTop: 2,
              paddingBottom: 2,
              border: 0,
            }}
            color={record.leaveTypeColor}
          >
            {record.leaveTypeName}
          </Tag> */}
          <Tag
            style={{
              borderRadius: 20,
              paddingRight: 20,
              paddingLeft: 20,
              paddingTop: 2,
              paddingBottom: 2,
              border: 0,
              color: '#2D68FE',
              background: '#CDE7FF',
              borderColor: `${record.leaveTypeColor}2a`,
            }}
          >
            {record.leaveTypeName}
          </Tag>
        </Space>
      ),
      // hideInTable: selectedEmployee ? true : false
    },
    {
      title: 'Requested Days',
      dataIndex: 'numberOfLeaveDates',
      hideInSearch: true,
    },
    // {
    //     title: 'Leave Balance (Days)',
    //     dataIndex: 'netLeaveBalance',
    //     sorter: false,
    //     hideInSearch: true,
    //     // hideInTable: selectedEmployee ? true : false
    // },
    {
      title: 'Status',
      dataIndex: '',
      hideInSearch: true,
      render: (_, record) => (
        <Space>
          <Tag
            style={{
              borderRadius: 20,
              paddingRight: 20,
              paddingLeft: 20,
              paddingTop: 2,
              paddingBottom: 2,
              border: 0,
              backgroundColor:
                record.StateLabel === 'Approved'
                  ? '#C0FFC7'
                  : record.StateLabel === 'Pending'
                  ? '#FFC470'
                  : '#FFC0C0',
              color:
                record.StateLabel === 'Approved'
                  ? '#3E8D47'
                  : record.StateLabel === 'Pending'
                  ? '#B26A04'
                  : '#AA4343',
            }}
          >
            {record.StateLabel}
          </Tag>
        </Space>
      ),
    },
    {
      title: 'Actions',
      dataIndex: '',
      search: false,
      render: (_, record) => (
        <Space>
          {/* <Link
                        to={()}> */}
          {/* <Button
                            type="text"
                            icon={<Image src={viewIcon} preview={false} />}
                        /> */}
          <div
            className={styles.view}
            onClick={() => {
              // record.fromDate = moment(record.fromDate, "DD-MM-YYYY").format("YYYY-MM-DD")
              // record.toDate = moment(record.toDate, "DD-MM-YYYY").format("YYYY-MM-DD")
              setleaveDataSet(record);
              let empName = record.firstName + ' ' + record.lastName;
              setEmployeeName(empName);
              setIsModalVisible(true);

              if (record.actionId) {
                setActionIds(record.actionId);
              } else {
                setActionIds([]);
              }

              if (record.workflowId) {
                setWorkflowId(record.workflowId);
              } else {
                setWorkflowId(null);
              }

              if (record.workflowInstanceIdNo) {
                setworkflowInstanceId(record.workflowInstanceIdNo);
              } else {
                setworkflowInstanceId(null);
              }

              if (record.hasPendingCoveringPersonRequests) {
                const requestScope =
                  props.accessLevel == 'employee'
                    ? 'EMPLOYEE'
                    : props.accessLevel == 'manager'
                    ? 'MANAGER'
                    : props.accessLevel == 'admin'
                    ? 'ADMIN'
                    : null;
                setRelateScope(requestScope);
              } else {
                getRealteActions(record);
              }
            }}
          >
            <span style={{ position: 'relative', top: 3 }}>
              <Edit height={15} width={15} />
            </span>
          </div>

          <LeaveComment
            leaveData={record}
            leaveId={record.id}
            refreshLeaveList={refreshLeaveList}
          ></LeaveComment>

          {/* </Link> */}
        </Space>
      ),
    },
  ];

  async function callGetEmployeeData() {
    try {
      if (hasPermitted('admin-leave-request-access') && props.accessLevel == 'admin') {
        try {
          const { data } = await getAllEmployeeList('ADMIN');
          const allemployees = data.map((employee: any) => {
            return {
              label: employee.employeeNumber+' | '+employee.employeeName,
              value: employee.id,
            };
          });

          const activeEmployees = data.map((employee: any) => {
            if (employee.isActive) {
              return {
                label: employee.employeeNumber+' | '+employee.employeeName,
                value: employee.id,
              };
            }
          });

          setSelectorEmployees(activeEmployees);
          setSelectorEmployeesWithInactives(allemployees)
        } catch (err) {
          console.log(err);
        }
      }

      if (hasPermitted('manager-leave-request-access') && props.accessLevel == 'manager') {
        try {
          const { data } = await getAllEmployeeList('MANAGER');
          const allemployees = data.map((employee: any) => {
            return {
              label: employee.employeeNumber+' | '+employee.employeeName,
              value: employee.id,
            };
          });

          const activeEmployees = data.map((employee: any) => {
            if (employee.isActive) {
              return {
                label: employee.employeeNumber+' | '+employee.employeeName,
                value: employee.id,
              };
            }
          });

          setSelectorEmployees(activeEmployees);
          setSelectorEmployeesWithInactives(allemployees);
        } catch (err) {
          console.log(err);
        }
      }
    } catch (err) {
      console.log(err);
    }
  }

  async function getRealteActions(res: any) {
    try {
      if (res.workflowId != null) {
        const requestScope = othersView ? 'MANAGER' : 'EMPLOYEE';
        accessibleWorkflowActions(
          res.workflowId,
          res.employeeIdNo,
          { scope: requestScope },
          res.workflowInstanceIdNo,
        ).then((resData: any) => {
          const { actions, scope } = resData.data;
          const actionIds: any = [];

          actions.forEach(async (element: any) => {
            actionIds.push(element.id);
          });

          setRelateScope(scope);

          setPermittedActionIds(actionIds);
          setActions(actions);
        });
      }
    } catch (err) {
      console.log(err);
    }
  }

  async function callGetLeaveRequestData(
    pageNo?: number,
    pageCount?: number,
    sort = { name: 'fromDate', order: 'DESC' },
    filters,
  ) {
    try {
      setLoading(true);

      let statusobj = {
        statusSet: selectedStatus,
      };

      const params = {
        employee: selectedEmployee,
        fromDate: fromDate,
        toDate: toDate,
        pageNo: pageNo,
        pageCount: pageCount,
        sort: sort,
        filter: filters,
        leaveType: props.accessLevel == 'manager' ? JSON.stringify(selectedLeaveType) : selectedLeaveType,
        status: statusobj,
        location: location,
        department: department,
        isWithInactiveEmployees: isWithInactiveEmployees,
      };

      // console.log(params);
      // return;

      setLeaveRequestData([]);
      setDataCount(0);
      if (hasPermitted('admin-leave-request-access') && othersView && props.accessLevel == 'admin') {
        await getEmployeeRequestAdminData(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      } else if (hasPermitted('manager-leave-request-access') && othersView && props.accessLevel == 'manager') {
        await getEmployeeRequestManagerData(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      } else {
        await getEmployeeRequestEmployeeData(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      }
      setLoading(false);
    } catch (err) {
      setLoading(false);
      console.log(err);
    }
  }

  async function resetLeaveRequestList(
    pageNo?: number,
    pageCount?: number,
    sort = { name: 'fromDate', order: 'DESC' },
    filters,
  ) {
    try {
      setLoading(true);

      let statusobj = {
        statusSet: [],
      };

      const params = {
        employee: undefined,
        fromDate: undefined,
        toDate: undefined,
        pageNo: pageNo,
        pageCount: pageCount,
        sort: sort,
        filter: filters,
        leaveType: null,
        status: statusobj,
        location: null,
        department: null,
        isWithInactiveEmployees: isWithInactiveEmployees,
      };

      setLeaveRequestData([]);
      setDataCount(0);
      if (hasPermitted('admin-leave-request-access') && othersView && props.accessLevel == 'admin') {
        await getEmployeeRequestAdminData(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      } else if (hasPermitted('manager-leave-request-access') && othersView && props.accessLevel == 'manager') {
        await getEmployeeRequestManagerData(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      } else {
        await getEmployeeRequestEmployeeData(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      }
      setLoading(false);
    } catch (err) {
      setLoading(false);
      console.log(err);
    }
  }

  function onChange(dates: any, dateStrings: any) {
    if (dates) {
      setFromDate(dateStrings[0]);
      setToDate(dateStrings[1]);
    } else {
      setFromDate(undefined);
      setToDate(undefined);
    }
  }

  const handleCancel = () => {
    setIsModalVisible(false);
  };


  const handleLeaveCancel = async (id: any) => {
    try {
      cancelLeave(id)
        .then((response: any) => {
          setIsModalVisible(false);
          message.success({
            content: intl.formatMessage({
              id: 'leaveCancelSuccess',
              defaultMessage: 'Leave Sucessfully Canceled.',
            }),
            key,
          });
          let sortValue = { name: 'id', order: 'DESC' };
          callGetLeaveRequestData(1, 20, sortValue);
        })
        .catch((error: APIResponse) => {
          message.error({
            content: intl.formatMessage({
              id: 'rejectedTimeChange',
              defaultMessage: 'Failed to cancel leave request.',
            }),
            key,
          });
        });
    } catch (err) {
      console.log(err);
    }
  };

  const getLeaveTypes = async () => {
    try {
      const actions: any = [];
      const leaveTypeEnum: object = {};
      const response = await getModel('leaveType');
      let path: string;
      let params = { sorter: { name: 'name', order: 'ASC' } }

      if (!_.isEmpty(response.data)) {
        path = `/api${response.data.modelDataDefinition.path}`;
      }
      const res = await request(path, { params });

      await res.data.forEach(async (element: any, i: number) => {
        await actions.push({ value: element['id'], label: element['name'] });
        leaveTypeEnum[element['id']] = {
          text: element['name'],
          status: element['id'],
        };
      });
      setLeaveTypes({ ...leaveTypeEnum });
      setRelatedLeaveTypes(actions);
      return actions;
    } catch (err) {
      console.log(err);
    }
  };

  const getLeaveStates = async () => {
    try {
      const actions: any = [];
      let params = { sorter: { name: 'label', order: 'ASC' } }
      let path = `/api/leave-request-workflow-states`;
      const res = await request(path, { params });

      await res.data.forEach(async (element: any) => {
        await actions.push({
          value: element['workflowStateId'],
          label: element['priorStateLabel'],
        });
      });
      setRelatedLeaveStates(actions);
      return actions;
    } catch (err) {
      console.log(err);
    }
  };

  async function callDownloadTeamLeaves(
    sort = { name: 'fromDate', order: 'DESC' },
    filters,
  ) {
    try {
      setLoading(true);

      let statusobj = {
        statusSet: selectedStatus,
      };

      const params = {
        employee: selectedEmployee,
        fromDate: fromDate,
        toDate: toDate,
        sort: JSON.stringify(sort),
        filter: JSON.stringify(tableState['filters']),
        leaveType: selectedLeaveType,
        status: JSON.stringify(statusobj),
        location: location,
        department: department,
        isWithInactiveEmployees: isWithInactiveEmployees,
      };

      if (hasPermitted('admin-leave-request-access') && othersView && props.accessLevel == 'admin') {
        await exportLeaveRequestAdminData(params).then((response: any) => {
          if (response.data) {
            downloadBase64File(
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              response.data,
              'leaveRequestData.xlsx',
            );
          }
        });
      } else if (hasPermitted('manager-leave-request-access') && othersView && props.accessLevel == 'manager') {
        await exportLeaveRequestManagerData(params).then((response: any) => {
          if (response.data) {
            downloadBase64File(
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              response.data,
              'leaveRequestData.xlsx',
            );
          }
        });
      }
      setLoading(false);
    } catch (err) {
      setLoading(false);
      console.log(err);
    }
  }

  const getAdminLocations = async () => {
    try {
      if (props.others && hasPermitted('admin-leave-request-access') && props.accessLevel == 'admin') {
        const actions: any = [];
        let params = { sorter: { name: 'name', order: 'ASC' } }
        let path = `/api/adminAccessLocations`;
        const res = await request(path, { params });

        await res.data.forEach(async (element: any) => {
          await actions.push({ value: element['id'], label: element['name'] });
        });
        setRelatedAdminLocatons(actions);
        return actions;
      }
    } catch (err) {
      console.log(err);
    }
  };

  // const getDepartments = async () => {
  //   try {
  //     const actions: any = [];
  //     let params = {sorter : { name: 'name', order: 'ASC' }}
  //     let path = `/api/departments`;
  //     const res = await request(path, {params});

  //     await res.data.forEach(async (element: any) => {
  //       await actions.push({ value: element['id'], label: element['name'] });
  //     });

  //     setRelatedDepartments(actions);
  //     return actions;
  //   } catch (err) {
  //     console.log(err);
  //   }
  // };

  const getRelatedComments = async (id: any) => {
    try {
      setLoadingCommentListModel(true);
      const actions: any = [];
      let path = `/api/leaveRequest/getRelatedComments/` + id;
      const res = await request(path);
      setCommentList(res.data);
      setLoadingCommentListModel(false);
    } catch (err) {
      console.log(err);
    }
  };

  return (
    <>
      <Space direction="vertical" size={25} className={styles.mainSpace}>
        <div className={styles.mainDiv}>
          <Row>
            <Col className={styles.dateCol} span={6}>
              <Row className={styles.formLabel}>
                {intl.formatMessage({
                  id: 'leaves.date',
                  defaultMessage: 'Date',
                })}
              </Row>
              <RangePicker
                className={styles.rangePicker}
                ranges={{
                  Today: [moment(), moment()],
                  'This Month': [moment().startOf('month'), moment().endOf('month')],
                }}
                format={['DD-MM-YYYY']}
                onChange={onChange}
                value={
                  fromDate !== undefined && toDate !== undefined
                    ? [moment(fromDate, 'DD-MM-YYYY'), moment(toDate, 'DD-MM-YYYY')]
                    : null
                }
              />
            </Col>
            <Access accessible={hasPermitted('employee-leave-request-access') && !othersView}>
              <Col className={styles.statusCol} span={5}>
                <Row className={styles.formLabel}>
                  {intl.formatMessage({
                    id: 'leaves.status',
                    defaultMessage: 'Status',
                  })}
                </Row>
                <Select
                  name="leaveState"
                  showSearch
                  optionFilterProp="label"
                  mode={'multiple' as const}
                  onChange={(value) => {
                    setSelectedStatus(value);
                  }}
                  className={styles.status}
                  placeholder={intl.formatMessage({
                    id: 'leaves.status.placeholder',
                    defaultMessage: 'Select Status',
                  })}
                  value={selectedStatus}
                  maxTagCount={'responsive' as const}
                  options={[
                    {
                      value: 'Pending',
                      label: 'Pending',
                    },
                    {
                      value: 'Approved',
                      label: 'Approved',
                    },
                    {
                      value: 'Rejected',
                      label: 'Rejected',
                    },
                    {
                      value: 'Cancelled',
                      label: 'Cancelled',
                    },
                  ]}
                />
              </Col>
            </Access>
            <Access
              accessible={
                (hasPermitted('manager-leave-request-access') ||
                  hasPermitted('admin-leave-request-access')) &&
                othersView
              }
            >
              <Col className={styles.formCol} span={5}>
                <Row className={styles.formLabel}>
                  {intl.formatMessage({
                    id: 'leaves.employee',
                    defaultMessage: 'Employee',
                  })}
                </Row>

                {isWithInactiveEmployees ? (
                  <ProFormSelect
                    name="select"
                    options={selectorEmployeesWithInactives}
                    showSearch
                    fieldProps={{
                      optionItemRender(item) {
                        return item.label;
                      },
                      onChange: (value) => {
                        setSelectedEmployee(value);
                      },
                    }}
                    value={selectedEmployee}
                    placeholder={intl.formatMessage({
                      id: 'leaves.employee.placeholder',
                      defaultMessage: 'Search Employee',
                    })}
                  />
                ) : (
                  <ProFormSelect
                    name="select"
                    options={selectorEmployees}
                    showSearch
                    fieldProps={{
                      optionItemRender(item) {
                        return item.label;
                      },
                      onChange: (value) => {
                        setSelectedEmployee(value);
                      },
                    }}
                    value={selectedEmployee}
                    placeholder={intl.formatMessage({
                      id: 'leaves.employee.placeholder',
                      defaultMessage: 'Search Employee',
                    })}
                  />
                )}
              </Col>
            </Access>
            <Access
              accessible={
                (hasPermitted('manager-leave-request-access') ||
                  hasPermitted('admin-leave-request-access')) &&
                othersView
              }
            >
              <Col className={styles.formCol} span={5}>
                <Row className={styles.formLabel}>
                  {intl.formatMessage({
                    id: 'leaves.leaveType',
                    defaultMessage: 'Leave Type',
                  })}
                </Row>
                <ProFormSelect
                  name="leaveTypes"
                  showSearch
                  mode={hasPermitted('manager-leave-request-access') ? 'multiple' : 'single'}
                  fieldProps={{
                    optionItemRender(item) {
                      return item.label;
                    },
                    maxTagCount: 2,
                    onChange: (value) => {
                      setSelectedLeaveType(value);
                    },
                  }}
                  options={relatedLeaveTypes}
                  placeholder={intl.formatMessage({
                    id: 'leaves.leaveType.placeholder',
                    defaultMessage: 'Select Leave Type',
                  })}
                  value={selectedLeaveType}
                />
              </Col>
            </Access>
            <Access
              accessible={
                (hasPermitted('manager-leave-request-access') ||
                  hasPermitted('admin-leave-request-access')) &&
                othersView
              }
            >
              <Col className={styles.switch} span={8}>
                <Text className={styles.text}>
                  {intl.formatMessage({
                    id: 'leaves.includeInactiveEmployees',
                    defaultMessage: 'Include Inactive Employees',
                  })}
                </Text>
                <Switch
                  onChange={(checked: boolean, event: Event) => {
                    setSelectedEmployee(undefined);
                    setIsWithInactiveEmployees(checked);
                  }}
                  checkedChildren="Yes"
                  unCheckedChildren="No"
                  defaultChecked={false}
                  checked={isWithInactiveEmployees}
                />
              </Col>
            </Access>
            <Access accessible={hasPermitted('employee-leave-request-access') && !othersView}>
              <Col className={styles.resetBtnCol} span={8}>
                <Space>
                  <Col>
                    <Tooltip title="reset">
                      <Button
                        type="default"
                        className={styles.resetBtn}
                        size="middle"
                        onClick={async () => {
                          setLoading(true);
                          setSelectedEmployee(undefined);
                          setFromDate(undefined);
                          setToDate(undefined);
                          setLocation(null);
                          setDepartment(null);
                          setSelectedLeaveType(
                            hasPermitted('manager-leave-request-access') &&
                              props.accessLevel == 'manager'
                              ? []
                              : null,
                          );
                          setIsWithInactiveEmployees(false);
                          setSelectedStatus([]);

                          resetLeaveRequestList(1, 20);
                        }}
                      >
                        {intl.formatMessage({
                          id: 'leaves.reset',
                          defaultMessage: ' Reset',
                        })}
                      </Button>
                    </Tooltip>
                  </Col>
                  <Col className={styles.searchCol}>
                    <Tooltip title="search">
                      <Button
                        type="primary"
                        icon={<SearchOutlined />}
                        size="middle"
                        onClick={async () => {
                          setLoading(true);
                          callGetLeaveRequestData(1, 20);
                        }}
                      >
                        {intl.formatMessage({
                          id: 'leaves.search',
                          defaultMessage: 'Search',
                        })}
                      </Button>
                    </Tooltip>
                  </Col>
                </Space>
              </Col>
            </Access>
          </Row>
          <Access
            accessible={
              (hasPermitted('admin-leave-request-access') ||
                hasPermitted('manager-leave-request-access')) &&
              othersView
            }
          >
            <Row className={styles.row}>
              <Col className={styles.dateCol} span={6}>
                <Row className={styles.formLabel}>
                  {intl.formatMessage({
                    id: 'leaves.status',
                    defaultMessage: 'Status',
                  })}
                </Row>
                <Select
                  name="leaveState"
                  showSearch
                  optionFilterProp="label"
                  mode={'multiple' as const}
                  onChange={(value) => {
                    setSelectedStatus(value);
                  }}
                  className={styles.status}
                  placeholder={intl.formatMessage({
                    id: 'leaves.status.placeholder',
                    defaultMessage: 'Select Status',
                  })}
                  value={selectedStatus}
                  maxTagCount={'responsive' as const}
                  options={[
                    {
                      value: 'Pending',
                      label: 'Pending',
                    },
                    {
                      value: 'Approved',
                      label: 'Approved',
                    },
                    {
                      value: 'Rejected',
                      label: 'Rejected',
                    },
                    {
                      value: 'Cancelled',
                      label: 'Cancelled',
                    },
                  ]}
                />
              </Col>
              {/* <Access accessible={(hasPermitted('admin-leave-request-access')) && othersView && props.accessLevel == 'admin'}>
                          <Col
                              className={styles.formCol}
                              span={5}
                          >
                              <Row className={styles.formLabel}>
                                {intl.formatMessage({
                                  id: 'leaves.department',
                                  defaultMessage: 'Department',
                                })}

                              </Row>
                              <ProFormSelect
                                  name="departmentSelect"
                                  showSearch
                                  options={relatedDepartments}
                                  fieldProps={{
                                      optionItemRender(item) {
                                          return item.label;
                                      },
                                      onChange: (value) => {
                                          setDepartment(value);
                                      }
                                  }}
                                  placeholder= {intl.formatMessage({
                                    id: 'leaves.department.placeholder',
                                    defaultMessage: 'Select Department',
                                  })}
                                  value={department}
                              />
                          </Col>

                          <Col
                              className={styles.formCol}
                              span={5}
                          >
                              <Row className={styles.formLabel}>
                                {intl.formatMessage({
                                  id: 'leaves.location',
                                  defaultMessage: 'Location',
                                })}

                              </Row>
                              <ProFormSelect
                                  name="locationSelect"
                                  showSearch
                                  options={relatedAdminLocatons}
                                  fieldProps={{
                                      optionItemRender(item) {
                                          return item.label;
                                      },
                                      onChange: (value) => {
                                          setLocation(value);
                                      }
                                  }}
                                  placeholder= {intl.formatMessage({
                                    id: 'leaves.location.placeholder',
                                    defaultMessage: 'Select Location',
                                  })}
                                  value={location}
                                  filterOption={true}
                              />
                          </Col>
                        </Access> */}
              <Col className={styles.resetMainCol} span={8}>
                <Space>
                  <Col className={styles.resetCol}>
                    <Tooltip title="reset">
                      <Button
                        type="default"
                        className={styles.resetBtn}
                        onClick={async () => {
                          setLoading(true);
                          setSelectedEmployee(undefined);
                          setFromDate(undefined);
                          setToDate(undefined);
                          setLocation(null);
                          setDepartment(null);
                          setSelectedLeaveType(
                            hasPermitted('manager-leave-request-access') &&
                              props.accessLevel == 'manager'
                              ? []
                              : null,
                          );
                          setIsWithInactiveEmployees(false);
                          setSelectedStatus([]);
                          resetLeaveRequestList(1, 20);
                        }}
                      >
                        {intl.formatMessage({
                          id: 'leaves.reset',
                          defaultMessage: 'Reset',
                        })}
                      </Button>
                    </Tooltip>
                  </Col>
                  <Col className={styles.searchCol}>
                    <Tooltip title="search">
                      <Button
                        type="primary"
                        icon={<SearchOutlined />}
                        onClick={async () => {
                          setLoading(true);
                          callGetLeaveRequestData(1, 20);
                        }}
                      >
                        {intl.formatMessage({
                          id: 'leaves.search',
                          defaultMessage: 'Search',
                        })}
                      </Button>
                    </Tooltip>
                  </Col>
                  <Col className={styles.excelCol}>
                    <Tooltip title="Download Excel">
                      <Button
                        type="primary"
                        icon={<DownloadOutlined />}
                        size="middle"
                        loading={loadingExcelDownload}
                        onClick={async () => {
                          callDownloadTeamLeaves();
                        }}
                      />
                    </Tooltip>
                  </Col>
                </Space>
              </Col>
            </Row>
          </Access>
        </div>

        <Row
          style={{
            width: '100%',
          }}
        >
          <ProTable<LeaveRequestItem>
            columns={columns}
            actionRef={actionRef}
            dataSource={leaveRequestData}
            request={async (params = { current: 1, pageSize: 20 }, sort, filter) => {
              const _filter = Object.keys(filter)
                .filter((key) => !_.isEmpty(filter[key]))
                .reduce((obj, key) => {
                  obj[key] = filter[key];
                  return obj;
                }, {});

              const sortValue = sort?.employeeName
                ? { name: 'employeeName', order: sort?.employeeName === 'ascend' ? 'ASC' : 'DESC' }
                : sort?.fromDate
                ? { name: 'fromDate', order: sort?.fromDate === 'ascend' ? 'ASC' : 'DESC' }
                : sort?.toDate
                ? { name: 'toDate', order: sort?.toDate === 'ascend' ? 'ASC' : 'DESC' }
                : { name: 'fromDate', order: 'DESC' };
              const filters = filter ? filter : '';

              const tableParams = {
                current: params?.current,
                pageSize: params?.pageSize,
                sortValue: sortValue,
                filters: _filter,
              };
              setTableState(tableParams);
              await callGetLeaveRequestData(params?.current, params?.pageSize, sortValue, filters);
              return leaveRequestData;
            }}
            pagination={{ pageSize: 20, total: dataCount, hideOnSinglePage: true }}
            search={false}
            options={{
              reload: () => {
                actionRef.current?.reset();
                actionRef.current?.reload();
                setSelectedEmployee(undefined);
                setFromDate(undefined);
                setToDate(undefined);
                setLocation(null);
                setDepartment(null);
                setSelectedLeaveType(
                  hasPermitted('manager-leave-request-access') && props.accessLevel == 'manager'
                    ? []
                    : null,
                );
                setIsWithInactiveEmployees(false);
                setSelectedStatus([]);
              },
            }}
            style={{ width: '100%' }}
          />
        </Row>

        <Modal
          title={
            <Row>
              <Col>
                <Space style={{ paddingTop: 4 }}>
                  {intl.formatMessage({
                    id: 'pages.Workflows.addNewWorkflow',
                    defaultMessage: 'Leave Request',
                  })}
                </Space>
              </Col>
              <Col style={{ marginLeft: 20 }}>
                <Space>
                  <Tag
                    style={{
                      borderRadius: 20,
                      paddingRight: 20,
                      paddingLeft: 20,
                      paddingTop: 2,
                      paddingBottom: 2,
                      border: 0,
                      backgroundColor:
                        leaveDataSet === 'Approved'
                          ? '#C0FFC7'
                          : leaveDataSet === 'Pending'
                          ? '#FFC470'
                          : '#FFC0C0',
                      color:
                        leaveDataSet === 'Approved'
                          ? '#3E8D47'
                          : leaveDataSet === 'Pending'
                          ? '#B26A04'
                          : '#AA4343',
                    }}
                  >
                    {leaveDataSet.StateLabel}
                  </Tag>
                </Space>
              </Col>
            </Row>
          }
          visible={isModalVisible}
          width={880}
          onCancel={handleCancel}
          centered
          destroyOnClose={true}
          // footer={[
          //   <Space>
          //     <Access
          //       accessible={(hasPermitted('admin-leave-request-access') && othersView) ||
          //       (hasPermitted('employee-leave-request-access') && !othersView) || (hasPermitted('manager-leave-request-access') && othersView)}
          //     >
          //       {
          //       !leaveDataSet.hasPendingCoveringPersonRequests ? (

          //         <></>
          //         // actions.map((element) => {
          //         //   if (_.get(element, 'actionName', false)) {
          //         //     return (
          //         //       <Popconfirm
          //         //         title={
          //         //           <FormattedMessage
          //         //             id="actionConfirmTitle"
          //         //             defaultMessage="Are sure you want perform this action?"
          //         //           />
          //         //         }
          //         //         placement="top"
          //         //         onConfirm={() => {
          //         //           updateWorkflowInstance(element.id, workflowInstanceId, workflowId, 2);
          //         //         }}
          //         //         okText="Yes"
          //         //         cancelText="No"
          //         //       >
          //         //         <Button
          //         //           key={element.id}
          //         //           // onClick={}
          //         //           type={element.isPrimary ? 'primary' : 'default'}
          //         //         >
          //         //           {element.label}
          //         //         </Button>
          //         //       </Popconfirm>
          //         //     );
          //         //   }
          //         // })
          //       ) : (
          //         <>
          //           {/* {
          //             props.accessLevel === 'employee' ? (
          //               <Popconfirm
          //                 title={
          //                   <FormattedMessage
          //                     id="cancelConfirmTitle"
          //                     defaultMessage="Are sure you want to cancel?"
          //                   />
          //                 }
          //                 placement="top"
          //                 onConfirm={() => {
          //                   cancelLeaveCoveringRequest();
          //                 }}
          //                 okText="Yes"
          //                 cancelText="No"
          //               >
          //                 <Button
          //                   key={'cancel'}
          //                   style={{borderRadius: 6}}
          //                   type={'default'}
          //                 >
          //                   <FormattedMessage
          //                     id="cancel"
          //                     defaultMessage="Cancel Request"
          //                   />

          //                 </Button>
          //               </Popconfirm>

          //             ) : (
          //               <></>
          //             )
          //           } */}

          //         </>
          //       )

          //       }
          //     </Access>
          //   </Space>,
          // ]}
          footer={false}
        >
          {loadingModel ? (
            <Spin size="large" spinning={loadingModel} />
          ) : (
            <>
              <Form form={approverCommentForm}>
                <LeaveRequest
                  fromLeaveRquestList={true}
                  scope={relateScope}
                  leaveData={leaveDataSet}
                  setLeaveDataSet={setleaveDataSet}
                  employeeId={leaveDataSet.employeeIdNo}
                  employeeFullName={employeeName}
                  setApproverComment={setApproverComment}
                  actions={actions}
                ></LeaveRequest>
              </Form>
            </>
          )}
        </Modal>
      </Space>
    </>
  );
};

export default LeaveTableView;
