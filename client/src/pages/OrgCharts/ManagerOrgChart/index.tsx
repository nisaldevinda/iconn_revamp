import React, { useEffect, useState } from 'react';
import TreeDiagram from '@/components/Charts/DecompositionChart';
import OrgChart from '@/components/Charts/ManagerOrgPrevilageChart';
import {
  getManagerOrganizationChartData,
  getManagerIsolatedOrganizationChartData,
  addEntity,
  editEntity,
  deleteEntity,
} from '@/services/department';
import { getEmployeeList } from '@/services/dropdown';
import { useIntl } from 'react-intl';
import { Spin, message } from 'antd';
import _ from 'lodash';
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from '@/pages/403';

const ManagerOrgChart: React.FC = () => {
  const [treeData, setTreeData] = useState({});
  const [hierarchyConfig, setHierarchyConfig] = useState({});
  const [employeeList, setEmployeeList] = useState([]);
  const [entityWiseEmpData, setEntityWiseEmpData] = useState([]);
  const [loading, setLoading] = useState<boolean>(false);
  const access = useAccess();
  const { hasPermitted } = access;
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    getOrgTreeData();
  }, [loading]);

  useEffect(() => {
    getEmployeeListData();
  }, []);

  const getEmployeeListData = async () => {
    try {
      //   const { data } = await getEmployeeList('ADMIN');
      //   const employees = data.map((employee) => {
      //     return {
      //       label: employee.employeeNumber+' - '+employee.employeeName,
      //       value: employee.id,
      //     };
      //   });
      setEmployeeList([]);
    } catch (error) {
      console.error(error);
    }
  };

  const getOrgTreeData = async () => {
    try {
      const departmentTree = await getManagerOrganizationChartData();
      setTreeData(departmentTree.data.orgData);
      setHierarchyConfig(departmentTree.data.hierarchyConfig);
      setEntityWiseEmpData(departmentTree.data.entityWiseEmpData);
      setRefresh((prev) => prev + 1);
    } catch (error) {
      console.error(error);
    }
  };

  const getIsolatedOrgTreeData = async (entityId: number) => {
    try {
      console.log(entityId);
      const departmentTree = await getManagerIsolatedOrganizationChartData(entityId);
      setTreeData(departmentTree.data.orgData);
      setHierarchyConfig(departmentTree.data.hierarchyConfig);
      setEntityWiseEmpData(departmentTree.data.entityWiseEmpData);
      setRefresh((prev) => prev + 1);
    } catch (error) {
      console.error(error);
    }
  };

  const addNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      const response = await addEntity(data);
      console.log(response);
    } catch (error) {
      console.error(error);
    }
    setLoading(false);
  };

  const editNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      const response = await editEntity(data);
      console.log(response);
    } catch (error) {
      console.error(error);
    }
    setLoading(false);
  };

  const deleteNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      const response = await deleteEntity(data);
      console.log(response);
    } catch (error) {
      console.error(error);
      message.error(error.message);
    }
    setLoading(false);
  };

  return (
    <>
      <Access
        accessible={hasPermitted('manager-org-chart-access')}
        fallback={<PermissionDeniedPage />}
      >
        {_.isUndefined(treeData) || _.isEmpty(treeData) ? (
          <Spin />
        ) : (
          <OrgChart
            data={treeData}
            refresh={refresh}
            hierarchyConfig={hierarchyConfig}
            employeeList={employeeList}
            entityWiseEmpData={entityWiseEmpData}
            getIsolatedOrgTreeData={getIsolatedOrgTreeData}
            getOrgTreeData={getOrgTreeData}
            addNodeHandler={addNodeHandler}
            editNodeHandler={editNodeHandler}
            deleteNodeHandler={deleteNodeHandler}
          />
        )}
      </Access>
    </>
  );
};

export default ManagerOrgChart;
