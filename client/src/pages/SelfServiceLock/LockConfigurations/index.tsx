import React, { useEffect, useState } from 'react';
import { Button, Col, Row, Card, Form, message, Select } from 'antd';
import BasicContainer from '@/components/BasicContainer';
import AddEditForm from '@/pages/SelfServiceLock/LockConfigurations/addEditForm';
import { getModel, Models } from '@/services/model';
import {
  addServiceLockConfig,
  getAllServiceLockConfigs,
  removeServiceLockConfig,
  updateServiceLockConfig,
} from '@/services/selfServiceLockConfig';
import {
  addPeriodConfig,
  getAllPeriodConfigs,
  removePeriodConfig,
  updatePeriodConfig,
} from '@/services/selfLockPeriodConfig';
import { downloadTemplate, uploadTemplate } from '@/services/bulkUpload';
import TemplateModal from '@/pages/BulkUpload/employee-bulk-upload/templateModel';
import Validator from '@/pages/BulkUpload/employee-bulk-upload/validator';
import { useIntl, FormattedMessage } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, history } from 'umi';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { genarateEmptyValuesObject } from '@/utils/utils';
import request, { APIResponse } from '@/utils/request';
import PermissionDeniedPage from '@/pages/403';
import _ from 'lodash';
import '../style.css';
import moment from 'moment';

