import { Avatar, Card, Row } from "antd";
import { FormattedMessage, Link } from "umi";
import AccessLevelIcon from '../../assets/access-levels.svg';

const AccessLevelsCard: React.FC = () => {
    return <Card
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={AccessLevelIcon} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.AccessLevels "
                        defaultMessage="Access Levels"
                    />
                </span>
            </>
        }
    >
        <Row>
            <Link data-key="user" to="/settings/users">
                <FormattedMessage id="settings.Users" defaultMessage="Users" />
            </Link>
        </Row>
        <Row>
            <Link data-key="userRoles" to="/settings/accesslevels">
                <FormattedMessage id="settings.UserRoles " defaultMessage="User Roles" />
            </Link>
        </Row>
    </Card>
}

export default AccessLevelsCard;
