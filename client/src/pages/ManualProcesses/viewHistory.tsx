import React , { useState ,useEffect } from 'react';
import Table from './components/table';
import { getManualProcessHistory ,getLeaveAccrualEmployeeList} from '@/services/manualProcesses';
import { Tag } from 'antd';
import { useAccess, Access, useParams ,useIntl } from 'umi';
import { PageContainer } from '@ant-design/pro-layout';
import PermissionDeniedPage from '../403';
import moment from 'moment';
import { EyeOutlined} from '@ant-design/icons';
import { ModalForm } from '@ant-design/pro-form';
import { Form } from 'antd';
const ViewHistory: React.FC = () => {
  const access = useAccess();
  const intl = useIntl();
  const params = useParams();
  const [ModalVisible, setModalVisible] = useState<boolean>(false);
  const { hasPermitted } = access;
  const { type } = params;
  const [ manualProcessId , setManualProcessId] = useState('');
  const [pageTitle , setPageTitle] = useState('');

  useEffect(() =>{
     if ( type === 'attendance-process') {
      setPageTitle('Attendance Process History');
     } else {
      setPageTitle('Leave Accrual History')
     }
  },[]);

  const getData = async (params, sorter, filter) => {
  
    try {
      let typeParam;
      switch (type) {
        case 'attendance-process':
          typeParam = 'ATTENDANCE_PROCESS';
          break;
        default:
          typeParam = 'LEAVE_ACCRUAL';
          break;
      }
      const { data } = await getManualProcessHistory({ type: typeParam });
      return {
        data: data,
        success: true,
      };
    } catch (error) {
      console.log(error);
      return {
        data: [],
        success: false,
      };
    }
  };
  const getEmployeeList = async () => {

    try {
      const { data } = await getLeaveAccrualEmployeeList({ manualProcessId: manualProcessId });
      return {
        data: data,
        success: true,
      };
    } catch (error) {
      return {
        data: [],
        success: false,
      };
    }
  };
  const columns: ProColumns<TableListItem> = [
    {
      title: 'Username',
      width: 360,
      dataIndex: 'employeeName',
    },
    {
      title: 'Date',
      dataIndex: 'date',
      align: 'left',
      width: 320,
      render: (_, record) => {
        return <div 
          style={{
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap'
          }}>
            { moment(record.date,"YYYY-MM-DD").isValid() ? moment(record.date).format("DD-MM-YYYY ") : null}
          </div>
      }
    },
    {
      title: 'Status',
      dataIndex: 'status',
      align: 'left',
      render: (_, record) => {
        let tag;
        switch (record.status) {
          case 'COMPLETED':
            tag = <Tag color="success">success</Tag>;
            break;
          case 'PENDING':
            tag = <Tag color="processing">processing</Tag>;
            break;
          case 'ERROR':
            tag = <Tag color="error">error</Tag>;
            break;
        }
        return tag;
      },
    },
    {
      title: 'Executed At',
      dataIndex: 'createdAt',
      align: 'left',
      width: 320,
      render: (_, record) => {
        return <div 
                style={{
                  textOverflow: 'ellipsis',
                  whiteSpace: 'nowrap'
                }}>
                  {moment(record.date,"YYYY-MM-DD").isValid() ? moment(record.ExecutedAt).format('DD-MM-YYYY HH:mm:ss') :null}
                </div>
      }
    },
  ];
   

  if (params.type === "leave-accrual") {
    columns.push({
      title: 'Action',
      width: 360,
      render: (_, record) => {
        return (
          <a
            onClick={async() => {
              setModalVisible(true);
              setManualProcessId(record.id);
            }}
          >
            <EyeOutlined />
          </a>
        )
      }
    })
  }

  const employeeListColumns  = [
    {
      title: 'Employee Name',
      width: 360,
      dataIndex: 'employeefullName',
    },
    {
      title: 'Employee Number',
      width: 360,
      dataIndex: 'employeeNumber',
    },
    {
      title: 'Allocated Leave Count',
      width: 360,
      dataIndex: 'entilementCount',
    },
    {
      title: 'Leave Type',
      width: 360,
      dataIndex: 'LeaveTypeName',
    },
  ]; 
  return (
    <Access accessible={hasPermitted('manual-process')} fallback={<PermissionDeniedPage />}>
      <PageContainer
        header={{
          title: intl.formatMessage({
            id: `pages.History.title`,
            defaultMessage: `${pageTitle}`,
          })
        }}
      >
        <Table columns={columns} request={getData} width={'50%'} />
          <ModalForm
            title={intl.formatMessage({
              id: `manual-processes-title`,
              defaultMessage: `Leave Accrual Employee Entitlement List`,
            })}
            visible={ModalVisible}
            onVisibleChange={setModalVisible}
            submitter={{
              submitButtonProps: {
                style: {
                  display: 'none',
                },
              },
            }}
          >
            <Table columns={employeeListColumns} request={getEmployeeList} />
          </ModalForm>
      </PageContainer>
    </Access>
  );
};

export default ViewHistory;
