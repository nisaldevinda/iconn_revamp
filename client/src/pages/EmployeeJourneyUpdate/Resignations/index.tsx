import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { useIntl } from "umi";
import { Col, message, Row, Select, Typography } from 'antd';
import CurrentEmployeeStatusCard from '../Components/CurrentEmployeeStatusCard';
import CreateResignation from './CreateResignation';
import { getEmployee } from '@/services/employee';
import Skeleton from '../Components/Skeleton';
import EmployeeJourneyHistory from '@/components/EmployeeJourney/History';
import EmployeeJourneyUpcoming from '@/components/EmployeeJourney/Upcoming';
import { getEntity } from '@/services/department';

interface ResignationsProps {
    data: any
}

const Resignations: React.FC<ResignationsProps> = (props) => {
    const intl = useIntl();

    const [loading, setLoading] = useState(false);
    const [employee, setEmployee] = useState<any>();
    const [currentJob, setCurrentJob] = useState();

    useEffect(() => {
        if (!_.isEmpty(employee)) {
            const _currentJob = employee.jobs?.find(job => job.id == employee.currentJobsId);
            setCurrentJob(_currentJob);
        }
    }, [employee]);

    const retrieveEmployee = async (id) => {
        setLoading(true);
        const response = await getEmployee(id);

        if (response.error) {
            message.error(response.message);
            return;
        }

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
            setLoading(false);
        });
    }

    return (<div style={{ marginTop: 24, padding: 4 }}>
        <Typography.Title level={5}>
            {intl.formatMessage({
                id: 'employee_journey_update.resignations',
                defaultMessage: "Resignations",
            })}
        </Typography.Title>
        <Select
            showSearch
            placeholder={intl.formatMessage({
                id: 'employee_journey_update.select_employee',
                defaultMessage: "Select Employee",
            })}
            optionFilterProp="children"
            style={{ width: 256 }}
            onChange={retrieveEmployee}
            filterOption={(input, option) =>
                (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
            }
            options={props.data?.employees}
        />

        {loading
            ? <Skeleton />
            : employee && <>
                <Row>
                    <Col span={24}>
                        <CurrentEmployeeStatusCard
                            data={props.data}
                            employee={employee}
                            currentJob={currentJob}
                            mode='resignations'
                        />
                    </Col>
                </Row>
                <Row gutter={10}>
                    <Col span={12}>
                        <CreateResignation
                            data={props.data}
                            employee={employee}
                            setEmployee={setEmployee}
                            hasUpcomingJobs={!_.isEmpty(employee?.jobs?.filter(job => job.employeeJourneyType == 'RESIGNATIONS' && !job.isRollback && job.effectiveDate > props?.data?.companyDate) ?? [])}
                        />
                    </Col>
                    <Col span={12}>
                        <EmployeeJourneyUpcoming
                            title={intl.formatMessage({
                                id: 'employee_journey_update.upcoming_resignation',
                                defaultMessage: "Upcoming Resignation",
                            })}
                            employee={employee}
                            setEmployee={setEmployee}
                            data={props.data}
                            records={employee?.jobs?.map((job: any) => {
                                return {
                                    ...job,
                                    previousRecord: (employee?.jobs ?? []).find((_job: any) => _job.id == job.previousRecordId)
                                };
                            })
                                .filter(job => job.employeeJourneyType == 'RESIGNATIONS' && job.effectiveDate > props?.data?.companyDate) ?? []}
                        />
                        <EmployeeJourneyHistory
                            title={intl.formatMessage({
                                id: 'employee_journey_update.resignation_history',
                                defaultMessage: "Resignation History",
                            })}
                            employee={employee}
                            data={props.data}
                            records={employee?.jobs?.map((job: any) => {
                                return {
                                    ...job,
                                    previousRecord: (employee?.jobs ?? []).find((_job: any) => _job.id == job.previousRecordId)
                                };
                            })
                                .filter(job => job.employeeJourneyType == 'RESIGNATIONS' && job.effectiveDate <= props?.data?.companyDate) ?? []}
                        />
                    </Col>
                </Row>
            </>}
    </div>);
};

export default Resignations;
