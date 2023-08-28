import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { Avatar, Button, Card, Col, Modal, Row, Select, Typography, message as Message } from 'antd';
import Meta from 'antd/lib/card/Meta';
import { FormattedMessage, useIntl } from "umi";
import Icon, { UserOutlined } from "@ant-design/icons";
import HistoryIcon from '@/assets/EmployeeJourneyUpdate/history_white.svg';
import Text from 'antd/lib/typography/Text';
import EmployeeJourneyHistory from '@/components/EmployeeJourney/History';
import request from "@/utils/request";
import { getModel } from "@/services/model";
import moment from 'moment';

interface CurrentEmployeeStatusCardProps {
    data: any,
    employee: any,
    currentJob: any,
    mode: 'promotions' | 'confirmation_contracts' | 'transfers' | 'resignations'
}

const CurrentEmployeeStatusCard: React.FC<CurrentEmployeeStatusCardProps> = (props) => {
    const intl = useIntl();

    const [isHistoryModalVisible, setHistoryModalVisible] = useState(false);
    const [historyModalSelector, setHistoryModalSelector] = useState('ALL');
    const [historyModalRecords, setHistoryModalRecords] = useState([]);
    const [profilePicture, setProfilePicture] = useState();
    const [jobs, setJobs] = useState([]);

    useEffect(() => {
        setJobs((props.employee?.jobs ?? []).map((job: any) => {
            return {
                ...job,
                previousRecord: (props.employee?.jobs ?? []).find((_job: any) => _job.id == job.previousRecordId)
            };
        }));
    }, [props.employee]);

    const getProfilePicture = async (empId: any) => {
        try {
            const response = await getModel('employee');
            const path = `/api${response.data.modelDataDefinition.path}/` + empId + `/profilePicture`;
            const { data } = await request(path);

            if (data !== null) {
                setProfilePicture(data['data']);
            }
        } catch (error) {
            setProfilePicture(undefined);
        }
    }

    const onChangeHistoryModalSelctor = (value) => {
        setHistoryModalSelector(value);

        const _records = value == 'ALL'
            ? jobs
            : jobs?.filter(job => job.employeeJourneyType == value);

        setHistoryModalRecords(_records);
    }

    return <>
        <Typography.Title level={5} style={{ marginTop: 24 }}>
            {intl.formatMessage({
                id: 'employee_journey_update.current_employee_status',
                defaultMessage: "Current Employment Status",
            })}
        </Typography.Title>
        <Card
            title={<Meta
                avatar={props.employee.profilePicture ? <Avatar src={profilePicture} /> : <Avatar icon={<UserOutlined />}
                />}
                title={props.employee.employeeName}
            />}
            extra={
                <Button
                    className="job-history-button"
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
            }
        >
            <Row>
                {/* Employee Number */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.employee_number"
                                defaultMessage="Employee Number"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.employee.employeeNumber}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Org Structure */}
                {
                    !_.isEmpty(props.currentJob?.orgStructureEntity)
                        ? Object.keys(props.currentJob?.orgStructureEntity).map(level => <Col span={8}>
                            <Row>
                                <Col span={12}>{level}</Col>
                                <Col span={12}>
                                    <Typography.Text className='colon-before-text'>
                                        {props.currentJob?.orgStructureEntity[level].name}
                                    </Typography.Text>
                                </Col>
                            </Row>
                        </Col>)
                        : null
                }
                {/* Pay Grade */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.pay_grade"
                                defaultMessage="Pay Grade"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.payGrades?.find(record => record.value == props.currentJob?.payGradeId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Job Category */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.job_category"
                                defaultMessage="Job Category"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.jobCategories?.find(record => record.value == props.currentJob?.jobCategoryId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Appintment Date */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.hire_date"
                                defaultMessage="Hire Date"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {moment(props.employee.hireDate).format("DD-MM-YYYY")}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Job Title */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.job_title"
                                defaultMessage="Job Title"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.jobTitles?.find(record => record.value == props.currentJob?.jobTitleId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Location */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.location"
                                defaultMessage="Location"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.locations?.find(record => record.value == props.currentJob?.locationId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Reporting Person */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.reporting_person"
                                defaultMessage="Reporting Person"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.employees?.find(record => record.value == props.currentJob?.reportsToEmployeeId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Functional Reporting Person */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.functional_reporting_person"
                                defaultMessage="Functional Reporting Person"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.employees?.find(record => record.value == props.currentJob?.functionalReportsToEmployeeId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Calendar */}
                <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.calendar"
                                defaultMessage="Calendar"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.data?.calendars?.find(record => record.value == props.currentJob?.calendarId)?.label}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>
                {/* Calendar */}
                {props.mode == 'confirmation_contracts' && <Col span={8}>
                    <Row>
                        <Col span={12}>
                            <FormattedMessage
                                id="employee_journey_update.contract_renewal_date"
                                defaultMessage="Contract Renewal Date"
                            />
                        </Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.employee.contractRenewalDate}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>}
            </Row>
        </Card>
        <Modal
            visible={isHistoryModalVisible}
            title={intl.formatMessage({
                id: 'employee_journey_update.job_history',
                defaultMessage: "Job History",
            })}
            onCancel={() => setHistoryModalVisible(false)}
            footer={null}
            width={720}
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
            <EmployeeJourneyHistory data={props?.data} records={historyModalRecords} employee={props.employee} />
        </Modal>
    </>
};

export default CurrentEmployeeStatusCard;
