import React, { useEffect, useState } from 'react';
import TreeDiagram from '@/components/Charts/DecompositionChart';
import OrgChart from '@/pages/WorkflowBuilder/BuilderView/index';
import { getOrganizationTree, addEntity, editEntity, deleteEntity } from '@/services/department';
import {
  getWorkflowConfigTree,
  addWorkflowEntity,
  addWorkflowApproverLevelEntity,
  changeWorkflowLevelConfigurations,
  deleteApprovalLevel,
  workflowApproverPools,
} from '@/services/workflowServices';
import { getEmployeeList } from '@/services/dropdown';
import { useIntl } from 'react-intl';
import { Spin, message } from 'antd';
import _ from 'lodash';
import { useAccess, Access, useParams } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllManagers, getWorkflowPermittedManagers } from '@/services/user';

export type FormTemplateRouteParams = {
  id: string;
};

const WorkflowConfig: React.FC = () => {
  const [treeData, setTreeData] = useState({});
  const [hierarchyConfig, setHierarchyConfig] = useState({});
  const [employeeList, setEmployeeList] = useState([]);
  const [poolList, setPoolList] = useState([]);
  const [designationList, setDesignationList] = useState([]);
  const [jobCategoryList, setJobCategoryList] = useState([]);
  const [approvalTypeCategoryList, setApprovalTypeCategoryList] = useState([
    {
      label: 'Common',
      value: 'COMMON',
    },
    {
      label: 'Job Category',
      value: 'JOB_CATEGORY',
    },
    {
      label: 'Designation',
      value: 'DESIGNATION',
    },
    {
      label: 'User Role Type',
      value: 'USER_ROLE',
    },
  ]);
  const [commonApprovalTypeList, setCommonApprovalTypeList] = useState([
    {
      label: 'Reporting Person',
      value: 'REPORTING_PERSON',
    },
  ]);
  const [approverUserRolesList, setApproverUserRolesList] = useState([
    {
      label: 'Admin Role',
      value: 1,
    },
    {
      label: 'Manager Role',
      value: 3,
    },
  ]);
  const { id } = useParams<FormTemplateRouteParams>();
  const [loading, setLoading] = useState<boolean>(false);
  const access = useAccess();
  const { hasPermitted } = access;

  useEffect(() => {
    getWorkflowTreeData();
  }, [loading]);

  useEffect(() => {
    getEmployeeListData();
    getPoolListData();
    getJobCategoryListData();
    getDesignationListData();
  }, []);

  const getEmployeeListData = async () => {
    try {
      const { data } = await getWorkflowPermittedManagers(id);
      const employees = data.map((employee) => {
        return {
          label: employee.employeeNumber+' | '+employee.employeeName,
          value: employee.id,
        };
      });
      setEmployeeList(employees);
    } catch (error) {
      console.error(error);
    }
  };

  const getPoolListData = async () => {
    try {
      const { data } = await workflowApproverPools({});
      const pools = data.map((pool) => {
        return {
          label: pool.poolName,
          value: pool.id,
        };
      });
      setPoolList(pools);
    } catch (error) {
      console.error(error);
    }
  };

  const getJobCategoryListData = async () => {
    try {
      const { data } = await getAllJobCategories();
      const jobCategories = data.map((jobCategory) => {
        return {
          label: jobCategory.name,
          value: jobCategory.id,
        };
      });
      setJobCategoryList(jobCategories);
    } catch (error) {
      console.error(error);
    }
  };

  const getDesignationListData = async () => {
    try {
      const { data } = await getAllJobTitles();
      const designations = data.map((designation) => {
        return {
          label: designation.name,
          value: designation.id,
        };
      });
      setDesignationList(designations);
    } catch (error) {
      console.error(error);
    }
  };

  const getWorkflowTreeData = async () => {
    try {
      const workflowTree = await getWorkflowConfigTree(id);
      setTreeData(workflowTree.data.orgData);
      setHierarchyConfig(workflowTree.data.hierarchyConfig);
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

  const addWorkflowNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      let params = {
        isProcedureDefined: true,
      };
      const response = await addWorkflowEntity(params, id);
      console.log(response);
    } catch (error) {
      console.error(error);
    }
    setLoading(false);
  };

  const addApproverLevelNodeHandler = async (level: any, data: any) => {
    setLoading(true);
    try {
      let params = {
        levelSequence: level,
        levelName: 'Approve Level ' + level,
        workflowId: id,
        levelType: null,
        staticApproverEmployeeId: null,
        dynamicApprovalTypeCategory: null,
        commonApprovalType: null,
        approverUserRoles: JSON.stringify([]),
        approverJobCategories: JSON.stringify([]),
        approverDesignation: JSON.stringify([]),
        approverPoolId: null,
        approvalLevelActions: JSON.stringify([]),
      };
      const response = await addWorkflowApproverLevelEntity(params);
      console.log(response);
    } catch (error) {
      message.error(error.message);
      console.error(error);
    }
    setLoading(false);
  };

  const changeWorkflowLevelConfigs = async (params: any) => {
    setLoading(true);
    try {
      const response = await changeWorkflowLevelConfigurations(params);
      console.log(response);
    } catch (error) {
      message.error(error.message);
    }
    setLoading(false);
  };

  const editNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      const response = await editEntity(data);
      console.log(response);
    } catch (error) {
      message.error(error.message);
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

  const deleteWorkflowNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      let params = {
        isProcedureDefined: false,
      };
      const response = await addWorkflowEntity(params, id);
      console.log(response);
    } catch (error) {
      console.error(error);
      message.error(error.message);
    }
    setLoading(false);
  };

  const deleteLevelNodeHandler = async (data: any) => {
    setLoading(true);
    try {
      let params = JSON.parse(data.levelData);
      const response = await deleteApprovalLevel(params);
      console.log(response);
    } catch (error) {
      console.error(error);
      message.error(error.message);
    }
    setLoading(false);
  };

  return (
    <>
      <Access accessible={hasPermitted('org-chart-read')} fallback={<PermissionDeniedPage />}>
        {_.isUndefined(treeData) || _.isEmpty(treeData) ? (
          <Spin />
        ) : (
          <div className="workflowOrgChart">
            <OrgChart
              data={treeData}
              hierarchyConfig={hierarchyConfig}
              employeeList={employeeList}
              poolList={poolList}
              approvalTypeCategoryList={approvalTypeCategoryList}
              commonApprovalTypeList={commonApprovalTypeList}
              approverUserRolesList={approverUserRolesList}
              designationList={designationList}
              jobCategoryList={jobCategoryList}
              addNodeHandler={addNodeHandler}
              addWorkflowNodeHandler={addWorkflowNodeHandler}
              addApproverLevelNodeHandler={addApproverLevelNodeHandler}
              deleteWorkflowNodeHandler={deleteWorkflowNodeHandler}
              deleteLevelNodeHandler={deleteLevelNodeHandler}
              changeWorkflowLevelConfigs={changeWorkflowLevelConfigs}
              editNodeHandler={editNodeHandler}
              deleteNodeHandler={deleteNodeHandler}
            />
          </div>
        )}
      </Access>
    </>
  );
};

export default WorkflowConfig;
