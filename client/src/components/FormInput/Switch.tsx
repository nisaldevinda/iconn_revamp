import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormSwitch } from "@ant-design/pro-form";
import { Col } from "antd";
import React from "react";
import { useIntl } from "react-intl";
import _ from "lodash";

export type SwitchProps = {
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

const Switch: React.FC<SwitchProps> = (props) => {
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
      <ProFormSwitch
        width="md"
        name={fieldName}
        label={label}
        disabled={props.readOnly}
        placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
          ? intl.formatMessage({
            id: props.fieldDefinition.placeholderKey,
            defaultMessage: props.fieldDefinition.defaultPlaceholder,
          })
          : 'Select rate'}
        rules={generateProFormFieldValidation(
          props.fieldDefinition,
          props.modelName,
          props.fieldName,
          props.values
        )}
        fieldProps={{
          onChange: (value) => {
            const currentValues = {...props.values};
            currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
            props.setValues(currentValues);
          }
        }}
        initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default Switch;
