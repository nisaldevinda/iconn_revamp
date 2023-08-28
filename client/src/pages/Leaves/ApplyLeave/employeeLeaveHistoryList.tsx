import React, { useEffect, useRef, useState } from 'react';
import { DownloadOutlined, SearchOutlined } from '@ant-design/icons';
import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { ProFormDateRangePicker, ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import { Popover } from 'antd';

// import { approveTimeChange, approveTimeChangeAdmin, requestTimeChange, accessibleWorkflowActions, updateInstance } from '@/services/attendance';
import request, { APIResponse } from '@/utils/request';
import { getModel, Models } from '@/services/model';
import _, { trim, values } from 'lodash';
import { CommentOutlined, EyeOutlined } from '@ant-design/icons';
import styles from './index.less';
import { downloadBase64File } from '@/utils/utils';
import { ReactComponent as Edit } from '../../../assets/attendance/Edit.svg';
import { ReactComponent as TeamInfo } from '../../../assets/SideBar/teaminfo-green.svg';
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
  Select,
  Input,
} from 'antd';

import { getEmployeeShortLeaveDataSet, getEmployeeLeaveHistoryDataSet } from '@/services/leave';

const { Text } = Typography;
const { Search } = Input;

moment.locale('en');

export type TableViewProps = {
  employeeId?: number;
  others?: boolean;
  nonEditModel?: boolean;
  adminView?: boolean;
  accessLevel?: string;
  listType: string;
};

type LeaveRequestItem = {
  id: number;
  date: string;
  employeeName: string;
  shortLeaveType: string;
  name: string;
  reason: string;
  numberOfHours: number;
  StateLabel: string;
};

