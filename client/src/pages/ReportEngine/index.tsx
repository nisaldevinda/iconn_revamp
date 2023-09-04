import React, { useRef, useState } from 'react';
import { getAllReports, printToPdf, removeReport } from '@/services/reportService';
import ProTable, { ProColumns, ActionType } from '@ant-design/pro-table';
import { Space, Select, Divider, Button, Tooltip, Popconfirm, message } from 'antd';
import { history, useAccess, Access } from 'umi';
import PermissionDeniedPage from './../403';
import { FormattedMessage, useIntl } from 'react-intl';

import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import { DeleteOutlined, EditOutlined, EyeOutlined, PlusOutlined } from '@ant-design/icons';
import { APIResponse } from '@/utils/request';
import { getPrivileges } from '@/utils/permission';

const ReportData: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;
  const intl = useIntl();
  const actionRef = useRef<ActionType>();
  const privilege = getPrivileges();
  const [searchText, setSearchText] = useState('');

  const columns: ProColumns<any>[] = [
    {
      title: 'Report Name',
      dataIndex: 'reportName',
      key: 'reportName',
    },
    {
      title: 'Actions',
      key: 'actions',
      width: 200,
      render: (text, record, index) => {
        return (
          <Space direction="horizontal" style={{ float: 'left' }}>
            <div onClick={(e) => e.stopPropagation()}>
              <Tooltip
                placement={'bottom'}
                key="viewRecordTooltip"
                title={intl.formatMessage({
                  id: 'view',
                  defaultMessage: 'View',
                })}
              >
                <a
                  key="viewRecordTooltip"
                  onClick={() => history.push(`/report-engine/get-report/${record.id}`)}
                >
                  <EyeOutlined style={{ color: '#2D68FE' }} />
                </a>
              </Tooltip>
            </div>
            {/* <DeleteOutlined onClick={()=>removeReport(record.id)} /> */}
            {record.isSystemReport ? (
              <div onClick={(e) => e.stopPropagation()}>
                <Tooltip
                  placement={'bottom'}
                  key="editRecordTooltip"
                  title={intl.formatMessage({
                    id: 'edit',
                    defaultMessage: 'Edit',
                  })}
                >
                  <EditOutlined
                    style={{
                      background: '#fff',
                      color: '#ccc',
                    }}
                  />
                </Tooltip>
              </div>
            ) : (
              <Tooltip
                placement={'bottom'}
                key="editRecordTooltip"
                title={intl.formatMessage({
                  id: 'edit',
                  defaultMessage: 'Edit',
                })}
              >
                <a
                  key="editRecordButton"
                  onClick={() => history.push(`/report-engine/report-wizard/${record.id}`)}
                >
                  <EditOutlined style={{ color: '#2D68FE' }} />
                </a>
              </Tooltip>
            )}
            {record.isSystemReport ? (
              <div onClick={(e) => e.stopPropagation()}>
                <Tooltip
                  placement={'bottom'}
                  key="deleteRecordTooltip"
                  title={intl.formatMessage({
                    id: 'delete',
                    defaultMessage: 'Delete',
                  })}
                >
                  <DeleteOutlined
                    style={{
                      background: '#fff',
                      color: '#ccc',
                    }}
                  />
                </Tooltip>
              </div>
            ) : (
              <div onClick={(e) => e.stopPropagation()}>
                <Popconfirm
                  key="deleteRecordConfirm"
                  title={intl.formatMessage({
                    id: 'are_you_sure',
                    defaultMessage: 'Are you sure?',
                  })}
                  onConfirm={async () => {
                    const key = 'deleting';
                    message.loading({
                      content: intl.formatMessage({
                        id: 'deleting',
                        defaultMessage: 'Deleting...',
                      }),
                      key,
                    });
                    removeReport(record.id)
                      .then((response: APIResponse) => {
                        if (response.error) {
                          message.error({
                            content:
                              response.message ??
                              intl.formatMessage({
                                id: 'failedToDelete',
                                defaultMessage: 'Failed to delete',
                              }),
                            key,
                          });
                          return;
                        }

                        message.success({
                          content:
                            response.message ??
                            intl.formatMessage({
                              id: 'successfullyDeleted',
                              defaultMessage: 'Successfully deleted',
                            }),
                          key,
                        });

                        actionRef?.current?.reload();
                      })

                      .catch((error: APIResponse) => {
                        message.error({
                          content: intl.formatMessage({
                            id: 'failedToDelete',
                            defaultMessage: 'Failed to delete',
                          }),
                          key,
                        });
                      });
                  }}
                  okText="Yes"
                  cancelText="No"
                >
                  <Tooltip
                    placement={'bottom'}
                    key="deleteRecordTooltip"
                    title={intl.formatMessage({
                      id: 'delete',
                      defaultMessage: 'Delete',
                    })}
                  >
                    <a key="deleteRecordButton">
                      <DeleteOutlined style={{ color: '#2D68FE' }} />
                    </a>
                  </Tooltip>
                </Popconfirm>
              </div>
            )}
          </Space>
        );
      },
    },
  ];
  const handleSearch = () => {
    return {
      className: 'basic-container-search',
      placeholder: "Search by Report Name",
      onChange: (value: any) => {
        setSearchText(value.target.value);
        if (_.isEmpty(value.target.value)) {
          actionRef.current?.reset();
          actionRef.current?.reload();
        }
      },
      value:searchText
    };
  };

  return (
    <Access accessible={hasPermitted('reports-read-write')} fallback={<PermissionDeniedPage />}>
      <div style={{ backgroundColor: '#F6F9FF', borderTopLeftRadius: '30px', padding: '50px' }}>
        <PageContainer>
        <ProTable
          actionRef={actionRef}
          rowKey="id"
          search={false}
          columns={columns}
          options={{
            reload: () => {
              actionRef.current?.reset();
              actionRef.current?.reload();
              setSearchText('');
            },
            search: true,
          }}
          toolbar={{
            search: handleSearch(),
          }}
          pagination={{ pageSize: 10, defaultPageSize: 10 }}
          request={async (params, filter) => {
            const response = await getAllReports({ ...params }, privilege);

            return {
              data: response.data.data,
              success: true,
              total: response.data.total,
            };
          }}
          toolBarRender={() => [
            <Access accessible={hasPermitted('reports-read-write')}>
              <Button
                type="primary"
                key="primary"
                onClick={() => {
                  history.push('/report-engine/report-wizard/new');
                }}
              >
                <PlusOutlined /> New
              </Button>
            </Access>,
          ]}
          onRow={(record, rowIndex) => {
            return {
              onClick: async () => {
                const { id } = record;
                if (!record.isSystemReport) {
                  history.push(`/report-engine/report-wizard/${id}`);
                }
              },
            };
          }}
        />
        </PageContainer>
      </div>
    </Access>
  );
};

export default ReportData;
