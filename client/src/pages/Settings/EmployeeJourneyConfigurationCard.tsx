import { Avatar, Card, Row } from "antd";
import { FormattedMessage, Link, useAccess } from "umi";
import EmployeeFieldsIcon from '../../assets/employee-fields.svg';

const EmployeeJourneyConfigurationCard: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return <Card
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={EmployeeFieldsIcon} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.EmployeeJourneyConfiguration"
                        defaultMessage="Employee Journey Configuration"
                    />
                </span>
            </>
        }
    >
        <Row>
            <Link data-key="promotionType" to="/settings/employee-journey-configurations/promotion-type">
                <FormattedMessage id="settings.PromotionType" defaultMessage="Promotion Type" />
            </Link>
        </Row>
        <Row>
            <Link data-key="confirmationReason" to="/settings/employee-journey-configurations/confirmation-reason">
                <FormattedMessage id="settings.ConfirmationReason" defaultMessage="Confirmation Reason" />
            </Link>
        </Row>
        <Row>
            <Link data-key="transferType" to="/settings/employee-journey-configurations/transfer-type">
                <FormattedMessage id="settings.TransferType" defaultMessage="Transfer Type" />
            </Link>
        </Row>
        <Row>
            <Link data-key="resignationType" to="/settings/employee-journey-configurations/resignation-type">
                <FormattedMessage id="settings.ResignationType" defaultMessage="Resignation Type" />
            </Link>
        </Row>
        <Row>
            <Link data-key="resignationReason" to="/settings/employee-journey-configurations/resignation-reason">
                <FormattedMessage id="settings.ResignationReason" defaultMessage="Resignation Reason" />
            </Link>
        </Row>
        {hasPermitted('config-resignation-process-read-write') &&
            <Row>
                <Link data-key="resignatioProcessConfiguration" to="/settings/employee-journey-configurations/config-resignation-process">
                    <FormattedMessage id="settings.ResignationProcessConfiguration" defaultMessage="Resignation Process Configuration" />
                </Link>
            </Row>
        }
    </Card>
}

export default EmployeeJourneyConfigurationCard;
