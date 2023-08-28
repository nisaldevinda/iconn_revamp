import React, { useEffect, useState, useRef } from 'react';
import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import { history, useIntl, FormattedMessage, useAccess, Access } from 'umi';
import type { ProColumns, ColumnsState } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ActionType } from '@ant-design/pro-table';
import styles from './index.less';
import { getModel, Models } from '@/services/model';
import { deleteNotice, getAllNotices } from '@/services/notice';
import { getAllFormTemplates, deleteFormTemplates } from '@/services/template';
import {
  Button,
  Card,
  Space,
  Row,
  Col,
  Form,
  Select,
  Tag,
  Tooltip,
  Popconfirm,
  message as Message,
  Tabs,
  Input,
  Spin,
  Image,
  Modal,
  Checkbox,
  message,
  Drawer,
} from 'antd';
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SearchOutlined,
  SettingOutlined,
  EyeOutlined,
} from '@ant-design/icons';
import { getAllUser } from '@/services/user';
import moment from 'moment-timezone';
import TemplateIcon from '../../assets/templateBuilder/templateIcon.svg';
import ListEditIcon from '../../assets/templateBuilder/list-edit-icon.svg';
import { APIResponse } from '@/utils/request';
import {
  queryDefineData,
  addDefineData,
  updateDefine,
  removeDefine,
  workflowEmployeeGroups,
  queryContextData,
} from '@/services/workflowServices';
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
import context from 'react-bootstrap/esm/AccordionContext';

