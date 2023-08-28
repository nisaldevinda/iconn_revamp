import Action from './fragments/Actions';
import Contexts from './fragments/Contexts';
import States from './fragments/States';
import Permissions from './fragments/Permissions';
import DefineWorkflow from './fragments/DefineWorkflow';
import StateTransition from './fragments/WorkflowTrasnsitions';
import EmployeeGroups from './fragments/WorkflowEmployeeGroups';
import WorkflowApprovalPool from './fragments/WorkflowApprovalPool';


export default [
  // {
  //   name: 'States',
  //   component: <States />
  // },
  // {
  //   name: 'Actions',
  //   component: <Action />
  // }
  // ,
  // {
  //   name: 'Permissions',
  //   component: <Permissions />
  // },
  {
    name: 'Contexts',
    component: <Contexts />
  },
  // {
  //   name: 'Workflow',
  //   component: <DefineWorkflow />
  // },
  // {
  //   name: 'Workflow State Transitions',
  //   component: <StateTransition />
  // },
  {
    name: 'Workflow Employee Groups',
    component: <EmployeeGroups />
  },
  {
    name: 'Workflow Approver Pools',
    component: <WorkflowApprovalPool />
  },
  
  


];
