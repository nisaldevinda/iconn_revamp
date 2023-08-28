import React, { useState } from 'react';
import Table, { TableListItem } from './components/table';
import { ProColumns } from '@ant-design/pro-table';
import { history, useIntl, useAccess, Access } from 'umi';
import { PageContainer } from '@ant-design/pro-layout';
import { Col, message, Row, Form, message as Message } from 'antd';
import { ModalForm, ProFormDatePicker, ProFormGroup } from '@ant-design/pro-form';
import { runManualProcess } from '@/services/manualProcesses';
import PermissionDeniedPage from '../403';

const ManualProcesses: React.FC = () => {
  const intl = useIntl();
  const [modalForm] = Form.useForm();
  const access = useAccess();
  const { hasPermitted } = access;
  const [ModalVisible, setModalVisible] = useState<boolean>(false);
  const [processType, setProcessType] = useState<strimg>('');
  const manualProcess = [
    {
      task: 'Leave Accrual',
      description: 'Run leave accrual process for selected date',
      type: 'LEAVE_ACCRUAL',
    },
    {
      task: 'Attendance Process',
      description: 'Run attendance process for selected date',
      type: 'ATTENDANCE_PROCESS',
    },
  ];

  const getData = (params, sorter, filter) => {
    // The form search item will be passed in from params and passed to the backend interface.
    console.log(params, sorter, filter);
    return Promise.resolve({
      data: manualProcess,
      success: true,
    });
  };
  const columns: ProColumns<TableListItem> = [
    {
      title: 'Task',
      width: 200,
      dataIndex: 'task',
    },
    {
      title: 'Description',
      dataIndex: 'description',
      align: 'left',
    },
    {
      title: 'Action',
      dataIndex: 'action',
      align: 'left',
      width: 200,
      render: (_, record) => (
        <Row>
          <Col span={12}>
            <a
              onClick={() => {
                const { type } = record;
                setProcessType(type);
                setModalVisible(true);
              }}
            >
              Execute
            </a>
          </Col>
          <Col span={12}>
            <a
              onClick={() => {
                const { type } = record;
                let typeParam;
                switch (type) {
                  case 'ATTENDANCE_PROCESS':
                    typeParam = 'attendance-process';
                    break;
                  default:
                    typeParam = 'leave-accrual';
                    break;
                }
                history.push(`manual-processes/${typeParam}/history`);
              }}
            >
              View History
            </a>
          </Col>
        </Row>
      ),
    },
  ];

  return (
    <Access accessible={hasPermitted('manual-process')} fallback={<PermissionDeniedPage />}>
      <PageContainer>
        <Table columns={columns} request={getData} />
        <ModalForm
          form={modalForm}
          title={intl.formatMessage({
            id: `manual-processes-title`,
            defaultMessage: `Execute Process`,
          })}
          visible={ModalVisible}
          onVisibleChange={setModalVisible}
          submitter={{
            searchConfig: {
              submitText: intl.formatMessage({
                id: 'execute',
                defaultMessage: 'Execute',
              }),
              resetText: intl.formatMessage({
                id: 'cancel',
                defaultMessage: 'Cancel',
              }),
            },
          }}
          onFinish={async (formData) => {
            try {
              const { processDate } = formData;
              const { message } = await runManualProcess({
                type: processType,
                date: processDate,
              });
              Message.success(message);
              setModalVisible(false);
              modalForm.resetFields();
              return true;
            } catch (error) {
              console.log(error);
              Message.error(message);
              return false;
            }
          }}
        >
          <ProFormGroup>
            <ProFormDatePicker
              name="processDate"
              label="Process Date"
              width="md"
              required
              rules={[
                {
                  required: true,
                  message: intl.formatMessage({
                    id: `manual-processes-process-date-feild`,
                    defaultMessage: `Required`,
                  }),
                },
              ]}
            />
          </ProFormGroup>
        </ModalForm>
      </PageContainer>
    </Access>
  );
};

export default ManualProcesses;
