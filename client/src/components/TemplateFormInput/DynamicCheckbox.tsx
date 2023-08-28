import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormRadio } from '@ant-design/pro-form';
import { Col, Form, Radio, Space, Checkbox } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';
import type { CheckboxValueType } from 'antd/es/checkbox/Group';

export type CheckboxProps = {
  fieldName: string;
  answerDetails: any;
  fieldDefinition: {
    type: string;
    isEditable: string;
    validations: {
      isRequired: boolean;
    };
  };
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
};

const DynamicCheckbox: React.FC<CheckboxProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;

  const options = _.get(props, 'answerDetails.options').map((data) => {
    return {
      label: intl.formatMessage({
        id: `pages.${data.value}`,
        defaultMessage: data.label,
      }),
      value: data.value,
    };
  });

  return (
    <Col data-key={fieldName} span={14} className={'templateBuilderCheckBox'}>
      <Form.Item>
        <Checkbox.Group
          options={options}
          onChange={(checkedValues: CheckboxValueType[]) => {
            const currentValues = { ...props.values };
            currentValues[props.fieldName] = checkedValues;
            props.setValues(currentValues);
          }}

           defaultValue={_.get(props.values,fieldName,"")}
        ></Checkbox.Group>
      </Form.Item>
    </Col>
  );
};

export default DynamicCheckbox;
