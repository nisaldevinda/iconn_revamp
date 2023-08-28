import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormDatePicker } from '@ant-design/pro-form';
import { Col } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';

export type DatePickerProps = {
  fieldName: string;
  fieldDefinition: {
    name: string;
    type: string;
    validations: {
      isRequired: boolean;
    };
  };
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
};

const DatePicker: React.FC<DatePickerProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;

  return (
    <Col data-key={fieldName} span={12}>
      <ProFormDatePicker
        width="md"
        name={fieldName}
        fieldProps={{
          format:'DD-MM-YYYY',
          onChange: (value) => {
            const currentValues = { ...props.values };
            currentValues[fieldName] =
              !_.isNull(value) && !_.isUndefined(value) ? value.format('YYYY-MM-DD') : null;
            props.setValues(currentValues);
          },
        //   showToday: props.fieldDefinition.disableTodayOption ? false : true,
        }}
        // initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default DatePicker;
