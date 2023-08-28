import ProTable from '@ant-design/pro-table';
import { Modal } from 'antd';
import { history } from "umi";

interface TabTableProps {
  dataSource: any[];
}

const TabTable: React.FC<TabTableProps> = (props) => {
  return (
    <ProTable
      columns={[
        {
          title: 'Email',
          key: 'email',
          dataIndex: 'email',
        },
        {
          title: 'Status',
          key: 'status',
          dataIndex: 'status',
        },
        {
          title: 'Actions',
          key: 'option',
          valueType: 'option',
          render: (_, record) => [
            <>
              {record.status == 'SUCCESS' && (
                <a
                  key="viewEmployee"
                  onClick={() => history.push(`/employees/${record.employeeId}`)}
                >
                  View Employee
                </a>
              )}
            </>,
            <>
              {record.status == 'ERROR' && (
                <a
                  key="viewError"
                  onClick={() =>
                    Modal.error({
                      title: 'Error occurrs due to,',
                      content: (
                        <ul>
                          {JSON.parse(record.responseData)?.errors.map((error: any) => (
                            <li>{error}</li>
                          ))}
                        </ul>
                      ),
                    })
                  }
                >
                  View Error
                </a>
              )}
            </>,
          ],
        },
      ]}
      rowKey="id"
      headerTitle="Azure Users"
      dataSource={props.dataSource}
      toolBarRender={false}
      search={false}
    />
  );
};

export default TabTable;
