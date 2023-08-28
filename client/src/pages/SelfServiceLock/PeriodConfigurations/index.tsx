import React, { useEffect, useState } from 'react';
import { Button, Col, Row, Card, Form, message } from 'antd';
import BasicContainer from '@/components/BasicContainer';
import AddEditForm from '@/pages/SelfServiceLock/PeriodConfigurations/addEditForm';
import { getModel, Models } from '@/services/model';
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

const PeriodConfiguration: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [model, setModel] = useState<any>();
  const [refresh, setRefresh] = useState(0);
  const [addPeriodConfigFormVisible, setAddPeriodConfigFormVisible] = useState(false);
  const [editPeriodConfigFormVisible, setEditPeriodConfigFormVisible] = useState(false);
  const [addPeriodConfigFormReference] = Form.useForm();
  const [editPeriodConfigFormReference] = Form.useForm();
  const [addPeriodConfigFormChangedValue, setAddPeriodConfigFormChangedValue] = useState({});
  const [editPeriodConfigFormChangedValue, setEditPeriodConfigFormChangedValue] = useState({});
  const [currentRecord, setCurrentRecord] = useState<any>();

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel('selfServiceLockDatePeriods').then((response) => {
        const userModel = response.data;
        setModel(userModel);
      });
    }
  }, []);

  const addViewProps = {
    title: intl.formatMessage({
      id: `add_day_type`,
      defaultMessage: `Add Date Period Configuraton`,
    }),
    key: `add_day_type`,
    visible: addPeriodConfigFormVisible,
    onVisibleChange: setAddPeriodConfigFormVisible,
    form: addPeriodConfigFormReference,
    onValuesChange: setAddPeriodConfigFormChangedValue,
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

      await addPeriodConfig(convertTagString(currentRecord))
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
          setAddPeriodConfigFormVisible(false);
        })

        .catch((error: APIResponse) => {
          if (error.data && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              addPeriodConfigFormReference.setFields([
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
  };

  const convertTagObject = (record) => {
    const convRecord = {};
    for (const key in record) {
      console.log(key);

      switch (key) {
        case 'configuredMonth':
          convRecord[key] = moment(record[key], 'YYYY/MMM');
          break;
        case 'fromDate':
          convRecord[key] = moment(record[key], 'YYYY-MM-DD');
          break;
        case 'toDate':
          convRecord[key] = moment(record[key], 'YYYY-MM-DD');
          break;

        default:
          convRecord[key] = record[key];
          break;
      }
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

  const editViewProps = {
    title: intl.formatMessage({
      id: `edit_day_type`,
      defaultMessage: `Edit Date Period Configuraton`,
    }),
    key: `edit_day_type`,
    visible: editPeriodConfigFormVisible,
    onVisibleChange: setEditPeriodConfigFormVisible,
    form: editPeriodConfigFormReference,
    onValuesChange: setEditPeriodConfigFormChangedValue,
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

      await updatePeriodConfig(convertTagString(currentRecord))
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
            setEditPeriodConfigFormVisible(false);
          }
        })
        .catch((error: APIResponse) => {
          if (error.data && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              editPeriodConfigFormReference.setFields([
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
          <Card
            style={{ height: '70vh' }}
            title={
              false
            }
          >
            <BasicContainer
              rowId="id"
              titleKey="workCalendarDayType"
              refresh={refresh}
              defaultTitle="Day Types"
              model={model}
              tableColumns={[
                { name: 'configuredMonth'},
                { name: 'fromDate' },
                { name: 'toDate' },
              ]}
              recordActions={['add', 'edit', 'delete']}
              disableSearch={false}
              addFormType="function"
              editFormType="function"
              getAllFunction={getAllPeriodConfigs}
              addFunction={async () => {
                const intialValues = genarateEmptyValuesObject(model);
                setCurrentRecord(intialValues);
                setAddPeriodConfigFormVisible(true);
              }}
              editFunction={async (record) => {
                record.fromDate = record.fromDateOrginal;
                record.toDate = record.toDateOrginal;
                console.log(record);

                const intialValues = genarateEmptyValuesObject(model);
                setCurrentRecord({ ...record });
                setEditPeriodConfigFormVisible(true);
              }}
              deleteFunction={removePeriodConfig}
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
                addDayTypeFormVisible={addPeriodConfigFormVisible}
                editDayTypeFormVisible={editPeriodConfigFormVisible}
                form={addPeriodConfigFormReference}
              ></AddEditForm>
            </ModalForm>

            <DrawerForm
              drawerProps={{
                destroyOnClose: true,
              }}
              width="35vw"
              {...editViewProps}
            >
              <AddEditForm
                model={model}
                values={currentRecord}
                setValues={setCurrentRecord}
                addDayTypeFormVisible={addPeriodConfigFormVisible}
                editDayTypeFormVisible={editPeriodConfigFormVisible}
                form={editPeriodConfigFormReference}
              ></AddEditForm>
            </DrawerForm>
          </Card>
        </Col>
      </Row>
    </>
  );
};

export default PeriodConfiguration;
