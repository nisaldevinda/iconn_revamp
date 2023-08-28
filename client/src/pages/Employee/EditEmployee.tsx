import React, { useState, useEffect } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import { PageContainer } from '@ant-design/pro-layout';
import { Col, message, Row, Modal } from 'antd';
import DynamicForm from '@/components/DynamicForm';
import { useIntl, history } from 'umi';
import _ from 'lodash';
import { ProFormSelect } from '@ant-design/pro-form';
import { APIResponse } from '@/utils/request';
import SideBusinessCard from '@/components/SideBusinessCard';

interface EditEmployeeProps {
  service: any,
  id: string,
  returnRoute: string,
  enableQuickSwitch: boolean;
  isMyProfile?: boolean,
  model?: any,
  scope: string,
  activeKey?: string
}

const EditEmployee: React.FC<EditEmployeeProps> = (props) => {

  const intl = useIntl();
  const [employee, setEmployee] = useState({});
  const [employeeList, setEmployeeList] = useState([]);
  const [employeeModel, setEmployeeModel] = useState<ModelType>();
  const [employeePermission, setEmployeePermission] = useState<any>();
  const [loading, setLoading] = useState(true);
  const [tabActiveKey, setTabActiveKey] = useState<string>();
  const [defaultActiveKey, setDefaultActiveKey] = useState<string>();

  const {
    updateEmployee,
    getEmployee,
    checkShiftAllocated,
    getAllEmployee,
    createEmployeeMultiRecord,
    updateEmployeeMultiRecord,
    deleteEmployeeMultiRecord,
    getEmployeeFieldAccessPermission } = props.service;

  const { id, scope, activeKey } = props;

  useEffect(() => {
    getEmployeeFieldAccessPermission(id).then((permission: any) => {
      setEmployeePermission(permission.data)
    });
    if (props.activeKey) {
      setDefaultActiveKey(activeKey);
    }
  }, [id]);

  useEffect(() => {
    if (props.model) {
      setEmployeeModel(props.model);
    } else {
      getModel(Models.Employee, 'edit').then((model) => {
        setEmployeeModel(model.data);
      });
    }
  }, [props.model]);

  useEffect(() => {
    fetchEmployeeData();
    if (!props.isMyProfile) {
      fetchEmployeeList();
    }
  }, [id]);

  const warning = () => {
    Modal.warning({
      title: "No Any Shift Allocated For This Employee",
      style: {top: 270},
      content: 'Please assign shift to this employee using shift assign option or work pattern assign option.',
    });
  };

  const checkIsShiftAllocated = async () => {
    setLoading(true);

    if (checkShiftAllocated) {
      await checkShiftAllocated(id).then((response) => {
        if (!response.data.isHaveShift) {
          warning();
        }
      })  
    }

    setLoading(false);
  }

  const fetchEmployeeData = async () => {
    setLoading(true);

    await getEmployee(id).then((response) => {
      if (response && response.data) {
        setEmployee(response.data);
      }
    }).then(()=>{
      if (!props.isMyProfile) {
        checkIsShiftAllocated();
      }
    })

    setLoading(false);
  }

  const fetchEmployeeList = async () => {
    const response = await getAllEmployee();

    console.log(response);

    let actions =  response.data?.map((item: any) => {
      return {
        label: item.employeeNumber+' | '+item.employeeName,
        value: item.id,
      };
    });

    setEmployeeList(actions);
  }

  const tabularDataCreator = async (parentId: string, multirecordAttribute: string, data: any) => {
    const response = await createEmployeeMultiRecord(parentId, multirecordAttribute, data);

    await getEmployee(parentId).then((response) => {
      if (response && response.data) {
        setEmployee(response.data);
      }
    })

    return response;
  }

  const tabularDataUpdater = async (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => {
    const response = await updateEmployeeMultiRecord(parentId, multirecordAttribute, multirecordId, data);

    await getEmployee(parentId).then((response) => {
      if (response && response.data) {
        setEmployee(response.data);
      }
    })

    return response;
  }

  const tabularDataDeleter = async (parentId: string, multirecordAttribute: string, multirecordId: number) => {
    await deleteEmployeeMultiRecord(parentId, multirecordAttribute, multirecordId).then((res) => {
      if (res && res.data) {
        message.success({
          content:
            res.message ??
            intl.formatMessage({
              id: res.message,
              defaultMessage: res.message,
            }),

        });
      }
    });

    await getEmployee(parentId).then((response) => {
      if (response && response.data) {
        setEmployee(response.data);
      }
    })
  }

  return (
    <PageContainer
      className='edit-employee-page-container'
      extra={
        props.enableQuickSwitch ? <ProFormSelect
          showSearch
          // request={async () => {
            // const response = await getAllEmployee();
            // return response.data?.map((item: any) => {
            //   return {
            //     label: item.employeeName,
            //     value: item.id,
            //   };
            // });
          // }}
          options={employeeList}
          fieldProps={{
            className: "employee-quick-switch",
            value: !loading ? employee.id : '',
            onChange: (employeeId) => {
              if (!props.isMyProfile) {
                setDefaultActiveKey(tabActiveKey);
                history.push(`${props.returnRoute}/${_.isUndefined(employeeId) ? props.id : employeeId}`)
              }
            },
          }}
          label={intl.formatMessage({
            id: 'employee_quick_switch',
            defaultMessage: 'Switch Employee',
          })}
          style={{
            width: 250
          }}
        /> : <></>
      }
      loading={loading || !employee || !employeeModel || !employeePermission}
    >
      <Row gutter={16}>
        <Col>
          <SideBusinessCard employeeId={employee.id} loading={loading} scope={scope} />
        </Col>
        <Col style={{ width: 'calc( 100% - 280px )' }}>
          <DynamicForm
            formType='update'
            model={employeeModel}
            permission={employeePermission}
            initialValues={employee}
            setTabActiveKey={setTabActiveKey}
            defaultActiveKey={defaultActiveKey}
            scope={scope}

            onFinish={async (values: any) => {
              if (values.id) {
                // setLoading(true);
                const key = 'updating';
                message.loading({
                  content: intl.formatMessage({
                    id: 'updating',
                    defaultMessage: 'Updating...',
                  }),
                  key,
                });

                // Handle user access feature
                if (values.allowAccess || values.user) {
                  const user = {
                    ...values.user,
                    email: values.workEmail,
                    firstName: values.firstName,
                    middleName: values.middleName,
                    lastName: values.lastName,
                    employeeRoleId: values.employeeRoleId,
                    managerRoleId: values.managerRoleId,
                    adminRoleId: values.adminRoleId,
                  };
                  values = { ...values, user };
                } else {
                  delete values.user;
                }

                delete values.allowAccess;
                delete values.employeeRoleId;
                delete values.managerRoleId;
                delete values.adminRoleId;

                await updateEmployee(values.id, values)
                  .then((response: APIResponse) => {
                    if (response.data) {
                      setEmployee(response.data);
                    }

                    message.success({
                      content:
                        response.message ??
                        intl.formatMessage({
                          id: 'successfullyUpdated',
                          defaultMessage: 'Successfully updated',
                        }),
                      key,
                    });
                    if (!props.isMyProfile) {
                      history.push(`${props.returnRoute}/${response.data.id}`);
                    }
                    setLoading(false);
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
                            id: 'failedToUpdate',
                            defaultMessage: 'Cannot Update',
                          }),
                      key,
                    });
                    setLoading(false);

                    throw error;
                  });
              }

            }}
            tabularDataCreator={tabularDataCreator}
            tabularDataUpdater={tabularDataUpdater}
            tabularDataDeleter={tabularDataDeleter}
            submitbuttonLabel={intl.formatMessage({
              id: 'UPDATE',
              defaultMessage: 'Update',
            })}
            resetbuttonLabel={intl.formatMessage({
              id: 'RESET',
              defaultMessage: 'Reset',
            })}
          />
        </Col>
      </Row>
    </PageContainer>
  );
};

export default EditEmployee;
