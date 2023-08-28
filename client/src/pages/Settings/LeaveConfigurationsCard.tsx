import { Avatar, Card, Row } from "antd";
import { FormattedMessage, Link, useAccess } from "umi";
import LeaveConfigIcon from '../../assets/leave-config.svg';

const LeaveConfigurationsCard: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return <Card
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={LeaveConfigIcon} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.LeaveConfigurations  "
                        defaultMessage="Leave Configurations "
                    />
                </span>
            </>
        }
    >
        {hasPermitted('leave-type-config') ? (
            <Row>
                <Link data-key="leaveTypes" to="/settings/leave-types">
                    <FormattedMessage id="settings.LeavesTypes " defaultMessage="Leave Types" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
    </Card>
}

export default LeaveConfigurationsCard;
