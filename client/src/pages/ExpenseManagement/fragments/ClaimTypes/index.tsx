import React, { useState, useEffect } from 'react';
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import {
  message,
  Popconfirm,
  Tooltip,
  Form,
  Row,
  Col,
  Space,
  Spin,
  Tag,
  Empty,
  Typography,
} from 'antd';
import request, { APIResponse } from '@/utils/request';
import { useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, useParams } from 'umi';
import PermissionDeniedPage from '../../../403';
import {
  claimTypes,
  addClaimType,
  updateClaimType,
  removeClaimType,
} from '@/services/expenseModule';
import { genarateEmptyValuesObject } from '@/utils/utils';
import CreateForm from './create';
import moment from 'moment';
import { claimCategories } from '@/services/expenseModule';
import { number } from 'currency-codes';

export type ClaimTypeProps = {
  refresh?: number;
};

const WorkflowApproverPool: React.FC<ClaimTypeProps> = (props) => {
  const { Text } = Typography;
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
  const [claimCategoriesList, setClaimCategoriesList] = useState([]);

  useEffect(() => {
    if (!model) {
      getModel('claimType').then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

  useEffect(() => {
    setRefresh((prev) => prev + 1);
    getClaimCategories();
  }, [props.refresh]);

  useEffect(() => {
    getClaimCategories();
  }, []);

  const getClaimCategories = async () => {
    try {
      const actions: any = [];
      const { data } = await claimCategories({});
      const res = data.map((cat: any) => {
        actions.push({ value: cat.id, label: cat.name });
        return {
          label: cat.name,
          value: cat.id,
        };
      });
      setClaimCategoriesList(actions);
      return res;
    } catch (err) {
      console.log(err);
      return [];
    }
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
      id: `add_claim_type`,
      defaultMessage: `Add Claim Type`,
    }),
    key: `add_claim_type`,
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
        orgEntityId: currentRecord.orgEntityId,
        typeName: currentRecord.typeName,
        claimCategoryId: currentRecord.claimCategoryId,
        amountType: currentRecord.amountType,
        maxAmount: currentRecord.maxAmount ? String(currentRecord.maxAmount) : null,
        orderType: currentRecord.orderType,
        isAllowAttachment: currentRecord.isAllowAttachment,
        isAttachmentMandatory: currentRecord.isAttachmentMandatory,
        isAllocationEnable: currentRecord.isAllocationEnable,
      };

      await addClaimType(params)
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
      id: `edit_claim_type`,
      defaultMessage: `Edit Claim Type`,
    }),
    key: `edit_claim_type`,
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
        orgEntityId: currentRecord.orgEntityId,
        typeName: currentRecord.typeName,
        claimCategoryId: currentRecord.claimCategoryId,
        amountType: currentRecord.amountType,
        maxAmount: currentRecord.maxAmount ? String(currentRecord.maxAmount) : null,
        orderType: currentRecord.orderType,
        isAllowAttachment: currentRecord.isAllowAttachment,
        isAttachmentMandatory: currentRecord.isAttachmentMandatory,
        isAllocationEnable: currentRecord.isAllocationEnable,
      };

      await updateClaimType(params)
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
      <Row>
        <Text style={{ fontSize: 22, color: '#394241' }}>{'Claim Types'}</Text>
      </Row>
      <BasicContainer
        rowId="id"
        titleKey="claimTypes"
        defaultTitle="Claim Types"
        refresh={refresh}
        model={model}
        tableColumns={[
          { name: 'typeName', sortable: true, filterable: true },
          { name: 'amountType', sortable: true, filterable: true },
          { name: 'claimCategory', sortable: true, filterable: true },
          { name: 'orderType', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        searchFields={['typeName']}
        // disableSearch = {true}
        addFormType="function"
        editFormType="function"
        getAllFunction={claimTypes}
        addFunction={async () => {
          const intialValues = genarateEmptyValuesObject(model);
          intialValues['orgEntityId'] = 1;
          setCurrentRecord(intialValues);
          setAddApproverPoolFormVisible(true);
        }}
        editFunction={async (record) => {
          // const intialValues = genarateEmptyValuesObject(model);
          record['isAllowAttachment'] =
            record['isAllowAttachment'] == 1 || record['isAllowAttachment'] == true ? true : false;
          record['isAttachmentMandatory'] =
            record['isAttachmentMandatory'] == 1 || record['isAttachmentMandatory'] == true
              ? true
              : false;
          record['isAllocationEnable'] =
            record['isAllocationEnable'] == 1 || record['isAllocationEnable'] == true
              ? true
              : false;
          record['maxAmount'] = record['maxAmount'] ? parseFloat(record['maxAmount']) : null;
          setCurrentRecord(record);
          setEditApproverPoolFormVisible(true);
        }}
        deleteFunction={removeClaimType}
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
        <Row>
          <Col span={24}>
            <CreateForm
              model={model}
              isEditView={false}
              claimCategoriesList={claimCategoriesList}
              emptySwitch={emptySwitch}
              values={currentRecord}
              setValues={setCurrentRecord}
              addGroupFormVisible={addApproverPoolFormVisible}
              editGroupFormVisible={editApproverPoolFormVisible}
              form={addApproverPoolFormReference}
            ></CreateForm>
          </Col>
        </Row>
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
          claimCategoriesList={claimCategoriesList}
          emptySwitch={emptySwitch}
          values={currentRecord}
          setValues={setCurrentRecord}
          addGroupFormVisible={addApproverPoolFormVisible}
          editGroupFormVisible={editApproverPoolFormVisible}
          form={editUserFormReference}
        ></CreateForm>
      </DrawerForm>
    </>
  );
};

export default WorkflowApproverPool;
