import React, {useRef, useEffect, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout'
import { Card ,Col , Row, Tooltip ,Button , Form ,Select, Space ,Switch ,Image, Checkbox ,Typography, Empty, Spin, DatePicker, Popover, } from 'antd';
import ProTable from '@ant-design/pro-table';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProForm, { ProFormDateRangePicker } from '@ant-design/pro-form';
import { DragOutlined, SettingOutlined } from '@ant-design/icons';
import { Access, useAccess, useIntl, FormattedMessage } from 'umi';
import ExportIcon from '../../../assets/leaveEntitlementUsageReport/export-csv-file-icon.svg';
import TimeLineIcon from '../../../assets/leaveEntitlementUsageReport/icon-show-timeline.svg';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location'
import { getAllDepartment } from '@/services/department';
import { getLeaveEntitlementUsage , getLeaveTypes, checkShortLeaveAccessabilityForCompany } from'@/services/leave';
import { downloadBase64File } from '@/utils/utils';
import PermissionDeniedPage from '@/pages/403';
import moment from 'moment';
import { getEmployeeList, getEmployeeListByEntityId, getAllEmployeeList } from '@/services/dropdown';
import OrgSelector from '@/components/OrgSelector';
import "./index.css"
import { DndProvider, useDrop, useDrag } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { getAllEntities } from '@/services/department';

const LeaveUsageReportForEmployee: React.FC = () => {
    const { RangePicker } = DatePicker;
    const tableRef = useRef<ActionType>();
    const { Option } = Select;
    const [form] = Form.useForm();
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const { Text } = Typography;
    const [reportType , setReportType] = useState('');
    const [requestType , setRequestType] = useState('');
    const [fromDate, setFromDate] = useState();
    const [toDate, setToDate] = useState();
    const [columnKeys, setColumnKeys] = useState([]);
    const [employees, setEmployees] = useState([]);
    const [selectedLeaveTypes, setSelectedLeaveTypes] = useState([]);
    const [jobTitles, setJobTitles] = useState([]);
    const [department, setDepartment] = useState([]);
    const [location, setLocation] = useState([]);
    const [reportData, setReportData] = useState({});
    const [hierarchyConfig, setHierarchyConfig] = useState<Object>({});
    const [leaveEntitlements ,setLeaveEntitlements] = useState([]);
    const [disabled, setDisabled] = useState(false);
    const [loading, setLoading] = useState(false);
    const [entitlementTableColumns, setEntitlementTableColumns] = useState([]);
    const [leaveSummaryTableColumns, setLeaveSummaryTableColumns] = useState([]);
    const [leaveTypes, setLeaveTypes] = useState([]);
    const [leavePeriod , setLeavePeriod] = useState('current');
    const [isShowShortLeaveTab, setIsShowShortLeaveTab] = useState(false);
    const [entityId, setEntityId] = useState(1);
    const [isWithInactiveEmployees, setIsWithInactiveEmployees] = useState<any>(false);
    const [isEnableInactiveEmployees, setIsEnableInactiveEmployees] = useState<any>(false);
    useEffect(() =>{
      
      const fetchEmployeeData = async () => {
        try {
          const { data } = await getAllEmployeeList('ADMIN');


          
          setEmployees(data);
        } catch (err) {
          console.log(err)
        }
      }
      const  fetchEntities = async () => {
        const entities = await getAllEntities();
        setHierarchyConfig(entities.data.orgHierarchyConfig);
      }
      const  fetchJobTitleData = async () => {
        let params = {sorter : { name: 'name', order: 'ASC' }}
        const {data} = await getAllJobTitles(params);
        setJobTitles(data);
      }
      const  fetchLocationData = async () => {
        let params = {sorter : { name: 'name', order: 'ASC' }}
        const {data} = await getAllLocations(params);
        setLocation(data);
      }
      const  fetchDepartmentData = async () => {
        let params = {sorter : { name: 'name', order: 'ASC' }}
        const {data} = await getAllDepartment(params);
        setDepartment(data);
      }
      const fetchLeaveType = async () => {
        let params = {sorter : { name: 'name', order: 'ASC' }}
        const {data} = await getLeaveTypes(params);
        setLeaveTypes(data);
      }
      fetchEmployeeData();
      checkShortLeaveAccessability();
      fetchEntities();
      fetchLeaveType();
    },[]);

    useEffect(() =>{
      if (reportType === 'leaveSummaryReport') {
        getEmployeesByEntityId(entityId);
      }
      
    },[entityId]);

    const checkShortLeaveAccessability = async () => {
      try {
        const response = await checkShortLeaveAccessabilityForCompany({});
        setIsShowShortLeaveTab(response.data.isMaintainShortLeave);
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

    const getEmployeesByEntityId = async(entityId:any) => {
      try {
        const { data } = await getEmployeeListByEntityId('ADMIN', entityId);
        setEmployees(data);
      } catch (err) {
        console.log(err)
      }
    };

    const processdLeaveSummaryColumns = () => {
      let processedCols: any = [];
      const columnArray = [...columnKeys];
  
      columnArray.map((columnData: any) => {
        if (columnData.isShowColumn) {
          let tempCol = null;
          switch (columnData.name) {
            case 'Employee Name':
              tempCol = {
                title: <FormattedMessage id="Attendance.employeeName" defaultMessage="Employee Name" />,
                dataIndex: 'employeeName',
                key: 'employeeName',
                render: (_, record) => {
                  return {
                    children: <Space>{record.employeeName}</Space>,
                  };
                },
              };
  
              break;
            case 'Employee Number':
              tempCol = {
                title: <FormattedMessage id="Attendance.employeeNumber" defaultMessage="Employee Number" />,
                dataIndex: 'employeeNumber',
                key: 'employeeNumber',
                render: (_, record) => {
                  return {
                    children: <Space>{record.employeeNumber}</Space>,
                  };
                },
              };
  
              break;
            case 'Reporting Person':
              tempCol = {
                title: (
                  <FormattedMessage
                    id="Attendance.shift"
                    defaultMessage={intl.formatMessage({
                      id: 'shiftName',
                      defaultMessage: 'Reporting Person',
                    })}
                  />
                ),
                dataIndex: 'reportsTo',
                key: 'reportsTo',
                width: 180,
                render: (_, record) => {
                  return {
                    children: (
                      <Space>
                        {record.reportsTo !== null && record.reportsTo.length <= 20 ? (
                          <span>{record.reportsTo}</span>
                        ) : record.reportsTo !== null && record.reportsTo.length > 20 ? (
                          <Tooltip title={record.reportsTo}>
                            {record.reportsTo.substring(0, 20 - 3) + '...'}
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
            case 'Start Date':
              tempCol = {
                title: (
                  <FormattedMessage
                    id="Attendance.fromDate"
                    defaultMessage={intl.formatMessage({
                      id: 'fromDate',
                      defaultMessage: 'Start Date',
                    })}
                  />
                ),
                width: 100,
                dataIndex: 'fromDate',
                key: 'fromDate',
                render: (_, record) => {
                  return {
                    children: (
                      <>
                        <Space>
                          {moment(record.fromDate, 'YYYY-MM-DD').isValid()
                            ? moment(record.fromDate).format('DD-MM-YYYY')
                            : null}
                        </Space>
                      </>
                    ),
                  };
                },
              };
  
              break;
            case 'End Date':
              tempCol = {
                title: (
                  <FormattedMessage
                    id="Attendance.toDate"
                    defaultMessage={intl.formatMessage({
                      id: 'toDate',
                      defaultMessage: 'End Date',
                    })}
                  />
                ),
                width: 100,
                dataIndex: 'toDate',
                key: 'toDate',
                render: (_, record) => {
                  return {
                    children: (
                      <>
                        {/* <Row>
                                  </Row> */}
                        <Space>
                          {moment(record.toDate, 'YYYY-MM-DD').isValid()
                            ? moment(record.toDate).format('DD-MM-YYYY')
                            : null}
                        </Space>
                      </>
                    ),
                  };
                },
              };
  
              break;
            case 'Status':
              tempCol = {
                title: (
                  <FormattedMessage
                    id="Attendance.StateLabel"
                    defaultMessage={intl.formatMessage({
                      id: 'StateLabel',
                      defaultMessage: 'Status',
                    })}
                  />
                ),
                dataIndex: 'StateLabel',
                key: 'StateLabel',
                // hideInTable: !isMaintainOt ? true : false,
                search: false,
                render: (_, record) => {
                  return {
                    children: (
                      <Space>
                        <div>
                          <Row>
                            <Space>{record.StateLabel ? record.StateLabel : '-'}</Space>
                          </Row>
                        </div>
                      </Space>
                    ),
                  };
                },
              };
  
              break;
            case 'Num Of Days':
              tempCol = {
                title: (
                  <FormattedMessage
                    id="Attendance.OtHours"
                    defaultMessage={intl.formatMessage({
                      id: 'numberOfLeaveDates',
                      defaultMessage: 'Num Of Days',
                    })}
                  />
                ),
                dataIndex: 'numberOfLeaveDates',
                key: 'numberOfLeaveDates',
                // hideInTable: !isMaintainOt ? true : false,
                search: false,
                render: (_, record) => {
                  return {
                    children: (
                      <Space>
                        <div>
                          <Row>
                            <Space>
                              {record.numberOfLeaveDates ? record.numberOfLeaveDates : '-'}
                            </Space>
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
                    id="Attendance.leaveTypeName"
                    defaultMessage={intl.formatMessage({
                      id: 'leaveTypeName',
                      defaultMessage: 'Leave Type',
                    })}
                  />
                ),
                dataIndex: 'leaveTypeName',
                key: 'leaveTypeName',
                // hideInTable: !isMaintainOt ? true : false,
                search: false,
                render: (_, record) => {
                  return {
                    children: (
                      <Space>
                        <div>
                          <Row>
                            <Space>
                              {record.leaveTypeName ? record.leaveTypeName : '-'}
                            </Space>
                          </Row>
                        </div>
                      </Space>
                    ),
                  };
                },
              };
  
              break;
            case 'Reason':
              tempCol = {
                title: (
                  <FormattedMessage
                    id="Attendance.reason"
                    defaultMessage={intl.formatMessage({
                      id: 'reason',
                      defaultMessage: 'Reason',
                    })}
                  />
                ),
                dataIndex: 'reason',
                key: 'reason',
                render: (_, record) => {
                  return {
                    children: (
                      <Space>
                      {record.reason !== null && record.reason.length <= 20 ? (
                        <span>{record.reason}</span>
                      ) : record.reason !== null && record.reason.length > 20 ? (
                        <Tooltip title={record.reason}>
                          {record.reason.substring(0, 20 - 3) + '...'}
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
          }
  
          processedCols.push(tempCol);
        }
      });
      setLeaveSummaryTableColumns(processedCols);
    };

    
    const onFinish = async (params:any,) => {
      setLoading(true);
      if (reportType == 'leaveEntitlement') {
        await setLeaveEntitlements([]);
        processdColumns(selectedLeaveTypes);
      }


      if (reportType == 'leaveSummaryReport') {
        await setLeaveEntitlements([]);
        processdLeaveSummaryColumns();
      }

      const recordData = params;
      recordData.reportType = reportType;
      recordData.dataType ="table";
      recordData.pageNo=1;
      recordData.pageCount = 10000;

      if (reportType !== 'employeeLeaveRequestReport' && reportType !== 'leaveSummaryReport') {
        recordData.leavePeriod = params.leavePeriod ?? leavePeriod;
        recordData.leaveType = reportType == 'leaveEntitlement' ?  JSON.stringify(params.leaveType) : params.leaveType;
        recordData.activeState = isWithInactiveEmployees;
        recordData.entityId = entityId;
      }

      if (reportType === 'leaveSummaryReport') {
        recordData.employee = params.employee ? JSON.stringify(params.employee) : null;
        recordData.dateRange = null;
        recordData.fromDate = fromDate;
        recordData.toDate = toDate;
        recordData.leaveStatus = JSON.stringify(params.leaveStatus);
        recordData.activeState = isWithInactiveEmployees;
        recordData.entityId = entityId;
        recordData.columnHeaders = JSON.stringify(columnKeys);
      }

     
      setReportData(recordData);
      const { message, data } = await getLeaveEntitlementUsage(recordData);

      console.log(data);
      
      setLeaveEntitlements(data);
      setDisabled(true);
      setLoading(false);
    }
    const reset =() =>{
      form.resetFields();
      setEntityId(1);
      setReportType(null);
      setLeaveEntitlements([]);
    }
    let reportColumns =  [];
   
    if (reportType === "employee") {
      reportColumns = [
        {
          key: 'type',
          title: <FormattedMessage id="leaveUsageReport.LeaveType" defaultMessage="Leave Type" />,
          dataIndex: 'name',
          sorter: true,
          width:120
        },
        {
          key: 'leavePeriod',
          title:  <FormattedMessage id="leaveUsageReport.LeavePeriod" defaultMessage="Leave Period" />,
          dataIndex: 'leavePeriod',
          sorter: true,
          width:200,
          render:(record) => {
            let data =record.split('to');
            return (`${moment(data[0],'YYYY-MM-DD').format("DD-MM-YYYY")} to ${moment(data[1],'YYYY-MM-DD').format("DD-MM-YYYY")}`)
          }
        },
        {
          key: 'entitlementCount',
          title: 
            <>
             <Row justify='end'> <FormattedMessage id="leaveUsageReport.Entitlement" defaultMessage="Entitlement" /></Row>
             <Row align="bottom" justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.days" defaultMessage="(days)" /></Row> 
            </>,
          dataIndex: 'entitlementCount',
          width:90,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.entitlementCount}</span>
            )
          }
        },
        {
          key: 'pendingCount',
          title: 
            <>
              <Row justify='end' ><span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="leaveUsageReport.PendingApproval" defaultMessage="Pending Approval" /></span></Row>
              <Row justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.PendingApproval.days" defaultMessage="(days)" /> </Row>
            </>,
          dataIndex: 'pendingCount',
          width: 120,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.pendingCount}</span>
            )
          }
          },
        {
          key: 'usedCount',
          title: 
            <>
             <Row justify='end'> <FormattedMessage id="leaveUsageReport.Approved" defaultMessage="Approved" /> </Row>
             <Row justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.Approved.days" defaultMessage="(days)" /> </Row>
            </>,
          dataIndex: 'usedCount',
          width: 80,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.usedCount}</span>
            )
          }
        },
        {
          key: 'leaveBalance',
          title: 
          <>
            <Row justify='end'><span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="leaveUsageReport.Leave Balance" defaultMessage="Leave Balance" />  </span></Row>
            <Row justify='end' style={{color:'#909A99'}}><FormattedMessage id="leaveUsageReport.LeaveBalance.days" defaultMessage="(days)" /> </Row>
          </>,
          dataIndex: 'leaveBalance',
          width: 100,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.leaveBalance}</span>
            )
          }
        },
        // {
        //   key: 'action',
        //   title: 'Action',
        //   dataIndex: 'option',
        //   valueType: 'option',
        //   width: 80,
        //   render: (_, record) => [
        //     <Tooltip key="show-timeline" title="Show Timeline">
        //       <Button
        //         onClick={() => {
                  
        //         }}
        //         icon={<Image src={TimeLineIcon} preview={false}/>}
        //       >
                
        //       </Button>
        //     </Tooltip>

        //   ],
        // },
      ];
    } else if (reportType === "leaveType") {
      reportColumns = [
        {
          key: 'employeeNumber',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.EmployeeNumber" defaultMessage="Employee Number" />,
          dataIndex: 'employeeNumber',
          sorter: true,
          width: 140
        },
        {
          key: 'employeeName',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.EmployeeName" defaultMessage="Employee Name" />,
          dataIndex: 'employeeName',
          sorter: true,
        },
        {
          key: 'type',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Leave Type" />,
          dataIndex: 'name',
          sorter: true,
          width: 150
        },
        {
          key: 'leavePeriod',
          title: <FormattedMessage id="leaveUsageReport.LeaveType.LeavePeriod" defaultMessage="Leave Period" />,
          dataIndex: 'leavePeriod',
          sorter: true,
          width: 250,
          render:(record) => {
            let data =record.split('to');
            return (`${moment(data[0],'YYYY-MM-DD').format("DD-MM-YYYY")} to ${moment(data[1],'YYYY-MM-DD').format("DD-MM-YYYY")}`)
          }
        },
        {
          key: 'entitlementCount',
          title:
            <>
              <Row justify='end'> <FormattedMessage id="leaveUsageReport.LeaveType.Entitlement" defaultMessage="Entitlement" /></Row>
              <Row justify='end' style={{ color: '#909A99' }}><FormattedMessage id="leaveUsageReport.LeaveType.Entitlement.days" defaultMessage="(days)" /></Row>
            </>,
          dataIndex: 'entitlementCount',
          width: 110,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.entitlementCount}</span>
            )
          }
        },
        {
          key: 'pendingCount',
          title: 
            <>
              <Row justify='end'><span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="leaveUsageReport.LeaveType.PendingApproval" defaultMessage="Pending Approval" /></span></Row>
             <Row justify='end' style={{color:'#909A99'}}><FormattedMessage id="leaveUsageReport.LeaveType.PendingApproval.days" defaultMessage="(days)" /></Row>
            </>,
          dataIndex: 'pendingCount',
          width: 100,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.pendingCount}</span>
            )
          }
        },
        {
          key: 'usedCount',
          title: 
            <>
              <Row justify='end'><FormattedMessage id="leaveUsageReport.LeaveType.Approved" defaultMessage="Approved" /></Row>
              <Row justify='end' style={{color:'#909A99'}}><FormattedMessage id="leaveUsageReport.LeaveType.Approved.days" defaultMessage="(days)" /></Row>
            </>,
          dataIndex: 'usedCount',
          width: 100,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.usedCount}</span>
            )
          }
        },
        {
          key: 'leaveBalance',
          title: 
            <>              
              <Row justify='end'><span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="leaveUsageReport.LeaveType.LeaveBalance" defaultMessage="Leave Balance" /> </span></Row>
              <Row justify='end' style={{color:'#909A99'}}><FormattedMessage id="leaveUsageReport.LeaveType.LeaveBalance.days" defaultMessage="(days)" /> </Row>
            </>,
          dataIndex: 'leaveBalance',
          width: 110,
          render:(_,record) =>{
            return (
              <span style={{ float:'right',paddingRight:'15px' }}>{record.leaveBalance}</span>
            )
          }
        },
        // {
        //   key: 'action',
        //   title: 'Action',
        //   dataIndex: 'option',
        //   valueType: 'option',
        //   width: 80,
        //   render: (_, record) => [
        //     <Tooltip key="show-timeline" title="Show Timeline">
        //       <Button
        //         onClick={() => {
                  
        //         }}
        //         icon={<Image src={TimeLineIcon} preview={false}/>}
        //       >
                
        //       </Button>
        //     </Tooltip>


        //   ],
        // },
      ];
    } else if (reportType == 'employeeLeaveRequestReport') {
      reportColumns = [
        {
          key: 'employeeNumber',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.EmployeeNumber" defaultMessage="Employee Number" />,
          dataIndex: 'employeeNumber',
          width: 140
        },
        {
          key: 'employeeName',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.EmployeeName" defaultMessage="Employee Name" />,
          dataIndex: 'employeeName',
        },
        {
          key: 'leaveTypeName',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Leave Type" />,
          dataIndex: 'leaveTypeName',
          width: 150,
          hideInTable: requestType == 'leave' ? false : true
        },
        {
          key: 'fromDate',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage={ requestType == 'leave' ? "Start Date" : "Date"} />,
          dataIndex: 'fromDate',
          sorter: true,
          width: 150,
          render: (_, record) => {
            return {
              children: (
                <Space>
                  {moment(record.fromDate, 'YYYY-MM-DD').isValid() 
                    ? moment(record.fromDate).format('DD-MM-YYYY')
                    : '-'}
                </Space>
              ),
            };
          },
        },
        {
          key: 'toDate',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="End Date" />,
          dataIndex: 'toDate',
          sorter: true,
          hideInTable: requestType == 'leave' ? false : true,
          width: 150,
          render: (_, record) => {
            return {
              children: (
                <Space>
                  {moment(record.toDate, 'YYYY-MM-DD').isValid() 
                    ? moment(record.toDate).format('DD-MM-YYYY')
                    : '-'}
                </Space>
              ),
            };
          },
        },
        {
          key: 'numberOfLeaveDates',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Leave Count" />,
          dataIndex: 'numberOfLeaveDates',
          hideInTable: requestType == 'leave' ? false : true,
          width: 150
        },
        {
          key: 'hours',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Hours" />,
          dataIndex: 'hours',
          hideInTable: requestType == 'leave' ? true : false,
          width: 150
        },
        {
          key: 'reason',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Reason" />,
          dataIndex: 'reason',
          width: 150
        },
        {
          key: 'StateLabel',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Request Status" />,
          dataIndex: 'StateLabel',
          width: 150
        },
        {
          key: 'levelApproveDetails',
          title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.LeaveType" defaultMessage="Approver Name" />,
          dataIndex: 'levelApproveDetails',
          width: 450
        },
        
        
      ];

    }
    const columns: ProColumns<any>[] = reportColumns;

    const processdColumns = (leaveTypesDataset:any) => {
      let processedCols:any = [{
        key: 'employeeNumber',
        title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.EmployeeNumber" defaultMessage="Employee Number" />,
        dataIndex: 'employeeNumber',
        sorter: true,
        fixed: 'left',
        width: 200
      },
      {
        key: 'employeeName',
        title: <FormattedMessage id="leaveUsageReport.LeaveTypeReport.EmployeeName" defaultMessage="Employee Name" />,
        dataIndex: 'employeeName',
        sorter: true,
        fixed: 'left',
        width: 400
      }];

      leaveTypesDataset.map((leaveTypeId:any) => {
        const index = leaveTypes.findIndex((item) => leaveTypeId == item.id);
        let tempCol = {
          title: leaveTypes[index]['name'],
          key: 'leaveType-'+leaveTypes[index]['id'],
          dataIndex: 'leaveType-'+leaveTypes[index]['id'],
          width: 'auto',
          children: [
            {
              title: <>
              <Row justify='end'> <FormattedMessage id="leaveUsageReport.Allocation" defaultMessage="Allocation" /></Row>
              <Row align="bottom" justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.days" defaultMessage="(days)" /></Row> 
             </>,
              dataIndex: 'street',
              key: 'leaveType-'+leaveTypes[index]['id']+'allocation',
              width: 10,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                     {record['leaveTypeDetails']['leaveType-'+leaveTypes[index]['id']]['allocated']}
                    </Space>
                  ),
                };
              }
            },
            {
              title: <>
              <Row justify='end'> <FormattedMessage id="leaveUsageReport.Approved" defaultMessage="Approved" /></Row>
              <Row align="bottom" justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.days" defaultMessage="(days)" /></Row> 
             </>,
              dataIndex: '',
              key: 'leaveType-'+leaveTypes[index]['id']+'approved',
              width: 10,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                     {record['leaveTypeDetails']['leaveType-'+leaveTypes[index]['id']]['approved']}
                    </Space>
                  ),
                };
              }
            },
            {
              title: <>
              <Row justify='end'> <FormattedMessage id="leaveUsageReport.Pending" defaultMessage="Pending" /></Row>
              <Row align="bottom" justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.days" defaultMessage="(days)" /></Row> 
             </>,
              dataIndex: '',
              key: 'leaveType-'+leaveTypes[index]['id']+'pending',
              width: 10,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                     {record['leaveTypeDetails']['leaveType-'+leaveTypes[index]['id']]['pending']}
                    </Space>
                  ),
                };
              }
            },
            {
              title: <>
              <Row justify='end'> <FormattedMessage id="leaveUsageReport.Balance" defaultMessage="Balance" /></Row>
              <Row align="bottom" justify='end' style={{color:'#909A99'}}> <FormattedMessage id="leaveUsageReport.days" defaultMessage="(days)" /></Row> 
             </>,
              dataIndex: '',
              key: 'leaveType-'+leaveTypes[index]['id']+'balance',
              width: 10,
              render: (_, record) => {
                return {
                  children: (
                    <Space>
                     {record['leaveTypeDetails']['leaveType-'+leaveTypes[index]['id']]['balance']}
                    </Space>
                  ),
                };
              }
            },
          ]
          
        }
        processedCols.push(tempCol);
      });
      setEntitlementTableColumns(processedCols);
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

    return (
      <Access
        accessible={hasPermitted('leave-entitlement-report-access')}
        fallback={<PermissionDeniedPage />}
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
              <Form form={form} onFinish={onFinish} autoComplete="off" layout="vertical">
                <Row>
                  <Col
                    style={{
                      height: 35,
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
                          setLeaveEntitlements([]);
                          form.setFieldsValue({
                            'leaveType' : value == 'leaveEntitlement' ? [] : null,
                            'leavePeriod': 'current',
                          });
                          setEntityId(1);
                          if (value === 'leaveSummaryReport') {
                            getEmployeesByEntityId(1);
                            let cols = [
                              {
                                name: 'Employee Number',
                                isShowColumn: true,
                                mappedDataIndex: 'employeeNumber',
                              },
                              {
                                name: 'Employee Name',
                                isShowColumn: true,
                                mappedDataIndex: 'employeeName',
                              }
                            ];


                            for (let property in hierarchyConfig) {
                              let tempCol = {
                                name: hierarchyConfig[property],
                                isShowColumn: true,
                                mappedDataIndex: property,
                              }

                              cols.push(tempCol);
                           
                            }


                            let colSet2 = [
                              { name: 'Reporting Person', isShowColumn: true, mappedDataIndex: 'reportsTo' },
                              {
                                name: 'Leave Type',
                                isShowColumn: true,
                                mappedDataIndex: 'leaveTypeName',
                              },
                              { name: 'Status', isShowColumn: true, mappedDataIndex: 'StateLabel' },
                              { name: 'Start Date', isShowColumn: true, mappedDataIndex: 'fromDate' },
                              { name: 'End Date', isShowColumn: true, mappedDataIndex: 'toDate' },
                              {
                                name: 'Num Of Days',
                                isShowColumn: true,
                                mappedDataIndex: 'numberOfLeaveDates',
                              },
                              {
                                name: 'Reason',
                                isShowColumn: true,
                                mappedDataIndex: 'reason',
                              }
                            ];

                            //push col set 2 to main array
                            for (let col2Property in colSet2) { 
                              cols.push(colSet2[col2Property]);
                            }



                            setColumnKeys(cols);




                          }
                          setReportType(value);
                        }}
                        style={{
                          borderRadius: 6,
                          width: '100%',
                        }}
                        allowClear={true}
                      >
                        <Option value="employee">Employee</Option>
                        <Option value="leaveType">Leave Type</Option>
                        <Option value="leaveEntitlement">Leave Entitlement Report</Option>
                        <Option value="employeeLeaveRequestReport">Employee Leave Request Report</Option>
                        <Option value="leaveSummaryReport">Leave Summary Report</Option>
                      </Select>
                    </Form.Item>
                  </Col>
                  {reportType === 'employee' && (
                    <>
                      <Col
                        style={{
                          width: 350,
                          height: 35,
                          paddingLeft: 20,
                          textAlign: 'left',
                          marginTop: 35,
                        }}
                        span={4}
                      >
                        <Text
                          style={{
                            marginRight: 30,
                            verticalAlign: 'bottom',
                          }}
                        >
                          {intl.formatMessage({
                            id: 'includeInactive',
                            defaultMessage: 'Enable Inactive Employees',
                          })}
                        </Text>
                        <Switch
                          onChange={(checked: boolean, event: Event) => {
                            setIsEnableInactiveEmployees(checked);
                            form.setFieldsValue({
                              'employee' : null
                            })
                          }}
                          checkedChildren="Yes"
                          unCheckedChildren="No"
                        />
                      </Col>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="employee"
                          label={intl.formatMessage({
                            id: 'employeeName',
                            defaultMessage: 'Employee Name',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.employeeName',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          {
                            isEnableInactiveEmployees ? 
                            <Select
                              showSearch
                              style={{
                                width: '100%',
                              }}
                              placeholder={intl.formatMessage({
                                id: 'selectEmployee',
                                defaultMessage: 'Select Employee',
                              })}
                              optionFilterProp="children"
                              allowClear={true}
                            >
                              {employees.map((employee) => {
                                return (
                                  <Option key={employee.id} value={employee.id}>
                                    {`${employee.employeeNumber} | ${employee.employeeName}`}
                                  </Option>
                                );
                              })}
                            </Select> : <Select
                              showSearch
                              style={{
                                width: '100%',
                              }}
                              placeholder={intl.formatMessage({
                                id: 'selectEmployee',
                                defaultMessage: 'Select Employee',
                              })}
                              optionFilterProp="children"
                              allowClear={true}
                            >
                              {employees.map((employee) => {
                                return (
                                  employee.isActive ?
                                  <Option key={employee.id} value={employee.id}>
                                    {`${employee.employeeNumber} | ${employee.employeeName}`}
                                  </Option> : <></>
                                );
                              })}
                            </Select>
                          }
                        </Form.Item>
                      </Col>
                      <Col
                        span={3}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="leavePeriod"
                          label={intl.formatMessage({
                            id: 'leavePeriod',
                            defaultMessage: 'Leave Period',
                          })}
                        >
                          <Select
                            defaultValue={leavePeriod}
                            style={{
                              width: '100%',
                            }}
                          >
                            <Option value="current">Current</Option>
                            <Option value="future">Future</Option>
                            <Option value="past">Past</Option>
                          </Select>
                        </Form.Item>
                      </Col>
                    </>
                  )}
                  {reportType === 'employeeLeaveRequestReport' && (
                    <>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="requestType"
                          label={intl.formatMessage({
                            id: 'requestType',
                            defaultMessage: 'Request Type',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.requestType',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          <Select
                            style={{
                              width: '100%',
                            }}
                            placeholder={'Select Request Type'}
                            onChange={(val) => {
                              setRequestType(val);
                              setLeaveEntitlements([]);
                            }}
                          >
                            <Option value="leave">Leave</Option>
                            {
                              isShowShortLeaveTab ? 
                              <Option value="short-leave">Short Leave</Option> : <></>
                            }
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col
                        style={{
                          width: 350,
                          height: 35,
                          paddingLeft: 20,
                          textAlign: 'left',
                          marginTop: 35,
                        }}
                        span={4}
                      >
                        <Text
                          style={{
                            marginRight: 30,
                            verticalAlign: 'bottom',
                          }}
                        >
                          {intl.formatMessage({
                            id: 'includeInactive',
                            defaultMessage: 'Enable Inactive Employees',
                          })}
                        </Text>
                        <Switch
                          onChange={(checked: boolean, event: Event) => {
                            setIsEnableInactiveEmployees(checked);
                            form.setFieldsValue({
                              'employee' : null
                            })
                          }}
                          checkedChildren="Yes"
                          unCheckedChildren="No"
                        />
                      </Col>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="employee"
                          label={intl.formatMessage({
                            id: 'employeeName',
                            defaultMessage: 'Employee Name',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.employeeName',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          {/* <Select
                            showSearch
                            style={{
                              width: '100%',
                            }}
                            placeholder={intl.formatMessage({
                              id: 'selectEmployee',
                              defaultMessage: 'Select Employee',
                            })}
                            optionFilterProp="children"
                            allowClear={true}
                          >
                            {employees.map((employee) => {
                              return (
                                <Option key={employee.id} value={employee.id}>
                                  {`${employee.employeeName} - ${employee.employeeNumber}`}
                                </Option>
                              );
                            })}
                          </Select> */}
                          {
                            isEnableInactiveEmployees ? 
                            <Select
                              showSearch
                              style={{
                                width: '100%',
                              }}
                              placeholder={intl.formatMessage({
                                id: 'selectEmployee',
                                defaultMessage: 'Select Employee',
                              })}
                              optionFilterProp="children"
                              allowClear={true}
                            >
                              {employees.map((employee) => {
                                return (
                                  <Option key={employee.id} value={employee.id}>
                                    {`${employee.employeeNumber} | ${employee.employeeName}`}
                                  </Option>
                                );
                              })}
                            </Select> : <Select
                              showSearch
                              style={{
                                width: '100%',
                              }}
                              placeholder={intl.formatMessage({
                                id: 'selectEmployee',
                                defaultMessage: 'Select Employee',
                              })}
                              optionFilterProp="children"
                              allowClear={true}
                            >
                              {employees.map((employee) => {
                                return (
                                  employee.isActive ?
                                  <Option key={employee.id} value={employee.id}>
                                    {`${employee.employeeNumber} | ${employee.employeeName}`}
                                  </Option> : <></>
                                );
                              })}
                            </Select>
                          }
                        </Form.Item>
                      </Col>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="requestStatus"
                          label={intl.formatMessage({
                            id: 'requestStatus',
                            defaultMessage: 'Request Status',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.requestStatus',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          <Select
                            style={{
                              width: '100%',
                            }}
                            placeholder={'Select Request Status'}
                          >
                            <Option value="1">Pending</Option>
                            <Option value="2">Approved</Option>
                            <Option value="3">Rejected</Option>
                            <Option value="4">Cancelled</Option>
                          </Select>
                        </Form.Item>
                      </Col>
                    </>
                  )}
                  {reportType === 'leaveType' && (
                    <>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="leaveType"
                          label={intl.formatMessage({
                            id: 'leaveType',
                            defaultMessage: 'Leave Type',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.leaveType',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          <Select
                            showSearch
                            style={{
                              width: '100%',
                            }}
                            placeholder={intl.formatMessage({
                              id: 'selectleaveType',
                              defaultMessage: 'Select Leave Type',
                            })}
                            optionFilterProp="children"
                            allowClear={true}
                          >
                            {leaveTypes.map((leaveType) => {
                              return (
                                <Option key={leaveType.id} value={leaveType.id}>
                                  {leaveType.name}
                                </Option>
                              );
                            })}
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="leavePeriod"
                          label={intl.formatMessage({
                            id: 'leavePeriod',
                            defaultMessage: 'Leave Period',
                          })}
                        >
                          <Select
                            defaultValue={leavePeriod}
                            style={{
                              width: '100%',
                            }}
                          >
                            <Option value="current">Current</Option>
                            <Option value="future">Future</Option>
                            <Option value="past">Past</Option>
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col
                        style={{
                          width: 350,
                          height: 35,
                          paddingLeft: 20,
                          textAlign: 'left',
                          marginTop: 35,
                        }}
                        span={6}
                      >
                        <Text
                          style={{
                            marginRight: 30,
                            verticalAlign: 'bottom',
                          }}
                        >
                          {intl.formatMessage({
                            id: 'includeInactive',
                            defaultMessage: 'Include Inactive Employees',
                          })}
                        </Text>
                        <Switch
                          onChange={(checked: boolean, event: Event) => {
                            setIsWithInactiveEmployees(checked);
                          }}
                          checkedChildren="Yes"
                          unCheckedChildren="No"
                        />
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

                  {reportType === 'leaveEntitlement' && (
                    <>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="leaveType"
                          label={intl.formatMessage({
                            id: 'leaveTypes',
                            defaultMessage: 'Leave Type',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveEntitlementReport.leaveType',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          <Select
                            showSearch
                            style={{
                              width: '100%',
                            }}
                            placeholder={intl.formatMessage({
                              id: 'selectleaveType',
                              defaultMessage: 'Select Leave Type',
                            })}
                            optionFilterProp="children"
                            onChange={(val) => {
                              setSelectedLeaveTypes(val);
                            }}
                            mode='multiple'
                            allowClear={true}
                          >
                            {leaveTypes.map((leaveType) => {
                              return (
                                <Option key={leaveType.id} value={leaveType.id}>
                                  {leaveType.name}
                                </Option>
                              );
                            })}
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="leavePeriod"
                          label={intl.formatMessage({
                            id: 'leavePeriod',
                            defaultMessage: 'Leave Period',
                          })}
                        >
                          <Select
                            defaultValue={leavePeriod}
                            style={{
                              width: '100%',
                            }}
                          >
                            <Option value="current">Current</Option>
                            <Option value="future">Future</Option>
                            <Option value="past">Past</Option>
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col
                        style={{
                          width: 350,
                          height: 35,
                          paddingLeft: 20,
                          textAlign: 'left',
                          marginTop: 35,
                        }}
                        span={6}
                      >
                        <Text
                          style={{
                            marginRight: 30,
                            verticalAlign: 'bottom',
                          }}
                        >
                          {intl.formatMessage({
                            id: 'includeInactive',
                            defaultMessage: 'Include Inactive Employees',
                          })}
                        </Text>
                        <Switch
                          onChange={(checked: boolean, event: Event) => {
                            setIsWithInactiveEmployees(checked);
                          }}
                          checkedChildren="Yes"
                          unCheckedChildren="No"
                        />
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

                  {reportType === 'leaveSummaryReport' && (
                    <>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="dateRange"
                          label={intl.formatMessage({
                            id: 'dateRange',
                            defaultMessage: 'Date Range',
                          })}
                          rules={[
                            {
                              required: true,
                              message: intl.formatMessage({
                                id: 'leaveSummaryReport.dateRange',
                                defaultMessage: 'Required',
                              }),
                            },
                          ]}
                        >
                          <RangePicker format={'DD-MM-YYYY'} onChange={onChange} style={{width: '100%'}} />
                        </Form.Item>
                      </Col>
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                        }}
                      >
                        <Form.Item
                          name="leaveStatus"
                          label={intl.formatMessage({
                            id: 'leaveStatus',
                            defaultMessage: 'Leave Status',
                          })}
                        >
                          <Select
                            mode={'multiple'}
                            placeholder={'Select Leave Status'}
                            style={{
                              width: '100%',
                            }}
                          >
                            <Option value={1}>Pending</Option>
                            <Option value={2}>Approved</Option>
                            <Option value={3}>Rejected</Option>
                            <Option value={4}>Cancelled</Option>
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col
                        style={{
                          width: 350,
                          height: 35,
                          paddingLeft: 20,
                          textAlign: 'left',
                          marginTop: 35,
                        }}
                        span={6}
                      >
                        <Text
                          style={{
                            marginRight: 30,
                            verticalAlign: 'bottom',
                          }}
                        >
                          {intl.formatMessage({
                            id: 'includeInactive',
                            defaultMessage: 'Include Inactive Employees',
                          })}
                        </Text>
                        <Switch
                          onChange={(checked: boolean, event: Event) => {
                            setIsWithInactiveEmployees(checked);
                          }}
                          checkedChildren="Yes"
                          unCheckedChildren="No"
                        />
                      </Col>
                      <OrgSelector
                        value={entityId}
                        setValue={(value: number) => setEntityId(value)}
                        span={6}
                        colStyle={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                          paddingTop: 30,
                          paddingBottom: 65,
                        }}
                      />
                      <Col
                        span={6}
                        style={{
                          height: 35,
                          width: 250,
                          paddingLeft: 20,
                          marginTop: 30
                        }}
                      >
                        <Form.Item
                          name="employee"
                          label={intl.formatMessage({
                            id: 'employeeName',
                            defaultMessage: 'Employee Name',
                          })}
                        >
                          <Select
                            showSearch
                            style={{
                              width: '100%',
                            }}
                            mode={'multiple'}
                            maxTagCount = {1}
                            placeholder={intl.formatMessage({
                              id: 'selectEmployee',
                              defaultMessage: 'Select Employee',
                            })}
                            optionFilterProp="children"
                            allowClear={true}
                          >
                            {employees.map((employee) => {
                              return (
                                <Option key={employee.id} value={employee.id}>
                                  {`${employee.employeeName} - ${employee.employeeNumber}`}
                                </Option>
                              );
                            })}
                          </Select>
                        </Form.Item>
                      </Col>
                    </>
                    
                  )}
                  {reportType === 'employee' && (
                    <>
                      <Col
                        span={4}
                        style={{
                          height: 35,
                          paddingLeft: 20,
                          paddingTop: 30,
                          paddingBottom: 35,
                        }}
                      >
                        <Space>
                          <Button onClick={reset} type="primary">
                            <FormattedMessage id="GENERATEREPORT" defaultMessage="Reset" />
                          </Button>
                          <Button htmlType="submit" style={{ float: 'right' }} type="primary">
                            <FormattedMessage
                              id="GENERATEREPORT"
                              defaultMessage="Generate Report"
                            />
                          </Button>
                        </Space>
                      </Col>
                    </>
                  )}
                  {reportType === 'leaveType' && (
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
                          <Button onClick={reset} type="primary">
                            <FormattedMessage id="GENERATEREPORT" defaultMessage="Reset" />
                          </Button>
                          <Button htmlType="submit" type="primary">
                            <FormattedMessage
                              id="GENERATEREPORT"
                              defaultMessage="Generate Report"
                            />
                          </Button>
                        </Space>
                      </Col>
                    </>
                  )}
                  {reportType === 'leaveSummaryReport' && (
                    <>
                      <Col
                        span={1}
                        style={{
                          //   height: 35,
                          width: 250,
                          paddingLeft: 20,
                          marginTop: 30
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
                            <Button style={{ marginTop: 30, borderRadius: 6 }} type="default">
                              <SettingOutlined
                                style={{ fontSize: 18, marginTop: 2 }}
                              ></SettingOutlined>
                            </Button>
                          </Tooltip>
                        </Popover>
                      </Col>
                      <Col
                        span={4}
                        style={{
                          height: 35,
                          paddingLeft: 20,
                          paddingTop: 60,
                          paddingBottom: 35,
                        }}
                      >
                        <Space>
                          <Button onClick={reset} type="primary">
                            <FormattedMessage id="GENERATEREPORT" defaultMessage="Reset" />
                          </Button>
                          <Button htmlType="submit" type="primary">
                            <FormattedMessage
                              id="GENERATEREPORT"
                              defaultMessage="Generate Report"
                            />
                          </Button>
                        </Space>
                      </Col>
                    </>
                  )}
                  {reportType === 'leaveEntitlement' && (
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
                          <Button onClick={reset} type="primary">
                            <FormattedMessage id="GENERATEREPORT" defaultMessage="Reset" />
                          </Button>
                          <Button htmlType="submit" type="primary">
                            <FormattedMessage
                              id="GENERATEREPORT"
                              defaultMessage="Generate Report"
                            />
                          </Button>
                        </Space>
                      </Col>
                    </>
                  )}
                  {reportType === 'employeeLeaveRequestReport' && (
                    <>
                      <Col
                        span={4}
                        style={{
                          height: 35,
                          paddingLeft: 20,
                          paddingTop: 28,
                          paddingBottom: 35,
                        }}
                      >
                        <Space>
                          <Button onClick={reset} type="primary">
                            <FormattedMessage id="GENERATEREPORT" defaultMessage="Reset" />
                          </Button>
                          <Button htmlType="submit" type="primary">
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
              {leaveEntitlements.length > 0 ? (
                <Card>
                  <Row>
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
                          setLoading(true);
                          const excelData = reportData;
                          excelData.dataType = '';
                          if (reportType === 'leaveSummaryReport') {
                            excelData.columnHeaders = JSON.stringify(columnKeys);
                          }
                          const { data } = await getLeaveEntitlementUsage(excelData);
                          if (data.data) {
                            let reportName = 'LeaveEntitlementReport.xlsx';
                            switch (reportType) {
                              case 'employee':
                                reportName = 'Leave Report - Employee.xlsx';
                                break;
                              case 'leaveType':
                                reportName = 'Leave Report -Leave Type.xlsx';
                                break;
                              case 'leaveEntitlement':
                                reportName = 'Leave Entitlement Report.xlsx';
                                break;
                              case 'employeeLeaveRequestReport':
                                if (requestType == 'leave') {
                                  reportName = 'Employee Leave Request Report - Leave.xlsx';
                                } else {
                                  reportName = 'Employee Leave Request Report - Short Leave.xlsx';

                                }
                                break;
                              case 'leaveSummaryReport':
                                reportName = 'Leave Summary Report.xlsx';
                                break;

                              default:
                                break;
                            }

                            downloadBase64File(
                              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                              data.data,
                              reportName,
                            );
                          }
                          setLoading(false);
                        }}
                      >
                        <span style={{ verticalAlign: 'top', paddingLeft: '4px' }}> Export</span>
                      </Button>
                    </Col>
                  </Row>
                  <br />
                    {reportType === 'leaveType' || reportType === 'employee' || reportType === 'employeeLeaveRequestReport' ?
                    <ProTable<any>
                      actionRef={tableRef}
                      rowKey="id"
                      search={false}
                      options={false}
                      request={async (params = { pageNo: 1, pageCount: 10000 }, sort) => {
                       
                        const sortValue = sort?.name
                          ? { name: 'type', order: sort?.name === 'ascend' ? 'ASC' : 'DESC' }
                          : sort?.employeeName
                          ? { name: 'employeeName', order: sort?.employeeName === 'ascend' ? 'ASC' : 'DESC' }
                          : sort?.fromDate
                          ? { name: 'fromDate', order: sort?.fromDate === 'ascend' ? 'ASC' : 'DESC' }
                          : sort?.toDate
                          ? { name: 'toDate', order: sort?.toDate === 'ascend' ? 'ASC' : 'DESC' }
                          : {
                              name: 'leavePeriod',
                              order: sort?.leavePeriod === 'ascend' ? 'ASC' : 'DESC',
                            };

                        const tableParams = reportData;
                        tableParams.sort = sortValue;
                        const { data } = await getLeaveEntitlementUsage(reportData);
                        setLeaveEntitlements(data);
                      }}
                      columns={columns}
                      dataSource={leaveEntitlements}
                      className="custom-table"
                      pagination={{ pageSize: 100, defaultPageSize: 100, hideOnSinglePage: false }}
                    />  : reportType == 'leaveEntitlement' ? 
                    <ProTable<any>
                      actionRef={tableRef}
                      rowKey="id"
                      scroll={ selectedLeaveTypes.length > 3 ? { x: '130vw'} : {}}
                      search={false}
                      bordered
                      options={false}
                      request={async (params = { pageNo: 1, pageCount: 10000 }, sort) => {
                        const sortValue = sort?.employeeNumber
                          ? { name: 'employeeNumber', order: sort?.employeeNumber === 'ascend' ? 'ASC' : 'DESC' }
                          : sort?.employeeName
                          ? { name: 'employeeName', order: sort?.employeeName === 'ascend' ? 'ASC' : 'DESC' }
                          : {
                              name: 'leavePeriod',
                              order: sort?.leavePeriod === 'ascend' ? 'ASC' : 'DESC',
                            };

                        const tableParams = reportData;
                        tableParams.sort = sortValue;
                        const { data } = await getLeaveEntitlementUsage(reportData);
                        setLeaveEntitlements(data);
                      }}
                      columns={entitlementTableColumns}
                      dataSource={leaveEntitlements}
                      className="custom-table"
                      pagination={{ pageSize: 100, defaultPageSize: 100, hideOnSinglePage: false }}
                    /> : reportType == 'leaveSummaryReport' ? 
                    <ProTable<any>
                      actionRef={tableRef}
                      rowKey="id"
                      search={false}
                      bordered
                      options={false}
                      request={async (params = { pageNo: 1, pageCount: 10000 }, sort) => {
                        const sortValue = sort?.employeeNumber
                          ? { name: 'employeeNumber', order: sort?.employeeNumber === 'ascend' ? 'ASC' : 'DESC' }
                          : sort?.employeeName
                          ? { name: 'employeeName', order: sort?.employeeName === 'ascend' ? 'ASC' : 'DESC' }
                          : {
                              name: 'leavePeriod',
                              order: sort?.leavePeriod === 'ascend' ? 'ASC' : 'DESC',
                            };

                        const tableParams = reportData;
                        tableParams.sort = sortValue;
                        const { data } = await getLeaveEntitlementUsage(reportData);
                        setLeaveEntitlements(data);
                      }}
                      columns={leaveSummaryTableColumns}
                      dataSource={leaveEntitlements}
                      className="custom-table"
                      pagination={{ pageSize: 100, defaultPageSize: 100, hideOnSinglePage: false }}
                    /> : <></> }
                </Card>
              ) : reportType ? <Card>
                  <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                </Card> : null
              }
            </Spin>
          </Space>
        </PageContainer>
      </Access>
    );
};

export default LeaveUsageReportForEmployee;