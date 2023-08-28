import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormSelect } from '@ant-design/pro-form';
import { Col } from 'antd';
import _ from 'lodash';
import React from 'react';
import { useIntl } from 'react-intl';

export type SelectorProps = {
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
  recentlyChangedValue: any;
};

const Selector: React.FC<SelectorProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;

  return (
    <Col data-key={fieldName} span={24}>
      <ProFormSelect
        width="md"
        name={fieldName}
        disabled={props.readOnly}
        request={async () =>
          props.answerDetails.options.map((value) => {
            return {
              label: intl.formatMessage({
                id: `${props.fieldName}.${value.value}`,
                defaultMessage: value.label,
              }),
              value: value.value,
            };
          })
        }
        rules={
          props.answerDetails.isRequired
            ? [
                {
                  required: true,
                  message: intl.formatMessage({
                    id: `${fieldName}.rules.required`,
                    defaultMessage: `Required`,
                  }),
                },
              ]
            : []
        }
        fieldProps={{
          onChange: (value) => {
            const currentValues = { ...props.values };
            currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
            props.setValues(currentValues);
          },
        }}
        // initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default Selector;
