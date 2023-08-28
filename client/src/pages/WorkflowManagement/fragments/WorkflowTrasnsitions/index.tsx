import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    queryStateTransitionData,
    addStateTransitionData,
    updateStateTransition,
    removeStateTransition
} from '@/services/workflowServices'
import { queryDefineData } from '@/services/workflowServices'
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { Select } from 'antd';
import { genarateEmptyValuesObject } from '@/utils/utils';
import request, { APIResponse } from '@/utils/request';
import { message, Popconfirm, Tooltip, Form, Row, Col, Space, Spin, Tag, Empty, Button } from 'antd';
import { useIntl } from 'react-intl';
import TransitionForm from './transitionForm';

const StateTransition: React.FC = () => {

    const intl = useIntl();
    const [workflow, setWorkflow] = useState([]);
    const [workflowId, setWorkflowId] = useState([])
    const [refresh,setRefresh]=useState(false)
    const [refreshTable,setRefreshTable]=useState(0);
    const [currentRecord, setCurrentRecord] = useState<any>();
    const [addTransitionFormVisible, setAddTransitionFormVisible] = useState(false);
    const [editTransitionFormVisible, setEditTransitionFormVisible] = useState(false);
    const [viewTransitionFormVisible, setViewTransitionFormVisible] = useState(false);
    const [addTransitionFormReference] = Form.useForm();
    const [editTransitionFormReference] = Form.useForm();
    const [viewTransitionFormReference] = Form.useForm();
    const [addTransitionFormChangedValue, setAddTransitionFormChangedValue] = useState({});
    const [editTransitionFormChangedValue, setEditTransitionFormChangedValue] = useState({});
    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.WorkflowTransitions).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })
    const getWorkflows = async () => {
        const workFlows: any = []
        queryDefineData({}).then((data) => {

            data.data.forEach(async (element: any) => {
                await workFlows.push({ value: element.id, label: element.workflowName });

            });
            setWorkflow(workFlows)
            return workFlows
        })
    }
    const convertTagObject = (record) => {
        const convRecord = {};
        for (const key in record) {
          convRecord[key] = record[key];
        }
        return convRecord;
    };

    const addViewProps = {
        title: intl.formatMessage({
          id: `add_state_transition`,
          defaultMessage: `Add State Transitions`,
        }),
        key: `add_state_transition`,
        visible: addTransitionFormVisible,
        onVisibleChange: setAddTransitionFormVisible,
        form: addTransitionFormReference,
        onValuesChange: setAddTransitionFormChangedValue,
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
          
        await addStateTransitionData(currentRecord)
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
                    addTransitionFormReference.setFields([
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
    
              setRefreshTable(prev => prev + 1);
              setAddTransitionFormVisible(false)
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
                  addTransitionFormReference.setFields([
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
          defaultMessage: `Edit State Transitions`,
        }),
        key: `edit_emp_grp`,
        visible: editTransitionFormVisible,
        onVisibleChange: setEditTransitionFormVisible,
        form: editTransitionFormReference,
        onValuesChange: setEditTransitionFormChangedValue,
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
    
          await updateStateTransition(currentRecord)
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
                    editTransitionFormReference.setFields([
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
              setRefreshTable(prev => prev + 1);
              // actionRef?.current?.reload();
              setEditTransitionFormVisible(false);
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
                  editTransitionFormReference.setFields([
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

    const viewFormProps = {
      title: intl.formatMessage({
        id: `view_emp_grp`,
        defaultMessage: `View State Transitions`,
      }),
      key: `view_emp_grp`,
      visible: viewTransitionFormVisible,
      onVisibleChange: setViewTransitionFormVisible,
      form: viewTransitionFormReference,
      onValuesChange: setEditTransitionFormChangedValue,
      submitter: {
        render: (props, doms) => {
          return [
            <Button key="cancel" size="middle" onClick={() => {
              setViewTransitionFormVisible(false);
            }} >
                Cancel
            </Button>
          ]
        }
      },
      initialValues: convertTagObject(currentRecord),
  };

    

    return (
        <>
            <BasicContainer
                rowId='id'
                titleKey='StateTransitions'
                defaultTitle='State Transitions'
                model={model}
                refresh={refreshTable}
                tableColumns={[
                    { name: 'workflow', sortable: false, filterable: false },
                    { name: 'action', sortable: true, filterable: true },
                    { name: 'priorState', sortable: true, filterable: true },
                    { name: 'postState', sortable: true, filterable: true },
                    { name: 'permissionType', sortable: true, filterable: true },
                ]}
                recordActions={[
                    'add',
                    'edit',
                    'delete',
                    'view'
                ]}
                addFormType='function'
                editFormType='function'
                getAllFunction={queryStateTransitionData}
                addFunction={async () => {
                    const intialValues = genarateEmptyValuesObject(model);
                    intialValues['permittedRoles'] = [];
                    setCurrentRecord(intialValues);
                    setAddTransitionFormVisible(true);
                    
                }}
                editFunction={async (record) => {
                    // const intialValues = genarateEmptyValuesObject(model);
                    setCurrentRecord(record);
                    setEditTransitionFormVisible(true);
                }}

                viewFunction={async (record) => {
                    // const intialValues = genarateEmptyValuesObject(model);
                    setCurrentRecord(record);
                    setViewTransitionFormVisible(true);
                }}
                deleteFunction={removeStateTransition
                }
                disableSearch={true}
                toolbarFilter={true}
                toolbarFilterId={workflowId}
                toolbarFilterFunction={
                    <Select
                        style={{ width: 200 }}
                        placeholder="Please select"
                        allowClear

                        onChange={(e: any) => { 
                            setRefresh(true)
                            setWorkflowId(e) 
                            setRefresh(false)}}
                        onClick={() => { getWorkflows() }}
                        options={workflow}
                    />}
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

                <TransitionForm 
                model={model}
                isEdit= {false}
                isEditView = {false} 
                values={currentRecord} 
                setValues={setCurrentRecord} 
                addGroupFormVisible={addTransitionFormVisible} 
                editGroupFormVisible = {editTransitionFormVisible} 
                form= {addTransitionFormReference}
                ></TransitionForm>
                
            </ModalForm>

            <DrawerForm
            drawerProps={{
                destroyOnClose: true,
            }}
            width="40vw"
            {...editViewProps}
            >
                <TransitionForm 
                model={model}
                isEdit= {true}
                isEditView = {false} 
                values={currentRecord} 
                setValues={setCurrentRecord} 
                addGroupFormVisible={addTransitionFormVisible} 
                editGroupFormVisible = {editTransitionFormVisible} 
                form= {editTransitionFormReference}
                ></TransitionForm>
            </DrawerForm>


            <DrawerForm
            drawerProps={{
                destroyOnClose: true,
            }}
            width="40vw"
            {...viewFormProps}
            >
                <TransitionForm 
                model={model}
                isEdit= {true}
                isEditView = {false} 
                values={currentRecord} 
                setValues={setCurrentRecord} 
                addGroupFormVisible={addTransitionFormVisible} 
                editGroupFormVisible = {editTransitionFormVisible} 
                form= {viewTransitionFormReference}
                ></TransitionForm>
            </DrawerForm>

            
        </>
    ) 
}

export default StateTransition