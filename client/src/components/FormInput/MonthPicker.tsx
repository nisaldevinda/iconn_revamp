import { generateProFormFieldValidation } from '@/utils/validator';
import { Col } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';
import { ProFormDatePicker } from '@ant-design/pro-form';
import { Moment } from 'moment';

export type MonthPickerProps = {
  modelName: string;
  fieldName: string;
  fieldNamePrefix?: string;
  fieldDefinition: {
    name: string;
    labelKey: string;
    defaultLabel: string;
    type: string;
    isEditable: string;
    isSystemValue: string;
    validations: {
      isRequired: boolean;
      min: number;
      max: number;
    };
    placeholderKey: string;
    defaultPlaceholder: string;
    defaultValue: string;
  };
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
  recentlyChangedValue: any;
};

const MonthPicker: React.FC<MonthPickerProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });
  return (
    <Col data-key={fieldName} span={12}>
      <ProFormDatePicker
        width="65%"
        name={fieldName}
        label={label}
        disabled={props.readOnly}
        picker="month"
        format="MMM"
        placeholder={
          props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
            ? intl.formatMessage({
                id: props.fieldDefinition.placeholderKey,
                defaultMessage: props.fieldDefinition.defaultPlaceholder,
              })
            : ''
        }
        rules={generateProFormFieldValidation(
          props.fieldDefinition,
          props.modelName,
          props.fieldName,
          props.values,
        )}
        fieldProps={{
          onChange: (value: Moment) => {
            const currentValues = { ...props.values };
            currentValues[fieldName] =
              !_.isNull(value) && !_.isUndefined(value) ? value.format('YYYY-MM-DD') : null;
            // !_.isNull(value) && !_.isUndefined(value) ? moment(value).month() + 1 : null;
            props.setValues(currentValues);
          },
        }}
        initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default MonthPicker;
