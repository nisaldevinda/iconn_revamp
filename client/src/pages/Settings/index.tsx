import { Col, Row } from 'antd';
import React from 'react';
import { useAccess } from 'umi';
import './index.css';

import EmployeeFieldsCard from './EmployeeFieldsCard';
import AccessLevelsCard from './AccessLevelsCard';
import ShiftConfigurationsCard from './ShiftConfigurationsCard';
import LeaveConfigurationsCard from './LeaveConfigurationsCard';
import CompensationsAndBenefitsCard from './CompensationsAndBenefitsCard';
import GeneralSettingsCard from './GeneralSettingsCard';
import EmployeeJourneyConfigurationCard from './EmployeeJourneyConfigurationCard';

const SettingsPage: React.FC = () => {
    const access = useAccess();
    const { hasPermitted, hasAnyPermission } = access;

    const hasPermittedEmployeeFieldsCard = () => {
        return hasPermitted('master-data-write')
    }

    const hasPermittedAccessLevelsCard = () => {
        return hasAnyPermission(['user-read-write', 'access-levels-read-write'])
    }

    const hasPermittedShiftConfigurationsCard = () => {
        return hasAnyPermission([
            'work-shifts-read-write',
            'work-calendar-read-write',
            'work-pattern-read-write',
            'work-calendar-day-type-read-write',
            'pay-type-read-write',
            'shifts-assign-read-write',
        ])
    }

    const hasPermittedLeaveConfigurationsCard = () => {
        return hasAnyPermission(['leave-type-config'])
    }

    const hasPermittedCompensationsAndBenefitsCard = () => {
        return hasAnyPermission([
            'financial-year-read-write',
            'expense-management-read-write',
        ])
    }

    const hasPermittedGeneralSettingsCard = () => {
        return hasAnyPermission([
            'bulk-upload-read-write',
            'company-info-read-write',
            'document-template-read-write',
            'manual-process',
            'workflow-management-read-write',
            'scheduled-jobs-log',
            'email-template-read-write',
        ])
    }

    const hasPermittedEmployeeJourneyConfigurationCard = () => {
        return hasPermitted('master-data-write')
    }

    return (
        <>
            <p className="settings-title">Settings</p>
            <Row className='settingSections' gutter={[16, { xs: 8, sm: 16, md: 24, lg: 32 }]}>
                {/* Employee Fields Card */}
                {hasPermittedEmployeeFieldsCard() &&
                    <Col span={6}>
                        <Row>
                            <EmployeeFieldsCard />
                        </Row>
                    </Col>
                }

                {/* Access Levels Card And Shift Configurations Card */}
                {hasPermittedAccessLevelsCard() && hasPermittedShiftConfigurationsCard() &&
                    <Col span={6}>
                        {/* Access Levels Card */}
                        {hasPermittedAccessLevelsCard() &&
                            <Row>
                                <AccessLevelsCard />
                            </Row>
                        }
                        {/* Shift Configurations Card */}
                        {hasPermittedShiftConfigurationsCard() &&
                            <Row>
                                <ShiftConfigurationsCard />
                            </Row>
                        }
                    </Col>
                }

                {/* Leave Configurations Card, Compensations And Benefits Card and Employee Journey Configuration Card */}
                {hasPermittedLeaveConfigurationsCard() &&
                    hasPermittedCompensationsAndBenefitsCard() &&
                    hasPermittedEmployeeJourneyConfigurationCard() &&
                    <Col span={6}>
                        {/* Leave Configurations Card */}
                        {hasPermittedLeaveConfigurationsCard() &&
                            <Row>
                                <LeaveConfigurationsCard />
                            </Row>
                        }

                        {/* Compensations And Benefits Card */}
                        {hasPermittedCompensationsAndBenefitsCard() &&
                            <Row>
                                <CompensationsAndBenefitsCard />
                            </Row>
                        }

                        {/* Employee Journey Configuration Card */}
                        {hasPermittedEmployeeJourneyConfigurationCard() &&
                            <Row>
                                <EmployeeJourneyConfigurationCard />
                            </Row>
                        }
                    </Col>
                }

                {/* General Settings Card */}
                {hasPermittedGeneralSettingsCard() &&
                    <Col span={6}>
                        <Row>
                            <GeneralSettingsCard />
                        </Row>
                    </Col>
                }
            </Row>
        </>
    );
};
export default SettingsPage;
