import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { message, Popconfirm, Tooltip, Form, Row, Col, Space, Spin, Tag, Empty } from 'antd';
import request, { APIResponse } from '@/utils/request';
import { useIntl } from 'react-intl';
import {
    queryContextData,
    addContextData,
    updateContext,
    removeContext,
    workflowEmployeeGroups,
    addWorkflowEmployeeGroup,
    updateWorkflowEmployeeGroup,
    removeWorkflowEmployeeGroup
} from '@/services/workflowServices'
import { genarateEmptyValuesObject } from '@/utils/utils';
import CreateForm from './createGroup';


const WorkflowEmployeeGroup: React.FC = () => {

    const intl = useIntl();
    const [model, setModel] = useState<any>();
    const [addEmpGroupFormVisible, setAddEmpGroupFormVisible] = useState(false);
    const [editEmpGroupFormVisible, setEditEmpGroupFormVisible] = useState(false);
    const [addEmpGroupFormReference] = Form.useForm();
    const [editUserFormReference] = Form.useForm();
    const [addEmpGroupFormChangedValue, setAddEmpGroupFormChangedValue] = useState({});
    const [editEmpGroupFormChangedValue, setEditEmpGroupFormChangedValue] = useState({});
    const [currentRecord, setCurrentRecord] = useState<any>();
    const [refresh, setRefresh] = useState(0);



    useEffect(() => {
        if (!model) {
            getModel('workflowEmployeeGroup').then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    const convertTagString = (record) => {
        const convRecord = {};
        for (const key in record) {
            if (_.isArray(record[key])) {
            convRecord[key] = JSON.stringify(record[key]);
            } else convRecord[key] = record[key];
        }
        return convRecord;
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
    const emptySwitch = (fieldName) => {
        const key = {}
        key[fieldName] = [];
        editUserFormReference.setFieldsValue(key);
    };

    const addViewProps = {
        title: intl.formatMessage({
          id: `add_emp_group`,
          defaultMessage: `Add Employee Group`,
        }),
        key: `add_emp_group`,
        visible: addEmpGroupFormVisible,
        onVisibleChange: setAddEmpGroupFormVisible,
        form: addEmpGroupFormReference,
        onValuesChange: setAddEmpGroupFormChangedValue,
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
        //   console.log(currentRecord);
        //   return;
          
          await addWorkflowEmployeeGroup(currentRecord)
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
                    addEmpGroupFormReference.setFields([
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
              setAddEmpGroupFormVisible(false);
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
                  addEmpGroupFormReference.setFields([
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

    const editViewProps = {
        title: intl.formatMessage({
          id: `edit_emp_grp`,
          defaultMessage: `Edit Employee Group`,
        }),
        key: `edit_emp_grp`,
        visible: editEmpGroupFormVisible,
        onVisibleChange: setEditEmpGroupFormVisible,
        form: editUserFormReference,
        onValuesChange: setEditEmpGroupFormChangedValue,
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
    
        //   console.log(currentRecord);
        //   return;
          await updateWorkflowEmployeeGroup(currentRecord)
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
              setEditEmpGroupFormVisible(false);
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
        <>
            <BasicContainer
                rowId="id"
                titleKey="workflowEmployeeGroups"
                defaultTitle="Employee Groups"
                refresh={refresh}
                model={model}
                tableColumns={[
                    { name: 'name', sortable: true, filterable: true },
                    { name: 'comment', sortable: false, filterable: false },
                    { name: 'context', sortable: false, filterable: false },
                  ]}
                recordActions={['add', 'edit', 'delete']}
                searchFields={['name']}
                addFormType='function'
                editFormType='function'
                getAllFunction={workflowEmployeeGroups}
                addFunction={async () => {
                    const intialValues = genarateEmptyValuesObject(model);
                    setCurrentRecord(intialValues);
                    setAddEmpGroupFormVisible(true);
                    
                }}
                editFunction={async (record) => {
                    // const intialValues = genarateEmptyValuesObject(model);
                    record['context'] = record['contextId'];
                    setCurrentRecord(record);
                    setEditEmpGroupFormVisible(true);
                }}
                deleteFunction={removeWorkflowEmployeeGroup}
                permissions={{
                addPermission: 'workflow-management-read-write',
                editPermission: 'workflow-management-read-write',
                deletePermission: 'workflow-management-read-write',
                readPermission: 'workflow-management-read-write',
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
                isEditView = {false} 
                emptySwitch = {emptySwitch}
                values={currentRecord} 
                setValues={setCurrentRecord} 
                addGroupFormVisible={addEmpGroupFormVisible} 
                editGroupFormVisible = {editEmpGroupFormVisible} 
                form= {addEmpGroupFormReference}
                ></CreateForm>
                
            </ModalForm>

            <DrawerForm
            drawerProps={{
                destroyOnClose: true,
            }}
            width="40vw"
            {...editViewProps}
            >
            <CreateForm 
                model={model} 
                isEditView = {true} 
                emptySwitch = {emptySwitch}
                values={currentRecord} 
                setValues={setCurrentRecord} 
                addGroupFormVisible={addEmpGroupFormVisible} 
                editGroupFormVisible = {editEmpGroupFormVisible} 
                form= {addEmpGroupFormReference}
                ></CreateForm>
            </DrawerForm>
        </>
    );
}

export default WorkflowEmployeeGroup