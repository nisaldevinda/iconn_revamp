import React, { useRef, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import { Button, Tooltip, Popconfirm, message as Message } from 'antd';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { history, useAccess, Access } from 'umi';
import { EditOutlined, DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { IEmailTemplateListItem } from './data';
import { getEmailTemplates, deleteEmailTemplate } from '@/services/emailTemplate';
import PermissionDeniedPage from './../403';

export default (): React.ReactNode => {
  const tableRef = useRef<ActionType>();
  const [searchText, setSearchText] = useState('');
  const access = useAccess();
  const { hasPermitted } = access;
  const deleteTemplate = async (id: String) => {
    try {
      const { message } = await deleteEmailTemplate(id);
      Message.success(message);
      tableRef.current?.reload();
    } catch (err) {
      console.log(err);
    }
  };

  const columns: ProColumns<IEmailTemplateListItem>[] = [
    {
      key: 'formName',
      title: 'Name',
      dataIndex: 'formName',
      sorter: true,
    },
    {
      key: 'description',
      title: 'Description',
      dataIndex: 'description',
    },
    {
      key: 'actions',
      title: 'Actions',
      dataIndex: 'option',
      valueType: 'option',
      width: 150,
      render: (_, record) => [
        <Access accessible={hasPermitted('email-template-read-write')}>
          <Tooltip key="edit-tool-tip" title="Edit">
            <a
              key="edit-btn"
              onClick={() => {
                const { id } = record;
                history.push(`/settings/email-notifications/${id}`);
              }}
            >
              <EditOutlined />
            </a>
          </Tooltip>
        </Access>,
        <Access accessible={hasPermitted('email-template-read-write')}>
          <div onClick={(e) => e.stopPropagation()}>
            <Popconfirm
              key="delete-pop-confirm"
              placement="topRight"
              title="Are you sure to delete this Email Template ?"
              okText="Yes"
              cancelText="No"
              onConfirm={() => {
                const { id } = record;
                deleteTemplate(id);
              }}
            >
              <Tooltip key="delete-tool-tip" title="Delete">
                <a key="delete-btn">
                  <DeleteOutlined />
                </a>
              </Tooltip>
            </Popconfirm>
          </div>
        </Access>,
      ],
    },
  ];
  const handleSearch = () => {
    return {
      className: 'basic-container-search',
      placeholder: "Search",
      onChange: (value: any) => {
        setSearchText(value.target.value);
        if (_.isEmpty(value.target.value)) {
          tableRef.current?.reset();
          tableRef.current?.reload();
        }
      },
      value:searchText
    };
  };


  return (
    <Access
      accessible={hasPermitted('email-template-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer>
        <ProTable<any>
          actionRef={tableRef}
          rowKey="id"
          search={false}
          toolbar={{
            search: handleSearch(),
          }}
          toolBarRender={() => [
            <Access accessible={hasPermitted('email-template-read-write')}>
              <Button
                type="primary"
                key="primary"
                onClick={() => {
                  history.push('/settings/email-notifications/new');
                }}
              >
                <PlusOutlined /> New
              </Button>
            </Access>,
          ]}
          options={{ 
            fullScreen: false, 
            search: true,
            reload: () => {
              tableRef.current?.reset();
              tableRef.current?.reload();
              setSearchText('');
            }, 
            setting: false }}
          request={async ({ pageSize, current }, sort) => {
            const { data } = await getEmailTemplates({ pageSize, current, sort, searchText });
            return {
              data: data.data,
              success: true,
              total: data.total,
            };
          }}
          columns={columns}
          onRow={(record, rowIndex) => {
            return {
              onClick: async () => {
                const { id } = record;
                history.push(`/settings/email-notifications/${id}`);
              },
            };
          }}
        />
      </PageContainer>
    </Access>
  );
};
