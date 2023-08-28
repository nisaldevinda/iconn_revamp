import React from 'react';
import { Button, Col, Row } from 'antd';
import { downloadTemplate, uploadTemplate } from '@/services/bulkUpload';
import TemplateModal from '@/pages/BulkUpload/employee-bulk-upload/templateModel';
import Validator from '@/pages/BulkUpload/employee-bulk-upload/validator';
import { useIntl, FormattedMessage } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, history } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import '../style.css';

const EmployeeBulkUpload: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <>
      <Row>
        <Col span={24}>
          <Validator
            cardTitleRender={[
              <Row
                style={{
                  marginTop: 8,
                  float: 'right',
                  display: 'flex',
                  marginRight: '2vh',
                }}
              >
                <Col span={15}>
                  <TemplateModal
                    trigger={
                      <Button type="primary" key="console">
                        <FormattedMessage
                          id="bulk-upload-download-template"
                          defaultMessage="Download Template"
                        />
                      </Button>
                    }
                    cardTitle={intl.formatMessage({
                      id: 'bulk-upload-download-template-download-text',
                      defaultMessage: 'Download Template',
                    })}
                    onSubmit={downloadTemplate}
                  />
                </Col>
                <Col span={9}>
                  <Button
                    type="primary"
                    key="bulk-upload-history"
                    onClick={() => {
                      history.push('bulk-upload/viewHistory');
                    }}
                  >
                    <FormattedMessage id="bulk-upload-view-history" defaultMessage="View History" />
                  </Button>
                </Col>
              </Row>,
            ]}
            onFileUpload={uploadTemplate}
            intl={intl}
          />
        </Col>
      </Row>
    </>
  );
};

export default EmployeeBulkUpload;
