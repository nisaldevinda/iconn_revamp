import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import Icon from "@ant-design/icons";
import ConfirmationContractIcon from '@/assets/EmployeeJourneyUpdate/confirmation_contracts.svg';
import PromotionIcon from '@/assets/EmployeeJourneyUpdate/promotions.svg';
import ResignationIcon from '@/assets/EmployeeJourneyUpdate/resignations.svg';
import TransferIcon from '@/assets/EmployeeJourneyUpdate/transfers.svg';
import Promotions from './Promotions';
import ConfirmationContracts from './ConfirmationContracts';
import Transfers from './Transfers';
import Resignations from './Resignations';
import { getAllEmployee } from '@/services/employee';
import { message } from 'antd';
// import { getAllDepartment } from '@/services/department';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllDivisions } from '@/services/divsion';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getModel, Models } from '@/services/model';
import { getManagerList } from '@/services/dropdown';
import { queryPayGrades } from '@/services/PayGradeService';
import { getAllPromotionTypes } from '@/services/promotionTypes';
import { getAllConfirmationReasons } from '@/services/confirmationReasons';
import { getAllTransferTypes } from '@/services/transferTypes';
import { getAllResignationTypes } from '@/services/resignationTypes';
import { getCalendarList } from '@/services/workCalendarService';
import { getCompany } from '@/services/company';
import moment from 'moment';
import momentTZ from 'moment-timezone';
import { getAllTerminationReasons } from '@/services/terminationReason';

const EmployeeJourneyUpdate: React.FC = () => {
  const intl = useIntl();

  const [loading, setLoading] = useState<boolean>(false);
  const [activeTab, setActiveTab] = useState<string>('promotions');
  const [model, setModel] = useState();
  const [data, setData] = useState();

  useEffect(() => {
    init();
  }, []);

  const init = async () => {
    if (_.isEmpty(data)) {
      setLoading(true);

      let _data = {};
      let callStack = [];

      // retrieve employee job model
      callStack.push(getModel(Models.EmployeeJob)
        .then(response => {
          setModel(response?.data);

          // bind confirmation Actions
          if (response?.data?.modelDataDefinition?.fields?.confirmationAction?.values) {
            _data['confirmationActions'] = response?.data?.modelDataDefinition?.fields?.confirmationAction?.values.map(option => {
              return {
                value: option.value,
                label: intl.formatMessage({
                  id: option.labelKey,
                  defaultMessage: option.defaultLabel,
                })
              }
            });
          }
        })
        .catch(error => message.error(error?.message)));

      // retrieve all employee
      callStack.push(getAllEmployee()
        .then(response => _data = {
          ..._data,
          employees: response?.data.map(record => {
            return {
              value: record.id,
              label: record.employeeNumber+' | '+record.employeeName
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all employee
      callStack.push(getManagerList()
        .then(response => _data = {
          ..._data,
          managers: response?.data.map(record => {
            return {
              value: record.id,
              label: record.employeeNumber+' | '+record.employeeName
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all employee
      callStack.push(getAllEmploymentStatus()
        .then(response => _data = {
          ..._data,
          employmentStatus: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name,
              record: record
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all department
      // callStack.push(getAllDepartment()
      //   .then(response => _data = {
      //     ..._data,
      //     departments: response?.data.map(record => {
      //       return {
      //         value: record.id,
      //         label: record.name
      //       };
      //     }) ?? []
      //   })
      //   .catch(error => message.error(error?.message)));

      // retrieve all job category
      callStack.push(getAllJobCategories()
        .then(response => _data = {
          ..._data,
          jobCategories: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all division
      callStack.push(getAllDivisions()
        .then(response => _data = {
          ..._data,
          divisions: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all job title
      callStack.push(getAllJobTitles()
        .then(response => _data = {
          ..._data, jobTitles: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all location
      callStack.push(getAllLocations()
        .then(response => _data = {
          ..._data, locations: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all calendars
      callStack.push(getCalendarList()
        .then(response => _data = {
          ..._data, calendars: response?.data.map(record => {
            return {
              value: record.calendarId,
              label: record.menuItemName
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all pay grade
      callStack.push(queryPayGrades()
        .then(response => _data = {
          ..._data, payGrades: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));


      // retrieve all Promotion Type
      callStack.push(getAllPromotionTypes()
        .then(response => _data = {
          ..._data, promotionTypes: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));


      // retrieve all Confirmation Reason
      callStack.push(getAllConfirmationReasons()
        .then(response => _data = {
          ..._data, confirmationReasons: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));


      // retrieve all Transfer Type
      callStack.push(getAllTransferTypes()
        .then(response => _data = {
          ..._data, transferTypes: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all Resignation Type
      callStack.push(getAllResignationTypes()
        .then(response => _data = {
          ..._data, resignationTypes: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // retrieve all Resignation Reason
      callStack.push(getAllTerminationReasons()
        .then(response => _data = {
          ..._data, resignationReasons: response?.data.map(record => {
            return {
              value: record.id,
              label: record.name
            };
          }) ?? []
        })
        .catch(error => message.error(error?.message)));

      // set company date
      callStack.push(getCompany()
        .then(response => _data = {
          ..._data, companyDate: response?.data?.timeZone
            ? momentTZ(moment()).tz(response?.data?.timeZone).format('YYYY-MM-DD')
            : moment().format('YYYY-MM-DD')
        })
        .catch(error => message.error(error?.message)));

      Promise.all(callStack).then(() => {
        setData(_data);
        setLoading(false);
      });
    }
  }

  const contentRender = () => {
    switch (activeTab) {
      case 'confirmation_contracts':
        return <ConfirmationContracts data={data} />;
      case 'transfers':
        return <Transfers data={data} />;
      case 'resignations':
        return <Resignations data={data} />;
      default:
        return <Promotions data={data} />;
    }
  }

  return (
    <PageContainer
      loading={loading}
      className='employee-journey-container'
      ghost
      header={{
        title: intl.formatMessage({
          id: 'employee_journey_update',
          defaultMessage: "Employee Journey Update",
        }),
        breadcrumb: {},
      }}
      tabList={[
        {
          tab: <>
            {/* <Icon component={() => <img src={PromotionIcon} height={24} width={24} />} /> */}
            {intl.formatMessage({
              id: 'employee_journey_update.promotions',
              defaultMessage: "Promotions",
            })}
          </>,
          key: 'promotions',
        },
        {
          tab: <>
            {/* <Icon component={() => <img src={ConfirmationContractIcon} height={24} width={24} />} /> */}
            {intl.formatMessage({
              id: 'employee_journey_update.confirmation_contracts',
              defaultMessage: "Confirmation/Contracts",
            })}
          </>,
          key: 'confirmation_contracts',
        },
        {
          tab: <>
            {/* <Icon component={() => <img src={TransferIcon} height={24} width={24} />} /> */}
            {intl.formatMessage({
              id: 'employee_journey_update.transfers',
              defaultMessage: "Transfers",
            })}
          </>,
          key: 'transfers',
        },
        {
          tab: <>
            {/* <Icon component={() => <img src={ResignationIcon} height={24} width={24} />} /> */}
            {intl.formatMessage({
              id: 'employee_journey_update.resignations',
              defaultMessage: "Resignations",
            })}
          </>,
          key: 'resignations',
        }
      ]}
      tabProps={{
        type: 'card',
        hideAdd: true,
        activeKey: activeTab,
        onChange: setActiveTab
      }}
    >
      {contentRender()}
    </PageContainer>
  );
};

export default EmployeeJourneyUpdate;
