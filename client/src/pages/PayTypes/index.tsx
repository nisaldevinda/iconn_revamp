import React, { useEffect, useState } from 'react';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import { addPayType, getAllPayTypes, removePayType, updatePayType} from '@/services/payType';
import { message, Popconfirm, Tooltip, Form, Row, Col, Space, Spin, Tag, Input, Button} from 'antd';
import { SwapOutlined, SyncOutlined } from '@ant-design/icons';
import { useIntl } from 'react-intl';
import request, { APIResponse } from '@/utils/request';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { genarateEmptyValuesObject } from '@/utils/utils';
import { ProFormText } from "@ant-design/pro-form";
import { generateProFormFieldValidation } from "@/utils/validator";
import CreateForm from './create';
import EditForm from './edit';
import { CopyOutlined } from '@ant-design/icons';

const MemberList: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const [model, setModel] = useState<any>();
  const [addDayTypeFormVisible, setAddDayTypeFormVisible] = useState(false);
  const [editDayTypeFormVisible, setEditDayTypeFormVisible] = useState(false);
  const [addUserFormChangedValue, setAddDayTypeFormChangedValue] = useState({});
  const [editUserFormChangedValue, setEditDayTypeFormChangedValue] = useState({});
  const [addUserFormReference] = Form.useForm();
  const [editUserFormReference] = Form.useForm();
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    if (_.isEmpty(model)) {
        getModel('payType').then((response) => {
            const userModel = response.data;
            setModel(userModel);
        })

    }
  }, []);



  const addViewProps = {
    title: intl.formatMessage({
      id: `add_day_type`,
      defaultMessage: `Add Pay Type`,
    }),
    key: `add_day_type`,
    visible: addDayTypeFormVisible,
    onVisibleChange: setAddDayTypeFormVisible,
    form: addUserFormReference,
    onValuesChange: setAddDayTypeFormChangedValue,
    submitter: {
      searchConfig: {
        submitText: intl.formatMessage({
          id: 'add',
          defaultMessage: 'Save',
        }),
        resetText: intl.formatMessage({
          id: 'cancel',
          defaultMessage: 'Cancel',
        }),
      },
    },
    onFinish: async () => {
        const key = 'saving';

        if (currentRecord.typeColor) {
            let regex = /^#[0-9A-F]{6}$/i;

            if (!regex.test(currentRecord.typeColor)) {
                addUserFormReference.setFields([{
                        name: 'typeColor',
                        errors: ['Invalid hex value'] 
                    }
                ]);
                return;
            } else {
                addUserFormReference.setFields([{
                        name: 'typeColor',
                        errors: [] 
                    }
                ]);
            }
        }

        message.loading({
            content: intl.formatMessage({
                id: 'saving',
                defaultMessage: 'Saving...',
            }),
            key,
        });
      
        await addPayType(convertTagString(currentRecord))
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
            setAddDayTypeFormVisible(false);
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
    if (addDayTypeFormVisible || editDayTypeFormVisible) {

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

  const editViewProps = {
    title: intl.formatMessage({
      id: `edit_day_type`,
      defaultMessage: `Edit Pay Type`,
    }),
    key: `edit_day_type`,
    visible: editDayTypeFormVisible,
    onVisibleChange: setEditDayTypeFormVisible,
    form: editUserFormReference,
    onValuesChange: setEditDayTypeFormChangedValue,
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

        if (currentRecord.typeColor) {
            let regex = /^#[0-9A-F]{6}$/i;

            if (!regex.test(currentRecord.typeColor)) {
                addUserFormReference.setFields([{
                        name: 'typeColor',
                        errors: ['Invalid hex value'] 
                    }
                ]);
                return;
            } else {
                addUserFormReference.setFields([{
                        name: 'typeColor',
                        errors: [] 
                    }
                ]);
            }
        }

        message.loading({
        content: intl.formatMessage({
            id: 'updating',
            defaultMessage: 'Updating...',
        }),
        key,
        });

      
        await updatePayType(convertTagString(currentRecord)).then((response: APIResponse) => {
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
            setEditDayTypeFormVisible(false);
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
        titleKey="workCalendarDayType"
        refresh={refresh}
        defaultTitle="Day Types"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true,  },
          { name: 'code' },
          {
            name: 'type',
            valueEnum: {
                'GENERAL': 'General',
                'OVERTIME': 'Overtime',
              },
          },
          {
            name: 'rate',
          }
        ]}
        recordActions={[
          'add',
          'edit',
          'delete'
        ]}
        disableSearch={true}
        addFormType='function'
        editFormType='function'
        getAllFunction={getAllPayTypes}
        addFunction={async () => {
          const intialValues = genarateEmptyValuesObject(model);
          intialValues['type'] = 'GENERAL';
          setCurrentRecord(intialValues);
          setAddDayTypeFormVisible(true);
          
        }}
        editFunction={async (record) => {
            console.log(record);
          const intialValues = genarateEmptyValuesObject(model);
          setCurrentRecord({ intialValues, ...record });
          setEditDayTypeFormVisible(true);
        }}
        deleteFunction={removePayType}
        permissions={{
          addPermission: 'pay-type-read-write',
          editPermission: 'pay-type-read-write',
          deletePermission: 'pay-type-read-write',
          readPermission: 'pay-type-read-write',
        }}
      />


      <ModalForm
          width={550}
          modalProps={{
            destroyOnClose: true,
          }}
          {...addViewProps}
      >
        <CreateForm 
          model={model} 
          values={currentRecord} 
          setValues={setCurrentRecord} 
          addDayTypeFormVisible={addDayTypeFormVisible} 
          editDayTypeFormVisible = {editDayTypeFormVisible} 
          form= {addUserFormReference}
        ></CreateForm>
        
      </ModalForm>

      <DrawerForm
          drawerProps={{
            destroyOnClose: true,
          }}
          width="30vw"
          {...editViewProps}
        >
          <EditForm 
            model={model} values={currentRecord} 
            setValues={setCurrentRecord} 
            addDayTypeFormVisible={addDayTypeFormVisible} 
            editDayTypeFormVisible = {editDayTypeFormVisible} 
            form= {editUserFormReference}
            
          ></EditForm>
        </DrawerForm>
    </PageContainer>
  );
};

export default MemberList;
