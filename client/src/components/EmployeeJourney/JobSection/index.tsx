import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { Button, Modal, Col, Select, message, Row, Space, Skeleton } from 'antd';
import ProForm, { ProFormSelect } from '@ant-design/pro-form';
import { useIntl } from 'umi';
import { getManagerList } from '@/services/dropdown';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
// import { getAllDepartment } from '@/services/department';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllDivisions } from '@/services/divsion';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import { queryPayGrades } from '@/services/PayGradeService';
import Icon from "@ant-design/icons";
import HistoryIcon from '@/assets/EmployeeJourneyUpdate/history.svg';
import EmployeeJourneyHistory from '../History';
import Text from 'antd/lib/typography/Text';
import { updateCurrentJob } from '@/services/employeeJourney';
import { getModel, Models } from '@/services/model';
import { getAllScheme } from '@/services/scheme';
import { getEmployeeList } from '@/services/dropdown';
import OrgSelector from '@/components/OrgSelector';
import { getCalendarList } from '@/services/workCalendarService';
interface EmployeeProfileJobSectionProps {
  values: any,
  setValues: (value: any) => void
  permission: any,
  scope?: string
}

const EmployeeProfileJobSection: React.FC<EmployeeProfileJobSectionProps> = (props) => {
  const intl = useIntl();

  const [loading, setLoading] = useState<boolean>(false);
  const [data, setData] = useState();
  const [model, setModel] = useState();
  const [jobs, setJobs] = useState([]);
  const [currentJob, setCurrentJob] = useState();
  const [orgStructureEntityId, setOrgStructureEntityId] = useState<number>();
  const [isHistoryModalVisible, setHistoryModalVisible] = useState(false);
  const [historyModalSelector, setHistoryModalSelector] = useState('ALL');
  const [historyModalRecords, setHistoryModalRecords] = useState([]);

  useEffect(() => {
    init();
  }, []);

  useEffect(() => {
    setJobs((props.values?.jobs ?? []).map((job: any) => {
      return {
        ...job,
        previousRecord: (props.values?.jobs ?? []).find((_job: any) => _job.id == job.previousRecordId)
      };
    }));
    setupCurrentJob();
  }, [props.values]);

  const init = async () => {
    if (_.isEmpty(data)) {
      setLoading(true);

      let _data = {};
      let callStack = [];

      // retrieve employee job model
      callStack.push(getModel(Models.EmployeeJob)
        .then(response => {
          setModel(response?.data);
        })
        .catch(error => message.error(error.message)));

      // retrieve all employee
      callStack.push(getEmployeeList()
        .then(response => _data = {
          ..._data,
          employees: response?.data.map(record => {
            return {
              value: record.id,
              label: record.employeeName
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

      // retrieve all employment status
      callStack.push(getAllEmploymentStatus()
        .then(response => _data = {
          ..._data,
          employmentStatus: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name,
              record: record
            };
          }) ?? []
        })
        .catch(error => message.error(error.message)));

      // retrieve all department
      // callStack.push(getAllDepartment()
      //   .then(response => _data = {
      //     ..._data,
      //     departments: response?.data.map(record => {
      //       return {
      //         value: record.id,
      //         label: record.name
      //       };
      //     }) ?? []
      //   })
      //   .catch(error => message.error(error.message)));

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

      // retrieve all division
      callStack.push(getAllDivisions()
        .then(response => _data = {
          ..._data,
          divisions: response?.data.map(record => {
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


      // retrieve all pay grade
      callStack.push(queryPayGrades()
        .then(response => _data = {
          ..._data, payGrades: response?.data.map(record => {
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

      Promise.all(callStack).then(() => {
        setData(_data);
        setLoading(false);
      });
    }
  }

  const setupCurrentJob = async () => {
    if (props?.values?.jobs && props?.values?.currentJobsId) {
      const _currentJob = props?.values?.jobs.find(job => job.id == props?.values?.currentJobsId);
      setCurrentJob(_currentJob ?? undefined);
      setOrgStructureEntityId(_currentJob?.orgStructureEntityId ?? undefined);
    }
  }

  const onChangeHistoryModalSelctor = (value) => {
    setHistoryModalSelector(value);

    const _records = value == 'ALL'
      ? jobs
      : jobs.filter(job => job.employeeJourneyType == value);

    setHistoryModalRecords(_records);
  }

  const hasViewPermission = () => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    if (_.isObject(props?.permission) && _.has(props?.permission, 'readOnly') && props?.permission?.readOnly == '*') return true;

    return props?.permission['employee']?.viewOnly.includes('employeeJourney')
      || props?.permission['employee']?.canEdit.includes('employeeJourney');
  }

  const hasEditPermission = () => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    return props?.permission['employee']?.canEdit.includes('employeeJourney');
  }

  const onFinish = async (values: any) => {
    if (!currentJob) {
      message.error({
        content:
          intl.formatMessage({
            id: 'failedToUpdate',
            defaultMessage: 'Failed to Update',
          })
      });
    }

    const key = 'updating';
    message.loading({
      content: intl.formatMessage({
        id: 'updating',
        defaultMessage: 'Updating...',
      }),
      key,
    });

    const data = { ...currentJob, ...values, orgStructureEntityId };
    updateCurrentJob(props?.scope, props?.values?.id, data)
      .then(response => {
        const _data = { ...data };

        const _currentJobId = props.values?.currentJobsId;
        const _currentJobIndex = props.values?.jobs?.findIndex((job: any) => job.id == _currentJobId);

        if (_currentJobIndex > -1) {
          let _jobs = props.values?.jobs;
          _jobs[_currentJobIndex] = _data;
          props.setValues({
            ...props.values,
            jobs: _jobs
          });
          console.log('_jobs', _jobs);
        }

        message.success({
          content:
            response.message ??
            intl.formatMessage({
              id: 'successfullyUpdate',
              defaultMessage: 'Successfully Update',
            }),
          key,
        });
      })
      .catch(error => {
        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'failedToUpdate',
              defaultMessage: 'Failed to Update',
            }),
          key,
        });
      });
  }

  return loading && currentJob
    ? <Skeleton active className='dynamic-form-skeleton' />
    : <div style={{ padding: 16 }}>
      {data && currentJob && <ProForm
        initialValues={currentJob}
        onFinish={onFinish}
        submitter={{
          render: (props, doms) => {
            return [
              <Row wrap={false}>
                <Col flex="auto">
                  <Button
                    className="job-history-link"
                    type="link"
                    icon={<Icon style={{ display: 'inline-flex' }} component={() => <img src={HistoryIcon} height={15} width={15} />} />}
                    onClick={() => {
                      onChangeHistoryModalSelctor('ALL');
                      setHistoryModalVisible(true);
                    }}
                  >
                    {intl.formatMessage({
                      id: 'employee_journey_update.job_history',
                      defaultMessage: "Job History",
                    })}
                  </Button>
                </Col>
                <Col flex="none">
                  <Space>
                    <Button onClick={() => props.form?.resetFields()}>
                      {intl.formatMessage({
                        id: 'reset',
                        defaultMessage: "Reset",
                      })}
                    </Button>
                    <Button type="primary" onClick={() => props.form?.submit?.()}>
                      {intl.formatMessage({
                        id: 'save',
                        defaultMessage: "Save",
                      })}
                    </Button>
                  </Space>
                </Col>
              </Row>
            ];
          },
        }}>
        <Row gutter={12}>
          <Col span={12}>
            <ProFormSelect
              name="jobCategoryId"
              label={intl.formatMessage({
                id: 'employee_journey_update.job_category',
                defaultMessage: "Job Category",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.jobCategories}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_job_category',
                defaultMessage: "Select Job Category",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="jobTitleId"
              label={intl.formatMessage({
                id: 'employee_journey_update.job_title',
                defaultMessage: "Job Title",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.jobTitles}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_job_title',
                defaultMessage: "Select Job Title",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <OrgSelector
            value={orgStructureEntityId}
            setValue={(value: number) => setOrgStructureEntityId(value)}
            readOnly={!hasEditPermission()}
          />
          <Col span={12}>
            <ProFormSelect
              name="employmentStatusId"
              label={intl.formatMessage({
                id: 'employee_journey_update.employment_status',
                defaultMessage: "Employment Status",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.employmentStatus}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_employment_status',
                defaultMessage: "Select Employment Status",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="locationId"
              label={intl.formatMessage({
                id: 'employee_journey_update.location',
                defaultMessage: "Location",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.locations}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_location',
                defaultMessage: "Select Location",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="payGradeId"
              label={intl.formatMessage({
                id: 'employee_journey_update.pay_grade',
                defaultMessage: "Pay Grade",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.payGrades}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_pay_grade',
                defaultMessage: "Select Pay Grade",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="calendarId"
              label={intl.formatMessage({
                id: 'employee_journey_update.calendar',
                defaultMessage: "Calendar",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.calendars}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_calendar',
                defaultMessage: "Select Calendar",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="reportsToEmployeeId"
              label={intl.formatMessage({
                id: 'employee_journey_update.reporting_person',
                defaultMessage: "Reporting Person",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.managers}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_reporting_person',
                defaultMessage: "Select Reporting Person",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="functionalReportsToEmployeeId"
              label={intl.formatMessage({
                id: 'employee_journey_update.functional_reporting_person',
                defaultMessage: "Functional Reporting Person",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.managers}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_functional_reporting_person',
                defaultMessage: "Select Functional Reporting Person",
              })}
              rules={[{ required: true, message: 'Required' }]}
            />
          </Col>
          <Col span={12}>
            <ProFormSelect
              name="schemeId"
              label={intl.formatMessage({
                id: 'employee_journey_update.scheme',
                defaultMessage: "Scheme",
              })}
              showSearch
              disabled={!hasEditPermission()}
              options={data?.schemes}
              placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_scheme',
                defaultMessage: "Select Scheme",
              })}
            />
          </Col>
        </Row>
      </ProForm>}
      <Modal
        visible={isHistoryModalVisible}
        title={intl.formatMessage({
          id: 'employee_journey_update.job_history',
          defaultMessage: "Job History",
        })}
        onCancel={() => setHistoryModalVisible(false)}
        footer={null}
        width={1020}
        destroyOnClose={true}
      >
        <Text>
          {intl.formatMessage({
            id: 'Filter',
            defaultMessage: "Filter: ",
          })}
        </Text>
        <Select
          defaultValue={historyModalSelector}
          onChange={onChangeHistoryModalSelctor}
          style={{ marginBottom: 24, width: 180 }}
          options={[
            {
              value: 'ALL',
              label: intl.formatMessage({
                id: 'ALL',
                defaultMessage: "All",
              })
            }, {
              value: 'PROMOTIONS',
              label: intl.formatMessage({
                id: 'PROMOTIONS',
                defaultMessage: "Promotions",
              })
            }, {
              value: 'CONFIRMATION_CONTRACTS',
              label: intl.formatMessage({
                id: 'CONFIRMATION_CONTRACTS',
                defaultMessage: "Confirmation/Contracts",
              })
            }, {
              value: 'TRANSFERS',
              label: intl.formatMessage({
                id: 'TRANSFERS',
                defaultMessage: "Transfers",
              })
            }, {
              value: 'RESIGNATIONS',
              label: intl.formatMessage({
                id: 'RESIGNATIONS',
                defaultMessage: "Resignations",
              })
            },
          ]}
        />
        {historyModalRecords && <EmployeeJourneyHistory data={data} records={historyModalRecords} employee={props.values} />}
      </Modal>
    </div>;
};

export default EmployeeProfileJobSection;
