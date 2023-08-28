import React, { useRef, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import { Button, Tooltip, Popconfirm, message as Message ,Image , Col} from 'antd';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { history , useIntl, useAccess, Access} from 'umi';
import { EditOutlined, DeleteOutlined, PlusOutlined ,DiffOutlined  } from '@ant-design/icons';
import { getWorkPatterns, deleteWorkPattern ,createDuplicateWorkPattern} from '@/services/workPattern';
import PermissionDeniedPage from './../403';
import { IWorkPatternListItem } from './data';
import  ProForm ,{ ModalForm , ProFormText  } from '@ant-design/pro-form';
import DuplicateIcon from '../../assets/workPattern/icon-duplicate-pattern.svg';
import moment from 'moment';

export default (): React.ReactNode => {
  const tableRef = useRef<ActionType>();
  const [ModalVisible,setModalVisible] = useState(false);
  const [searchText, setSearchText] = useState('');
  const intl = useIntl();
  const [currentValues, setCurrentValues] = useState<any>();
  const access = useAccess();
  const { hasPermitted } = access;

  const deletePattern = async (id: String) => {
    try {
      const { message } = await deleteWorkPattern(id);
      Message.success(message);
      tableRef.current?.reload();
    } catch (err) {
      console.log(err);
    }
  };

const handleAdd = async (fields: any) => {
  
  try {
    const {message,data} = await createDuplicateWorkPattern(fields);
    setModalVisible(false);
    Message.success(message);
    tableRef.current?.reload();
   
  } catch (error) {
    console.log(err);
  }
};
  const columns: ProColumns<IWorkPatternListItem>[] = [
    {
      key: 'name',
      title: 'Work Pattern Name',
      dataIndex: 'name',
      sorter: true,
      width: 250,
    },
    {
      key: 'description',
      title: 'Description',
      dataIndex: 'description',
    },
    {
      key: 'createdOn',
      title: 'Created On',
      dataIndex: 'createdAt',
      render: (_, record) => {
        return  (moment(record.createdAt,"YYYY-MM-DD").isValid() ? moment(record.createdAt).format("DD-MM-YYYY") : null);
      },
      sorter: true,
    },
    {
      key: 'actions',
      title: 'Actions',
      dataIndex: 'option',
      valueType: 'option',
      width: 150,
      render: (_, record) => [
        <Access accessible={hasPermitted('work-pattern-read-write')}>
          <div onClick={(e) => e.stopPropagation()}>
            <Tooltip key="delete-tool-tip" title="Duplicate">
              <a key="delete-btn"
                onClick ={() => {
                  setCurrentValues(record);
                  setModalVisible(true);
                }}  
                
              >
                <Button icon={<Image src={DuplicateIcon} preview={false} />}
                  style={{ border:'none' }}
                
                />
              </a>
            </Tooltip>
          </div>
        </Access>,
        <Access accessible={hasPermitted('work-pattern-read-write')}>
          <Tooltip key="edit-tool-tip" title="Edit">
            <a
              key="edit-btn"
              onClick={() => {
                const { id } = record;
                history.push(`/settings/work-patterns/${id}`);
              }}
            >
              <EditOutlined />
            </a>
          </Tooltip>
        </Access>,
        <Access accessible={hasPermitted('work-pattern-read-write')}>

          <div onClick={(e) => e.stopPropagation()}>
            <Popconfirm
              key="delete-pop-confirm"
              placement="topRight"
              title="Are you sure?"
              okText="Yes"
              cancelText="No"
              onConfirm={() => {
                const { id } = record;
                deletePattern(id);
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
      placeholder: "Search by Name",
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
      accessible={hasPermitted('work-pattern-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer>
        <ProTable<any>
          actionRef={tableRef}
          rowKey="id"
          search={false}
          
          toolbar={{
            search: handleSearch()
          }}
          toolBarRender={() => [
            <Access accessible={hasPermitted('work-pattern-read-write')}>
            <Button
                type="primary"
                key="primary"
                onClick={() => {
                  history.push('/settings/work-patterns/new');
                }}
            >
                <PlusOutlined /> 
                {intl.formatMessage({
                  id:'pattern.title',
                  defaultMessage :'Add New Work Pattern'   
                })}
            </Button>
            </Access>
          ]}
          options={{ fullScreen: false,
            search: true, 
            reload: () => {
              tableRef.current?.reset();
              tableRef.current?.reload();
              setSearchText('');
            }
, 
            setting: false }}
          request={async ({ pageSize, current }, sort) => {
            const { data } = await getWorkPatterns({ pageSize, current, sort, searchText });
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
                history.push(`/settings/work-patterns/${id}`);
              },
            };
          }}
        />

         {/* duplicate Model   */}
      {ModalVisible && 
        <ModalForm
          width={500}
          title={intl.formatMessage({
            id: 'pages.pattern.addNewDocument',
            defaultMessage: 'Duplicate Work Pattern',
          })}
          onFinish={async (values: any) => {
            await handleAdd(values as any);
          }}
          visible={ModalVisible}
          onVisibleChange={setModalVisible}
          submitter={{
            searchConfig: {
              submitText: intl.formatMessage({
                id: 'createPattern',
                defaultMessage: 'Create Pattern',
              }),
              resetText: intl.formatMessage({
                id: 'cancel',
                defaultMessage: 'Cancel',
              }),
            },
          }}
         
        >
          <ProForm.Group>
            <Col style={{paddingLeft: 20}}>
            <ProFormText
              width="md"
              name="duplicatedFrom"
              label={intl.formatMessage({
                id: 'duplicatedFrom',
                defaultMessage: 'Duplicated From',
              })}
              
              initialValue={currentValues.name}
              disabled
            />
            </Col>
            <Col style={{ paddingLeft :20}}>
            <ProFormText
              width="md"
              name="newWorkPatternName"
              label={intl.formatMessage({
                id: 'newWorkPatternName',
                defaultMessage: 'New Work Pattern Name',
              })}
              placeholder={intl.formatMessage({
                id: 'New Work Pattern Name',
                defaultMessage: 'Enter a New Work Pattern Name',
              })}
              rules={[
                {
                  required: true,
                  message: 'New Work Pattern Name Required',
                },
              ]}
              fieldProps={{
                onChange: (value) => {  
                },
                autoComplete: "none"
              }}
             
            />
            </Col>
          </ProForm.Group>
        </ModalForm>
      }
      </PageContainer>
    </Access>
  );
};
