import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import Dash_ShowcaseLayout from './Dash_ShowcaseLayout';
import { updateDashboard } from '@/services/dashboard';
import './styles.css';
import './example-styles.css';
import { APIResponse } from '@/utils/request';
import { Button, Col, Row } from 'antd';
import Title from 'antd/lib/typography/Title';
import { DownloadOutlined } from '@ant-design/icons';
import DrawerView from '@/components/Attendance/DrawerView';
import { Access, useAccess } from 'umi';
import PermissionDeniedPage from './403';
import { getAttendance, getLastLogged } from '@/services/attendance';
import styles from './Dashboard.less';
import _ from 'lodash';
import TimeLogButton from '@/components/Dashboard/TimeLogButton';

export default (): React.ReactNode => {
  const [layout, setLayoutModel] = useState([]);
  const access = useAccess();
  const { hasPermitted } = access;

  const onLayoutChange = async (layout: any) => {
    setLayoutModel(layout);
    await onSaveChangedLayout(layout);
  };

  const onSaveChangedLayout = async (layoutPassed: any) => {
    // await updateDashboard(layout)
    await updateDashboard(layoutPassed)
      .then((response: APIResponse) => {})
      .catch((error: APIResponse) => {});
  };

  return (
    <div>
      {/* <Row style={{ marginBottom: '16px' }}>
        <Col span={12}>
          <Title level={1} style={{ marginTop: '16px' }}>
            Dashboard
          </Title>
        </Col>
        <Access accessible={hasPermitted('attendance-employee-access')}>
          <TimeLogButton />
        </Access>
      </Row> */}
      <Row>
        <Col span={24}>
          <Dash_ShowcaseLayout onLayoutChange={onLayoutChange} />
        </Col>
      </Row>
    </div>
  );
};
