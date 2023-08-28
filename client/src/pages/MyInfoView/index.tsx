import React, { useState, useEffect } from 'react';
import EditEmployee from '../Employee/EditEmployee'
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from './../403';
import myInfoService from '@/services/myInfoView';
import { Spin } from 'antd';
import _ from 'lodash';
import { getModel, Models, ModelType } from '@/services/model';

const MyInfoView: React.FC = (props) => {

  const access = useAccess();
  const { hasPermitted } = access;
  const [employee, setEmployee] = useState<any>();
  const [employeeModel, setEmployeeModel] = useState<ModelType>();
  const [activeKey, setActiveKey] = useState('');

  useEffect(() => {
    fetchEmployeeData();
    if (props.location.search) {
      const searchString = props.location.search.replaceAll('?', '');
      setActiveKey(searchString);
    }
  }, []);

  const fetchEmployeeData = async () => {
    await myInfoService.getEmployee().then((response: any) => {
      if (response && response.data) {
        setEmployee(response.data)
      }
    });

    await getModel(Models.Employee, 'edit').then((model) => {
      let modelData = { ...model.data };
      if (model?.data?.modelDataDefinition?.fields?.profilePicture?.actionRoute) {
        modelData.modelDataDefinition.fields.profilePicture.actionRoute = '/api/myProfile/{id}/profilePicture';
      }

      setEmployeeModel(modelData);
    });
  }

  return (
    <Access
      accessible={hasPermitted('my-info-view')}
      fallback={<PermissionDeniedPage />}
    >
      {
        (_.isUndefined(employeeModel) || _.isEmpty(employeeModel)) && (_.isUndefined(employee) || _.isEmpty(employee))
          ? <Spin />
          : <EditEmployee
            id={employee.id}
            service={myInfoService}
            returnRoute={''}
            enableQuickSwitch={false}
            isMyProfile={true}
            model={employeeModel}
            scope="EMPLOYEE"
            activeKey={activeKey}
          />
      }

    </Access>
  );

}


export default MyInfoView;
