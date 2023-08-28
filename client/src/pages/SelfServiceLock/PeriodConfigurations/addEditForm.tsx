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

  useEffect(() => {
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
    }
  }, []);

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
        <ProFormDatePicker
          style={{ borderRadius: 6 }}
          width={250}
          label="Configured Month"
          initialValue={null}
          name={'configuredMonth'}
          fieldProps={{
            onChange: (value) => {
              let configMonthSelected = !_.isNull(value) && !_.isUndefined(value) ? true : false;
              setIsConfiguredMonthSelected(configMonthSelected);
              const currentValues = { ...props.values };
              currentValues['configuredMonth'] =
                !_.isNull(value) && !_.isUndefined(value)
                  ? moment(value, 'YYYY/MMM').format('YYYY/MMM')
                  : null;
              props.setValues(currentValues);
            },
            autoComplete: 'none',
          }}
          picker="month"
          format={'YYYY/MMM'}
        />
      </Col>
      <Col span={12}>
        <Form.Item
          name="fromDate"
          label="From Date"
          initialValue={null}

          // rules={leavePeriodType !== 'FULL_DAY' ? [{
          // required: true,
          // message: 'Required',
          // }] : []}
        >
          <DatePicker
            style={{ borderRadius: 6, width: 250 }}
            disabled={!isConfiguredMonthSelected}
            format={'DD-MM-YYYY'}
            onChange={(value) => {
              let fromDateMoment = value;
              let toDateMoment = props.form.getFieldValue('toDate');

              if (fromDateMoment && toDateMoment) {
                if (!toDateMoment.isAfter(fromDateMoment)) {
                  props.form.setFields([
                    {
                      name: 'fromDate',
                      errors: ['Should be less than to date'],
                    },
                  ]);
                } else {
                  props.form.setFields([
                    {
                      name: 'fromDate',
                      errors: [],
                    },
                  ]);
                }
              }

              const currentValues = { ...props.values };
              currentValues['fromDate'] =
                !_.isNull(value) && !_.isUndefined(value)
                  ? moment(value, 'DD-MM-YYYY').format('YYYY-MM-DD')
                  : null;
              props.setValues(currentValues);
            }}
            disabledDate={(current) => {
              let selectMonth = moment(
                props.form.getFieldValue('configuredMonth'),
                'YYYY/MMM',
              ).format('YYYY-MM');
              let pastMonth = moment(selectMonth).subtract(1, 'M').format('YYYY-MM');
              let futureMonth = moment(selectMonth).add(1, 'M').format('YYYY-MM');

              let compareMonth = moment(current, 'DD-MM-YYYY').format('YYYY-MM');

              let isEqualToSelctedMonth = compareMonth == selectMonth;
              let isEqToLastMont = compareMonth == pastMonth;
              let isEqNextMonth = compareMonth == futureMonth;
              return !isEqualToSelctedMonth && !isEqToLastMont && !isEqNextMonth;
            }}
          />
        </Form.Item>
      </Col>
      <Col span={12}>
        <Form.Item
          name="toDate"
          label="To Date"
          initialValue={null}
          // rules={leavePeriodType !== 'FULL_DAY' ? [{
          // required: true,
          // message: 'Required',
          // }] : []}
        >
          <DatePicker
            format={'DD-MM-YYYY'}
            disabled={!isConfiguredMonthSelected}
            style={{ borderRadius: 6, width: 250 }}
            onChange={(value) => {
              let toDateMoment = value;
              let fromDateMoment = props.form.getFieldValue('fromDate');

              if (fromDateMoment && toDateMoment) {
                if (!toDateMoment.isAfter(fromDateMoment)) {
                  props.form.setFields([
                    {
                      name: 'toDate',
                      errors: ['Should be greater than from date'],
                    },
                  ]);
                } else {
                  props.form.setFields([
                    {
                      name: 'toDate',
                      errors: [],
                    },
                  ]);
                }
              }

              const currentValues = { ...props.values };
              currentValues['toDate'] =
                !_.isNull(value) && !_.isUndefined(value)
                  ? moment(value, 'DD-MM-YYYY').format('YYYY-MM-DD')
                  : null;
              props.setValues(currentValues);
            }}
            disabledDate={(current) => {
              let selectMonth = moment(
                props.form.getFieldValue('configuredMonth'),
                'YYYY/MMM',
              ).format('YYYY-MM');
              let pastMonth = moment(selectMonth).subtract(1, 'M').format('YYYY-MM');
              let futureMonth = moment(selectMonth).add(1, 'M').format('YYYY-MM');

              let compareMonth = moment(current, 'DD-MM-YYYY').format('YYYY-MM');

              let isEqualToSelctedMonth = compareMonth == selectMonth;
              let isEqToLastMont = compareMonth == pastMonth;
              let isEqNextMonth = compareMonth == futureMonth;
              return !isEqualToSelctedMonth && !isEqToLastMont && !isEqNextMonth;
            }}
          />
        </Form.Item>
      </Col>
      <Col span={24}></Col>
    </Row>
  );
};

export default AddEditForm;
