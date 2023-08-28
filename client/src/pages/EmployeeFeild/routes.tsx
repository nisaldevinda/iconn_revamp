import Religion from './fragments/Religion';
// import Department from './fragments/Department';
import EmploymentStatus from './fragments/EmploymentStatus';
import Race from './fragments/Race';
import Nationality from './fragments/Nationality'
import Gender from './fragments/Gender'
import Relationship from './fragments/Relationship'
// import TerminationReasons from "./fragments/TerminationReason";
import MaritalStatus from "./fragments/MaritalStatus"
import QualificationInstitutions from "./fragments/QualificationInstitutions"
// import Division from "./fragments/Division"
import Location from './fragments/Location';
import JobTitle from './fragments/JobTitle'
import JobCategory from './fragments/JobCategory'
import QualificationLevel from './fragments/QualificationLevel'
import Qualification from './fragments/Qualification'
import CompetencyType from './fragments/CompetencyType'
import Competency from './fragments/Competency'
import PayGrades from './fragments/PayGrades';
import SalaryComponents from './fragments/SalaryComponents';
import DynamicMaster from './fragments/DynamicMaster';
import { getAllDynamicForm } from '@/services/dynamicForm';
import NoticePeriodConfig from './fragments/NoticePeriodConfig';
import Scheme from './fragments/Scheme';
// import PromotionTypes from './fragments/PromotionTypes';
// import TransferTypes from './fragments/TransferTypes';
// import ResignationTypes from './fragments/ResignationTypes';
// import ConfirmationReasons from './fragments/ConfirmationReasons';

const routes = async () => {
  const staticMasterData = [
    // {
    //   name: 'Department',
    //   component: <Department />,
    //   key: 'department'
    // },
    // {
    //   name: 'Division',
    //   component: <Division />,
    //   key: 'division'
    // },
    {
      name: 'Employment Status',
      component: <EmploymentStatus />,
      key: 'employement-status'
    },
    {
      name: 'Job Title',
      component: <JobTitle />,
      key: 'job-title'
    },
    {
      name: 'Job Category',
      component: <JobCategory />,
      key: 'job-category'
    },
    {
      name: 'Notice Period Configuration',
      component: <NoticePeriodConfig />,
      key: 'notice-period-config'
    },
    {
      name: 'Location',
      component: <Location />,
      key: 'location'
    },
    // {
    //   name: 'Promotion Type',
    //   component: <PromotionTypes />,
    //   key: 'promotion-type'
    // },
    // {
    //   name: 'Confirmation Reason',
    //   component: <ConfirmationReasons />,
    //   key: 'confirmation-reason'
    // },
    // {
    //   name: 'Transfer Type',
    //   component: <TransferTypes />,
    //   key: 'transfer-type'
    // },
    // {
    //   name: 'Resignation Type',
    //   component: <ResignationTypes />,
    //   key: 'resignation-type'
    // },
    // {
    //   name: 'Resignation Reason',
    //   component: <TerminationReasons />,
    //   key: 'resignation-reason'
    // },
    {
      name: 'Qualification Level',
      component: <QualificationLevel />,
      key: 'qualification-level'
    },
    {
      name: 'Qualification',
      component: <Qualification />,
      key: 'qualification'
    },
    {
      name: ' Qualification Institution',
      component: <QualificationInstitutions />,
      key: 'qualification-institution'
    },
    {
      name: 'Competency Type',
      component: <CompetencyType />,
      key: 'competency-type'
    },
    {
      name: 'Compentency',
      component: <Competency />,
      key: 'competency'
    },
    {
      name: 'Nationality',
      component: <Nationality />,
      key: 'nationality'
    },
    {
      name: 'Marital Status',
      component: <MaritalStatus />,
      key: 'marital-status'
    },
    {
      name: 'Gender',
      component: <Gender />,
      key: 'gender'
    },
    {
      name: 'Religion',
      component: <Religion />,
      key: 'religion'
    },
    {
      name: 'Race',
      component: <Race />,
      key: 'race'
    },
    {
      name: 'Relationship',
      component: <Relationship />,
      key: 'relationship'
    },
    {
      name: 'Salary Components',
      component: <SalaryComponents />,
      key: 'salary-components'
    },
    {
      name: 'Pay Grades',
      component: <PayGrades />,
      key: 'pay-grades'
    },
    {
      name: 'Bank',
      component: <DynamicMaster modelName='bank' modelTitle='Bank' />,
      key: 'bank'
    },
    {
      name: 'Bank Branch',
      component: <DynamicMaster modelName='bankBranch' modelTitle='Bank Branch' />,
      key: 'bankBranch'
    },
    {
      name: 'Scheme',
      component: <Scheme />,
      key: 'scheme'
    }
  ];

  const ignoreForms = ['employee/add', 'employee/edit'];
  const dynamicFormRes = await getAllDynamicForm();
  const dynamicForm = dynamicFormRes.data
    .filter(form => !ignoreForms.includes(form.id))
    .map(form => {
      return {
        type: 'formBuilder',
        name: form.title,
        component: <DynamicMaster modelName={form.modelName} modelTitle={form.title} />,
        key: form.modelName
      };
    });

  return staticMasterData.concat(dynamicForm);
}

export default routes
