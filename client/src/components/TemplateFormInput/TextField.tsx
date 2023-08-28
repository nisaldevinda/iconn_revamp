import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormText } from '@ant-design/pro-form';
import _ from 'lodash';
import { Col } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';

export type TextFieldProps = {
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
  //   recentlyChangedValue: any
};

const TextField: React.FC<TextFieldProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;

  return (
    <Col data-key={fieldName} span={24}>
      <ProFormText
        width={'100%'}
        name={fieldName}
        style={{ height: 50 }}
        key={fieldName}
        disabled={props.readOnly}
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
            currentValues[fieldName] =
              !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                ? value.target.value
                : null;
            props.setValues(currentValues);
          },
          autoComplete: 'none',
        }}
        initialValue={null}
      />
    </Col>
  );
};

export default TextField;
