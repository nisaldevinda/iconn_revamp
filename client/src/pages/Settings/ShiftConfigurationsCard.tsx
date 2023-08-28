import { Avatar, Card, Row } from "antd";
import { FormattedMessage, Link, useAccess } from "umi";
import shiftConfiguration from '../../assets/icon-shift-configuration-with-background.svg';

const ShiftConfigurationsCard: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return <Card
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={shiftConfiguration} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.section.shiftConfiguration"
                        defaultMessage="Shift Configurations"
                    />
                </span>
            </>
        }
    >
        {hasPermitted('work-calendar-day-type-read-write') ? (
            <Row>
                <Link data-key="dayType" to="/settings/work-calander-day-type">
                    <FormattedMessage id="settings.DayType" defaultMessage="Day Types" />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('pay-type-read-write') ? (
            <Row>
                <Link data-key="payTypes" to="/settings/pay-type">
                    <FormattedMessage id="settings.PayType" defaultMessage="Pay Types" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('work-calendar-read-write') ? (
            <Row>
                <Link data-key="workCalendar" to="/settings/work-calander">
                    <FormattedMessage
                        id="settings.WorkCalendar"
                        defaultMessage="Work Calendars"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('work-pattern-read-write') ? (
            <Row>
                <Link data-key="workPattern" to="/settings/work-patterns">
                    <FormattedMessage
                        id="settings.WorkPattern"
                        defaultMessage="Work Patterns"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('work-shifts-read-write') ? (
            <Row>
                <Link data-key="workShifts" to="/settings/work-shifts">
                    <FormattedMessage id="settings.workShifts" defaultMessage="Work Shifts" />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('shifts-assign-read-write') ? (
            <Row>
                <Link data-key="workShifts" to="/settings/shifts-assign">
                    <FormattedMessage id="settings.shiftAssign" defaultMessage="Shift Assign" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('work-pattern-assign-read-write') ? (
            <Row>
                <Link data-key='workPatternAssign' to="/settings/work-pattern-assign">
                    <FormattedMessage id="settings.workPatternAssign" defaultMessage="Work Pattern Assign" />
                </Link>
            </Row>
        ) : (
            <></>
        )}

    </Card>
}

export default ShiftConfigurationsCard;
