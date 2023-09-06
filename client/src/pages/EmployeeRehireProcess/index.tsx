import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { FormattedMessage, useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { Card, Button, Radio, Space, Row, Col, message, Avatar, Typography, Skeleton, Switch, Form } from 'antd';
import ProTable from '@ant-design/pro-table';
import { getReactiveEligibleList, getRejoinEligibleList, reactiveEmployee, rejoinEmployee } from '@/services/employeeJourney';
import { ModalForm, ProFormDatePicker, ProFormSelect } from '@ant-design/pro-form';
import OrgSelector from '@/components/OrgSelector';
import { getManagerList } from '@/services/dropdown';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import { getCalendarList } from '@/services/workCalendarService';
import { getAllScheme } from '@/services/scheme';
// import { getAllEntities } from '@/services/department';
import { getEmployee } from '@/services/employee';
import { getModel } from '@/services/model';
import request from "@/utils/request";
import { UserOutlined } from "@ant-design/icons";

const EmployeeRehireProcess: React.FC = () => {
  const intl = useIntl();
  const [formReference] = Form.useForm();

  const [loading, setLoading] = useState<boolean>(false);
  const [initializing, setInitializing] = useState<boolean>(false);
  const [employeeLoading, setEmployeeLoading] = useState<boolean>(false);
  const [formSubmitting, setFormSubmitting] = useState<boolean>(false);
  const [type, setType] = useState<'rejoin' | 'reactive'>('rejoin');
  const [columns, setColumns] = useState();
  const [masterData, setMasterData] = useState();
  const [rejoinEligibleList, setRejoinEligibleList] = useState([]);
  const [reactiveEligibleList, setReactiveEligibleList] = useState([]);
  const [modalVisible, setModalVisible] = useState(false);
  const [modelEmployee, setModelEmployee] = useState();
  const [orgStructureEntityId, setOrgStructureEntityId] = useState<number>();
  const [isOldEmployeeNumber, setIsOldEmployeeNumber] = useState(true);

  useEffect(() => {
    loadMasterData();
    updateEligibleList();
  }, [])

  useEffect(() => {
    updateTableColumns();
  }, [type]);

  const updateTableColumns = () => {
    const _columns = [
      {
        dataIndex: 'employee_name',
        title: intl.formatMessage({
          id: 'employee_journey_update.employee_name',
          defaultMessage: "Employee Name",
        }),
      },
      {
        dataIndex: 'resignation_type_name',
        title: intl.formatMessage({
          id: 'employee_journey_update.resignation_type',
          defaultMessage: "Resignation Type",
        }),
      },
      {
        dataIndex: 'resigned_date',
        title: intl.formatMessage({
          id: 'employee_journey_update.resigned_date',
          defaultMessage: "Resigned Date",
        }),
      },
      {
        dataIndex: 'resigned_duration_days',
        title: intl.formatMessage({
          id: 'employee_journey_update.resigned_duration_days',
          defaultMessage: "Resigned Duration",
        }),
        render: (value: number) => {
          const yearCount = Math.floor(value / 365);
          const monthCount = Math.floor((value % 365) / 30);
          const dayCount = value % 30;

          return yearCount == 1 ? `${yearCount} Year ` : yearCount > 0 ? `${yearCount} Years ` : ''
            .concat(monthCount == 1 ? `${monthCount} Month ` : monthCount > 0 ? `${monthCount} Months ` : '')
            .concat(dayCount == 1 ? `${dayCount} Day ` : dayCount > 0 ? `${dayCount} Days ` : '')
            .trim();
        },
      },
      {
        dataIndex: 'employee_id',
        title: intl.formatMessage({
          id: 'employee_journey_update.actions',
          defaultMessage: "Actions",
        }),
        render: (value: string) => <Button
          type='primary'
          onClick={() => {
            setIsOldEmployeeNumber(true);
            loadEmployee(value);
            setModalVisible(true);
          }}>
          {type == 'reactive'
            ? <FormattedMessage id="employee_journey_update.reactive_btn" defaultMessage="Reactive" />
            : <FormattedMessage id="employee_journey_update.rejoin_btn" defaultMessage="Rejoin" />}
        </Button>
      }
    ];

    setColumns(_columns);
  }

  const updateEligibleList = async (specific?: 'rejoin' | 'reactive') => {
    setLoading(true);

    if (specific == 'rejoin') {
      const _employeeList = await getRejoinEligibleList();
      setRejoinEligibleList(_employeeList.data);
    } else if (specific == 'reactive') {
      const _employeeList = await getReactiveEligibleList();
      setReactiveEligibleList(_employeeList.data);
    } else {
      const _employeeList1 = await getRejoinEligibleList();
      setRejoinEligibleList(_employeeList1.data);
      const _employeeList2 = await getReactiveEligibleList();
      setReactiveEligibleList(_employeeList2.data);
    }

    setLoading(false);
  }

  const loadMasterData = async () => {
    if (_.isEmpty(masterData)) {
      setInitializing(true);

      let _data = {};
      let callStack = [];

      // retrieve all location
      callStack.push(getAllLocations()
        .then(response => _data = {
          ..._data, locations: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error.message)));

      // retrieve all job category
      callStack.push(getAllJobCategories()
        .then(response => _data = {
          ..._data,
          jobCategories: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error.message)));

      // retrieve all job title
      callStack.push(getAllJobTitles()
        .then(response => _data = {
          ..._data, jobTitles: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error.message)));

      // retrieve all managers
      callStack.push(getManagerList()
        .then(response => _data = {
          ..._data,
          managers: response?.data.map(record => {
            return {
              value: record.id,
              label: record.employeeName
            };
          }) ?? []
        })
        .catch(error => message.error(error.message)));

      // retrieve all schemes
      callStack.push(getAllScheme()
        .then(response => _data = {
          ..._data, schemes: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error.message)));

      // retrieve all calendars
      callStack.push(getCalendarList()
        .then(response => _data = {
          ..._data, calendars: response?.data.map(record => {
            return {
              value: record.calendarId,
              label: record.menuItemName
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve org entities
      // callStack.push(getAllEntities()
      //   .then(response => _data = {
      //     ..._data, orgEntities: response?.data ?? {}
      //   })
      //   .catch(error => message.error(error?.message)));


      Promise.all(callStack).then(() => {
        setMasterData(_data);
        setInitializing(false);
      });
    }
  }

  const loadEmployee = async (id: string) => {
    try {
      setEmployeeLoading(true);
      let _employee = await getEmployee(id);

      let currentJob = _employee?.data?.jobs?.find((job: any) => job.id == _employee?.data?.currentJobsId);
      _employee.data.currentJob = {
        ...currentJob,
        effectiveDate: undefined
      };

      setOrgStructureEntityId(_employee.data.currentJob?.orgStructureEntityId ?? undefined);

      const response = await getModel('employee');
      const path = `/api${response.data.modelDataDefinition.path}/` + id + `/profilePicture`;
      const { data } = await request(path);

      _employee.data.profilePicture = data?.data;
      setModelEmployee(_employee.data);
      formReference.setFieldsValue(_employee.data);
      setEmployeeLoading(false);
    } catch (error) {
      setModelEmployee(undefined);
      setEmployeeLoading(false);
    }
  }

  const modalOnFinish = async (values: any) => {
    setFormSubmitting(true);

    const key = 'submitting';
    message.loading({
      content: type == 'reactive'
        ? intl.formatMessage({
          id: 'reactivating',
          defaultMessage: 'Reactivating...',
        })
        : intl.formatMessage({
          id: 'rejoining',
          defaultMessage: 'Rejoining...',
        }),
      key,
    });

    const data = {
      employeeId: modelEmployee?.id,
      type,
      isNewEmployeeNumber: type == 'reactive' ? !isOldEmployeeNumber : false,
      currentJob: modelEmployee?.currentJob,
      changes: {
        ...values,
        orgStructureEntityId
      }
    }

    if (type == 'reactive') {
      reactiveEmployee(data)
        .then((response: any) => {
          if (response.error) {
            message.error({
              content: intl.formatMessage({
                id: 'failedToReactivate',
                defaultMessage: 'Failed to Reactivate.',
              }),
              key,
            });
          } else {
            message.success({
              content: intl.formatMessage({
                id: 'successfullyReactivated',
                defaultMessage: 'Successfully Reactivated.',
              }),
              key,
            });
            updateEligibleList('reactive');
            setModalVisible(false);
          }
          setFormSubmitting(false);
        })
        .catch((error: any) => {
          message.error({
            content: intl.formatMessage({
              id: 'failedToReactivate',
              defaultMessage: 'Failed to Reactivate.',
            }),
            key,
          });
          setFormSubmitting(false);
        });
    } else {
      rejoinEmployee(data)
        .then((response: any) => {
          if (response.error) {
            message.error({
              content: intl.formatMessage({
                id: 'failedToRejoin',
                defaultMessage: 'Failed to Rejoin.',
              }),
              key,
            });
          } else {
            message.success({
              content: intl.formatMessage({
                id: 'successfullyRejoined',
                defaultMessage: 'Successfully Rejoined.',
              }),
              key,
            });
            updateEligibleList('rejoin');
            setModalVisible(false);
          }
          setFormSubmitting(false);
        })
        .catch((error: any) => {
          message.error({
            content: intl.formatMessage({
              id: 'failedToRejoin',
              defaultMessage: 'Failed to Rejoin.',
            }),
            key,
          });
          setFormSubmitting(false);
        });
    }
  }

  return (
    <div
      style={{
        backgroundColor: 'white',
        borderTopLeftRadius: '30px',
        paddingLeft: '50px',
        paddingTop: '50px',
        width: '100%',
        paddingRight: '0px',
      }}
    >
      <PageContainer>
        <Card>
          <Space direction="vertical">
            <Typography.Text>
              <FormattedMessage
                id="employee_rehire_process.rehire_type"
                defaultMessage="Rehire Type"
              />
            </Typography.Text>
            <Radio.Group
              defaultValue="rejoin"
              onChange={(value) => {
                setType(value?.target?.value ?? 'rejoin');
              }}
            >
              <Radio.Button value="rejoin">
                <FormattedMessage id="employee_rehire_process.rejoin" defaultMessage="Rejoin" />
              </Radio.Button>
              <Radio.Button value="reactive">
                <FormattedMessage id="employee_rehire_process.reactive" defaultMessage="Reactive" />
              </Radio.Button>
            </Radio.Group>
            <Typography.Text>
              <FormattedMessage
                id="employee_rehire_process.employee_name"
                defaultMessage="Employee Name"
              />
            </Typography.Text>
          </Space>
          <ProTable
            columns={columns}
            dataSource={type == 'reactive' ? reactiveEligibleList : rejoinEligibleList}
            loading={loading}
            rowKey="employee_id"
            toolBarRender={false}
            search={false}
          />
        </Card>
        <ModalForm
          title={
            type == 'reactive'
              ? intl.formatMessage({
                  id: `reactive_employee`,
                  defaultMessage: `Reactive Employee`,
                })
              : intl.formatMessage({
                  id: `rejoin_employee`,
                  defaultMessage: `Rejoin Employee`,
                })
          }
          key="action_employee"
          visible={modalVisible}
          onVisibleChange={setModalVisible}
          form={formReference}
          submitter={{
            render: () => (
              <Space>
                <Button onClick={() => formReference.resetFields()}>
                  {intl.formatMessage({
                    id: 'reset',
                    defaultMessage: 'Reset',
                  })}
                </Button>
                <Button
                  type="primary"
                  disabled={employeeLoading}
                  loading={formSubmitting}
                  onClick={() => formReference.submit?.()}
                >
                  {type == 'reactive'
                    ? intl.formatMessage({
                        id: 'reactive',
                        defaultMessage: 'Reactive',
                      })
                    : intl.formatMessage({
                        id: 'rejoin',
                        defaultMessage: 'Rejoin',
                      })}
                </Button>
              </Space>
            ),
          }}
          modalProps={{
            destroyOnClose: true,
            centered: true,
          }}
          initialValues={modelEmployee?.currentJob}
          onFinish={modalOnFinish}
        >
          <Skeleton loading={initializing || employeeLoading} active>
            {modelEmployee && (
              <>
                <Row>
                  <Space style={{ marginBottom: 24 }}>
                    {modelEmployee.profilePicture ? (
                      <Avatar src={modelEmployee.profilePicture} />
                    ) : (
                      <Avatar icon={<UserOutlined />} />
                    )}
                    <Typography.Title level={5} style={{ margin: 0 }}>
                      {modelEmployee.fullName}
                    </Typography.Title>
                  </Space>
                </Row>
                <Row>
                  <Card
                    style={{
                      backgroundColor: '#FFF0D8',
                      borderRadius: 6,
                      marginBottom: 16,
                    }}
                    bodyStyle={{
                      padding: 8,
                    }}
                  >
                    <Typography.Text style={{ color: '#AB6A05', margin: 0 }}>
                      <FormattedMessage
                        id="old_employee_number"
                        defaultMessage="Old Employee Number"
                      />
                    </Typography.Text>
                    <Typography.Title level={3} style={{ color: '#FCAE34', margin: 0 }}>
                      {modelEmployee.employeeNumber}
                    </Typography.Title>
                  </Card>
                  {type == 'reactive' && (
                    <Space direction="horizontal" style={{ marginLeft: 8 }}>
                      <Typography.Text>
                        {intl.formatMessage({
                          id: 'do_you_want_to_proceed_with_old_employee_number',
                          defaultMessage: 'Do you want to proceed with Old Employee Number',
                        })}
                      </Typography.Text>
                      <Switch checked={isOldEmployeeNumber} onChange={setIsOldEmployeeNumber} />
                    </Space>
                  )}
                </Row>
                <Row gutter={24}>
                  <OrgSelector
                    span={8}
                    orgEntities={masterData?.orgEntities}
                    value={orgStructureEntityId}
                    setValue={(value: number) => setOrgStructureEntityId(value)}
                  />
                  <Col span={8}>
                    <ProFormSelect
                      name="locationId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.location',
                        defaultMessage: 'Location',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.locations}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_location',
                        defaultMessage: 'Select Location',
                      })}
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormSelect
                      name="jobCategoryId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.job_category',
                        defaultMessage: 'Job Category',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.jobCategories}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_job_category',
                        defaultMessage: 'Select Job Category',
                      })}
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormSelect
                      name="jobTitleId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.job_title',
                        defaultMessage: 'Job Title',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.jobTitles}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_job_title',
                        defaultMessage: 'Select Job Title',
                      })}
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormSelect
                      name="reportsToEmployeeId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.reporting_person',
                        defaultMessage: 'Reporting Person',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.managers}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_reporting_person',
                        defaultMessage: 'Select Reporting Person',
                      })}
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormSelect
                      name="functionalReportsToEmployeeId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.functional_reporting_person',
                        defaultMessage: 'Functional Reporting Person',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.managers}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_functional_reporting_person',
                        defaultMessage: 'Select Functional Reporting Person',
                      })}
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormSelect
                      name="schemeId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.scheme',
                        defaultMessage: 'Scheme',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.schemes}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_scheme',
                        defaultMessage: 'Select Scheme',
                      })}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormSelect
                      name="calendarId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.calendar',
                        defaultMessage: 'Calendar',
                      })}
                      showSearch
                      // disabled={!hasEditPermission()}
                      options={masterData?.calendars}
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_calendar',
                        defaultMessage: 'Select Calendar',
                      })}
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                  <Col span={8}>
                    <ProFormDatePicker
                      width="lg"
                      format="DD-MM-YYYY"
                      name="effectiveDate"
                      label={
                        type == 'reactive'
                          ? intl.formatMessage({
                              id: 'employee_journey_update.reactive_effective_date',
                              defaultMessage: 'Reactive Effective Date',
                            })
                          : intl.formatMessage({
                              id: 'employee_journey_update.rejoin_effective_date',
                              defaultMessage: 'Rejoin Effective Date',
                            })
                      }
                      // disabled={!hasEditPermission()}
                      placeholder={
                        type == 'reactive'
                          ? intl.formatMessage({
                              id: 'employee_journey_update.select_resignation_effective_date',
                              defaultMessage: 'Select Reactive Effective Date',
                            })
                          : intl.formatMessage({
                              id: 'employee_journey_update.select_resignation_effective_date',
                              defaultMessage: 'Select Rejoin Effective Date',
                            })
                      }
                      rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                </Row>
              </>
            )}
          </Skeleton>
        </ModalForm>
      </PageContainer>
    </div>
  );
};

export default EmployeeRehireProcess;
