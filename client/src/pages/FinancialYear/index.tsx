import React, { useState, useEffect } from 'react';
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { message, Popconfirm, Tooltip, Form, Row, Col, Space, Spin, Tag, Empty } from 'antd';
import request, { APIResponse } from '@/utils/request';
import { useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, useParams } from 'umi';
import PermissionDeniedPage from './../403';
import {
  financialYears,
  addFinancialYear,
  updateFinancialYear,
  removeFinancialYear,
} from '@/services/financialYear';
import { genarateEmptyValuesObject } from '@/utils/utils';
import CreateForm from './create';
import moment from 'moment';

const WorkflowApproverPool: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;
  //   const { id } = useParams();
  const intl = useIntl();
  const [model, setModel] = useState<any>();
  const [addApproverPoolFormVisible, setAddApproverPoolFormVisible] = useState(false);
  const [editApproverPoolFormVisible, setEditApproverPoolFormVisible] = useState(false);
  const [addApproverPoolFormReference] = Form.useForm();
  const [editUserFormReference] = Form.useForm();
  const [addApproverPoolFormChangedValue, setAddApproverPoolFormChangedValue] = useState({});
  const [editApproverPoolFormChangedValue, setEditApproverPoolFormChangedValue] = useState({});
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    if (!model) {
      getModel('financialYear').then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

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
    const key = {};
    key[fieldName] = [];
    editUserFormReference.setFieldsValue(key);
  };

  const addViewProps = {
    title: intl.formatMessage({
      id: `add_financial_year`,
      defaultMessage: `Add Financial Year Configuration`,
    }),
    key: `add_financial_year`,
    visible: addApproverPoolFormVisible,
    onVisibleChange: setAddApproverPoolFormVisible,
    form: addApproverPoolFormReference,
    onValuesChange: setAddApproverPoolFormChangedValue,
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

      let params = {
        id: currentRecord.id,
        fromYearAndMonth: currentRecord.fromYearAndMonth.startOf('month').format('YYYY-MM-DD'),
        toYearAndMonth: currentRecord.toYearAndMonth.endOf('month').format('YYYY-MM-DD'),
        financialDateRangeString:
          currentRecord.fromYearAndMonth.format('MMMM YYYY') +
          ' / ' +
          currentRecord.toYearAndMonth.format('MMMM YYYY'),
        isSetAsDefault: currentRecord.isSetAsDefault,
      };

      await addFinancialYear(params)
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
                addApproverPoolFormReference.setFields([
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

          setRefresh((prev) => prev + 1);
          setAddApproverPoolFormVisible(false);
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
              <>{error.message}</>
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
              addApproverPoolFormReference.setFields([
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
      defaultMessage: `Edit Financial Year Configuration`,
    }),
    key: `edit_emp_grp`,
    visible: editApproverPoolFormVisible,
    onVisibleChange: setEditApproverPoolFormVisible,
    form: editUserFormReference,
    onValuesChange: setEditApproverPoolFormChangedValue,
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

      let params = {
        id: currentRecord.id,
        fromYearAndMonth: currentRecord.fromYearAndMonth.startOf('month').format('YYYY-MM-DD'),
        toYearAndMonth: currentRecord.toYearAndMonth.endOf('month').format('YYYY-MM-DD'),
        financialDateRangeString:
          currentRecord.fromYearAndMonth.format('MMMM YYYY') +
          ' / ' +
          currentRecord.toYearAndMonth.format('MMMM YYYY'),
        isSetAsDefault: currentRecord.isSetAsDefault,
      };

      await updateFinancialYear(params)
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
          setRefresh((prev) => prev + 1);
          // actionRef?.current?.reload();
          setEditApproverPoolFormVisible(false);
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
              <>{error.message}</>
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
      <Access accessible={hasPermitted('master-data-write')} fallback={<PermissionDeniedPage />}>
        <PageContainer>
          <BasicContainer
            rowId="id"
            titleKey="workflowEmployeeGroups"
            defaultTitle="Employee Groups"
            refresh={refresh}
            model={model}
            tableColumns={[
              { name: 'financialDateRangeString', sortable: true, filterable: true },
              { name: 'isSetAsDefault', sortable: true, filterable: true },
            ]}
            recordActions={['add', 'edit', 'delete']}
            searchFields={['financialDateRangeString']}
            // disableSearch = {true}
            addFormType="function"
            editFormType="function"
            getAllFunction={financialYears}
            addFunction={async () => {
              const intialValues = genarateEmptyValuesObject(model);
              setCurrentRecord(intialValues);
              setAddApproverPoolFormVisible(true);
            }}
            editFunction={async (record) => {
              // const intialValues = genarateEmptyValuesObject(model);
              console.log(record['isSetAsDefault']);
              record['fromYearAndMonth'] = moment(
                record['fromYearAndMonth'],
                'YYYY-MM-DD',
              ).isValid()
                ? moment(record['fromYearAndMonth'], 'YYYY-MM-DD')
                : null;
              record['toYearAndMonth'] = moment(record['toYearAndMonth'], 'YYYY-MM-DD').isValid()
                ? moment(record['toYearAndMonth'], 'YYYY-MM-DD')
                : null;
              record['isSetAsDefault'] =
                record['isSetAsDefault'] == 'Yes' || record['isSetAsDefault'] == true
                  ? true
                  : false;

              setCurrentRecord(record);
              setEditApproverPoolFormVisible(true);
            }}
            deleteFunction={removeFinancialYear}
            permissions={{
              addPermission: 'workflow-management-read-write',
              editPermission: 'workflow-management-read-write',
              deletePermission: 'workflow-management-read-write',
              readPermission: 'workflow-management-read-write',
            }}
          />
        </PageContainer>
      </Access>

      <ModalForm
        modalProps={{
          destroyOnClose: true,
        }}
        {...addViewProps}
      >
        <CreateForm
          model={model}
          isEditView={false}
          emptySwitch={emptySwitch}
          values={currentRecord}
          setValues={setCurrentRecord}
          addGroupFormVisible={addApproverPoolFormVisible}
          editGroupFormVisible={editApproverPoolFormVisible}
          form={addApproverPoolFormReference}
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
          isEditView={true}
          emptySwitch={emptySwitch}
          values={currentRecord}
          setValues={setCurrentRecord}
          addGroupFormVisible={addApproverPoolFormVisible}
          editGroupFormVisible={editApproverPoolFormVisible}
          form={addApproverPoolFormReference}
        ></CreateForm>
      </DrawerForm>
    </>
  );
};

export default WorkflowApproverPool;
