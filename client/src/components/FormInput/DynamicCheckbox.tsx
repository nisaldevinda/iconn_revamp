import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormCheckbox } from "@ant-design/pro-form";
import { Col } from "antd";
import React from "react";
import { useIntl } from "react-intl";
import _ from "lodash";
import { Checkbox } from 'antd';

export type CheckboxProps = {
  modelName: string,
  fieldName: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: string,
    isEditable: string,
    isSystemValue: string,
    validations: {
      isRequired: boolean,
      min: number,
      max: number
    },
    placeholderKey: string,
    defaultPlaceholder: string,
    defaultValue: string,
  },
  readOnly: boolean;
  values: {},
  setValues: (values: any) => void,
  recentlyChangedValue: any
};

const DynamicCheckbox: React.FC<CheckboxProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });

  return (
    <Col data-key={fieldName} span={14}>
      <Checkbox
      style={{ marginBottom: '20px' ,width:"md"}}
        name={fieldName}
        
        onChange={(value) => {
            const currentValues = {...props.values};
            currentValues[props.fieldName] = !_.isNull(value.target.checked) && !_.isUndefined(value.target.checked) ? value.target.checked : null;
            props.setValues(currentValues);
          }}
          defaultChecked={parseInt(_.get(props.values,fieldName,false),10)}
      >{label}</Checkbox>
    </Col>
  );
};

export default DynamicCheckbox;
