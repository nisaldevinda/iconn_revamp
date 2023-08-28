import React from 'react';
import { Button, Col, Row, Tabs } from 'antd';
import { downloadTemplate, uploadTemplate } from '@/services/bulkUpload';
import LockConfiguration from '@/pages/SelfServiceLock/LockConfigurations/index';
import PeriodConfiguration from '@/pages/SelfServiceLock/PeriodConfigurations/index';
import { useIntl, FormattedMessage } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, history } from 'umi';
import PermissionDeniedPage from '@/pages/403';
const { TabPane } = Tabs;

const SelfServiceLock: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <>
      <Access
        accessible={hasPermitted('self-service-lock')}
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer style={{ height: '80vh' }}>
          <Row>
            <Col span={24}>
              <div className="selfServiceLockCard">
                <Tabs type="card" onChange={(value) => {}}>
                  <TabPane forceRender={true} tab="Period Configuration" key="periodCofiguration">
                    <PeriodConfiguration></PeriodConfiguration>
                  </TabPane>
                  <TabPane forceRender={true} tab="Self Service Lock" key="selfServiceLock">
                    <LockConfiguration></LockConfiguration>
                  </TabPane>
                </Tabs>
              </div>
            </Col>
          </Row>
        </PageContainer>
      </Access>
    </>
  );
};

export default SelfServiceLock;
