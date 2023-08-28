import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import ProForm, { ProFormTextArea } from '@ant-design/pro-form';
import _, { values } from 'lodash';
import { Row, Col, FormInstance } from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission';
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { ProFormText } from '@ant-design/pro-form';
import { getAllManagers, getWorkflowPermittedManagers } from '@/services/user';
import { queryContextData } from '@/services/workflowServices';

export type CreateFormProps = {
  model: Partial<ModelType>;
  values: {};
  setValues: (values: any) => void;
  addGroupFormVisible: boolean;
  editGroupFormVisible: boolean;
  isEditView: boolean;
  form: FormInstance;
  emptySwitch: any;
};

const CreatePool: React.FC<CreateFormProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [model, setModel] = useState<any>();
  const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
  const [locationOptions, setLocationOptions] = useState([]);
  const [jobTitleOptions, setJobTitleOptions] = useState([]);
  const [employementStatusOptions, setEmploymentStatusOptions] = useState([]);
  const [departmentOptions, setDepartmentOptions] = useState([]);
  const [divisionOptions, setDivisionOptions] = useState([]);
  const [reportedPersonOptions, setReportedPersonOptions] = useState([]);
  const [contextOptions, setContextOptions] = useState([]);
  const [employeeOptions, setEmployeeOptions] = useState(undefined);

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel('workflowEmployeeGroup').then((response) => {
        const groupModel = response.data;
        setModel(groupModel);
      });
    }
    getOptions();
  }, []);

  const updateValues = (fieldName: any, value: any) => {
    const currentValues = { ...props.values };
    currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
    props.emptySwitch(fieldName);
    props.setValues(currentValues);
  };

  const visibility = (fieldName) => {
    if (
      props.values[fieldName] != null &&
      props.values[fieldName] != undefined &&
      props.values[fieldName] != 0
    ) {
      return true;
    } else {
      return false;
    }
  };
  const convertToOptions = (data, valueField: string, labelField: string) => {
    const arr: { value: string | number; label: string | number; disabled?: boolean }[] = [];
    arr.push({ value: '*', label: 'All' });

    data.forEach((element: { [x: string]: any }) => {
      arr.push({ value: element[valueField], label: element[labelField] });
    });
    return arr;
  };

  const getOptions = async () => {
    try {
      const employeeData = await getWorkflowPermittedManagers();
      if (employeeData.data) {
        const arr: { value: string | number; label: string | number; disabled?: boolean }[] = [];
        employeeData.data.forEach((element: { [x: string]: any }) => {
          arr.push({ value: element['id'], label: element['employeeNumber']+' | '+element['employeeName'] });
        });

        await setEmployeeOptions(arr);
      }
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
      <Col span={24}>
        <ProFormText
          name={'poolName'}
          label="Pool Name"
          width="100%"
          rules={[
            {
              required: true,
              message: intl.formatMessage({
                id: `employeegroups.name`,
                defaultMessage: `Required`,
              }),
            },
            { max: 100, message: 'Maximum length is 100 characters.' },
          ]}
          fieldProps={{
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues['poolName'] =
                !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                  ? value.target.value
                  : null;
              props.setValues(currentValues);
            },
            autoComplete: 'none',
          }}
        />
      </Col>
      <Col span={24}>
        <ProFormSelect
          options={employeeOptions}
          width="100%"
          showSearch
          name="poolPermittedEmployees"
          label="Pool Permitted Employees"
          disabled={false}
          placeholder={'Select Employee'}
          rules={[
            {
              required: true,
              message: intl.formatMessage({
                id: `employeegroups.context`,
                defaultMessage: `Required`,
              }),
            },
          ]}
          fieldProps={{
            mode: 'multiple',
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues['poolPermittedEmployees'] =
                !_.isNull(value) && !_.isUndefined(value) ? value : null;
              props.setValues(currentValues);
            },
          }}
          initialValue={[]}
        />
      </Col>
    </Row>
  );
};

export default CreatePool;
