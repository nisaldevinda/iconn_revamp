import {
  Form,
  Row,
  Col,
  Select,
  Button,
  Tabs,
  Checkbox,
  Collapse,
  message as Message,
  Divider,
  Input,
  Spin,
} from 'antd';
import React, { useState, Key, useRef, useEffect } from 'react';
import { useIntl, useParams, history } from 'umi';
import { PageContainer, FooterToolbar } from '@ant-design/pro-layout';
import {
  updateUserRoles,
  queryUserRolesMeta,
  queryByUserRolesID,
  getAccessManageFields,
  getAccessManageMandotaryFields
} from '@/services/userRole';
import _ from 'lodash';
import FieldTable from '../components/fieldTable';
import { TabData, FieldPermission } from '../userRoleData';
import styles from './styles.less'
const { Option } = Select;

const { TabPane } = Tabs;
const { Panel } = Collapse;

export type EditUserRoleParams = {
  id: string;
};

interface TableType {
  reload: (resetPageIndex?: boolean) => void;
  reloadAndRest: () => void;
  reset: () => void;
  clearSelected?: () => void;
  startEditable: (rowKey: Key) => boolean;
  cancelEditable: (rowKey: Key) => boolean;
}

const EditUserRole: React.FC = () => {
  const tableRef = useRef<TableType>();
  const intl = useIntl();
  const { id } = useParams<EditUserRoleParams>();
  const [permittedActionsCheckList, setpermittedActionsCheckList] = useState([]);
  const [isDirectAccess, setIsDirectAccess] = useState(false);
  const [isInDirectAccess, setIsInDirectAccess] = useState(false);
  const [selectedRole, setSelectedRole] = useState<string | null>(null);
  const [roles, setRoles] = useState([]);
  const [permissionList, setPermissionList] = useState([]);
  const [rolePermissions, setRolePermissions] = useState([]);
  const [selectedRolePermissions, setSelectedRolePermissions] = useState([]);
  const [fieldList, setFieldList] = useState<TabData[]>([]);
  const [fieldAccessLevels, setFieldAccessLevels] = useState<FieldPermission[]>([]);
  const [scopeAccess, setScopeAccess] = useState([]);
  const [selectedScopes, setSelectedScopes] = useState({});
  const [workflows, setWorkflows] = useState([]);
  const [workflowAccess, setWorkflowAccess] = useState([]);
  const [selectedWorkflowAccess, setSelectedWorkflowAccess] = useState([]);
  const [form] = Form.useForm();
  const [load, setload] = useState(false);
  const [mandotaryFieldSet, setMandotaryField] = useState([]);
  const [isCheckedAddEmployee, setIsCheckedAddEmployee] = useState<boolean>(false);

  useEffect(() => {
    const fetchData = async () => {
      await getUserRoleMeta();
      // await getModelData();
    };
    try {
      fetchData();
    } catch (error) {
      console.log('error:', error);
    }
  }, []);

  useEffect(() => {
    const fetchData = async () => {
      await getUserRoleById(id);
    };
    try {
      fetchData();
    } catch (error) {
      console.log('error:', error);
    }
  }, [id, rolePermissions, workflows]);

  const getUserRoleMeta = async () => {
    try {
      const { data } = await queryUserRolesMeta();
      const { roles, permissions, rolePermissions, workflows } = data;
      setRoles(roles);
      setPermissionList(permissions);
      setRolePermissions(rolePermissions);
      setWorkflows(workflows);
    } catch (error) {
      Message.error('Getting User Roles Failed, please try again');
    }
  };

  const getAccessLevels = (tabData: TabData[]) => {
    const fieldPermissions: FieldPermission[] = [];
    tabData.forEach((tab) => {
      tab.sections.forEach((section) => {
        section.fields.forEach((field) => {
          fieldPermissions.push({
            key: field.key,
            tab: tab['key'],
            section: section['key'],
            label: field.value,
            fieldType: 'Optional',
            permission: 'noAccess',
          });
        });
      });
    });
    return fieldPermissions;
  };

  const getUserRoleById = async (userid: any) => {
    try {
      setload(true);
      const { data: fieldData } = await getAccessManageFields();
      const { data: mondotaryFields } = await getAccessManageMandotaryFields();
      const accessLevels = getAccessLevels(fieldData);
      const { data } = await queryByUserRolesID(userid);
      const {
        customCriteria: customCriteriaData,
        isDirectAccess,
        isInDirectAccess,
        permittedActions,
        fieldPermissions,
        title,
        type,
        workflowManagementActions,
      } = data;
      setFieldList(fieldData);
      const permissionLabels = getPermissonList(type);
      let newAccessLevels = updateFieldPermissions([...accessLevels], fieldPermissions);

      if (permittedActions.includes('employee-create')) {

        newAccessLevels = newAccessLevels.map((item) => {

          const fieldKeyArray = item['key'].split(".");
          let field = fieldKeyArray[1];
          let tabIndex = mondotaryFields.findIndex((tab) => tab.key == item['tab']);
          let sectionIndex = mondotaryFields[tabIndex]['sections'].findIndex((section) => section.key == item['section']);
          let sectionWiseMandatoryFields = mondotaryFields[tabIndex]['sections'][sectionIndex]['fields'];

          if (sectionWiseMandatoryFields.includes(field)) {
            item['fieldType'] = 'Mandotary';
          }
          return item;
        });

      }

      setSelectedWorkflowAccess(workflowManagementActions);
      setFieldAccessLevels([...newAccessLevels]);
      setSelectedRolePermissions(permissionLabels);
      setMandotaryField(mondotaryFields);
      form.setFieldsValue({ title: title, role: type });
      setpermittedActionsCheckList(permittedActions);
      setIsDirectAccess(isDirectAccess);
      setIsInDirectAccess(isInDirectAccess);
      if (type in rolePermissions && 'scopeAccess' in rolePermissions[type]) {
        const scopeAccess = rolePermissions[type]['scopeAccess'];
        setScopeAccess(getScopes(scopeAccess));
      }
      if (type in rolePermissions && 'workflows' in rolePermissions[type]) {
        const roleWorkflows = rolePermissions[type]['workflows'];
        setWorkflowAccess(getWorkflowObjects(roleWorkflows));
      }
      setSelectedScopes(computeSelectedScopes(customCriteriaData));
      setSelectedRole(type);
      setload(false);
      tableRef.current?.reload();
    } catch (error) {
      setload(false);
      Message.error('Getting User Roles Failed, please try again');
    }
  };

  const computeSelectedScopes = (scopes: any) => {
    const selectedScopes: any[] = [];
    Object.entries(scopes).forEach(([scopeName, scopeData]) => {
      scopeData.forEach((value: string) => {
        selectedScopes.push(`${scopeName}-${value}`);
      });
    });
    return selectedScopes;
  }

  const updateFieldPermissions = (defaultAccessLevels: any, roleAccessLeves: any) => {
    const models = _.keys(roleAccessLeves);

    const readOnlyFields = models.reduce((readOnlyFields: string[], model: string) => {
      const result = roleAccessLeves[model].viewOnly.map((field: string) => {
        return `${model}.${field}`;
      });
      return [...readOnlyFields, ...result];
    }, []);

    let newAccessLevels: any[] = [];
    _.forEach(readOnlyFields, (key) => {
      const index = defaultAccessLevels.findIndex((fieldAccessLevel: any) => fieldAccessLevel.key == key);
      if (index !== -1) {
        newAccessLevels = [...defaultAccessLevels];
        newAccessLevels[index].permission = 'viewOnly';
      }
    });

    const canEditFields = models.reduce((readOnlyFields: string[], model: string) => {
      const result = roleAccessLeves[model].canEdit.map((field: string) => {
        return `${model}.${field}`;
      });
      return [...readOnlyFields, ...result];
    }, []);

    _.forEach(canEditFields, (key) => {
      const index = defaultAccessLevels.findIndex(
        (fieldAccessLevel: any) => fieldAccessLevel.key == key,
      );
      if (index !== -1) {
        newAccessLevels = [...defaultAccessLevels];
        newAccessLevels[index].permission = 'canEdit';
      }
    });
    return _.isEmpty(newAccessLevels) ? defaultAccessLevels : newAccessLevels;
  };

  const getPermissionLabels = (permissionKeys: string[]): any => {
    return permissionKeys.map((key) => {
      return {
        key,
        label: permissionList[key],
      };
    });
  };

  const getWorkflowObjects = (workflowKeys: string[]): any => {
    return workflowKeys.map((key) => {
      return {
        key,
        label: workflows[key],
      };
    });
  };

  const getScopes = (scopes: any): any => {
    const scopeList: any[] = [];
    Object.entries(scopes).forEach(([scopeName, scopeData]) => {
      const scopeDataList: any = [];
      Object.entries(scopeData).forEach(([key, value]) => {
        const id = `${scopeName}-${key}`;
        scopeDataList.push({ key: id, value });
      });
      scopeList.push({ scopeName, scopeDataList });
    });
    return scopeList;
  };

  const handleOnRoleChange = (value: string) => {
    const permissionLabels = getPermissonList(value);
    setSelectedRolePermissions(permissionLabels);
  };

  const getPermissonList = (value: string) => {
    const permissionKeys = (rolePermissions.hasOwnProperty(value)) ? rolePermissions[value]['permission-list'] : [];
    return getPermissionLabels(permissionKeys);
  };

  const handleOnChangePermissions = (permissions: any) => {
    if (permissions.includes("employee-create")) {
      setIsCheckedAddEmployee(true);
      handleAddEmployeeChecked();
    } else {
      setIsCheckedAddEmployee(false);
      handleAddEmployeeUnChecked();
    }
    setpermittedActionsCheckList(permissions);
  };

  const handleAddEmployeeChecked = () => {
    let newAccessLevels = [...fieldAccessLevels];

    newAccessLevels = newAccessLevels.map((item) => {

      const fieldKeyArray = item['key'].split(".");
      let field = fieldKeyArray[1];
      let tabIndex = mandotaryFieldSet.findIndex((tab) => tab.key == item['tab']);
      let sectionIndex = mandotaryFieldSet[tabIndex]['sections'].findIndex((section) => section.key == item['section']);
      let sectionWiseMandatoryFields = mandotaryFieldSet[tabIndex]['sections'][sectionIndex]['fields'];

      if (sectionWiseMandatoryFields.includes(field)) {
        item['fieldType'] = 'Mandotary';
      }
      return item;
    });

    setFieldAccessLevels(newAccessLevels);
  };

  const handleAddEmployeeUnChecked = () => {
    let newAccessLevels = [...fieldAccessLevels];

    newAccessLevels = newAccessLevels.map((item) => {
      item['fieldType'] = 'Optional';
      return item;
    });
    setFieldAccessLevels(newAccessLevels);
  };

  const handleOnChangePermission = (record, value) => {
    const { key } = record;
    const index = fieldAccessLevels.findIndex((fieldAccessLevel) => fieldAccessLevel.key == key);
    if (index !== -1) {
      const newAccessLevels = [...fieldAccessLevels];
      newAccessLevels[index].permission = value;
      setFieldAccessLevels(newAccessLevels);
    }
  };

  const handleOnBulkSelectChange = (permissionLevel: string, selectedRowKeys: string[]) => {
    if (permissionLevel !== '') {
      const fieldPermissions = [...fieldAccessLevels];
      selectedRowKeys.forEach((selectedKey: string) => {
        const index = fieldPermissions.findIndex((element) => element.key === selectedKey);
        if (index !== -1) {
          const obj = fieldPermissions[index];
          const readOnly = fieldList.find(tab => tab.key == obj.tab)
            ?.sections.find(section => section.key == obj.section)
            ?.fields.find(field => field.key == obj.key)
            ?.readOnly;

          const _permissionLevel = readOnly && permissionLevel == 'canEdit' ? 'viewOnly' : permissionLevel;
          fieldPermissions.splice(index, 1, { ...obj, permission: _permissionLevel });
        }
      });
      setFieldAccessLevels(fieldPermissions);
    }
  };

  const handleOnChangeScope = (scopes: any) => {
    setSelectedScopes(scopes);
  };

  const handleOnWorkflowSelect = (workflows: any) => {
    setSelectedWorkflowAccess(workflows);
  };

  const formatScope = (scopes: any) => {
    const selectedScopes: any = {};
    scopes.forEach((scope: string) => {
      const res = scope.split('-');
      if (!selectedScopes[res[0]]) {
        selectedScopes[res[0]] = [];
      }
      selectedScopes[res[0]].push(res[1]);
    });
    return selectedScopes;;
  };

  // Save user Role - Submint button function
  const handleAdd = async () => {
    try {
      await form.validateFields();
      if (selectedRole === 'ADMIN' && permittedActionsCheckList.includes('employee-create')) {

        let unEditableMandatoryFieldCount = 0;

        fieldAccessLevels.map((item) => {
          if (item['fieldType'] == 'Mandotary' && item['permission'] != 'canEdit') {

            unEditableMandatoryFieldCount++;
          }
          return item;
        });

        if (unEditableMandatoryFieldCount > 0) {
          Message.error({
            content: intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Please change field level permissions to Can Edit for all mandatory fields',
            })
          });
          return;
        }
      }

      const { message, data } = await updateUserRoles(id, {
        title: form.getFieldValue('title'),
        type: form.getFieldValue('role'),
        isDirectAccess: isDirectAccess,
        isInDirectAccess: isInDirectAccess,
        customCriteria: formatScope(selectedScopes),
        isEditable: true,
        isVisibility: true,
        permittedActions: permittedActionsCheckList,
        workflowManagementActions: selectedWorkflowAccess,
        fieldAccessLevels,
      });
      Message.success(message);
    } catch (error: any) {
      if (!_.isEmpty(error.data) && _.isObject(error.data)) {
        for (const fieldName in error.data) {
          form.setFields([
            {
              name: fieldName,
              errors: error.data[fieldName][0] === "This is an unique field." ? ["Role Name is already existing"] : error.data[fieldName]
            }
          ]);

        }
      } else {
        if (!_.isEmpty(error.message)) {
          let errorMessage;
          let errorMessageInfo;
          if (error.message.includes(".")) {
            let errorMessageData = error.message.split(".");
            errorMessage = errorMessageData.slice(0, 1);
            errorMessageInfo = errorMessageData.slice(1).join('.');
          }
          Message.error({
            content:
              error.message ?
                <>
                  {errorMessage ?? error.message}
                  <br />
                  <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                    {errorMessageInfo ?? ''}
                  </span>
                </>
                : intl.formatMessage({
                  id: 'failedToUpdate',
                  defaultMessage: 'Cannot Update',
                }),
          });
        }

      }
    }
  };

  // cancel button click
  const handleCancel = () => {
    history.push('/settings/accesslevels');
  };

  return (
    <PageContainer style={{ background: 'white' }} loading={load}>
      {load ? (
        <Spin></Spin>
      ) : (
        <>
          <Row style={{ background: 'white' }}>
            <Form form={form} layout="vertical">
              <Form.Item data-key="roleType" name="role" label="Role Type">
                <Select
                  showSearch
                  style={{ width: 200 }}
                  placeholder="Select a role"
                  optionFilterProp="children"
                  onChange={handleOnRoleChange}
                  filterOption={(input, option: any) =>
                    option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                  }
                  disabled
                >
                  {roles.map((role) => {
                    return (
                      <Option key={role} value={role}>
                        {role}
                      </Option>
                    );
                  })}
                </Select>
              </Form.Item>

              <Form.Item
                data-key="roleName"
                name="title"
                label="Role Name"
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'roleName',
                      defaultMessage: 'Required.',
                    }),
                  },
                  {
                    max: 25,
                    message: intl.formatMessage({
                      id: 'roleName',
                      defaultMessage: 'Maximum length is 25 characters.',
                    }),
                  },
                ]}
              >
                <Input placeholder="Edit User Role" />
              </Form.Item>
            </Form>
          </Row>

          <Row style={{ height: 'auto', background: 'white' }}>
            {selectedRole && selectedRolePermissions.length > 0 ? (
              <Col span={8}>
                <h3>Other Actions and Permissions</h3>
                <Checkbox.Group
                  style={{ width: '100%' }}
                  onChange={handleOnChangePermissions}
                  value={permittedActionsCheckList}
                >
                  {selectedRolePermissions.map((actinPremission: any) => {
                    return (
                      <Row key={actinPremission.key}>
                        <Checkbox data-key={actinPremission.key} value={actinPremission.key}>{actinPremission.label}</Checkbox>
                      </Row>
                    );
                  })}
                </Checkbox.Group>
              </Col>
            ) : (
              <Col span={8}></Col>
            )}
            {selectedRole && !_.isEmpty(scopeAccess) ? (
              <Col span={8}>
                <h3>Scope of Access</h3>
                {selectedRole === 'MANAGER' ? (
                  <Checkbox data-key="direct" defaultChecked disabled>
                    Direct
                  </Checkbox>
                ) : null}
                <Checkbox.Group
                  style={{ width: '100%' }}
                  value={selectedScopes}
                  onChange={handleOnChangeScope}
                >
                  {scopeAccess.map((scope: any) => {
                    return selectedRole === 'MANAGER' ? (
                      <>
                        {scope.scopeDataList.map((actinPremission: any) => {
                          return (
                            <Row key={actinPremission.key}>
                              <Checkbox data-key={actinPremission.key} value={actinPremission.key}>
                                {actinPremission.value}
                              </Checkbox>
                            </Row>
                          );
                        })}
                      </>
                    ) : (
                      <Collapse defaultActiveKey={['1', '2', '3', '4']} ghost>
                        <Panel header={_.startCase(scope.scopeName)} key="1">
                          {scope.scopeDataList.map((actinPremission: any) => {
                            return (
                              <Row key={actinPremission.key}>
                                <Checkbox data-key={actinPremission.key} value={actinPremission.key}>
                                  {actinPremission.value}
                                </Checkbox>
                              </Row>
                            );
                          })}
                        </Panel>
                      </Collapse>
                    );
                  })}
                </Checkbox.Group>
              </Col>
            ) : (
              <Col span={8}></Col>
            )}
            {selectedRole && workflowAccess.length > 0 ? (
              <Col span={8}>
                <h3>Workﬂow Management</h3>
                <Checkbox.Group
                  style={{ width: '100%' }}
                  onChange={handleOnWorkflowSelect}
                  value={selectedWorkflowAccess}
                >
                  {workflowAccess.map((workflow: any) => {
                    return (
                      <Row key={workflow.key}>
                        <Checkbox data-key={workflow.key} value={workflow.key}>{workflow.label}</Checkbox>
                      </Row>
                    );
                  })}
                </Checkbox.Group>
              </Col>
            ) : (
              <Col span={8}></Col>
            )}
          </Row>
          <Row style={{ marginTop: '2vh', marginBottom: '5vh' }}>
            <h3>Field Data Access Management</h3>
            <Col span={24}>
              <Tabs defaultActiveKey="1" tabPosition={'left'}>
                {fieldList.map((tab, id) => {
                  return (
                    <TabPane className={`${tab.key}-tab`} tab={tab.label} key={id + 1}>
                      <Collapse defaultActiveKey={['1']}>
                        {tab.sections.map((section, id) => {
                          return (
                            <Panel className={styles.panel} header={section.label} key={id + 1}>
                              <FieldTable
                                dataKey={section.key}
                                isCheckedAddEmployee={isCheckedAddEmployee}
                                fields={section.fields}
                                selectedRole={selectedRole}
                                fieldAccessLevels={fieldAccessLevels}
                                handleOnChangePermission={handleOnChangePermission}
                                handleOnSelectChange={handleOnBulkSelectChange}
                              />
                            </Panel>
                          );
                        })}
                      </Collapse>
                    </TabPane>
                  );
                })}
              </Tabs>
            </Col>
            <Divider></Divider>
          </Row>
          <Row style={{ background: 'white', paddingBottom: '10px' }}>
            <FooterToolbar>
              <Button data-key="back" type="primary" style={{ marginRight: '1vh' }} onClick={handleCancel}>
                {intl.formatMessage({
                  id: 'BACK',
                  defaultMessage: 'Back',
                })}
              </Button>
              <Button data-key="update" type="primary" style={{ marginRight: '1vh' }} onClick={handleAdd}>
                {intl.formatMessage({
                  id: 'UPDATE',
                  defaultMessage: 'Update',
                })}
              </Button>
            </FooterToolbar>
          </Row>
        </>
      )}
    </PageContainer>
  );
};

export default EditUserRole;