const LockConfiguration: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [model, setModel] = useState<any>();
  const [refresh, setRefresh] = useState(0);
  const [addSelfServiceLockConfigFormVisible, setAddSelfServiceLockConfigFormVisible] =
    useState(false);
  const [editSelfServiceLockConfigFormVisible, setEditSelfServiceLockConfigFormVisible] =
    useState(false);
  const [addSelfServiceLockConfigFormReference] = Form.useForm();
  const [editSelfServiceLockConfigFormReference] = Form.useForm();
  const [datePeriods, setDatePeriods] = useState([]);
  const [datePeriodId, setDatePeriodId] = useState(null);
  const [addPeriodConfigFormChangedValue, setAddSelfServiceLockConfigFormChangedValue] = useState(
    {},
  );
  const [editPeriodConfigFormChangedValue, setEditSelfServiceLockConfigFormChangedValue] = useState(
    {},
  );
  const [currentRecord, setCurrentRecord] = useState<any>();

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel('selfServiceLock').then((response) => {
        const userModel = response.data;
        setModel(userModel);
      });
    }
  }, []);

  const addViewProps = {
    title: intl.formatMessage({
      id: `add_day_type`,
      defaultMessage: `Add Self Service Config`,
    }),
    key: `add_day_type`,
    visible: addSelfServiceLockConfigFormVisible,
    onVisibleChange: setAddSelfServiceLockConfigFormVisible,
    form: addSelfServiceLockConfigFormReference,
    onValuesChange: setAddSelfServiceLockConfigFormChangedValue,
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
      message.loading({
        content: intl.formatMessage({
          id: 'saving',
          defaultMessage: 'Saving...',
        }),
        key,
      });

      await addServiceLockConfig(convertTagString(currentRecord))
        .then((response: APIResponse) => {
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
          setAddSelfServiceLockConfigFormVisible(false);
        })

        .catch((error: APIResponse) => {
          let errorMessage;
          let errorMessageInfo;
          if (error && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              addSelfServiceLockConfigFormReference.setFields([
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
      switch (key) {
        case 'effectiveFrom':
          convRecord[key] = moment(record[key], 'YYYY-MM-DD');
          editSelfServiceLockConfigFormReference.setFieldsValue({
            effectiveFrom: moment(record[key], 'YYYY-MM-DD'),
          });
          break;
        case 'selfServicesStatus':
          let savedServices = record[key];
          let selectedServices = [];

          for (const key11 in savedServices) {
            if (savedServices[key11]) {
              selectedServices.push(key11);
            }
          }

          convRecord[key] = selectedServices;
          break;
        default:
          convRecord[key] = record[key];
          break;
      }
    }
    return convRecord;
  };
  const getRelatedDatePeriods = async () => {
    try {
      const actions: any = [];
      const { data } = await getAllPeriodConfigs();
      const res = data.map((period: any) => {
        actions.push({ value: period.id, label: period.configuredMonth });
        return {
          label: period.configuredMonth,
          value: period.id,
        };
      });
      setDatePeriods(actions);
      return res;
    } catch (err) {
      console.log(err);
      return [];
    }
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

  const editViewProps = {
    title: intl.formatMessage({
      id: `edit_day_type`,
      defaultMessage: `Edit Self Service Lock Config`,
    }),
    key: `edit_day_type`,
    visible: editSelfServiceLockConfigFormVisible,
    onVisibleChange: setEditSelfServiceLockConfigFormVisible,
    form: editSelfServiceLockConfigFormReference,
    onValuesChange: setEditSelfServiceLockConfigFormChangedValue,
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

      await updateServiceLockConfig(convertTagString(currentRecord))
        .then((response: APIResponse) => {
          if (!response.error) {
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
            setEditSelfServiceLockConfigFormVisible(false);
          }
        })
        .catch((error: APIResponse) => {
          let errorMessage;
          let errorMessageInfo;

          if (error.data && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              editSelfServiceLockConfigFormReference.setFields([
                {
                  name: feildName,
                  errors: errors,
                },
              ]);
            }
          } else {
            message.error({
              content: error.message,
              key,
            });
          }
        });
    },
    initialValues: convertTagObject(currentRecord),
  };

  return (
    <>
      <Row>
        <Col span={24}>
          <Card style={{ height: '70vh' }} title={false}>
            <BasicContainer
              rowId="id"
              titleKey="workCalendarDayType"
              refresh={refresh}
              defaultTitle="Day Types"
              model={model}
              tableColumns={[
                { name: 'selfServiceLockDatePeriod', sortable: true },
                { name: 'effectiveFrom' },
                { name: 'selfServiceLockDatePeriod.fromDate' },
                { name: 'selfServiceLockDatePeriod.toDate' },
                { name: 'status' },
              ]}
              recordActions={['add', 'edit']}
              disableSearch={true}
              toolbarFilter={true}
              toolbarFilterId={datePeriodId}
              toolbarFilterFunction={
                  <Select
                      style={{ width: 200 }}
                      placeholder="Please select"
                      allowClear

                      onChange={(e: any) => { 
                          // setRefresh(true)
                          setDatePeriodId(e) 
                          // setRefresh(false)
                        }
                      }
                      onClick={() => { getRelatedDatePeriods() }}
                      options={datePeriods}
                  />}
              addFormType="function"
              searchFields={['selfServiceLockDatePeriod']}
              editFormType="function"
              getAllFunction={getAllServiceLockConfigs}
              addFunction={async () => {
                const intialValues = genarateEmptyValuesObject(model);
                intialValues['effectiveFrom'] = moment();
                setCurrentRecord(intialValues);
                setAddSelfServiceLockConfigFormVisible(true);
              }}
              editFunction={async (record) => {
                const intialValues = { ...record };

                console.log(record);
                intialValues.effectiveFrom = record.effectiveFromLabel;
                intialValues.selfServicesStatus = JSON.parse(intialValues.selfServicesStatus);
                setCurrentRecord(intialValues);
                setEditSelfServiceLockConfigFormVisible(true);
              }}
              deleteFunction={removeServiceLockConfig}
              permissions={{
                addPermission: 'self-service-lock',
                editPermission: 'self-service-lock',
                deletePermission: 'self-service-lock',
                readPermission: 'self-service-lock',
              }}
            />

            <ModalForm
              width={750}
              modalProps={{
                destroyOnClose: true,
              }}
              {...addViewProps}
            >
              <AddEditForm
                model={model}
                values={currentRecord}
                setValues={setCurrentRecord}
                addDayTypeFormVisible={addSelfServiceLockConfigFormVisible}
                editDayTypeFormVisible={editSelfServiceLockConfigFormVisible}
                form={addSelfServiceLockConfigFormReference}
              ></AddEditForm>
            </ModalForm>

            <DrawerForm
              drawerProps={{
                destroyOnClose: true,
              }}
              width="40vw"
              {...editViewProps}
            >
              <AddEditForm
                model={model}
                values={currentRecord}
                setValues={setCurrentRecord}
                addDayTypeFormVisible={addSelfServiceLockConfigFormVisible}
                editDayTypeFormVisible={editSelfServiceLockConfigFormVisible}
                form={editSelfServiceLockConfigFormReference}
              ></AddEditForm>
            </DrawerForm>
          </Card>
        </Col>
      </Row>
    </>
  );
};

export default LockConfiguration;
