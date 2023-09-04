import React, { useEffect, useState, useRef } from 'react';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import { history, useIntl, FormattedMessage, useAccess, Access } from 'umi';
import ProTable from '@ant-design/pro-table';
import type { ActionType } from '@ant-design/pro-table';
import { getModel, Models } from '@/services/model';
import { deleteNotice, getAllNotices } from '@/services/notice';
import { Button, Card, Space, Row, Col, Form, Select, Tag, Tooltip, Popconfirm, message as Message, Tabs, Input, Spin } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { getAllUser } from '@/services/user';

import PermissionDeniedPage from '../403';
import { getUserList } from '@/services/dropdown';

const Notice: React.FC = () => {
  const { TabPane } = Tabs;

  const [initializing, setInitializing] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [model, setModel] = useState<any>();
  const [noticesStatus, setNoticesStatus] = useState([]);
  const [users, setUsers] = useState([]);
  const [noticesData, setNoticesData] = useState([]);
  const [companyNoticesData, setCompanyNoticesData] = useState([]);
  const [teamNoticesData, setTeamNoticesData] = useState([]);

  const [filterData, setFilterData] = useState([]);
  const [tabActiveKey, setTabActiveKey] = useState<string>('company-notices');

  const tableRef = useRef<ActionType>();
  const { Option } = Select;
  const [form] = Form.useForm();
  const intl = useIntl();

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

  const noticesColumns = [
    {
      key: 'topic',
      title: <FormattedMessage id="notices.list.Topic" defaultMessage="Topic" />,
      dataIndex: 'topic',
      sorter: true,
      width: 120
    },
    {
      title: <FormattedMessage id="notices.list.status" defaultMessage="Status" />,
      sorter: true,
      filters: true,
      dataIndex: 'status',
      onFilter: true,
      valueEnum: generateStatusEnum(),
      width: 200,
      render: (record: any) => {
        console.log(record)
        let color;
        if (record.props.text == "Published") {
          color = '#88C627';
        }
        if (record.props.text == "Unpublished") {
          color = '#FF4D4F';
        }
        if (record.props.text == "Draft") {
          color = '#9C50FF';
        }
        if (record.props.text == "Archived") {
          color = '#F8A325';
        }
        return (
          <Tag color={color} key={record}>{record}</Tag>
        )
      }
    },
    {
      title: <FormattedMessage id="notices.list.creator" defaultMessage="Creator" />,
      sorter: true,
      filters: true,
      dataIndex: 'createdBy',
      onFilter: true,
      valueEnum: generateCreatorEnum(),
      width: 200
    },
    {
      key: 'actions',
      title: 'Actions',
      dataIndex: 'option',
      valueType: 'option',
      width: 150,
      render: (_, record) => [
        <Access accessible={tabActiveKey == 'company-notices'
          ? hasPermitted('company-notice-read-write')
          : hasPermitted('team-notice-read-write')}>
          <div onClick={(e) => e.stopPropagation()}>
            <Tooltip key="edit-tool-tip" title="Edit">
              <a
                key="edit-btn"
                onClick={() => {
                  const { id } = record;
                  history.push(`/notices/${id}`);
                }}
                style={{ marginLeft: 10, marginRight: 10 }}
              >
                <EditOutlined />
              </a>
            </Tooltip>
            <Popconfirm
              key="delete-pop-confirm"
              placement="topRight"
              title="Are you sure?"
              okText="Yes"
              cancelText="No"
              onConfirm={() => {
                const { id } = record;
                deleteNoticeRecord(id);
              }}
              style={{ marginLeft: 10, marginRight: 10 }}
            >
              <Tooltip key="delete-tool-tip" title="Delete">
                <a key="delete-btn">
                  <DeleteOutlined />
                </a>
              </Tooltip>
            </Popconfirm>
          </div>
        </Access>
      ],
    },
  ];

  const tabOnChange = (key: string) => {
    if (tabActiveKey !== key) {
      // form.resetFields();
      setTabActiveKey(key);
    }
  }

  return (
    <Access
      accessible={
        hasPermitted('company-notice-read-write') || hasPermitted('team-notice-read-write')
      }
      fallback={<PermissionDeniedPage />}
    >
      <div
        style={{
          backgroundColor: 'white',
          borderTopLeftRadius: '30px',
          paddingLeft: '50px',
          paddingRight:'50px',
          paddingTop: '50px',
          width: '100%',
        }}
      >
        <PageContainer>
          <Tabs
            defaultActiveKey="my-notices"
            activeKey={tabActiveKey}
            onChange={tabOnChange}
            tabBarExtraContent={
              <Button
                key="addNotice"
                onClick={(e) => {
                  history.push('/notices/create');
                }}
                type="primary"
              >
                <PlusOutlined /> Add Notice
              </Button>
            }
          >
            {hasPermitted('company-notice-read-write') && (
              <TabPane
                key="company-notices"
                tab={intl.formatMessage({
                  id: 'COMPANY_NOTICES',
                  defaultMessage: 'Company Notices',
                })}
              />
            )}

            {hasPermitted('team-notice-read-write') && (
              <TabPane
                key="team-notices"
                tab={intl.formatMessage({
                  id: 'TEAM_NOTICES',
                  defaultMessage: 'Team Notices',
                })}
              />
            )}
          </Tabs>

          <Card>
            <Space direction="vertical" size={25} style={{ width: '100%' }}>
              <div
                style={{
                  borderRadius: '5px',
                  background: '#FFFFFF',
                  padding: '20px',
                  width: '100%',
                  marginBottom: '20px',
                }}
              >
                <Form
                  form={form}
                  onFinish={getFilteredNotices}
                  autoComplete="off"
                  layout="vertical"
                >
                  <Row>
                    <Col
                      style={{
                        height: 35,
                        width: 250,
                      }}
                      span={6}
                    >
                      <Form.Item
                        name="topic"
                        label={intl.formatMessage({
                          id: 'topic',
                          defaultMessage: 'Topic',
                        })}
                      >
                        <Input
                          allowClear={true}
                          style={{
                            width: '100%',
                          }}
                          placeholder={intl.formatMessage({
                            id: 'notices.topic-input-placeholder',
                            defaultMessage: 'Enter Topic',
                          })}
                        />
                      </Form.Item>
                    </Col>

                    <Col
                      style={{
                        height: 35,
                        width: 250,
                        paddingLeft: 20,
                      }}
                      span={6}
                    >
                      <Form.Item
                        name="createdBy"
                        label={intl.formatMessage({
                          id: 'notices.createdBy.label',
                          defaultMessage: 'Creator',
                        })}
                      >
                        <Select
                          showSearch
                          style={{
                            width: '100%',
                          }}
                          placeholder={intl.formatMessage({
                            id: 'notices.createdBy',
                            defaultMessage: 'Select Creator',
                          })}
                          optionFilterProp="children"
                          allowClear={true}
                        >
                          {users.map((employee) => {
                            return (
                              <Option key={employee.id} value={employee.id}>
                                {employee.name}
                              </Option>
                            );
                          })}
                        </Select>
                      </Form.Item>
                    </Col>

                    <Col
                      style={{
                        height: 35,
                        width: 250,
                        paddingLeft: 20,
                      }}
                      span={6}
                    >
                      <Form.Item
                        name="status"
                        label={intl.formatMessage({
                          id: 'notices.status.label',
                          defaultMessage: 'Status',
                        })}
                      >
                        <Select
                          showSearch
                          style={{
                            width: '100%',
                          }}
                          placeholder={intl.formatMessage({
                            id: 'notices.status',
                            defaultMessage: 'Select Status',
                          })}
                          optionFilterProp="children"
                          allowClear={true}
                        >
                          {noticesStatus.map((status) => {
                            return (
                              <Option key={status.defaultLabel} value={status.value}>
                                {status.defaultLabel}
                              </Option>
                            );
                          })}
                        </Select>
                      </Form.Item>
                    </Col>
                    <Col
                      style={{
                        height: 35,
                        width: 250,
                        paddingLeft: 20,
                        paddingTop: 30,
                      }}
                      span={6}
                    >
                      <Button htmlType="submit" type="primary">
                        <FormattedMessage id="SEARCH" defaultMessage="Search" />
                      </Button>
                    </Col>
                  </Row>
                </Form>
              </div>
            </Space>
          </Card>
          <br />
          <Spin spinning={initializing}>
            {!initializing && (
              <ProTable<any>
                actionRef={tableRef}
                rowKey="id"
                search={false}
                options={true}
                request={async ({ pageSize, current }, sort) => {
                  setRefreshing(true);

                  let sorter = sort;
                  const { data } = await getAllNotices({
                    pageSize,
                    current,
                    sorter,
                    filterBy: filterData,
                  });
                  setNoticesData(data);

                  setRefreshing(false);
                }}
                columns={noticesColumns}
                dataSource={
                  tabActiveKey == 'company-notices' ? companyNoticesData : teamNoticesData
                }
                pagination={{ pageSize: 10, defaultPageSize: 10, hideOnSinglePage: true }}
                onRow={(record, rowIndex) => {
                  return {
                    onClick: async () => {
                      const { id } = record;
                      history.push(`/notices/${id}`);
                    },
                  };
                }}
              />
            )}
          </Spin>
        </PageContainer>
      </div>
    </Access>
  );
};

export default Notice;
