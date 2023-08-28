import { Avatar, Card, Row } from "antd";
import { FormattedMessage, Link, useAccess } from "umi";
import shiftConfiguration from '../../assets/icon-shift-configuration-with-background.svg';

const CompensationsAndBenefitsCard: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return <Card
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={shiftConfiguration} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.section.compensationAndBenifits"
                        defaultMessage="Compensations And Benefits"
                    />
                </span>
            </>
        }
    >
        {hasPermitted('financial-year-read-write') ? (
            <Row>
                <Link data-key="dayType" to="/settings/financial-year">
                    <FormattedMessage id="settings.FinantialYearConfiguration" defaultMessage="Financial Year Configurations" />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('expense-management-read-write') ? (
            <Row>
                <Link data-key="payTypes" to="/settings/expense-module">
                    <FormattedMessage id="settings.expenseManagementConfiguration" defaultMessage="Expense Management Configurations" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
    </Card>
}

export default CompensationsAndBenefitsCard;
