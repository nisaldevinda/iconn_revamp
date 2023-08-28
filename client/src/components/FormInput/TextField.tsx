import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormText } from "@ant-design/pro-form";
import _ from "lodash";
import { Col } from "antd";
import React from "react";
import { useIntl } from "react-intl";

export type TextFieldProps = {
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

const TextField: React.FC<TextFieldProps> = (props) => {
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
      <ProFormText
        width="md"
        name={fieldName}
        label={label}
        key={fieldName}
        disabled={props.readOnly}
        placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
          ? intl.formatMessage({
            id: props.fieldDefinition.placeholderKey,
            defaultMessage: props.fieldDefinition.defaultPlaceholder,
          })
          : props.fieldDefinition.type === 'email' ? 
          intl.formatMessage({
            id: props.fieldDefinition.labelKey,
            defaultMessage: 'john@abc.com',
          }) : ''}
        rules={generateProFormFieldValidation(
          props.fieldDefinition,
          props.modelName,
          props.fieldName,
          props.values
        )}
        fieldProps={{
          onChange: (value) => {
            const currentValues = {...props.values};
            currentValues[fieldName] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
            props.setValues(currentValues);
          },
          autoComplete: "none"
        }}
        initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default TextField;
