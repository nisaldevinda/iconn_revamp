import { Avatar, Card, Divider, Row, Typography } from "antd";
import { FormattedMessage, Link, useAccess } from "umi";
import EmployeeFieldsIcon from '../../assets/employee-fields.svg';
import { useEffect, useState } from "react";
import getMasterDataRoutes from '../EmployeeFeild/routes';

const EmployeeFieldsCard: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    const [loading, setLoading] = useState(false);
    const [masterDataRoutes, setMasterDataRoutes] = useState([]);
    const [dynamicFormRoutes, setDynamicFormRoutes] = useState([]);

    useEffect(() => {
        init();
    }, []);

    const init = async () => {
        setLoading(true);

        const _masterDataRoutes = await getMasterDataRoutes();
        setMasterDataRoutes(_masterDataRoutes.filter(route => !route.type));
        setDynamicFormRoutes(_masterDataRoutes.filter(route => route.type == 'formBuilder'));

        setLoading(false);
    }

    return <Card
        loading={loading}
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={EmployeeFieldsIcon} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.EmployeeFields"
                        defaultMessage="Employee Fields"
                    />
                </span>
            </>
        }
    >
        {masterDataRoutes.map((items, key) => (
            <Row>
                <Link data-key={items.key} to={`/settings/master-data/${items.key}`}>
                    {items.name}
                </Link>
            </Row>
        ))}

        {hasPermitted('employee-create') ? (
            <Row>
                <Link to="/settings/employee-number">
                    <FormattedMessage
                        id="settings.employeeNumber"
                        defaultMessage="Employee Number Configuration"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {dynamicFormRoutes.length > 0 && (
            <>
                <Divider style={{ margin: '10px 0px' }} orientation="left" />
                <Typography.Text type="secondary">
                    <FormattedMessage id="settings.customFields" defaultMessage="Custom Fields" />
                </Typography.Text>
            </>
        )}

        {dynamicFormRoutes.map((items, key) => (
            <Row>
                <Link data-key={items.key} to={`/settings/master-data/${items.key}`}>
                    {items.name}
                </Link>
            </Row>
        ))}
    </Card>
}

export default EmployeeFieldsCard;
