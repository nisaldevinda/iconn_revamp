import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import OrganizationalChart from '@/components/Charts/OrganizationalChart'
import { getEmployeeOrgChart } from '@/services/employee';
import { Spin, message as Message,Button ,Col } from 'antd';
import { useAccess, Access ,useIntl } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import { PageContainer } from '@ant-design/pro-layout';
import { getCompany, updateCompany } from '@/services/company';
import { ModalForm, ProFormSelect } from '@ant-design/pro-form';

import { getRootEmployees } from '@/services/dropdown';

const EmployeeChart: React.FC = () => {

  const [diagramData, setDiagramData] = useState(null);
  const access = useAccess()
  const { hasPermitted } = access;
  const [modalVisible , setIsModalVisible] = useState(false);
  const intl = useIntl();
  const [companyId , setCompanyId] = useState('');
  const [rootEmployees , setRootEmployees] = useState([]);
  
  const getEmployeeChartData =() =>{
    getEmployeeOrgChart().then((res) => {
      if (
        !_.isEmpty(res.data) ||
        !_.isUndefined(res.data) ||
        _.isObject(res.data)
      ) {
        setDiagramData(res.data)
      }
    })
  }
  useEffect (() =>{
    fetchRootEmployee();
  },[]);

  const fetchRootEmployee = async () => {
    try {
      const { data } = await getCompany();
      const { id, rootEmployeeId } = data;
      setCompanyId(id);

      if (_.isNull(rootEmployeeId)) {
        setIsModalVisible(true);
      } else {
        getEmployeeChartData();
      }
      const employees = await getRootEmployees();
      const employeesList = employees.data.map((employee: any) => {
        return {
          label: employee.employeeNumber+' | '+employee.employeeName,
          value: employee.id,
        };
      });
      setRootEmployees(employeesList);
    } catch (error) {
      Message.error(error.message);
    }
  }
  const handleAdd = async (fields) => {
    try {
      const {data} = await updateCompany(companyId,fields);
      
      Message.success({
        content:
          intl.formatMessage({
            id: 'successfullyUpdated',
            defaultMessage: 'Successfully Updated',
          }),
      });
      setIsModalVisible(false);
      getEmployeeChartData();
    } catch (error) {
      
      let errorMessage;
      let errorMessageInfo;
      if (error.message.includes('.')) {
      let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
      }
      Message.error({
      content: error.message ? (
          <>
          {errorMessage ?? error.message}
          <br />
          <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
              {errorMessageInfo ?? ''}
          </span>
          </>
      ) : (
          intl.formatMessage({
          id: 'failedToSave',
          defaultMessage: 'Cannot Save',
          })
      ),
      
      });
    }
  }
  return (
    <div>
      <Access accessible={hasPermitted('org-chart-read')} fallback={<PermissionDeniedPage />}>
      <PageContainer>
        {
          _.isUndefined(diagramData) ||
            _.isEmpty(diagramData)
            ? 
              <ModalForm
                width={600}
                title={intl.formatMessage({
                  id: 'selectRootEmployee',
                  defaultMessage: 'Select Root Employee',
                })}
                onFinish={async (values: any) => {
                  await handleAdd(values as any);
                }}
                modalProps={{
                  //maskClosable:false,
                  closable:false
                }}
                
                visible={modalVisible}
                onVisibleChange={setIsModalVisible}
                  submitter={{
                    render: (props, defaultDoms) => {
                      return [
                        <Button
                          key="ok"
                          onClick={() => {
                            props.submit();
                          }}
                          type={"primary"}
                        >
                          Save
                        </Button>,
                      ];
                    },
                  }}
              >
                <Col style={{ paddingLeft: 20 }}>
                  <ProFormSelect
                    valuePropName="option"
                    showSearch
                    optionFilterProp="children"
                    filterOption={(input, option) =>
                      option?.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                    }
                    name="rootEmployeeId"
                    label={intl.formatMessage({
                      id: 'label.RootEmployee',
                      defaultMessage: 'Root Employee',
                    })}
                    options={rootEmployees}
                    rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'shiftName',
                          defaultMessage: 'Required',
                        })
                      }
                    ]}

                  />
                </Col>
              </ModalForm>
                 :
            <OrganizationalChart data={diagramData} />
        }
        </PageContainer>
      </Access>
    </div>
  )
}

export default EmployeeChart;