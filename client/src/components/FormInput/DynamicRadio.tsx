import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormRadio } from "@ant-design/pro-form";
import { Col, Form, Radio } from "antd";
import React from "react";
import { useIntl } from "react-intl";
import _ from "lodash";

export type RadioProps = {
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

const DynamicRadio: React.FC<RadioProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });

  const options = _.get(props,"fieldDefinition.options").map(data=>{
      return{
          label:intl.formatMessage({
            id: `pages.${data.labelKey}`,
            defaultMessage: data.defaultLabel,
          }),
          value:data.value
      }
  })
  

  return (
  <Col data-key={fieldName} span={14}>
  <Form.Item
        label={label}
       
        rules={generateProFormFieldValidation(
          props.fieldDefinition,
          props.modelName,
          props.fieldName,
          props.values
        )}
      >
<Radio.Group
          options={options}
          onChange={(value)=>{
            const currentValues = {...props.values};
            currentValues[props.fieldName] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
            props.setValues(currentValues);
          }}
         defaultValue={_.get(props.values,fieldName,"")}
        />
        </Form.Item>
    </Col>
  );
};

export default DynamicRadio;