const LeaveHistoryList: React.FC<TableViewProps> = (props) => {
  const actionRef = useRef<ActionType>();
  const [leaveTypes, setLeaveTypes] = useState<any>([]);
  const [relatedLeaveTypes, setRelatedLeaveTypes] = useState<any>([]);
  const [leaveRequestData, setLeaveRequestData] = useState([]);
  const [columnList, setColumnList] = useState([]);
  const [tableState, setTableState] = useState<any>({});
  const [approvalLevelList, setApprovalLevelList] = useState<any>([]);
  const [loading, setLoading] = useState(false);
  const [dataCount, setDataCount] = useState(0);
  const [shortLeaveTypes, setShortLeaveTypes] = useState<any>([]);
  const [relatedLeaveStates, setRelatedLeaveStates] = useState<any>([]);

  async function callGetLeaveRequestData(
    pageNo?: number,
    pageCount?: number,
    sort = { name: 'fromDate', order: 'DESC' },
    filters,
    searchString?: string,
  ) {
    try {
      setLoading(true);

      const params = {
        pageNo: pageNo,
        pageCount: pageCount,
        sort: sort,
        filter: filters,
        searchString: searchString,
      };

      setLeaveRequestData([]);
      setDataCount(0);

      if (props.listType == 'shortLeave') {
        const shortLeaveTypeArr: object = {};

        shortLeaveTypeArr['IN_SHORT_LEAVE'] = {
          text: 'IN',
          status: 'IN_SHORT_LEAVE',
        };
        shortLeaveTypeArr['OUT_SHORT_LEAVE'] = {
          text: 'OUT',
          status: 'OUT_SHORT_LEAVE',
        };
        setShortLeaveTypes({ ...shortLeaveTypeArr });
        getShortLeaveStates();
        await getEmployeeShortLeaveDataSet(params).then((response: any) => {
          if (response) {
            setLeaveRequestData(response.data.sheets);
            setDataCount(response.data.count);
          }
        });
      } else if (props.listType == 'leave') {
        await getEmployeeLeaveHistoryDataSet(params).then((response: any) => {
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

  useEffect(() => {
    if (props.listType && props.listType == 'leave') {
      getLeaveTypes();
      getLeaveStates();
    }
  }, []);

  const getShortLeaveStates = async () => {
    try {
      const actions: any = [];
      const shortLeaveStatusArr: object = {};

      let statusses = [
        {
          status: 'Pending',
          text: 'Pending'
        },
        {
          status: 'Approved',
          text: 'Approved'
        },
        {
          status: 'Rejected',
          text: 'Rejected'
        },
        {
          status: 'Cancelled',
          text: 'Cancelled'
        },
      ];

      await statusses.forEach(async (element: any) => {
        shortLeaveStatusArr[element['status']] = {
          status: element['status'],
          text: element['status'],
        };
      });


      setRelatedLeaveStates({ ...shortLeaveStatusArr });
      return actions;
    } catch (err) {
      console.log(err);
    }
  };

  const getLeaveStates = async () => {
    try {
      const actions: any = [];
      const leaveStatusArr: object = {};

      let statusses = [
        {
          status: 'Pending',
          text: 'Pending'
        },
        {
          status: 'Approved',
          text: 'Approved'
        },
        {
          status: 'Rejected',
          text: 'Rejected'
        },
        {
          status: 'Cancelled',
          text: 'Cancelled'
        },
      ];

      await statusses.forEach(async (element: any) => {
        leaveStatusArr[element['status']] = {
          status: element['status'],
          text: element['status'],
        };
      });

      setRelatedLeaveStates({...leaveStatusArr});
      return actions;
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
      let params = { sorter: { name: 'name', order: 'ASC' } };

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
  const getLeaveRequestApproverDetails = async (leaveData: any) => {
    try {
      setApprovalLevelList([]);

      if (leaveData.workflowInstanceIdNo) {
        let path: string;
        path = `/api/get-approval-level-wise-state/` + leaveData.workflowInstanceIdNo;
        const result = await request(path);
        if (result['data'] !== null) {
          let levels = [];
  
          Object.keys(result['data']).some((key) => {
            result['data'][key]['levelName'] = key == 'Initial Level' ? key :  key + ' Approval';
            result['data'][key]['approvers'] =
              result['data'][key]['approvers'].length > 0
                ? String(result['data'][key]['approvers'])
                : '--';
            levels.push(result['data'][key]);
          });
  
  
          setApprovalLevelList(levels);
        }
      }
    } catch (error) {
      console.log(error);
    }
  }

  const convertminutesToHoursAndMin = (minutes) => {
    if (minutes < 60) {
      return minutes + 'm';
    }

    if (minutes % 60 === 0) {
      return minutes / 60 + 'h';
    }

    let num = minutes;
    let hours = num / 60;
    let rhours = Math.floor(hours);
    let mins = (hours - rhours) * 60;
    let rminutes = Math.round(mins);
    return rhours + 'h ' + rminutes + 'm';
  };

  const shortLeaveColumns: ProColumns<LeaveRequestItem>[] = [
    {
      title: 'Date',
      dataIndex: 'date',
      width: '8%',
      sorter: true,
      hideInSearch: true,
    },
    {
      title: 'Start Time',
      dataIndex: 'fromTime',
      width: '8%',
      sorter: false,
      hideInSearch: true,
    },
    {
      title: 'End Time',
      dataIndex: 'toTime',
      width: '8%',
      sorter: false,
      hideInSearch: true,
    },
    {
      title: 'Hours',
      dataIndex: 'numberOfMinutes',
      width: '8%',
      hideInSearch: true,
      render: (_, record) => <Space>{convertminutesToHoursAndMin(record.numberOfMinutes)}</Space>,
    },
    {
      title: 'In/Out',
      dataIndex: 'shortLeaveType',
      width: '8%',
      // hideInSearch: true,
      filters: true,
      onFilter: true,
      valueEnum: shortLeaveTypes,
      render: (_, record) => (
        <Space>
          {record.shortLeaveType === 'OUT_SHORT_LEAVE'
            ? 'Out'
            : record.shortLeaveType === 'IN_SHORT_LEAVE'
            ? 'In'
            : ''}
        </Space>
      ),
    },
    {
      title: 'Reason',
      dataIndex: 'reason',
      sorter: false,
      width: '26%',
      hideInSearch: true,
      render: (_, record) => (
        <Space>
          {record.reason !== null && record.reason.length <= 30 ? (
            <span>{record.reason}</span>
          ) : record.reason !== null && record.reason.length > 30 ? (
            <Tooltip title={record.reason}>{record.reason.substring(0, 30 - 3) + '...'}</Tooltip>
          ) : (
            <>-</>
          )}
        </Space>
      ),
    },
    {
      title: 'Status',
      dataIndex: 'StateLabel',
      width: '10%',
      filters: true,
      onFilter: true,
      valueEnum: relatedLeaveStates,
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
            }}
            color={record.stateTagColor}
          >
            {record.StateLabel}
          </Tag>
        </Space>
      ),
    },
    {
      title: 'Requested At',
      dataIndex: 'requestedDate',
      width: '12%',
      sorter: false,
      hideInSearch: true,
      // hideInTable: selectedEmployee ? true : false
    },
    // {
    //   title: 'Approved At',
    //   dataIndex: 'approvedAt',
    //   width: '12%',
    //   sorter: false,
    //   hideInSearch: true,
    //   // hideInTable: selectedEmployee ? true : false
    // },
    // {
    //   title: 'Approved By',
    //   dataIndex: 'approvedBy',
    //   width: '10%',
    //   sorter: false,
    //   hideInSearch: true,
    //   // hideInTable: selectedEmployee ? true : false
    // },
    {
      title:'Approver Details',
      dataIndex: '',
      fixed: 'right',
      width: '6%',
      search: false,
      render: (_, record) => {
        return {
          props: {
            // style:
            //   record.leave.length > 0
            //     ? { background: '#FFF7E6' }
            //     : record.id === currentEditingRow
            //     ? { background: '#f2fced' }
            //     : record.incompleUpdate
            //     ? { background: '#FCFEF1' }
            //     : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>

                    {
                      record.workflowInstanceIdNo ? 
                      <Popover
                      content={
                        <Row style={{ width: 450 }}>
                          <List
                            itemLayout="horizontal"
                            dataSource={approvalLevelList}
                            style={
                              approvalLevelList.length > 3
                                ? { overflowY: 'scroll', height: 200, width: '100%' }
                                : { width: '100%' }
                            }
                            renderItem={(item, index) =>
                              <List.Item key={item.id}>
                                <List.Item.Meta
                                  // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                                  title={
                                    <Row style={{ width: '100%' }}>
                                      <p
                                        key="commentedUserName"
                                        style={{
                                          fontSize: 12,
                                          fontWeight: 500,
                                          marginBottom: 0,
                                          marginRight: 10,
                                        }}
                                      >
                                        {item.levelName}
                                      </p>
                                      <p
                                        key="commentDateTime"
                                        style={{
                                          fontSize: 10,
                                          marginBottom: 0,
                                          fontWeight: 400,
                                          marginRight: 10,
                                          paddingTop: 2,
                                          color: '#626D6C',
                                        }}
                                      >
                                        {'(' + item.approvers + ')'}
                                      </p>
                                      <Tag
                                        style={{
                                          borderRadius: 20,
                                          fontSize: 11,
                                          paddingRight: 20,
                                          paddingLeft: 20,
                                          paddingTop: 2,
                                          paddingBottom: 2,
                                          border: 0,
                                        }}
                                        color={item.stateTagColor}
                                      >
                                        {item.state}
                                      </Tag>

                                      {item.performAt ? 
                                        <Col style={{fontSize: 10, paddingTop: 5, color: '#626D6C'}}>{'At '+ item.performAt}</Col> : <></>
                                      }
                                    </Row>
                                  }
                                  description={<Col style={{fontSize: 10}} >{item.comment ? item.comment : 'No Comments'}</Col>}
                                />
                              </List.Item>
                            
                            }
                          />
                          
                        </Row>
                      }
                      placement="left"
                      title="Approver Details"
                      trigger="click"
                      onVisibleChange={(val) => {
                        if (val) {
                          getLeaveRequestApproverDetails(record);
                        }
                      }}
                    >
                    
                      <TeamInfo height={20} style={{ cursor: 'pointer' }} ></TeamInfo>
                      {/* <Comment height={20} width={20} style={{ cursor: 'pointer' }}></Comment> */}
                    </Popover> : <>--</>
                    }
                    
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    }
  ];

  const leaveListColumns: ProColumns<LeaveRequestItem>[] = [
    {
      title: 'Start Date',
      dataIndex: 'fromDate',
      width: '8%',
      sorter: true,
      hideInSearch: true,
    },
    {
      title: 'End Date',
      dataIndex: 'toDate',
      width: '8%',
      sorter: true,
      hideInSearch: true,
    },
    {
      title: 'Days',
      dataIndex: 'numberOfLeaveDates',
      width: '8%',
      hideInSearch: true,
    },
    {
      title: 'LeaveType',
      dataIndex: 'leaveTypeId',
      width: '8%',
      filters: true,
      onFilter: true,
      valueEnum: leaveTypes,
      render: (_, record) => <Space>{record.leaveTypeName}</Space>,
    },
    {
      title: 'Reason',
      dataIndex: 'reason',
      sorter: false,
      width: '26%',
      hideInSearch: true,
      render: (_, record) => (
        <Space>
          {record.reason !== null && record.reason.length < 30 ? (
            <span>{record.reason}</span>
          ) : record.reason !== null && record.reason.length > 30 ? (
            <Tooltip title={record.reason}>{record.reason.substring(0, 30 - 3) + '...'}</Tooltip>
          ) : (
            <>-</>
          )}
        </Space>
      ),
    },
    {
      title: 'Status',
      dataIndex: 'StateLabel',
      width: '10%',
      filters: true,
      onFilter: true,
      valueEnum: relatedLeaveStates,
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
            }}
            color={record.stateTagColor}
          >
            {record.StateLabel}
          </Tag>
        </Space>
      ),
    },
    {
      title: 'Requested At',
      dataIndex: 'requestedDate',
      width: '12%',
      sorter: false,
      hideInSearch: true,
      // hideInTable: selectedEmployee ? true : false
    },
    {
      title:'Approver Details',
      dataIndex: '',
      fixed: 'right',
      width: '6%',
      search: false,
      render: (_, record) => {
        return {
          props: {
            // style:
            //   record.leave.length > 0
            //     ? { background: '#FFF7E6' }
            //     : record.id === currentEditingRow
            //     ? { background: '#f2fced' }
            //     : record.incompleUpdate
            //     ? { background: '#FCFEF1' }
            //     : {},
          },
          children: (
            <Space>
              <div>
                <Row>
                  <Space>
                    {
                      record.workflowInstanceIdNo ? 
                      <Popover
                        content={
                          <Row style={{ width: 450 }}>
                            <List
                              itemLayout="horizontal"
                              dataSource={approvalLevelList}
                              style={
                                approvalLevelList.length > 3
                                  ? { overflowY: 'scroll', height: 200, width: '100%' }
                                  : { width: '100%' }
                              }
                              renderItem={(item, index) =>
                                <List.Item key={item.id}>
                                  <List.Item.Meta
                                    // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                                    title={
                                      <Row style={{ width: '100%' }}>
                                        <p
                                          key="commentedUserName"
                                          style={{
                                            fontSize: 12,
                                            fontWeight: 500,
                                            marginBottom: 0,
                                            marginRight: 10,
                                          }}
                                        >
                                          {item.levelName}
                                        </p>
                                        <p
                                          key="commentDateTime"
                                          style={{
                                            fontSize: 10,
                                            marginBottom: 0,
                                            fontWeight: 400,
                                            marginRight: 10,
                                            paddingTop: 2,
                                            color: '#626D6C',
                                          }}
                                        >
                                          {'(' + item.approvers + ')'}
                                        </p>
                                        <Tag
                                          style={{
                                            borderRadius: 20,
                                            fontSize: 11,
                                            paddingRight: 20,
                                            paddingLeft: 20,
                                            paddingTop: 2,
                                            paddingBottom: 2,
                                            border: 0,
                                          }}
                                          color={item.stateTagColor}
                                        >
                                          {item.state}
                                        </Tag>

                                        {item.performAt ? 
                                          <Col style={{fontSize: 10, paddingTop: 5, color: '#626D6C'}}>{'At '+ item.performAt}</Col> : <></>
                                        }
                                      </Row>
                                    }
                                    description={<Col style={{fontSize: 10}} >{item.comment ? item.comment : 'No Comments'}</Col>}
                                  />
                                </List.Item>
                              
                              }
                            />
                            
                          </Row>
                        }
                        placement="left"
                        title="Approver Details"
                        trigger="click"
                        onVisibleChange={(val) => {
                          if (val) {
                            getLeaveRequestApproverDetails(record);
                          }
                        }}
                      >
                      
                      <TeamInfo height={20} style={{ cursor: 'pointer' }} ></TeamInfo>
                        {/* <Comment height={20} width={20} style={{ cursor: 'pointer' }}></Comment> */}
                      </Popover> : <>--</>
                    }
                  </Space>
                </Row>
              </div>
            </Space>
          ),
        };
      },
    }
  ];

  return (
    <div style={{ height: 500 }}>
      <Row
        style={{
          width: '100%',
          marginBottom: 20,
        }}
      >
        <Col span={3} offset={21} style={{ display: 'flex' }}>
          {props.listType == 'shortLeave' ? (
            <>
              <div style={{ marginRight: 20, marginTop: 5 }}>Month</div>
              <DatePicker
                format="YYYY/MM"
                placeholder="Select month"
                onChange={async (value) => {
                  let searchString = value ? value.format('YYYY-MM') : null;
                  const sortValue = { name: 'date', order: 'DESC' };
                  const filters = tableState.filters;

                  const tableParams = {
                    current: 1,
                    pageSize: 10,
                    sortValue: sortValue,
                    filters: filters,
                  };
                  setTableState(tableParams);
                  await callGetLeaveRequestData(1, 10, sortValue, filters, searchString);
                }}
                picker="month"
              />
            </>
          ) : (
            <>
              <div style={{ marginRight: 20, marginTop: 5 }}>Year</div>
              <DatePicker
                format="YYYY"
                placeholder="Select year"
                onChange={async (value) => {
                  let searchString = value ? value.format('YYYY') : null;
                  const sortValue = { name: 'date', order: 'DESC' };
                  const filters = tableState.filters;

                  const tableParams = {
                    current: 1,
                    pageSize: 10,
                    sortValue: sortValue,
                    filters: filters,
                  };
                  setTableState(tableParams);
                  await callGetLeaveRequestData(1, 10, sortValue, filters, searchString);
                }}
                picker="year"
              />
            </>
          )}
        </Col>
      </Row>
      <Row
        style={{
          width: '100%',
        }}
      >
        <ProTable<LeaveRequestItem>
          columns={props.listType == 'shortLeave' ? shortLeaveColumns : leaveListColumns}
          actionRef={actionRef}
          dataSource={leaveRequestData}
          request={async (params = { current: 1, pageSize: 10 }, sort, filter) => {
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
          pagination={{ pageSize: 10, total: dataCount }}
          search={false}
          toolBarRender={false}
          // scroll={{ y: 370 }}
          options={
            {
              // reload: () => {
              //   actionRef.current?.reset();
              //   actionRef.current?.reload();
              //   setSelectedEmployee(undefined);
              //   setFromDate(undefined);
              //   setToDate(undefined);
              //   setLocation(null);
              //   setDepartment(null);
              //   setSelectedLeaveType(null);
              //   setIsWithInactiveEmployees(false);
              //   setSelectedStatus([]);
              // },
            }
          }
          style={{ width: '100%' }}
        />
      </Row>
    </div>
  );
};

export default LeaveHistoryList;
