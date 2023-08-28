/* eslint-disable guard-for-in */
/* eslint-disable no-restricted-syntax */
import React, { useState, Key, useRef, useEffect } from 'react';

import type { ProColumns, ColumnsState } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import {
  Button,
  message,
  Modal,
  Select,
  Typography,
  Table,
  Alert,
  Space,
  Popconfirm,
  Spin,
  Tag,
  Row,
  Col,
  Tabs,
  Card,
} from 'antd';
import { EyeOutlined } from '@ant-design/icons';
import { ModalForm } from '@ant-design/pro-form';
const { TabPane } = Tabs;
// change this accordingly

import {
  queryWorkflowInstances,
  deleteInstance,
  updateInstance,
  queryWorkflowFilterOptions,
  getMyRequests,
  getLeaveCoveringRequests,
  queryActionData,
  accessibleWorkflowActions,
} from '@/services/workflowServices';

import { updateLeaveCoveringState } from '@/services/leave';
import {
  getMyprofile,
  getEmployee,
  getMultiReocrdData,
  getRelationalDataSet,
  getDataDiffForProfileUpdate,
} from '@/services/employee';
import { getEmployee as getTeamMember } from '@/services/myTeams';
import { getAttachementList } from '@/services/leave';

import { useIntl } from 'umi';
import { PageContainer } from '@ant-design/pro-layout';
import _ from 'lodash';
import moment from 'moment-timezone';
import { diff } from 'json-diff';
import styles from './index.less';
import BusinessCard from '@/components/BusinessCard';
import { getModel, Models } from '@/services/model';
import CoveringRequest from './coveringRequest';
import PermissionDeniedPage from '@/pages/403';
import request, { APIResponse } from '@/utils/request';

moment.locale('en');

export type WorkflowProps = {
  pageType: pageEnum;
};
type pageEnum = 'allRequests' | 'myRequests';

// change as the page

type TableListItem = {
  id: number;
  workflowId: number;
  actionId: string;
  actionName: string;
  priorState: number;
  priorStateName: string;
  details: string;
  createdAt: string;
  contextName: string;
};

// change the page type

interface TableType {
  reload: (resetPageIndex?: boolean) => void;
  reloadAndRest: () => void;
  reset: () => void;
  clearSelected?: () => void;
  startEditable: (rowKey: Key) => boolean;
  cancelEditable: (rowKey: Key) => boolean;
}

