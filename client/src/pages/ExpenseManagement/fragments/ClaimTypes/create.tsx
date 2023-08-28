import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import ProForm, { ProFormTextArea } from '@ant-design/pro-form';
import _, { values } from 'lodash';
import { Row, Col, FormInstance, Form, Divider } from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission';
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { ProFormText, ProFormDatePicker, ProFormSwitch, ProFormDigit } from '@ant-design/pro-form';
import { getAllManagers, getWorkflowPermittedManagers } from '@/services/user';
import { queryContextData } from '@/services/workflowServices';
import OrgSelector from '@/components/OrgSelector';

export type CreateFormProps = {
  model: Partial<ModelType>;
  values: {};
  setValues: (values: any) => void;
  addGroupFormVisible: boolean;
  editGroupFormVisible: boolean;
  claimCategoriesList: any;
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
  const [amountType, setAmountType] = useState(undefined);
  const [maxAmount, setMaxAmount] = useState<any>(null);

  useEffect(() => {
    setAmountType(props.values.amountType);
  }, []);

  return (
    <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
      <Col span={24}>
        <Row gutter={24}>
          <OrgSelector
            value={props.values.orgEntityId}
            setValue={(orgEntityId: number) => {
              console.log(orgEntityId);
              const currentValues = { ...props.values };
              currentValues['orgEntityId'] = orgEntityId;
              props.setValues(currentValues);
            }}
          />
        </Row>
      </Col>
      <Divider></Divider>
      <Col span={12}>
        <ProFormText
          name="typeName"
          label="Type Name"
          width="100%"
          rules={[
            {
              required: true,
              message: 'Required',
            },
          ]}
          fieldProps={{
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues['typeName'] =
                !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                  ? value.target.value
                  : null;
              props.setValues(currentValues);
            },
            autoComplete: 'none',
          }}
          initialValue={null}
        />
      </Col>
      <Col span={12}>
        <ProFormSelect
          name="claimCategoryId"
          showSearch
          label={'Claim Category'}
          // options={selectorEmployees}
          options={props.claimCategoriesList}
          width="100%"
          rules={[
            {
              required: true,
              message: 'Required',
            },
          ]}
          fieldProps={{
            optionItemRender(item) {
              return item.label;
            },
            onChange: async (value) => {
              const currentValues = { ...props.values };
              currentValues['claimCategoryId'] = value;
              props.setValues(currentValues);
            },
          }}
          //   options={relatedDatePeriods}
          placeholder="Select Employee"
          style={{ marginBottom: 0 }}
        />
      </Col>
      <Col span={12}>
        <ProFormSelect
          name="amountType"
          showSearch
          label={'Amount Type'}
          // options={selectorEmployees}
          width="100%"
          rules={[
            {
              required: true,
              message: 'Required',
            },
          ]}
          fieldProps={{
            optionItemRender(item) {
              return item.label;
            },
            onChange: async (value) => {
              const currentValues = { ...props.values };

              if (value == 'UNLIMITED') {
                currentValues['maxAmount'] = null;
                props.form.setFieldsValue({
                  maxAmount: null,
                });
              }
              currentValues['amountType'] = value;
              setAmountType(value);
              props.setValues(currentValues);
            },
          }}
          options={[
            {
              label: 'Unlimited',
              value: 'UNLIMITED',
            },
            {
              label: 'Max Amount',
              value: 'MAX_AMOUNT',
            },
          ]}
          placeholder="Select Amount Type"
          style={{ marginBottom: 0 }}
        />
      </Col>
      <Col span={12}>
        <ProFormDigit
          label="Max Amount"
          name="maxAmount"
          disabled={!(amountType == 'MAX_AMOUNT')}
          min={1}
          rules={
            amountType == 'MAX_AMOUNT'
              ? [
                  {
                    required: true,
                    message: 'Required',
                  },
                ]
              : []
          }
          fieldProps={{
            onChange: async (value) => {
              const currentValues = { ...props.values };
              currentValues['maxAmount'] = value;
              props.setValues(currentValues);
            },
            precision: 0,
          }}
        />
      </Col>
      <Col span={12}>
        <ProFormSelect
          name="orderType"
          showSearch
          label={'Order Type'}
          width="100%"
          rules={[
            {
              required: true,
              message: 'Required',
            },
          ]}
          fieldProps={{
            optionItemRender(item) {
              return item.label;
            },
            onChange: async (value) => {
              const currentValues = { ...props.values };
              currentValues['orderType'] = value;
              props.setValues(currentValues);
            },
          }}
          options={[
            {
              label: 'Monthly',
              value: 'MONTHLY',
            },
            {
              label: 'Annualy',
              value: 'ANNUALY',
            },
          ]}
          placeholder="Select Order Type"
          style={{ marginBottom: 0 }}
        />
      </Col>
      <Col span={12}>
        <Row style={{ marginTop: 25 }}>
          <span style={{ marginRight: 15, marginTop: 5 }}>{'Allocation Enable'}</span>
          <ProFormSwitch
            name="isAllocationEnable"
            width={60}
            checkedChildren="Yes"
            unCheckedChildren="No"
            fieldProps={{
              onChange: (value) => {
                const currentValues = { ...props.values };
                currentValues['isAllocationEnable'] = value;
                props.setValues(currentValues);
              },
            }}
          />
        </Row>
      </Col>
      <Col span={8}>
        <Row style={{ marginTop: 25 }}>
          <span style={{ marginRight: 15, marginTop: 5 }}>{'Allow Attachments'}</span>
          <ProFormSwitch
            name="isAllowAttachment"
            width={60}
            checkedChildren="Yes"
            unCheckedChildren="No"
            fieldProps={{
              onChange: (value) => {
                const currentValues = { ...props.values };
                currentValues['isAllowAttachment'] = value;
                props.setValues(currentValues);
              },
            }}
          />
        </Row>
      </Col>
      {props.values.isAllowAttachment && (
        <Col span={12}>
          <Row style={{ marginTop: 25 }}>
            <span style={{ marginRight: 15, marginTop: 5 }}>{'Attachment Mandatory'}</span>
            <ProFormSwitch
              name="isAttachmentMandatory"
              width={60}
              checkedChildren="Yes"
              unCheckedChildren="No"
              fieldProps={{
                onChange: (value) => {
                  const currentValues = { ...props.values };
                  currentValues['isAttachmentMandatory'] = value;
                  props.setValues(currentValues);
                },
              }}
            />
          </Row>
        </Col>
      )}
    </Row>
  );
};

export default CreatePool;
