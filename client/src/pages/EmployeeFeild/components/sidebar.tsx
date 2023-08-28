import React, { useState, useEffect } from 'react';
import { Menu, Layout } from 'antd';
import getMasterDataRoutes from '../routes';
import { useParams, history } from 'umi';
import _ from 'lodash';

const { Content, Sider } = Layout;

const EmployeeFeildSidebar: React.FC = () => {
  const [masterDataRoutes, setMasterDataRoutes] = useState([]);
  const [content, setContent] = useState();
  const { id } = useParams();

  useEffect(() => {
    const _content = id ? _.find(masterDataRoutes, o => o.key == id)?.component : <></>;
    setContent(_content);
  }, [id]);

  useEffect(() => {
    init();
  }, []);

  const init = async () => {
    const _masterDataRoutes = await getMasterDataRoutes();
    setMasterDataRoutes(_masterDataRoutes);
    const _content = id ? _.find( _masterDataRoutes, o => o.key == id)?.component : <></>;
    setContent(_content);
  }

  if (masterDataRoutes && masterDataRoutes.length > 0) {
    return (
      <Layout>
        <Sider className="master-data-sider">
          <Menu
            defaultSelectedKeys={[id]}
            className="master-data-sider-menu"
            mode="inline"
            onClick={({ key }) => history.push(`/settings/master-data/${key}`)}
          >
            {masterDataRoutes.map((items, key) => (
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

export default EmployeeFeildSidebar;
