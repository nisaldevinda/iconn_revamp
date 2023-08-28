import React from 'react';
import { Button, Col, Row, Tabs } from 'antd';
import { downloadTemplate, uploadTemplate } from '@/services/bulkUpload';
import EmployeeBulkUpload from '@/pages/BulkUpload/employee-bulk-upload/index';
import LeaveBulkUpload from '@/pages/BulkUpload/leave-bulk-upload/index';
import { useIntl, FormattedMessage } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, history } from 'umi';
import PermissionDeniedPage from '@/pages/403';
const { TabPane } = Tabs;

const BulkUpload: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <>
      <Access
        accessible={hasPermitted('bulk-upload-read-write')}
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer style={{ height: '80vh' }}>
          <Row>
            <Col span={24}>
              <div className="bulkUploadCard">
                <Tabs type="card" onChange={(value) => {}}>
                  <TabPane forceRender={true} tab="Profile Bulk Upload" key="all">
                    <EmployeeBulkUpload></EmployeeBulkUpload>
                  </TabPane>
                  <TabPane forceRender={true} tab="Leave Bulk Upload" key="2">
                    <LeaveBulkUpload></LeaveBulkUpload>
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

export default BulkUpload;
