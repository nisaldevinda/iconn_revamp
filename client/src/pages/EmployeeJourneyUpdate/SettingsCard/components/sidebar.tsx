import React, { useState, useEffect } from 'react';
import { Menu, Layout } from 'antd';
import getEmployeeJourneyConfigurations from '../routes';
import { useParams, history, useAccess } from 'umi';
import _ from 'lodash';

const { Content, Sider } = Layout;

const EmployeeJourneyConfigurationSidebar: React.FC = () => {
    const [configurations, setConfigurations] = useState([]);
    const [content, setContent] = useState();
    const { id }: any = useParams();

    const access = useAccess();
    const { hasPermitted } = access;

    useEffect(() => {
        const _content = id ? _.find(configurations, (o: any) => o.key == id)?.component : <></>;
        setContent(_content);
    }, [id]);

    useEffect(() => {
        init();
    }, []);

    const init = async () => {
        const _configurations: any = await getEmployeeJourneyConfigurations(hasPermitted);
        setConfigurations(_configurations);
        const _content = id ? _.find(_configurations, o => o.key == id)?.component : <></>;
        setContent(_content);
    }

    if (configurations && configurations.length > 0) {
        return (
            <Layout>
                <Sider className="master-data-sider">
                    <Menu
                        defaultSelectedKeys={[id]}
                        className="master-data-sider-menu"
                        mode="inline"
                        onClick={({ key }) => history.push(`/settings/employee-journey-configurations/${key}`)}
                    >
                        {configurations.map((items: any) => (
                            <Menu.Item key={items.key}>{items.name}</Menu.Item>
                        ))}
                    </Menu>
                </Sider>
                <Content className="master-data-content">
                    {content}
                </Content>
            </Layout>
        );
    } else {
        return <></>;
    }
};

export default EmployeeJourneyConfigurationSidebar;
