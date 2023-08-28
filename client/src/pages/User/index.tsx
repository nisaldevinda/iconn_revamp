import React, { useEffect, useState } from 'react';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import { addUser, getAllUser, removeUser, updateUser, resetPasswordUser, changeActiveStatus } from '@/services/user';
import { message, Popconfirm, Tooltip, Form, Row, Col, Space, Spin, Tag } from 'antd';
import { SwapOutlined, SyncOutlined } from '@ant-design/icons';
import { useIntl } from 'react-intl';
import request, { APIResponse } from '@/utils/request';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { genarateEmptyValuesObject } from '@/utils/utils';
import { ProFormText } from "@ant-design/pro-form";
import { generateProFormFieldValidation } from "@/utils/validator";
import CreateForm from './createUser';
import EditForm from './editUser';

const MemberList: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const [model, setModel] = useState<any>();
  const [unAssignEmployees, setUnAssignEmployees] = useState<any>();
  const [addUserFormVisible, setAddUserFormVisible] = useState(false);
  const [editUserFormVisible, setEditUserFormVisible] = useState(false);
  const [addUserFormChangedValue, setAddUserFormChangedValue] = useState({});
  const [editUserFormChangedValue, setEditUserFormChangedValue] = useState({});
  const [addUserFormReference] = Form.useForm();
  const [editUserFormReference] = Form.useForm();
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [isEmployeeSelect, setIsEmployeeSelect] = useState<boolean>(false);
  const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
  const [unesignEmployeeSet, setUnesignEmployeeSet] = useState<undefined | Array<{ label: string, value: string }>>(undefined);
  const [employeeRoleSet, setEmployeeRoleSet] = useState<undefined | Array<{ label: string, value: string }>>(undefined);
  const [managerRoleSet, setManagerRoleSet] = useState<undefined | Array<{ label: string, value: string }>>(undefined);
  const [adminRoleSet, setAdminRoleSet] = useState<undefined | Array<{ label: string, value: string }>>(undefined);
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel(Models.User).then((response) => {
        const userModel = response.data;
        if (!hasGlobalAdminPrivileges()) {
          // remove admin role field & relation if not a global admin
          setIsGlobalAdmin(false);
          delete userModel.modelDataDefinition.fields.adminRole;
          delete userModel.modelDataDefinition.relations.adminRole;
        } else {
          setIsGlobalAdmin(true);
        }
        setModel(userModel);
      })
    }
  }, []);



  const addViewProps = {
    title: intl.formatMessage({
      id: `add_user`,
      defaultMessage: `Add User`,
    }),
    key: `add_user`,
    visible: addUserFormVisible,
    onVisibleChange: setAddUserFormVisible,
    form: addUserFormReference,
    onValuesChange: setAddUserFormChangedValue,
    submitter: {
      searchConfig: {
        submitText: intl.formatMessage({
          id: 'add',
          defaultMessage: 'Add',
        }),
        resetText: intl.formatMessage({
          id: 'cancel',
          defaultMessage: 'Cancel',
        }),
      },
    },
    onFinish: async () => {
      const key = 'saving';
      message.loading({
        content: intl.formatMessage({
          id: 'saving',
          defaultMessage: 'Saving...',
        }),
        key,
      });
      
      await addUser(convertTagString(currentRecord))
        .then((response: APIResponse) => {
          if (response.error) {
            message.error({
              content:
                response.message ??
                intl.formatMessage({
                  id: 'failedToSave',
                  defaultMessage: 'Cannot Save',
                }),
              key,
            });
            if (response.data && Object.keys(response.data).length !== 0) {
              for (const feildName in response.data) {
                const errors = response.data[feildName];
                addUserFormReference.setFields([
                  {
                    name: feildName,
                    errors: errors,
                  },
                ]);
              }
            }
            return;
          }

          message.success({
            content:
              response.message ??
              intl.formatMessage({
                id: 'successfullySaved',
                defaultMessage: 'Successfully Saved',
              }),
            key,
          });

          setRefresh(prev => prev + 1);
          setAddUserFormVisible(false);
        })

        .catch((error: APIResponse) => {
          let errorMessage;
          let errorMessageInfo;
          if (error.message.includes('.')) {
            let errorMessageData = error.message.split('.');
            errorMessage = errorMessageData.slice(0, 1);
            errorMessageInfo = errorMessageData.slice(1).join('.');
          }
          message.error({
            content: error.message ? (
              <>
                {errorMessage ?? error.message}
                <br />
                <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                  {errorMessageInfo ?? ''}
                </span>
              </>
            ) : (
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              })
            ),
            key,
          });
          if (error && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              addUserFormReference.setFields([
                {
                  name: feildName,
                  errors: errors,
                },
              ]);
            }
          }
        });
    },
  };

  const convertTagObject = (record) => {
    const convRecord = {};
    for (const key in record) {
      convRecord[key] = record[key];
      // if (hasJsonStructure(record[key])) {
      //   convRecord[key] = JSON.parse(record[key]);
      // } else convRecord[key] = record[key];
    }
    return convRecord;
  };

  const convertTagString = (record) => {
    const convRecord = {};
    for (const key in record) {
      if (_.isArray(record[key])) {
        convRecord[key] = JSON.stringify(record[key]);
      } else convRecord[key] = record[key];
    }
    return convRecord;
  };

  const getRules = (fieldName:any) => {
    if (addUserFormVisible || editUserFormVisible) {

      return generateProFormFieldValidation(
        model.modelDataDefinition.fields[fieldName],
        'user',
        fieldName,
        currentRecord
      );
    } else {
      return [];
    }
    
  }


  const getUnAssignEmployeeList = async (employeeId: any) => {
    let path: string = `/api/get-unassigned-employees-list`;
    const params = {};
    if (!_.isEmpty(model.modelDataDefinition.fields['employee'].modelFilters) && _.isObject(model.modelDataDefinition.fields['employee'].modelFilters)) {
      params['filter'] = model.modelDataDefinition.fields['employee'].modelFilters;
    }

    if (employeeId) {
      params['employeeId'] = employeeId;
    }

    try {
      request(path, { params }).then((response: APIResponse) => {
        if (response && response.data && Array.isArray(response.data)) {
          setUnAssignEmployees(response.data); 
          const dataSet = response.data?.map(data => {
            return {
              label: data[model.modelDataDefinition.fields['employee'].enumLabelKey],
              value: data[model.modelDataDefinition.fields['employee'].enumValueKey]
            };
          });
          setUnesignEmployeeSet(dataSet);
        }
      });
    } catch (error) {
      setUnesignEmployeeSet(undefined);
    }
    
  }

  const getEmployeeRoleList = async () => {
    let path: string = `/api/userRoles`;
    const params = {};
    if (!_.isEmpty(model.modelDataDefinition.fields['employeeRole'].modelFilters) && _.isObject(model.modelDataDefinition.fields['employeeRole'].modelFilters)) {
      params['filter'] = model.modelDataDefinition.fields['employeeRole'].modelFilters;
    }

    try {
      request(path, { params }).then((response: APIResponse) => {
        if (response && response.data && Array.isArray(response.data)) {
          const dataSet = response.data?.map(data => {
            return {
              label: data[model.modelDataDefinition.fields['employeeRole'].enumLabelKey],
              value: data[model.modelDataDefinition.fields['employeeRole'].enumValueKey]
            };
          });
          setEmployeeRoleSet(dataSet);
        }
      });
    } catch (error) {
      setEmployeeRoleSet(undefined);
    }
    
  }


  const getManagerRoleList = async () => {
    let path: string = `/api/userRoles`;
    const params = {};
    if (!_.isEmpty(model.modelDataDefinition.fields['managerRole'].modelFilters) && _.isObject(model.modelDataDefinition.fields['managerRole'].modelFilters)) {
      params['filter'] = model.modelDataDefinition.fields['managerRole'].modelFilters;
    }

    try {
      request(path, { params }).then((response: APIResponse) => {
        if (response && response.data && Array.isArray(response.data)) {
          const dataSet = response.data?.map(data => {
            return {
              label: data[model.modelDataDefinition.fields['managerRole'].enumLabelKey],
              value: data[model.modelDataDefinition.fields['managerRole'].enumValueKey]
            };
          });
          setManagerRoleSet(dataSet);
        }
      });
    } catch (error) {
      setManagerRoleSet(undefined);
    }
    
  }


  const getAdminRoleList = async () => {
    let path: string = `/api/userRoles`;
    const params = {};
    if (!_.isEmpty(model.modelDataDefinition.fields['adminRole'].modelFilters) && _.isObject(model.modelDataDefinition.fields['adminRole'].modelFilters)) {
      params['filter'] = model.modelDataDefinition.fields['adminRole'].modelFilters;
    }

    try {
      request(path, { params }).then((response: APIResponse) => {
        if (response && response.data && Array.isArray(response.data)) {
          const dataSet = response.data?.map(data => {
            return {
              label: data[model.modelDataDefinition.fields['adminRole'].enumLabelKey],
              value: data[model.modelDataDefinition.fields['adminRole'].enumValueKey]
            };
          });
          setAdminRoleSet(dataSet);
        }
      });
    } catch (error) {
      setAdminRoleSet(undefined);
    }
    
  }

  const editViewProps = {
    title: intl.formatMessage({
      id: `edit_user`,
      defaultMessage: `Edit User`,
    }),
    key: `edit_user`,
    visible: editUserFormVisible,
    onVisibleChange: setEditUserFormVisible,
    form: editUserFormReference,
    onValuesChange: setEditUserFormChangedValue,
    submitter: {
      searchConfig: {
        submitText: intl.formatMessage({
          id: 'update',
          defaultMessage: 'Update',
        }),
        resetText: intl.formatMessage({
          id: 'cancel',
          defaultMessage: 'Cancel',
        }),
      },
    },
    onFinish: async () => {
      const key = 'updating';
      message.loading({
        content: intl.formatMessage({
          id: 'updating',
          defaultMessage: 'Updating...',
        }),
        key,
      });

      let formData = {

      }

      await updateUser(convertTagString(currentRecord))
        .then((response: APIResponse) => {
          if (response.error) {
            message.error({
              content:
                response.message ??
                intl.formatMessage({
                  id: 'failedToUpdate',
                  defaultMessage: 'Failed to Update',
                }),
              key,
            });
            if (response.data && Object.keys(response.data).length !== 0) {
              for (const feildName in response.data) {
                const errors = response.data[feildName];
                editUserFormReference.setFields([
                  {
                    name: feildName,
                    errors: errors,
                  },
                ]);
              }
            }
            return;
          }

          message.success({
            content:
              response.message ??
              intl.formatMessage({
                id: 'successfullyUpdated',
                defaultMessage: 'Successfully Updated',
              }),
            key,
          });
          setRefresh(prev => prev + 1);
          // actionRef?.current?.reload();
          setEditUserFormVisible(false);
        })

        .catch((error: APIResponse) => {
          let errorMessage;
          let errorMessageInfo;
          if (error.message.includes('.')) {
            let errorMessageData = error.message.split('.');
            errorMessage = errorMessageData.slice(0, 1);
            errorMessageInfo = errorMessageData.slice(1).join('.');
          }

          message.error({
            content: error.message ? (
              <>
                {errorMessage ?? error.message}
                <br />
                <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                  {errorMessageInfo ?? ''}
                </span>
              </>
            ) : (
              intl.formatMessage({
                id: 'failedToUpdate',
                defaultMessage: 'Cannot Update',
              })
            ),
            key,
          });
          if (error.data && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              editUserFormReference.setFields([
                {
                  name: feildName,
                  errors: errors,
                },
              ]);
            }
          }
        });
    },
    initialValues: convertTagObject(currentRecord),
  };

  return (
    <PageContainer>
      <BasicContainer
        rowId="id"
        titleKey="user"
        refresh={refresh}
        defaultTitle="User"
        model={model}
        tableColumns={[
          { name: 'employeeName', sortable: true },
          { name: 'email' },
          {
            name: 'inactive',
            filterable: true,
            valueEnum: {
              0: {
                text: intl.formatMessage({
                  id: 'active',
                  defaultMessage: 'Active',
                }),
                status: 'Success',
              },
              1: {
                text: intl.formatMessage({
                  id: 'Inactive',
                  defaultMessage: 'Inactive',
                }),
                status: 'Error',
              },
            },
          },
        ]}
        recordActions={[
          'add',
          'edit',
          (record, tableRef) => (
            <Access accessible={hasPermitted('user-read-write')}>
              <div onClick={(e) => e.stopPropagation()}>
                <Popconfirm
                  key="passwordResettingRecordConfirm"
                  title={intl.formatMessage({
                    id: 'are_you_sure',
                    defaultMessage: 'Are you sure?',
                  })}
                  onConfirm={async () => {
                    const key = 'passwordResetting';
                    message.loading({
                      content: intl.formatMessage({
                        id: 'passwordResetting',
                        defaultMessage: 'Password Resetting...',
                      }),
                      key,
                    });

                    await resetPasswordUser(record.id)
                      .then((response: APIResponse) => {
                        if (response.error) {
                          message.error({
                            content:
                              response.message ??
                              intl.formatMessage({
                                id: 'resetPasswordFailed',
                                defaultMessage: 'Reset password failed',
                              }),
                            key,
                          });
                          return;
                        }

                        message.success({
                          content:
                            response.message ??
                            intl.formatMessage({
                              id: 'passwordResetSuccessfully',
                              defaultMessage: 'Password reset successfully',
                            }),
                          key,
                        });

                        tableRef.reload();
                      })

                      .catch((error: APIResponse) => {
                        message.error({
                          content:
                            error.message ??
                            intl.formatMessage({
                              id: 'resetPasswordFailed',
                              defaultMessage: 'Reset password failed',
                            }),
                          key,
                        });
                      });
                  }}
                  okText="Yes"
                  cancelText="No"
                >
                  <Tooltip
                    placement={'bottom'}
                    title={intl.formatMessage({
                      id: 'password_reset',
                      defaultMessage: 'Password Reset',
                    })}
                  >
                    <a key="passwordResetButton">
                      <SyncOutlined />
                    </a>
                  </Tooltip>
                </Popconfirm>
              </div>
            </Access>
          ),
          (record, tableRef) => (
            <Access accessible={hasPermitted('user-read-write')}>
              <div onClick={(e) => e.stopPropagation()}>
                <Popconfirm
                  key="passwordResettingRecordConfirm"
                  title={intl.formatMessage({
                    id: 'are_you_sure',
                    defaultMessage: 'Are you sure?',
                  })}
                  onConfirm={async () => {
                    const key = 'changingActiveStatus';
                    message.loading({
                      content: intl.formatMessage({
                        id: 'changingActiveStatus',
                        defaultMessage: 'Changing Active/Inactive Status...',
                      }),
                      key,
                    });

                    record.inactive = !record.inactive;

                    await changeActiveStatus(record)
                      .then((response: APIResponse) => {
                        if (response.error) {
                          message.error({
                            content:
                              response.message ??
                              intl.formatMessage({
                                id: 'changeActiveStatusFailed',
                                defaultMessage: 'Change active/inactive status failed',
                              }),
                            key,
                          });
                          return;
                        }

                        message.success({
                          content:
                            response.message ??
                            intl.formatMessage({
                              id: 'changeActiveStatusSuccessfully',
                              defaultMessage: 'Change active/inactive status successfully',
                            }),
                          key,
                        });

                        tableRef.reload();
                      })

                      .catch((error: APIResponse) => {
                        message.error({
                          content:
                            error.message ??
                            intl.formatMessage({
                              id: 'changeActiveStatusFailed',
                              defaultMessage: 'Change active/inactive status failed',
                            }),
                          key,
                        });
                      });
                  }}
                  okText="Yes"
                  cancelText="No"
                >
                  <Tooltip
                    placement={'bottomRight'}
                    title={intl.formatMessage({
                      id: 'change_active_inactive_status',
                      defaultMessage: 'Change Active/Inactive Status',
                    })}
                  >
                    <a key="passwordResetButton">
                      <SwapOutlined />
                    </a>
                  </Tooltip>
                </Popconfirm>
              </div>
            </Access>
          ),
        ]}
        // readableFields = {['fullName']}
        // editableFields = {['email']}
        searchFields= {['email', 'employeeName']}
        defaultSortableField = {{
          fildName: 'employeeName',
          mode: 'ascend'
        }}
        
        addFormType='function'
        editFormType='function'
        getAllFunction={getAllUser}
        addFunction={async () => {
          setIsEmployeeSelect(false);
          await getManagerRoleList();
          await getEmployeeRoleList();

          if (isGlobalAdmin) {
            await getAdminRoleList();
          }

          const intialValues = genarateEmptyValuesObject(model);
          setCurrentRecord(intialValues);
          await getUnAssignEmployeeList(null);
          setAddUserFormVisible(true);
          
        }}
        editFunction={async (record) => {

          if (record['employeeId']) {
            setIsEmployeeSelect(true);
          } else {
            setIsEmployeeSelect(false);
          }
  
          await getUnAssignEmployeeList(record['employeeId']);

          await getManagerRoleList();
          await getEmployeeRoleList();

          if (isGlobalAdmin) {
            await getAdminRoleList();
          }

          const intialValues = genarateEmptyValuesObject(model);
          setCurrentRecord({ intialValues, ...record });
          setEditUserFormVisible(true);
        }}
        deleteFunction={removeUser}
        permissions={{
          addPermission: 'user-read-write',
          editPermission: 'user-read-write',
          deletePermission: 'user-read-write',
          readPermission: 'user-read-write',
        }}
      />


      <ModalForm
          modalProps={{
            destroyOnClose: true,
          }}
          {...addViewProps}
      >

        <CreateForm 
          model={model} 
          values={currentRecord} 
          setValues={setCurrentRecord} 
          addUserFormVisible={addUserFormVisible} 
          editUserFormVisible = {editUserFormVisible} 
          employees={unesignEmployeeSet} 
          employeeRoles = {employeeRoleSet} 
          managerRoles = {managerRoleSet} 
          adminRoles = {adminRoleSet}
          employeeChanged = {isEmployeeSelect}
          setEmployeeChange = {setIsEmployeeSelect}
          form= {addUserFormReference}
          unAssignEmployees={unAssignEmployees}
        ></CreateForm>
        
      </ModalForm>

      <DrawerForm
          drawerProps={{
            destroyOnClose: true,
          }}
          width="40vw"
          {...editViewProps}
        >
          <EditForm 
            model={model} values={currentRecord} 
            setValues={setCurrentRecord} 
            addUserFormVisible={addUserFormVisible} 
            editUserFormVisible = {editUserFormVisible} 
            employees={unesignEmployeeSet} 
            employeeRoles = {employeeRoleSet} 
            managerRoles = {managerRoleSet} 
            adminRoles = {adminRoleSet}
            employeeChanged = {isEmployeeSelect}
            setEmployeeChange = {setIsEmployeeSelect}
            form= {editUserFormReference}
            unAssignEmployees={unAssignEmployees}
          ></EditForm>
        </DrawerForm>
    </PageContainer>
  );
};

export default MemberList;
