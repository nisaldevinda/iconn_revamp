import React, { useEffect, useState, useRef } from 'react';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import { history, useIntl, FormattedMessage, useAccess, Access } from 'umi';
import type { ProColumns, ColumnsState } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ActionType } from '@ant-design/pro-table';
import styles from './index.less';
import { getModel, Models } from '@/services/model';
import { deleteNotice, getAllNotices } from '@/services/notice';
import { getAllFormTemplates, deleteFormTemplates } from '@/services/template';
import { Button, Card, Space, Row, Col, Form, Select, Tag, Tooltip, Popconfirm, message as Message, Tabs, Input, Spin , Image} from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined, SearchOutlined } from '@ant-design/icons';
import { getAllUser } from '@/services/user';
import moment from 'moment-timezone';
import TemplateIcon from '../../assets/templateBuilder/templateIcon.svg';
import ListEditIcon from '../../assets/templateBuilder/list-edit-icon.svg';
import { APIResponse } from '@/utils/request';
import ProForm, {
  ProFormRadio,
  ProFormSelect,
  ProFormText,
  ProFormUploadButton,
  DrawerForm,
  ModalForm,
  ProFormGroup,
  ProFormList,
  ProFormSwitch,
  ProFormDigit,
} from '@ant-design/pro-form';

import PermissionDeniedPage from '../403';
import { getUserList } from '@/services/dropdown';

