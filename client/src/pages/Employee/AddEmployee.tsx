import React, { useState, useEffect } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import { addEmployee, getEmployeeFieldAccessPermission } from '@/services/employee';
import { PageContainer } from '@ant-design/pro-layout';
import { message, Spin } from 'antd';
import DynamicForm from '@/components/DynamicForm';
import { useIntl, history, useAccess, Access } from 'umi';
import { APIResponse } from '@/utils/request';
import PermissionDeniedPage from './../403';

const AddEmployee: React.FC = () => {
  const intl = useIntl();
  const [employeeModel, setEmployeeModel] = useState<ModelType>();
  const [employeePermission, setEmployeePermission] = useState<any>();
  const access = useAccess();
  const { hasPermitted } = access;

  useEffect(() => {
    if (!employeeModel) {
      getModel(Models.Employee, 'add').then((model) => {
        if (model && model.data) {
          setEmployeeModel(model.data);
        }
      });

      getEmployeeFieldAccessPermission('new').then((permission) => {
        setEmployeePermission(permission.data)
      });
    }
  }, []);

  return (
    <Access accessible={hasPermitted('employee-create')} fallback={<PermissionDeniedPage />}>
      <PageContainer>
        {employeeModel ? (
          <DynamicForm
            formType='add'
            model={employeeModel}
            permission={employeePermission}
            onFinish={async (values: any) => {
              const key = 'saving';
              message.loading({
                content: intl.formatMessage({
                  id: 'saving',
                  defaultMessage: 'Saving...',
                }),
                key,
              });

              // handle custom fields
              if (values.jobs[0]) {
                // set employeeJourneyType = 'JOINED' for initial job record
                values.jobs[0].employeeJourneyType = 'JOINED';

                // set hire date as Job effective date
                if (values.hireDate) {
                  values.jobs[0].effectiveDate = values.hireDate;
                }
              }

              // Handle user access feature
              if (values.allowAccess) {
                const user = {
                  email: values.workEmail,
                  firstName: values.firstName,
                  middleName: values.middleName,
                  lastName: values.lastName,
                  employeeRoleId: values.employeeRoleId,
                  managerRoleId: values.managerRoleId,
                  adminRoleId: values.adminRoleId,
                };
                values = { ...values, user };
              }

              delete values.allowAccess;
              delete values.employeeRoleId;
              delete values.managerRoleId;
              delete values.adminRoleId;

              await addEmployee(values)
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

                  history.push(`/employees/${response.data.id}`);
                })
                .catch((error: APIResponse) => {
                  let errorMessage;
                  let errorMessageInfo;
                  if (error.message.includes(".")) {
                    let errorMessageData = error.message.split(".");
                    errorMessage = errorMessageData.slice(0, 1);
                    errorMessageInfo = errorMessageData.slice(1).join('.');
                  }
                  message.error({
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
                          id: 'failedToSave',
                          defaultMessage: 'Cannot Save',
                        }),
                    key,
                  });

                  throw error;
                });
            }}
            submitbuttonLabel={intl.formatMessage({
              id: 'ADD',
              defaultMessage: 'Add',
            })}
            resetbuttonLabel={intl.formatMessage({
              id: 'RESET',
              defaultMessage: 'Reset',
            })}
          />
        ) : (
          <Spin />
        )}
      </PageContainer>
    </Access>
  );
};

export default AddEmployee;
