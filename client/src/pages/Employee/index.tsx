import React, { useEffect, useState } from 'react';
import { deleteEmployee, getAllEmployee } from '@/services/employee';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import _ from 'lodash';
import { history } from 'umi';
import { useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import './employee.css';

const MemberList: React.FC = () => {
  const intl = useIntl();

  const [model, setModel] = useState<any>();

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel(Models.Employee).then((response) => {
        setModel(response.data);
      });
    }
  }, []);

  return (
    <div
      style={{
        backgroundColor: 'white',
        borderTopLeftRadius: '30px',
        paddingLeft: '50px',
        paddingTop: '50px',
        width: '100%',
        paddingRight: '0px',
      }}
    >
      <PageContainer>
        <BasicContainer
          rowId="id"
          titleKey="employee"
          defaultTitle="Employee"
          model={model}
          tableColumns={[
            { name: 'employeeNumber' },
            { name: 'employeeName', sortable: true },
            { name: 'currentJobs.jobTitle' },
            { name: 'currentJobs.location' },
            // { name: 'currentJobs.department' },
            { name: 'currentJobs.reportsToEmployee' },
            { name: 'currentJobs.functionalReportsToEmployeeId' },
            {
              name: 'isActive',
              filterable: true,
              valueEnum: {
                1: {
                  text: intl.formatMessage({
                    id: 'active',
                    defaultMessage: 'Active',
                  }),
                  status: 'Success',
                },
                0: {
                  text: intl.formatMessage({
                    id: 'Inactive',
                    defaultMessage: 'Inactive',
                  }),
                  status: 'Error',
                },
              },
            },
          ]}
          defaultSortableField={{
            fildName: 'employeeName',
            mode: 'ascend',
          }}
          searchFields={['employeeNumber', 'employeeName']}
          addFormType="function"
          editFormType="function"
          getAllFunction={getAllEmployee}
          addFunction={async () => history.push('/employees/new')}
          editFunction={async (record) => history.push(`/employees/${record.id}`)}
          deleteFunction={async (record) => deleteEmployee(record.id)}
          permissions={{
            addPermission: 'employee-create',
            editPermission: 'employee-write',
            deletePermission: 'employee-write',
            readPermission: 'employee-read',
          }}
        />
      </PageContainer>
    </div>
  );
};

export default MemberList;
