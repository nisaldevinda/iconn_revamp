import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { Access, useAccess, useIntl } from 'umi';
import { Col, message, Row } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import EmployeeStatusCard from './Components/EmployeeStatusCard';
import CreateResignation from './ResignationForm';
import Skeleton from './Components/Skeleton';
import myInfoService from '@/services/myInfo';

import { getAllDepartment } from '@/services/department';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllDivisions } from '@/services/divsion';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getAllTerminationReasons } from '@/services/terminationReason'
import { getManagerList } from '@/services/dropdown';
import { queryPayGrades } from '@/services/PayGradeService';
import { getAllPromotionTypes } from '@/services/promotionTypes';
import { getAllConfirmationReasons } from '@/services/confirmationReasons';
import { getAllTransferTypes } from '@/services/transferTypes';
import { getAllResignationTypes } from '@/services/resignationTypes';
import { getCalendarList } from '@/services/workCalendarService';
import { getEntity } from '@/services/department';
import PermissionDeniedPage from '../403';

interface ResignationsProps {
  data: any;
}

const Resignations: React.FC<ResignationsProps> = (props) => {
  const access = useAccess();
  const { hasPermitted } = access;

  const [loading, setLoading] = useState(false);
  const [employee, setEmployee] = useState<any>();
  const [currentJob, setCurrentJob] = useState();
  const [data, setData] = useState();

  useEffect(() => {
    init();
  }, []);

  const init = async () => {
    if (_.isEmpty(data)) {
      setLoading(true);

      let _data = {};
      let callStack = [];

      // retrieve all employee
      callStack.push(
        getManagerList()
          .then(
            (response) =>
            (_data = {
              ..._data,
              managers:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.employeeName,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all employee
      callStack.push(
        getAllEmploymentStatus()
          .then(
            (response) =>
            (_data = {
              ..._data,
              employmentStatus:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.title,
                    record: record,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all department
      callStack.push(
        getAllDepartment()
          .then(
            (response) =>
            (_data = {
              ..._data,
              departments:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all job category
      callStack.push(
        getAllJobCategories()
          .then(
            (response) =>
            (_data = {
              ..._data,
              jobCategories:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all division
      callStack.push(
        getAllDivisions()
          .then(
            (response) =>
            (_data = {
              ..._data,
              divisions:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all job title
      callStack.push(
        getAllJobTitles()
          .then(
            (response) =>
            (_data = {
              ..._data,
              jobTitles:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all location
      callStack.push(
        getAllLocations()
          .then(
            (response) =>
            (_data = {
              ..._data,
              locations:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all calendars
      callStack.push(
        getCalendarList()
          .then(
            (response) =>
            (_data = {
              ..._data,
              calendars:
                response?.data.map((record) => {
                  return {
                    value: record.calendarId,
                    label: record.menuItemName,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all pay grade
      callStack.push(
        queryPayGrades()
          .then(
            (response) =>
            (_data = {
              ..._data,
              payGrades:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all Promotion Type
      callStack.push(
        getAllPromotionTypes()
          .then(
            (response) =>
            (_data = {
              ..._data,
              promotionTypes:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all Confirmation Reason
      callStack.push(
        getAllConfirmationReasons()
          .then(
            (response) =>
            (_data = {
              ..._data,
              confirmationReasons:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all Transfer Type
      callStack.push(
        getAllTransferTypes()
          .then(
            (response) =>
            (_data = {
              ..._data,
              transferTypes:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all Resignation Type
      callStack.push(
        getAllResignationTypes()
          .then(
            (response) =>
            (_data = {
              ..._data,
              resignationTypes:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      // retrieve all Resignation Reason
      callStack.push(
        getAllTerminationReasons()
          .then(
            (response) =>
            (_data = {
              ..._data,
              resignationReasons:
                response?.data.map((record) => {
                  return {
                    value: record.id,
                    label: record.name,
                  };
                }) ?? [],
            }),
          )
          .catch((error) => message.error(error?.message)),
      );

      Promise.all(callStack).then(() => {
        setData(_data);
        setLoading(false);
        fetchEmployeeData();
      });
    }
  };

  useEffect(() => {
    if (!_.isEmpty(employee)) {
      const _currentJob = employee.jobs?.find((job) => job.id == employee.currentJobsId);
      console.log(_currentJob);
      setCurrentJob(_currentJob);
    }
  }, [employee]);

  const fetchEmployeeData = async () => {
    await myInfoService.getEmployeeViewData().then((response: any) => {
      if (response && response.data) {
        console.log(response.data);


        const getEntityCallStack = [];
        let entityList = {};
        response.data.jobs.forEach(job => {
          if (job.orgStructureEntityId && !entityList[job.orgStructureEntityId]) {
            getEntityCallStack.push(getEntity(job.orgStructureEntityId).then(data => {
              entityList[job.orgStructureEntityId] = data.data;
            }));
          }
        });

        Promise.all(getEntityCallStack).then(() => {
          response.data.jobs = response.data.jobs.map(job => {
            return {
              ...job,
              orgStructureEntity: entityList[job.orgStructureEntityId]
            };
          });
          setEmployee(response.data);
          // setLoading(false);
        });

        // setEmployee(response.data);
      }
    });
  };

  return (
    <PageContainer>
      <Access
        accessible={hasPermitted('my-leave-entitlements')}
        fallback={<PermissionDeniedPage />}
      >
        <div style={{ marginTop: -25, padding: 4 }}>
          {loading ? (
            <Skeleton />
          ) : (
            employee && (
              <>
                <Row>
                  <Col span={24}>
                    <EmployeeStatusCard
                      data={data}
                      employee={employee}
                      currentJob={currentJob}
                      mode="resignations"
                    />
                  </Col>
                </Row>
                <Row gutter={10}>
                  <Col span={18}>
                    <CreateResignation
                      data={data}
                      employee={employee}
                      setEmployee={setEmployee}
                      hasUpcomingJobs={
                        !_.isEmpty(
                          employee?.jobs?.filter(
                            (job) =>
                              job.employeeJourneyType == 'RESIGNATIONS' &&
                              !job.isRollback &&
                              job.effectiveDate > props?.data?.companyDate,
                          ) ?? [],
                        )
                      }
                    />
                  </Col>
                </Row>
              </>
            )
          )}
        </div>{' '}
      </Access>
    </PageContainer>
  );
};

export default Resignations;