const TemplateBuilder: React.FC = () => {
  const { TabPane } = Tabs;

  const [initializing, setInitializing] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [model, setModel] = useState<any>();
  const [noticesStatus, setNoticesStatus] = useState([]);
  const [users, setUsers] = useState([]);
  const [noticesData, setNoticesData] = useState([]);
  const [templatesData, setTemplatesData] = useState([]);
  const [companyNoticesData, setCompanyNoticesData] = useState([]);
  const [teamNoticesData, setTeamNoticesData] = useState([]);

  const [filterData, setFilterData] = useState([]);
  const [tabActiveKey, setTabActiveKey] = useState<string>('company-notices');

  const tableRef = useRef<ActionType>();
  const { Option } = Select;
  const [form] = Form.useForm();
  const intl = useIntl();
  const [searchForm] = Form.useForm();

  const access = useAccess();
  const { hasPermitted } = access;

  useEffect(() => {
    init();
  }, []);

  useEffect(() => {
    refresh();
  }, [noticesData]);

  const init = async () => {
    setInitializing(true);

    if (_.isEmpty(model)) {
      getModel(Models.Notice).then((response) => {
        setModel(response.data);
        setNoticesStatus(response.data.modelDataDefinition.fields.status.values);
      })
    }

    if (_.isEmpty(users)) {
      getUserList().then(response => setUsers(response.data))
      // getAllUser().then(response => setUsers(response.data))
    }

    setInitializing(false);
  }

  const refresh = async () => {
    setRefreshing(true);

    let _companyNoticesData = [...noticesData];
    _companyNoticesData = _companyNoticesData.filter(notice => notice.type == 'COMPANY_NOTICES');
    setCompanyNoticesData(_companyNoticesData);

    let _teamNoticesData = [...noticesData];
    _teamNoticesData = _teamNoticesData.filter(notice => notice.type === 'TEAM_NOTICES');
    setTeamNoticesData(_teamNoticesData);

    setRefreshing(false);
  }

  const generateStatusEnum = () => {
    const valueEnum = {};
    noticesStatus.forEach(element => {
      valueEnum[element.value] = {
        text: element.defaultLabel
      }
    });
    return valueEnum
  }

  const generateCreatorEnum = () => {
    const valueEnum = {};
    users.forEach(user => {
      valueEnum[user.id] = {
        text: user.name
      }
    });
    return valueEnum
  }

  const deleteNoticeRecord = async (id: String) => {
    try {
      const { message } = await deleteNotice(id);
      Message.success(message);
      tableRef.current?.reload();
    } catch (err) {
      console.log(err);
    }
  };

  const getFilteredNotices = async (filterData: any) => {
    setFilterData(filterData)
    tableRef.current?.reload();
  }

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

  const columns: ProColumns<TableListItem>[] = [
    {
      dataIndex: 'createdAt',
      valueType: 'index',
      width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, dom) => [
        <Image src={TemplateIcon} style={{marginLeft: 10, marginTop: 5}} preview={false} height={55} />
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
            <Row>
                <Col>
                    <Row>
                      <Col style={{fontSize: 18 , color: '#394241'}}>{dom.name}</Col>
                    </Row>
                    { <Row>
                      <Col style={{fontSize: 12 , marginBottom: 2}}> <a>{'Settings'}</a></Col>
                    </Row>
                    }
                    
                </Col>
            </Row> 
          </div>
        </>,
      ],
    },
    {
      dataIndex: 'priorStateName',
      valueType: 'select',
      initialValue: 'All',
      // request: async () => getFilter(),
      render: (_, record) => (
          <Space style={{color: '#707070', fontSize: 14}}>
              {record.type == 'FEEDBACK' ? 'Feedback' : 'Evaluation'}
          </Space>
      ),
    },
    {
      dataIndex: 'createdOn',
      width: '350px',
      render: (_, record) => (
        <Space style={{ color: '#909A99', fontSize: 16 }}>
          {'Created On ' + record.createdAt}
        </Space>
      ),
    },
    {
      dataIndex: 'action',
      valueType: 'index',
      width: '150px',
      render: (entity, dom) => [
        <>
          <div
            className={styles.view}
            style={{display: 'flex'}}
          >
            <span onClick={() => {
              const { id } = dom;
              history.push(`/settings/template-builder/${id}`);
            }}>
              <Image src={ListEditIcon} style={{marginLeft: 10, marginTop: 5}} preview={false} height={22} />
            </span>
            <Popconfirm
                key="deleteRecordConfirm"
                title={intl.formatMessage({
                    id: 'are_you_sure',
                    defaultMessage: 'Are you sure?',
                })}
                onConfirm={async () => {
                  const { id } = dom;
                  deleteFormTemplates(id)
                  .then((response: APIResponse) => {
                      if (response.error) {
                        Message.error({
                              content:
                                  response.message ??
                                  intl.formatMessage({
                                      id: 'failedToDelete',
                                      defaultMessage: 'Failed to delete',
                                  }),
                          });
                          return;
                      }

                      Message.success({
                          content:
                              response.message ??
                              intl.formatMessage({
                                  id: 'successfullyDeleted',
                                  defaultMessage: 'Successfully deleted',
                              }),
                      });

                      tableRef?.current?.reload();
                  })

                  .catch((error: APIResponse) => {
                    Message.error({
                        content:
                          error.message ?
                          <>
                              {error.message}
                          </>
                          : intl.formatMessage({
                              id: 'failedToDelete',
                              defaultMessage: 'Failed to delete',
                          }),
                      });
                  });
                }}
                okText="Yes"
                cancelText="No"
            >
              <span style={{marginLeft: 20, paddingTop: 3}}>
                <DeleteOutlined
                  style={{ fontSize: 25, color: '#86C129' }}
                />  
              </span>
            </Popconfirm>
          </div>
        </>
      ],
    },
    
  ];


  return (
    
    <Access accessible={hasPermitted('company-notice-read-write') || hasPermitted('team-notice-read-write')} fallback={<PermissionDeniedPage />}>
      <PageContainer>
        <Row style={{width: '100%', marginBottom: 30}}>
          <Col span={16}>
          <ProForm
                id={'searchForm'}
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
                                if (searchForm.getFieldValue('templateName')) {
                                  let filter = {
                                    "name" : searchForm.getFieldValue('templateName')
                                  };
                                  const { data } = await getAllFormTemplates({ pageSize : 10, current: 1, sorter : {}, filterBy: filter });
                                  setTemplatesData(data);
                                  
                                } else {
                                  let filter = {};
                                  const { data } = await getAllFormTemplates({ pageSize : 10, current: 1, sorter : {}, filterBy: filter });
                                  setTemplatesData(data);
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
                <Row >
                  <Col span={24}>
                    <ProFormText
                      name="templateName"
                      width={400}
                      onChange={() => {

                      }}
                      placeholder={intl.formatMessage({
                        id :'attendance.startDate',
                        defaultMessage :'Search By Template Name'
                      })}
                    />
                  </Col>
                </Row>
              </ProForm>

          </Col>
          <Col span={8}>
            <div style={{float: 'right'}}>
              <Button
                type="primary"
                key="primary"
                onClick={() => {
                  history.push('/settings/template-builder/create');
                }}
              >
                <PlusOutlined /> Add New Template
              </Button>
            </div>
          </Col>
        </Row>
        <div className='templateBuilderList'>
        <Spin spinning={initializing}>
          {!initializing &&
            <ProTable<any>
              actionRef={tableRef}
              rowKey="id"
              showHeader={false}
              search={false}
              options={true}
              request={async ({ pageSize, current }, sort) => {

                let sorter = sort;
                const { data } = await getAllFormTemplates({ pageSize, current, sorter, filterBy: filterData });
                setTemplatesData(data);
              }}
              columns={columns}
              dataSource={templatesData} 
              toolBarRender={false}
              pagination={{ pageSize: 10, defaultPageSize: 10, hideOnSinglePage: true }}
              // onRow={(record, rowIndex) => {
              //   return {
              //     onClick: async () => {
                    // const { id } = record;
                    // history.push(`/notices/${id}`);
              //     },
              //   };
              // }}
            />
          }
        </Spin>
        </div>
      </PageContainer>
    </Access>
  );
};

export default TemplateBuilder;
