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
  addClaimPackage,
  updateClaimPackage,
  removeClaimPackage,
  claimPackages,
} from '@/services/expenseModule';
import { genarateEmptyValuesObject } from '@/utils/utils';
import CreateForm from './create';
import moment from 'moment';
import { claimCategories } from '@/services/expenseModule';
import { number } from 'currency-codes';

export type ClaimAssignProps = {
  refresh?: number;
};

const ClaimAssignments: React.FC<ClaimAssignProps> = (props) => {
  const { Text } = Typography;
  const access = useAccess();
  const { hasPermitted } = access;
  //   const { id } = useParams();
  const intl = useIntl();
  const [model, setModel] = useState<any>();
  const [addClaimPackageFormVisible, setAddClaimPackageFormVisible] = useState(false);
  const [editClaimPackageFormVisible, setEditClaimPackageFormVisible] = useState(false);
  const [addClaimPackageFormReference] = Form.useForm();
  const [editClaimPackageFormReference] = Form.useForm();
  const [addApproverPoolFormChangedValue, setAddApproverPoolFormChangedValue] = useState({});
  const [editApproverPoolFormChangedValue, setEditApproverPoolFormChangedValue] = useState({});
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    if (!model) {
      getModel('claimPackages').then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

  useEffect(() => {
    setRefresh((prev) => prev + 1);
  }, [props.refresh]);

  const convertTagObject = (record) => {
    const convRecord = {};
    for (const key in record) {
      convRecord[key] = record[key];
    }
    return convRecord;
  };
  const emptySwitch = (fieldName) => {
    const key = {};
    key[fieldName] = [];
    editClaimPackageFormReference.setFieldsValue(key);
  };

  const addViewProps = {
    title: intl.formatMessage({
      id: `add_claim_type`,
      defaultMessage: `Add Claim Package`,
    }),
    key: `add_claim_type`,
    visible: addClaimPackageFormVisible,
    onVisibleChange: setAddClaimPackageFormVisible,
    form: addClaimPackageFormReference,
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
        allowOrgEntityId: currentRecord.allowOrgEntityId,
        name: currentRecord.name,
        allowJobCategories: currentRecord.allowJobCategories
          ? currentRecord.allowJobCategories
          : [],
        allowEmploymentStatuses: currentRecord.allowEmploymentStatuses
          ? currentRecord.allowEmploymentStatuses
          : [],
        allocatedClaimTypes: currentRecord.allocatedClaimTypes
          ? currentRecord.allocatedClaimTypes
          : [],
      };

      await addClaimPackage(params)
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
                addClaimPackageFormReference.setFields([
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
          setAddClaimPackageFormVisible(false);
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
              addClaimPackageFormReference.setFields([
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
      defaultMessage: `Edit Claim Package`,
    }),
    key: `edit_claim_type`,
    visible: editClaimPackageFormVisible,
    onVisibleChange: setEditClaimPackageFormVisible,
    form: editClaimPackageFormReference,
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
        allowOrgEntityId: currentRecord.allowOrgEntityId,
        name: currentRecord.name,
        allowJobCategories: currentRecord.allowJobCategories
          ? currentRecord.allowJobCategories
          : [],
        allowEmploymentStatuses: currentRecord.allowEmploymentStatuses
          ? currentRecord.allowEmploymentStatuses
          : [],
        allocatedClaimTypes: currentRecord.allocatedClaimTypes
          ? currentRecord.allocatedClaimTypes
          : [],
      };

      await updateClaimPackage(params)
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
                editClaimPackageFormReference.setFields([
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
          setEditClaimPackageFormVisible(false);
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
              editClaimPackageFormReference.setFields([
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
        <Text style={{ fontSize: 22, color: '#394241' }}>{'Claim Packages'}</Text>
      </Row>
      <BasicContainer
        rowId="id"
        titleKey="claimPackages"
        defaultTitle="Claim Packages"
        refresh={refresh}
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        searchFields={['name']}
        // disableSearch = {true}
        addFormType="function"
        editFormType="function"
        getAllFunction={claimPackages}
        addFunction={async () => {
          const intialValues = genarateEmptyValuesObject(model);
          intialValues['allowOrgEntityId'] = 1;
          setCurrentRecord(intialValues);
          setAddClaimPackageFormVisible(true);
        }}
        editFunction={async (record) => {
          setCurrentRecord(record);
          setEditClaimPackageFormVisible(true);
        }}
        deleteFunction={removeClaimPackage}
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
        width="60vw"
      >
        <Row>
          <Col span={24}>
            <CreateForm
              model={model}
              isEditView={false}
              claimCategoriesList={[]}
              emptySwitch={emptySwitch}
              values={currentRecord}
              setValues={setCurrentRecord}
              addGroupFormVisible={addClaimPackageFormVisible}
              editGroupFormVisible={editClaimPackageFormVisible}
              form={addClaimPackageFormReference}
            ></CreateForm>
          </Col>
        </Row>
      </ModalForm>

      <DrawerForm
        drawerProps={{
          destroyOnClose: true,
        }}
        width="50vw"
        {...editViewProps}
      >
        <CreateForm
          model={model}
          isEditView={true}
          claimCategoriesList={[]}
          emptySwitch={emptySwitch}
          values={currentRecord}
          setValues={setCurrentRecord}
          addGroupFormVisible={addClaimPackageFormVisible}
          editGroupFormVisible={editClaimPackageFormVisible}
          form={editClaimPackageFormReference}
        ></CreateForm>
      </DrawerForm>
    </>
  );
};

export default ClaimAssignments;
