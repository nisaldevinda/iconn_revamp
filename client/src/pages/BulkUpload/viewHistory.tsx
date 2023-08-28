import React from 'react';
import { Tooltip, message } from 'antd';
import { getAllBulkUploadedHistory, getFileObject } from '@/services/bulkUpload';
import { useIntl, FormattedMessage } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import ProTable, { ProColumns } from '@ant-design/pro-table';
import { DownloadOutlined } from '@ant-design/icons';
import { humanReadableFileSize } from '@/utils/utils';
import { APIResponse } from '@/utils/request';
import { downloadBase64File } from '@/utils/utils';
import moment from 'moment';
const ViewHistory: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const renderActionsToolBar = (entity: any, record: any) => [
    <Tooltip
      key="download-tool-tip"
      title={intl.formatMessage({
        id: 'pages.document.download_tooltip',
        defaultMessage: 'Download',
      })}
    >
      <a
        key="download-btn"
        onClick={() => {
          const messageKey = 'downloading';
          message.loading({
            content: intl.formatMessage({
              id: 'pages.document.downloading',
              defaultMessage: 'Downloading...',
            }),
            key: messageKey,
          });
          getFileObject(record.id).then((response: APIResponse) => {
            let getBase64 = response.data.data;
            downloadBase64File(
              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
              getBase64.split(',')[1],
              response.data.name + '.xls',
            );
          });
        }}
      >
        <DownloadOutlined />
      </a>
    </Tooltip>,
  ];

  const columns: ProColumns<any>[] = [
    {
      title: (
        <FormattedMessage
          id="pages.bulk-upload.view-history.file_name"
          defaultMessage="File Name"
        />
      ),
      dataIndex: 'name',
      valueType: 'text',
    },
    {
      title: (
        <FormattedMessage
          id="pages.bulk-upload.view-history.file_size"
          defaultMessage="File Size"
        />
      ),
      dataIndex: 'size',
      valueType: 'text',
      render: (_, record) => humanReadableFileSize(record.size),
    },
    {
      title: (
        <FormattedMessage
          id="pages.bulk-upload.view-history.uploaded_date"
          defaultMessage="Uploaded Date and Time"
        />
      ),
      dataIndex: 'updatedAt',
      valueType: 'text',
      render: (_, record) => {
        return <div>
                {moment(record.updatedAt,"YYYY-MM-DD").isValid() ? moment(record.updatedAt).format('DD-MM-YYYY HH:mm:ss') :null}
              </div>
      }
    },
    {
      title: <FormattedMessage id="pages.document.actions" defaultMessage="Actions" />,
      width: 120,
      valueType: 'option',
      render: renderActionsToolBar,
    },
  ];

  return (
    <>
      <Access
        accessible={hasPermitted('bulk-upload-read-write')}
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer style={{ height: '80vh' }}>
          <ProTable<any>
            columns={columns}
            rowKey="id"
            options={{
              search: false,
            }}
            request={async () => {
              const fileHistroy = await getAllBulkUploadedHistory();
              return { data: fileHistroy.data };
            }}
            search={false}
            pagination={{
              showSizeChanger: true,
            }}
            dateFormatter="string"
          />
        </PageContainer>
      </Access>
    </>
  );
};

export default ViewHistory;