const WorkflowBuilder: React.FC = () => {
  const { TabPane } = Tabs;

  const [initializing, setInitializing] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [model, setModel] = useState<any>();
  const [checked, setChecked] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState<any>();
  const [workflowName, setWorkflowName] = useState<any>();
  const [selectedWorkflow, setSelectedWorkflow] = useState<any>({});
  const [formValues, setFormValues] = useState<any>();
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [noticesStatus, setNoticesStatus] = useState([]);
  const [users, setUsers] = useState([]);
  const [noticesData, setNoticesData] = useState([]);
  const [templatesData, setTemplatesData] = useState([]);
  const [companyNoticesData, setCompanyNoticesData] = useState([]);
  const [teamNoticesData, setTeamNoticesData] = useState([]);
  const [contextList, setContextList] = useState([]);
  const [empGroupList, setEmpGroupList] = useState([]);
  const [dataCount, setDataCount] = useState(0);

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
      });
    }

    if (_.isEmpty(users)) {
      getUserList().then((response) => setUsers(response.data));
      // getAllUser().then(response => setUsers(response.data))
    }

    setInitializing(false);
  };

  const refresh = async () => {
    // setRefreshing(true);
    // let _companyNoticesData = [...noticesData];
    // _companyNoticesData = _companyNoticesData.filter(notice => notice.type == 'COMPANY_NOTICES');
    // setCompanyNoticesData(_companyNoticesData);
    // let _teamNoticesData = [...noticesData];
    // _teamNoticesData = _teamNoticesData.filter(notice => notice.type === 'TEAM_NOTICES');
    // setTeamNoticesData(_teamNoticesData);
    // setRefreshing(false);
  };

  const addWorkflow = async (data: any) => {
    try {
      data.isAllowToCancelRequestByRequester = checked;
      const response = await addDefineData(data);
      Message.success(response.message);
      tableRef.current?.reload();
      setIsModalOpen(false);
      console.log(response);
    } catch (error) {
      Message.error(error.message);
      console.error(error);
    }
  };

  const handleCancel = async () => {
    setIsModalOpen(false);
  };

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
        <Image
          src={TemplateIcon}
          style={{ marginLeft: 10, marginTop: 5 }}
          preview={false}
          height={55}
        />,
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
              <Col style={{ marginTop: 15 }}>
                <Row>
                  <Col style={{ fontSize: 18, color: '#394241' }}>{dom.workflowName}</Col>
                </Row>
                <Row>
                  <Col style={{ fontSize: 14, color: 'grey' }}>{dom.description}</Col>
                </Row>
                {
                  <Row>
                    <Col style={{ fontSize: 12, marginBottom: 2 }}>
                      {' '}
                      <a
                        onClick={() => {
                          const { id } = dom;
                          history.push(`/settings/workflow-builder/${id}`);
                        }}
                        style={{ color: '#86C129' }}
                      >
                        {
                          <SettingOutlined
                            style={{ marginRight: 5, fontSize: 14 }}
                          ></SettingOutlined>
                        }
                        {'Configuration'}
                      </a>
                    </Col>
                  </Row>
                }
              </Col>
            </Row>
          </div>
        </>,
      ],
    },
    {
      dataIndex: 'contextId',
      render: (_, record) => (
        <>
          {record.contextId == 1
            ? 'Profile Update'
            : record.contextId == 2
            ? 'Leave Request'
            : record.contextId == 3
            ? 'Time Change Request'
            : record.contextId == 4
            ? 'Short Leave Request'
            : record.contextId == 5
            ? 'Shift Change Request'
            : record.contextId == 6
            ? 'Cancel Leave Request'
            : record.contextId == 7
            ? 'Resignation Request'
            : record.contextId == 8
            ? 'Cancel Short Leave Request'
            : record.contextId == 9
            ? 'Claim Request'
            : record.contextId == 10
            ? 'Post OT Request'
            : null}
        </>
      ),
    },
    {
      dataIndex: 'createdOn',
      width: '350px',
      render: (_, record) => (
        <Space style={{ color: '#909A99', fontSize: 16 }}>{'Created On ' + record.createdAt}</Space>
      ),
    },
    {
      dataIndex: 'action',
      valueType: 'index',
      width: '150px',
      render: (entity, dom) => [
        <>
          <div className={styles.view} style={{ display: 'flex' }}>
            {!dom.isReadOnly ? (
              <>
                <span
                  onClick={async () => {
                    const { id } = dom;
                    setSelectedWorkflow(dom);
                    // history.push(`/settings/template-builder/${id}`);
                    form.setFieldsValue({
                      workflowName: dom.workflowName,
                      description: dom.description,
                      contextId: dom.contextId,
                      employeeGroupId: dom.employeeGroupId,
                      isAllowToCancelRequestByRequester:
                        dom.isAllowToCancelRequestByRequester == 1 ? true : false,
                    });
                    setFormValues({
                      workflowName: dom.workflowName,
                      description: dom.description,
                      contextId: dom.contextId,
                      employeeGroupId: dom.employeeGroupId,
                      isAllowToCancelRequestByRequester:
                        dom.isAllowToCancelRequestByRequester == 1 ? true : false,
                    });
                    if (dom.isAllowToCancelRequestByRequester) {
                      setChecked(true);
                    } else {
                      setChecked(false);
                    }

                    let res = await workflowEmployeeGroups({
                      filter: {
                        contextId: [dom.contextId],
                      },
                    });
                    const groups = res.data.map((grp) => {
                      return {
                        label: grp.name,
                        value: grp.id,
                      };
                    });
                    setEmpGroupList(groups);
                    queryContextData({})
                      .then((res) => {
                        const contexts = res.data.map((context) => {
                          return {
                            label: context.contextName,
                            value: context.id,
                          };
                        });
                        setContextList(contexts);
                        setIsDrawerOpen(true);
                        console.log(res);
                      })
                      .catch((error) => {
                        console.log(error);
                      });
                  }}
                >
                  <Image
                    src={ListEditIcon}
                    style={{ marginLeft: 10, marginTop: 5 }}
                    preview={false}
                    height={16}
                  />
                </span>
                <Popconfirm
                  key="deleteRecordConfirm"
                  title={intl.formatMessage({
                    id: 'are_you_sure',
                    defaultMessage: 'Are you sure?',
                  })}
                  onConfirm={async () => {
                    const { id } = dom;
                    removeDefine(dom)
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
                          content: error.message ? (
                            <>{error.message}</>
                          ) : (
                            intl.formatMessage({
                              id: 'failedToDelete',
                              defaultMessage: 'Failed to delete',
                            })
                          ),
                        });
                      });
                  }}
                  okText="Yes"
                  cancelText="No"
                >
                  <span style={{ marginLeft: 20, paddingTop: 3 }}>
                    <DeleteOutlined style={{ fontSize: 18, color: '#86C129' }} />
                  </span>
                </Popconfirm>
              </>
            ) : (
              <>
                <span
                  onClick={async () => {
                    const { id } = dom;
                    setSelectedWorkflow(dom);
                    // history.push(`/settings/template-builder/${id}`);
                    form.setFieldsValue({
                      workflowName: dom.workflowName,
                      description: dom.description,
                      contextId: dom.contextId,
                      employeeGroupId: dom.employeeGroupId,
                      isAllowToCancelRequestByRequester:
                        dom.isAllowToCancelRequestByRequester == 1 ? true : false,
                    });
                    setFormValues({
                      workflowName: dom.workflowName,
                      description: dom.description,
                      contextId: dom.contextId,
                      employeeGroupId: dom.employeeGroupId,
                      isAllowToCancelRequestByRequester:
                        dom.isAllowToCancelRequestByRequester == 1 ? true : false,
                    });

                    console.log(dom);

                    let res = await workflowEmployeeGroups({
                      filter: {
                        contextId: [dom.contextId],
                      },
                    });
                    const groups = res.data.map((grp) => {
                      return {
                        label: grp.name,
                        value: grp.id,
                      };
                    });
                    setEmpGroupList(groups);
                    queryContextData({})
                      .then((res) => {
                        const contexts = res.data.map((context) => {
                          return {
                            label: context.contextName,
                            value: context.id,
                          };
                        });
                        setContextList(contexts);
                        setIsDrawerOpen(true);
                        console.log(res);
                      })
                      .catch((error) => {
                        console.log(error);
                      });
                  }}
                >
                  <EyeOutlined style={{ marginLeft: 25 }}></EyeOutlined>
                </span>
              </>
            )}
          </div>
        </>,
      ],
    },
  ];

  return (
    <Access
      accessible={
        hasPermitted('company-notice-read-write') || hasPermitted('team-notice-read-write')
      }
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer>
        <Row style={{ width: '100%', marginBottom: 30 }}>
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
                      <Col style={{ marginLeft: 10 }}>
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
                              if (searchForm.getFieldValue('workflowName')) {
                                let filter = {
                                  name: searchForm.getFieldValue('workflowName'),
                                };
                                const { data } = await queryDefineData({
                                  pageSize: 10,
                                  current: 1,
                                  sorter: {},
                                  keyword: searchForm.getFieldValue('workflowName'),
                                });

                                console.log(data);
                                setTemplatesData(data.data);
                                setDataCount(data.total);
                              } else {
                                let filter = {};
                                const { data } = await queryDefineData({
                                  pageSize: 10,
                                  current: 1,
                                  sorter: {},
                                  keyword: searchForm.getFieldValue('workflowName'),
                                });
                                setTemplatesData(data.data);
                                setDataCount(data.total);
                              }
                            }}
                          />
                        </Tooltip>
                      </Col>
                    </>,
                  ];
                },
              }}
            >
              <Row>
                <Col span={24}>
                  <ProFormText
                    name="workflowName"
                    width={400}
                    onChange={() => {}}
                    placeholder={intl.formatMessage({
                      id: 'attendance.startDate',
                      defaultMessage: 'Search By Workflow Name',
                    })}
                  />
                </Col>
              </Row>
            </ProForm>
          </Col>
          <Col span={8}>
            <div style={{ float: 'right' }}>
              <Button
                type="primary"
                key="primary"
                onClick={async () => {
                  queryContextData({})
                    .then((res) => {
                      const contexts = res.data.map((context) => {
                        return {
                          label: context.contextName,
                          value: context.id,
                        };
                      });
                      setContextList(contexts);
                      form.setFieldsValue({
                        workflowName: null,
                        description: null,
                        contextId: null,
                        employeeGroupId: null,
                        isAllowToCancelRequestByRequester: undefined,
                      });
                      setChecked(false);
                      setIsModalOpen(true);
                      console.log(res);
                    })
                    .catch((error) => {
                      console.log(error);
                    });
                }}
              >
                <PlusOutlined /> Add New Workflow
              </Button>
            </div>
          </Col>
        </Row>
        <div className="templateBuilderList">
          <Spin spinning={initializing}>
            {!initializing && (
              <ProTable<any>
                actionRef={tableRef}
                rowKey="id"
                showHeader={false}
                search={false}
                options={true}
                request={async ({ pageSize, current }, sort) => {
                  let sorter = sort;
                  const { data } = await queryDefineData({
                    pageSize,
                    current,
                    sorter,
                    filterBy: filterData,
                  });

                  setTemplatesData(data.data);
                  setDataCount(data.total);
                }}
                columns={columns}
                dataSource={templatesData}
                toolBarRender={false}
                pagination={{ pageSize: 10, total: dataCount }}
                // onRow={(record, rowIndex) => {
                //   return {
                //     onClick: async () => {
                // const { id } = record;
                // history.push(`/notices/${id}`);
                //     },
                //   };
                // }}
              />
            )}
          </Spin>
        </div>
      </PageContainer>
      <Modal
        title={'Add Workflow'}
        width={700}
        className={'workflowAddLevelModal'}
        visible={isModalOpen}
        footer={[
          <Button
            key="submit"
            type="primary"
            onClick={() => {
              form
                .validateFields()
                .then((values) => {
                  addWorkflow(values);
                  console.log(values);
                })
                .catch((info) => {
                  console.log('Validate Failed:', info);
                });
            }}
          >
            Save
          </Button>,
          <Button type="default" onClick={handleCancel}>
            Cancel
          </Button>,
        ]}
        onCancel={handleCancel}
      >
        <Form layout="vertical" form={form} initialValues={formValues}>
          <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
            <Col span={12}>
              <Form.Item label="Name" name="workflowName" rules={[{ required: true }]}>
                <Input style={{ borderRadius: 6 }} placeholder="Name" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Description" name="description">
                <Input style={{ borderRadius: 6 }} placeholder="Name" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Context Name" rules={[{ required: true }]} name="contextId">
                <Select
                  allowClear={true}
                  onChange={async (val) => {
                    console.log(val);
                    let res = await workflowEmployeeGroups({
                      filter: {
                        contextId: [val],
                      },
                    });
                    const groups = res.data.map((grp) => {
                      return {
                        label: grp.name,
                        value: grp.id,
                      };
                    });
                    setEmpGroupList(groups);
                  }}
                  options={contextList}
                  placeholder="Select Workflow Context"
                />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Employee Group" name="employeeGroupId">
                <Select
                  allowClear={true}
                  options={empGroupList}
                  placeholder="Select Employee Group"
                />
              </Form.Item>
            </Col>
            {form.getFieldValue('contextId') != 2 && form.getFieldValue('contextId') != 4 ? (
              <Col span={12} className={'workflowBuilderCheckbox'}>
                {/* <Form.Item   label="Employee Group" name="isAllowToCancelRequestByRequester"> */}
                <Checkbox
                  onChange={(val) => {
                    setChecked(val.target.checked);
                  }}
                  checked={checked}
                >
                  {'Is Allow to Cancel By Requested Employee'}
                </Checkbox>
                {/* </Form.Item> */}
              </Col>
            ) : (
              <></>
            )}
          </Row>
        </Form>
      </Modal>
      <DrawerForm
        width={650}
        title={
          selectedWorkflow.isReadOnly
            ? intl.formatMessage({
                id: 'view.workflow',
                defaultMessage: 'View Workflow',
              })
            : intl.formatMessage({
                id: 'edit.workflow',
                defaultMessage: 'Edit Workflow',
              })
        }
        onVisibleChange={setIsDrawerOpen}
        form={form}
        drawerProps={{
          destroyOnClose: true,
        }}
        visible={isDrawerOpen}
        onFinish={async (values) => {
          let params = {
            id: selectedWorkflow.id,
            workflowName: values.workflowName,
            description: values.description,
            contextId: values.contextId,
            employeeGroupId: values.employeeGroupId,
            isAllowToCancelRequestByRequester: checked,
          };

          updateDefine(params)
            .then((res) => {
              Message.success(res.message);

              tableRef.current?.reload();
              setIsDrawerOpen(false);
              console.log(res);
            })
            .catch((error) => {
              Message.success(error.message);
              console.log(error);
            });
        }}
        initialValues={formValues}
        submitter={
          selectedWorkflow.isReadOnly
            ? {
                render: (props, defaultDoms) => {
                  return [
                    <Button
                      key="Reset"
                      onClick={() => {
                        setIsDrawerOpen(false);
                      }}
                    >
                      Cancel
                    </Button>,
                  ];
                },
              }
            : {
                render: (props, defaultDoms) => {
                  return [
                    <Button
                      key="Reset"
                      onClick={() => {
                        setIsDrawerOpen(false);
                      }}
                    >
                      Cancel
                    </Button>,

                    <Button
                      key="ok"
                      onClick={() => {
                        props.submit();
                      }}
                      type={'primary'}
                    >
                      Update
                    </Button>,
                  ];
                },
              }
        }
      >
        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
          <Col span={12}>
            <Form.Item label="Name" name="workflowName" rules={[{ required: true }]}>
              <Input
                value={workflowName}
                disabled={selectedWorkflow.isReadOnly ? true : false}
                onChange={(val) => {
                  setWorkflowName(val);
                }}
                style={{ borderRadius: 6 }}
                placeholder="Name"
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Description" name="description">
              <Input disabled={selectedWorkflow.isReadOnly ? true : false} style={{ borderRadius: 6 }} placeholder="Name" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Context Name" rules={[{ required: true }]} name="contextId">
              <Select
                disabled={selectedWorkflow.isReadOnly ? true : false}
                onChange={async (val) => {
                  console.log(val);
                  let res = await workflowEmployeeGroups({
                    filter: {
                      contextId: [val],
                    },
                  });
                  const groups = res.data.map((grp) => {
                    return {
                      label: grp.name,
                      value: grp.id,
                    };
                  });
                  setEmpGroupList(groups);
                  form.setFieldsValue({
                    employeeGroupId: undefined,
                  });
                }}
                options={contextList}
                allowClear={true}
                placeholder="Select Workflow Context"
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Employee Group" name="employeeGroupId">
              <Select
                allowClear={true}
                disabled={selectedWorkflow.isReadOnly ? true : false}
                options={empGroupList}
                placeholder="Select Employee Group"
              />
            </Form.Item>
          </Col>
          {form.getFieldValue('contextId') != 2 && form.getFieldValue('contextId') != 4 ? (
            <Col span={24} className={'workflowBuilderCheckbox'}>
              {/* <Form.Item label="Employee Group" name="isAllowToCancelRequestByRequester"> */}
              <Checkbox
                onChange={(val) => {
                  setChecked(val.target.checked);
                }}
                disabled={selectedWorkflow.isReadOnly ? true : false}
                checked={checked}
              >
                {'Is Allow to Cancel By Requested Employee'}
              </Checkbox>
              {/* </Form.Item> */}
            </Col>
          ) : (
            <></>
          )}
        </Row>
      </DrawerForm>
    </Access>
  );
};

export default WorkflowBuilder;