const WorkflowInstance: React.FC<WorkflowProps> = (props) => {
  const [columnsStateMap, setColumnsStateMap] = useState<Record<string, ColumnsState>>({
    name: {
      show: false,
    },
    order: 2,
  });

  const [addModalVisible, handleAddModalVisible] = useState<boolean>(false);
  const [deleteWorkflowId, setDeleteId] = useState<number>(0);

  const tableRefAll = useRef<TableType>();
  const tableRefLeave = useRef<TableType>();
  const tableRefShortLeave = useRef<TableType>();
  const tableRefTime = useRef<TableType>();
  const tableRefProfile = useRef<TableType>();
  const intl = useIntl();
  const { confirm } = Modal;

  const [selectedRow, setSelectedRow] = useState({});
  const [dataChanges, setDataChanges] = useState([]);
  const [loggedInUser, setLogedInUser] = useState(0);
  const [employeeData, setEmployeeData] = useState();
  const [employeeId, setEmployeeId] = useState('');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isLoadAllRequest, setIsLoadAllRequest] = useState<boolean>(false);
  const [actions, setActions] = useState<any>([]);
  const [model, setModel] = useState<any>();
  const [relatedModel, setRelatedModel] = useState<any>();
  const [tableState, setTableState] = useState<any>({});
  const [relationData, setRelationData] = useState<any>({});
  const [updatedTimeOld, setUpdatedTimeOld] = useState();
  const [relatedEmployee, setRelatedEmployee] = useState();
  const [updatedTimeNew, setUpdatedTimeNew] = useState();
  const [isChangesAreNew, setisChangesAreNew] = useState(false);
  const [showThisIsFailureState, setShowThisIsFailureState] = useState(false);
  const [leaveDataSet, setleaveDataSet] = useState({});
  const [coveringRequestData, setCoveringRequestData] = useState({});
  const [timeChangeDataSet, setTimeChangeDataSet] = useState({});
  const [coveringPersonComment, setCoveringPersonComment] = useState<string | null>(null);
  const [employeeName, setEmployeeName] = useState<string | null>(null);
  const [relateScope, setRelateScope] = useState<string | null>(null);
  const [contextType, setContextType] = useState<string | null>('all');
  const [hasPermissionForAllRequests, setHasPermissionForAllRequests] = useState<boolean>(true);
  const [hasPermissionForProfileUpdateRequests, setHasPermissionForProfileUpdateRequests] =
    useState<boolean>(false);
  const [hasPermissionForLeaveRequests, setHasPermissionForLeaveRequests] =
    useState<boolean>(false);
  const [hasPermissionForShortLeaveRequests, setHasPermissionForShortLeaveRequests] =
    useState<boolean>(false);
  const [hasPermissionForTimeChangeRequests, setHasPermissionForTimeChangeRequests] =
    useState<boolean>(false);
  const [leaveCoveringRequestsData, setLeaveCoveringRequestsData] = useState([]);

  useEffect(() => {
    if (!model) {
      getModel(Models.Employee, 'edit').then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

  const getLastUpdatedTime = (field) => {
    if (field === 'currentVal') {
      return (
        <>
          <div>Current Data</div>
          <div className={styles.secondryInfo}>
            Last updated: {updatedTimeOld ? updatedTimeOld : '  --'}
          </div>
        </>
      );
    }
    return (
      <>
        <div>New Data</div>
        <div className={styles.secondryInfo}>
          Submitted on: {updatedTimeNew ? updatedTimeNew : '--'}
        </div>
      </>
    );
  };

  const getFilter = async () => {
    const data = await queryWorkflowFilterOptions();

    const o = [
      {
        label: 'All',
        value: 'All',
      },
    ];
    await data.data.forEach((element) => {
      o.push({
        label: element,
        value: element,
      });
    });
    return o;
  };

  const getEmployeeData = (event, relatedModel, relations) => {
    const requestScope = props.pageType === 'allRequests' ? 'MANAGER' : 'EMPLOYEE';
    const employeeId = event.leaveData.employeeId;

    let empName = event.firstName + ' ' + event.lastName;
    setEmployeeName(empName);
    setleaveDataSet(event.leaveData);
    setCoveringRequestData(event);
  };

  const showModal = async (event) => {
    setSelectedRow(event);
    setRelatedEmployee(event.leaveData.employeeId);
    setDataChanges([]);
    setIsLoading(true);
    setisChangesAreNew(false);
    setShowThisIsFailureState(false);
    let details = event.leaveData;

    details['modelName'] = details['modelName'] ? details['modelName'] : 'employee';

    const response = await getModel(details['modelName'], 'edit');
    setRelatedModel(response.data);
    var relations: object = {};

    getEmployeeData(event, response.data, relations);
    await handleAddModalVisible(true);
  };

  const updateCoveringRequest = (action, coveringRequestId) => {
    updateLeaveCoveringState({
      action,
      coveringRequestId,
      coveringPersonComment,
    })
      .then((res) => {
        switch (contextType) {
          case 'all':
            setHasPermissionForAllRequests(true);
            tableRefAll.current?.reload();
            break;
          case '1':
            setHasPermissionForProfileUpdateRequests(true);
            tableRefProfile.current?.reload();
            break;
          case '2':
            setHasPermissionForLeaveRequests(true);
            tableRefLeave.current?.reload();
            break;
          case '3':
            setHasPermissionForTimeChangeRequests(true);
            tableRefTime.current?.reload();
            break;
          case '4':
            setHasPermissionForShortLeaveRequests(true);
            tableRefShortLeave.current?.reload();
            break;

          default:
            break;
        }

        message.success(res.message);
      })
      .catch((error: APIResponse) => {
        message.error(error.message);
      });

    handleAddModalVisible(false);
  };

  useEffect(() => {
    const loggedinUser = JSON.parse(localStorage.getItem('user_session'));
    setLogedInUser(_.get(loggedinUser, 'userId', false));
  }, []);

  const requestAllData = async (params, sorter) => {
    let res = await getLeaveCoveringRequests({ ...params, sorter });
    setIsLoadAllRequest(true);
    setLeaveCoveringRequestsData(res.data);

    return res;
  };

  const getNameByDetails = (details: any) => {
    // const data = JSON.parse(details);
    return _.get(details, 'firstName', null);
  };

  const columns: ProColumns<TableListItem>[] = [
    {
      dataIndex: 'createdAt',
      valueType: 'index',
      width: '100px',
      filters: false,
      onFilter: false,

      render: (entity, dom) => [
        <div className={styles.tableDate}>
          <div className={styles.dateTime}>
            <div className={styles.headerDiv}></div>
            <div className={styles.date}>{moment(dom.createdAt).format('DD')}</div>
            <div className={styles.month}>{moment(dom.createdAt).format('MMM YYYY')}</div>
          </div>
        </div>,
      ],
    },
    {
      dataIndex: 'details',
      valueType: 'index',
      width: '600px',
      filters: false,
      render: (entity, dom) => [
        <>
          <div className={styles.profileDetails}>
            <>
              {' '}
              <Row style={{ marginLeft: 10 }}>
                <Col>
                  <Row>
                    <Col style={{ color: 'grey' }}>{'Leave'}&nbsp;Request&nbsp;By&nbsp;</Col>
                    <Col className={styles.profileName} onClick={() => {}}>
                      <BusinessCard employeeData={dom} text={getNameByDetails(dom)} />
                    </Col>
                  </Row>
                  <Row>
                    <Col style={{ fontSize: 12, marginBottom: 2 }}>{dom.displayHeading1}</Col>
                  </Row>
                  <Row>
                    <Col style={{ fontSize: 12 }}>{dom.displayHeading2}</Col>
                  </Row>
                </Col>
              </Row>
            </>
          </div>
        </>,
      ],
    },
    {
      dataIndex: 'priorStateName',
      valueType: 'select',
      initialValue: 'All',
      request: async () => getFilter(),
      render: (_, record) => (
        <Space>
          <Tag
            style={{
              borderRadius: 20,
              fontSize: 17,
              paddingRight: 20,
              paddingLeft: 20,
              paddingTop: 4,
              paddingBottom: 4,
              border: 0,
            }}
            color={record.stateTagColor}
          >
            {record.stateLabel}
          </Tag>
        </Space>
      ),
    },
    {
      dataIndex: 'requestedOn',
      width: '350px',
      render: (_, record) => (
        <Space style={{ color: '#909A99', fontSize: 16 }}>
          {'Requested On ' + record.requestedOn}
        </Space>
      ),
    },
    {
      dataIndex: 'action',
      valueType: 'index',
      width: '150px',
      render: (entity, dom) => [
        <>
          <div style={{ display: 'flex' }}>
            {dom.state != 'PENDING' ? (
              <></>
            ) : (
              <>
                <Popconfirm
                  title="Are you sure you want decline this request?"
                  placement="top"
                  onConfirm={(e) => {
                    e.stopPropagation();
                    updateCoveringRequest('decline', dom.id);
                  }}
                  okText="Yes"
                  onCancel={(e) => {
                    e.stopPropagation();
                  }}
                  cancelText="No"
                >
                  <Button
                    onClick={(e) => {
                      e.stopPropagation();
                    }}
                    style={{ marginRight: 10, borderRadius: 6 }}
                    danger
                  >
                    Decline
                  </Button>
                </Popconfirm>
                <Popconfirm
                  title="Are you sure you want accept this request?"
                  placement="top"
                  onConfirm={(e) => {
                    e.stopPropagation();
                    updateCoveringRequest('accept', dom.id);
                  }}
                  okText="Yes"
                  onCancel={(e) => {
                    e.stopPropagation();
                  }}
                  cancelText="No"
                >
                  <Button
                    onClick={(e) => {
                      e.stopPropagation();
                    }}
                    type="primary"
                  >
                    Accept
                  </Button>
                </Popconfirm>
              </>
            )}
          </div>
        </>,
      ],
    },
  ];

  let tableSec: string = '';
  let tableSubSec: string = '';
  const modalColumns = [
    {
      title: <div style={{ marginBottom: 38 }}>Section</div>,
      dataIndex: 'fieldName',
      width: '100px',
      render: (fieldName) => {
        const structure = _.get(model, 'frontEndDefinition.structure', '');
        let section;
        const details = JSON.parse(selectedRow['details']);

        if (details['isMultiRecord'] == true) {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(details['tabName'])) {
                section = element.defaultLabel;
              }
            });
          });
        } else {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(fieldName)) {
                section = element.defaultLabel;
              }
            });
          });
        }

        if (tableSec !== section) {
          tableSec = section;
          return <>{section}</>;
        }
        tableSec = section;
        return <></>;
      },
    },
    {
      title: <div style={{ marginBottom: 38 }}>Sub Section</div>,
      dataIndex: 'fieldSubName',
      width: '180px',
      render: (fieldSubName) => {
        const structure = _.get(model, 'frontEndDefinition.structure', '');
        const details = JSON.parse(selectedRow['details']);
        let subSection;

        if (details['isMultiRecord'] == true) {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(details['tabName'])) {
                subSection = el.defaultLabel;
              }
            });
          });
        } else {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(fieldSubName)) {
                subSection = el.defaultLabel;
              }
            });
          });
        }
        if (tableSubSec !== subSection) {
          tableSubSec = subSection;
          return <>{subSection}</>;
        }
        tableSubSec = subSection;
        return <></>;
      },
    },
    {
      title: <div style={{ marginBottom: 38 }}>Field Name</div>,
      dataIndex: 'field',
      width: '150px',
    },
    {
      title: getLastUpdatedTime('currentVal'),
      dataIndex: 'currentVal',
    },
    {
      title: getLastUpdatedTime('newVal'),
      dataIndex: 'newVal',
    },
  ];
  return (
    <>
      <div>
        <PageContainer
          header={{
            ghost: true,
          }}
        >
          <Card
            title={
              <Row>
                <Col span={12}>
                  <Space style={{ float: 'left' }}> {intl.formatMessage({
                    id: 'leaveCoveringRequest',
                    defaultMessage: 'Leave Covering Requests',
                  })}</Space>
                </Col>
                <Col span={12}>
                  <Space style={{ float: 'right' }}>
                    <div>{'Filter'}</div>
                    <div>
                      <Select
                        onChange={async (value) => {
                          if (value) {
                            const sortValue = {};

                            const tableParams = {
                              current: tableState?.current,
                              pageSize: tableState?.pageSize,
                              stateName: value,
                            };
                            setTableState(tableParams);

                            await requestAllData(tableParams, sortValue);
                          }
                        }}
                        style={{ width: 200 }}
                        allowClear={true}
                        defaultValue="All"
                        options={[
                          {
                            value: 'All',
                            label: 'All',
                          },
                          {
                            value: 'PENDING',
                            label: 'Pending',
                          },
                          {
                            value: 'DECLINED',
                            label: 'Declined',
                          },
                          {
                            value: 'APPROVED',
                            label: 'Approved',
                          },
                          {
                            value: 'CANCELED',
                            label: 'Canceled',
                          },
                        ]}
                      ></Select>
                    </div>
                  </Space>
                </Col>
              </Row>
            }
          >
            <ProTable<TableListItem>
              columns={columns}
              className={'coveringList'}
              request={async (params = { current: 1, pageSize: 100 }, sort, filter) => {
                const sortValue = {};

                const tableParams = {
                  current: params?.current,
                  pageSize: params?.pageSize,
                  stateName: 'All',
                };
                setTableState(tableParams);

                await requestAllData(tableParams, sortValue);
                return leaveCoveringRequestsData;
              }}
              actionRef={tableRefAll}
              dataSource={leaveCoveringRequestsData}
              rowKey="id"
              key={'allTable'}
              columnsStateMap={columnsStateMap}
              onColumnsStateChange={(map) => setColumnsStateMap(map)}
              span={1}
              pagination={{
                showSizeChanger: true,
              }}
              onRow={(record, rowIndex) => {
                return {
                  onClick: async () => {
                    showModal(record);
                  },
                };
              }}
              dateFormatter="string"
              search={false}
              showHeader={false}
              options={{
                fullScreen: false,
                reload: false,
                setting: false,
                density: false,
                search: false,
              }}
            />
          </Card>
        </PageContainer>
      </div>

      <ModalForm
        width={750}
        title={
          <>
            <Row>
              <Col>
                <Space style={{ paddingTop: 4 }}>
                  {intl.formatMessage({
                    id: 'leaveCoveringRequest',
                    defaultMessage: 'Leave Covering Request',
                  })}
                </Space>
              </Col>
              <Col style={{ marginLeft: 20 }}>
                <Space>
                  <Tag
                    style={{
                      borderRadius: 20,
                      fontSize: 17,
                      paddingRight: 20,
                      paddingLeft: 20,
                      paddingTop: 4,
                      paddingBottom: 4,
                      border: 0,
                    }}
                    color={coveringRequestData.stateTagColor}
                  >
                    {coveringRequestData.stateLabel}
                  </Tag>
                </Space>
              </Col>
            </Row>
          </>
        }
        modalProps={{
          destroyOnClose: true,
        }}
        onFinish={async (values: any) => {}}
        visible={addModalVisible}
        onVisibleChange={handleAddModalVisible}
        initialValues={{
          useMode: 'chapter',
        }}
        submitter={{
          render: () => {
            return [
              <>
                {selectedRow.state == 'PENDING' ? (
                  <>
                    <Popconfirm
                      title= {intl.formatMessage({
                        id: 'declineConfirm',
                        defaultMessage: "Are you sure you want decline this request?",
                      })}
                      placement="top"
                      onConfirm={(e) => {
                        e.stopPropagation();
                        updateCoveringRequest('decline', selectedRow.id);
                      }}
                      okText="Yes"
                      onCancel={(e) => {
                        e.stopPropagation();
                      }}
                      cancelText="No"
                    >
                      <Button
                        onClick={(e) => {
                          e.stopPropagation();
                        }}
                        style={{ marginRight: 10, borderRadius: 6 }}
                        danger
                      >
                        Decline
                      </Button>
                    </Popconfirm>
                    <Popconfirm
                      title= {intl.formatMessage({
                        id: 'acceptConfirm',
                        defaultMessage: "Are you sure you want accept this request?",
                      })}
                      placement="top"
                      onConfirm={(e) => {
                        e.stopPropagation();
                        updateCoveringRequest('accept', selectedRow.id);
                      }}
                      okText="Yes"
                      onCancel={(e) => {
                        e.stopPropagation();
                      }}
                      cancelText="No"
                    >
                      <Button
                        onClick={(e) => {
                          e.stopPropagation();
                        }}
                        type="primary"
                      >
                        Accept
                      </Button>
                    </Popconfirm>
                  </>
                ) : (
                  <></>
                )}
              </>,
            ];
          },
        }}
      >
        <CoveringRequest
          fromLeaveRquestList={false}
          scope={relateScope}
          employeeId={relatedEmployee}
          leaveData={leaveDataSet}
          coveringRequestData={coveringRequestData}
          setLeaveDataSet={setleaveDataSet}
          employeeFullName={employeeName}
          setCoveringPersonComment={setCoveringPersonComment}
        ></CoveringRequest>
      </ModalForm>
    </>
  );
};

export default WorkflowInstance;
