import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import _, { values } from 'lodash';
import {
  Row,
  Col,
  FormInstance,
  Input,
  Tooltip,
  Button,
  Form,
  Space,
  Radio,
  DatePicker,
  Checkbox,
} from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission';
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import {
  ProFormText,
  ProFormFieldSet,
  ProFormRadio,
  ProFormDigit,
  ProFormDatePicker,
} from '@ant-design/pro-form';
import { generateProFormFieldValidation } from '@/utils/validator';
import { SketchPicker } from 'react-color';
import { BgColorsOutlined } from '@ant-design/icons';
import moment from 'moment';
import { getAllPeriodConfigs } from '@/services/selfLockPeriodConfig';
import type { CheckboxValueType } from 'antd/es/checkbox/Group';

export type CreateFormProps = {
  model: Partial<ModelType>;
  values: {};
  setValues: (values: any) => void;
  addDayTypeFormVisible: boolean;
  editDayTypeFormVisible: boolean;
  form: FormInstance;
};

const AddEditForm: React.FC<CreateFormProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [model, setModel] = useState<any>();
  const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
  const [color, setColor] = useState('#000000');
  const [iconColor, setIconColor] = useState('#000000');
  const [colorCode, setColorCode] = useState('');
  const [showColorPicker, setShowColorPicker] = useState<boolean>(false);
  const [isConfiguredMonthSelected, setIsConfiguredMonthSelected] = useState<boolean>(false);
  const [isOverTimeSelected, setIsOverTimeSelected] = useState<boolean>(false);
  const [configuredMonth, setConfiguredMonth] = useState<boolean>(null);
  const [effectiveFromDate, setEffectiveFromDate] = useState<any>(null);
  const [relatedDatePeriods, setRelatedDatePeriods] = useState([]);

  useEffect(() => {
    getRelatedDatePeriods();

    if (_.isEmpty(model)) {
      getModel(Models.User).then((response) => {
        const userModel = response.data;
        setModel(userModel);
      });
    }

    if (props.values.id != null) {
      if (props.values.configuredMonth) {
        setIsConfiguredMonthSelected(true);
      } else {
        setIsConfiguredMonthSelected(false);
      }
    } else {
      // setEffectiveFromDate(props.values.effectiveFrom)
    }
  }, []);

  const getRelatedDatePeriods = async () => {
    try {
      const actions: any = [];
      const { data } = await getAllPeriodConfigs();
      const res = data.map((period: any) => {
        actions.push({ value: period.id, label: period.configuredMonth });
        return {
          label: period.configuredMonth,
          value: period.id,
        };
      });
      setRelatedDatePeriods(actions);
      return res;
    } catch (err) {
      console.log(err);
      return [];
    }
  };

  const getRules = (fieldName: any) => {
    if (props.addDayTypeFormVisible || props.editDayTypeFormVisible) {
      return generateProFormFieldValidation(
        props.model.modelDataDefinition.fields[fieldName],
        'user',
        fieldName,
        props.values,
      );
    } else {
      return [];
    }
  };

  return (
    <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
      <Col span={24}>
        <ProFormSelect
          name="selfServiceLockDatePeriodId"
          showSearch
          label={'Lock Effective Month'}
          // options={selectorEmployees}
          width={250}
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
              currentValues['selfServiceLockDatePeriodId'] = value;
              props.setValues(currentValues);
            },
          }}
          options={relatedDatePeriods}
          placeholder="Select Employee"
          style={{ marginBottom: 0 }}
        />
      </Col>
      <Col span={12}>
        <Form.Item name="effectiveFrom" label="Effective From">
          <DatePicker
            style={{ borderRadius: 6, width: 250 }}
            disabled={true}
            defaultValue={props.values.effectiveFrom}
            format={'DD-MM-YYYY'}
          />
        </Form.Item>
      </Col>
      {props.values.id != null ? (
        <Col span={10}>
          <Form.Item name="status" label="Status">
            <Radio.Group
              onChange={async (value) => {
                const currentValues = { ...props.values };
                currentValues['status'] = value.target.value;
                props.form.setFieldsValue({
                  effectiveFrom: moment(),
                });
                currentValues['effectiveFrom'] = moment();
                props.setValues(currentValues);
              }}
              buttonStyle="solid"
            >
              <Radio.Button value="LOCKED">Locked</Radio.Button>
              <Radio.Button value="UNLOCKED">Unlocked</Radio.Button>
            </Radio.Group>
          </Form.Item>
        </Col>
      ) : (
        <></>
      )}
      <Col span={24}>Lock Effective Self Services</Col>
      <Col span={24} className={'templateBuilderCheckBox'}>
        <Form.Item name="selfServicesStatus">
          <Checkbox.Group
            style={{ width: '100%' }}
            onChange={(value) => {
              const currentValues = { ...props.values };

              let selfServicesStatus = {
                timeChangeRequest: false,
                leaveRequest: false,
                shortLeaveRequest: false,
                shiftChangeRequest: false,
                leaveCancelRequest: false,
                leaveCancelationUpdate: false,
                adminLeaveRequest: false,
                adminShortLeaveRequest: false,
                adminShiftChange: false,
                postOtRequest: false,
              };

              value.map((service) => {
                selfServicesStatus[service] = true;
              });

              currentValues['selfServicesStatus'] = selfServicesStatus;
              props.form.setFieldsValue({
                effectiveFrom: moment(),
              });
              currentValues['effectiveFrom'] = moment();

              props.setValues(currentValues);
            }}
          >
            <Row style={{ width: '100%' }}>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'timeChangeRequest'}>Time Change Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'leaveRequest'}>Leave Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'shortLeaveRequest'}>Short Leave Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'shiftChangeRequest'}>Shift change Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'leaveCancelRequest'}>Cancel Leave Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'leaveCancelationUpdate'}>Leave Cancellation Update</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'adminLeaveRequest'}>Admin Leave Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'adminShortLeaveRequest'}>Admin Short Leave Request</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'adminShiftChange'}>Admin Shift Change</Checkbox>
              </Col>
              <Col style={{ padding: 8 }} span={8}>
                <Checkbox value={'postOtRequest'}>Post OT Request</Checkbox>
              </Col>
            </Row>
          </Checkbox.Group>
        </Form.Item>
      </Col>
    </Row>
  );
};

export default AddEditForm;
