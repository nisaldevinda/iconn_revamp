import React from 'react';
import { Button, Col, Row, Card,Spin, message, } from 'antd';
import { downloadLeaveTemplate, uploadLeaveTemplate } from '@/services/bulkUpload';
import TemplateModal from '@/pages/BulkUpload/employee-bulk-upload/templateModel';
import Validator from '@/pages/BulkUpload/leave-bulk-upload/validator';
import { useIntl, FormattedMessage } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, history } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import { UploadOutlined, DownloadOutlined } from '@ant-design/icons';
import { APIResponse } from '@/utils/request';
import { downloadBase64File } from '@/utils/utils';
import '../style.css';

const LeaveBulkUpload: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const downloadTemplate = async () => {
    const key = 'download-template';
    downloadLeaveTemplate().then((response: APIResponse) => {
      if (response.error) {
        message.error({
          content:
            response.message ??
            intl.formatMessage({
              id: 'failedToDownload',
              defaultMessage: 'Failed to download',
            }),
          key,
        });
        return;
      }
      if (!_.isUndefined(response.data) || !_.isEmpty(response.data)) {
        downloadBase64File(
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          response.data,
          'document.xlsx',
        );
      }
      message.success({
        content:
          response.message ??
          intl.formatMessage({
            id: 'successfullyDownloaded',
            defaultMessage: 'Successfully downloaded',
          }),
        key,
      });
    })
    .catch((error: APIResponse) => {
      message.error({
        content:
          error.message ??
          intl.formatMessage({
            id: 'failedToDownload',
            defaultMessage: 'Failed to download',
          }),
        key,
      });
    });
  }

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
                  <Button type="default" onClick={downloadTemplate} icon={<DownloadOutlined style={{paddingRight: 10}} />} style={{borderRadius: 6, textAlign: 'right'}} key="console">
                    <FormattedMessage
                      id="bulk-upload-download-leave-template"
                      defaultMessage="Download Leave Template"
                    />
                  </Button>
                </Col>
              </Row>,
            ]}
            onFileUpload={uploadLeaveTemplate}
            intl={intl}
          />
        </Col>
      </Row>
    </>
  );
};

export default LeaveBulkUpload;
