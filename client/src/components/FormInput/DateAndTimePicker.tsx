import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormDatePicker } from '@ant-design/pro-form';
import { Col } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';

export type DateAndTimePickerProps = {
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
    disableTodayOption: boolean;
  };
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
  recentlyChangedValue: any;
};

const DateAndTimePicker: React.FC<DateAndTimePickerProps> = (props) => {
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
        width="md"
        name={fieldName}
        label={label}
        disabled={props.readOnly}
        placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
          ? intl.formatMessage({
            id: props.fieldDefinition.placeholderKey,
            defaultMessage: props.fieldDefinition.defaultPlaceholder,
          })
          : 'Select ' .concat(label).concat(' (DD-MM-YYYY)')}
        rules={generateProFormFieldValidation(
          props.fieldDefinition,
          props.modelName,
          props.fieldName,
          props.values,
        )}
        fieldProps={{
          format:'DD-MM-YYYY',
          onChange: (value) => {
            const currentValues = { ...props.values };
            currentValues[fieldName] =
              !_.isNull(value) && !_.isUndefined(value) ? value.format('YYYY-MM-DD') : null;
            props.setValues(currentValues);
          },
          showToday: props.fieldDefinition.disableTodayOption ? false : true,
        }}
        initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default DateAndTimePicker;
