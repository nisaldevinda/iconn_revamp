import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormTextArea } from '@ant-design/pro-form';
import { Col } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';

export type TextAreaProps = {
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

const TextArea: React.FC<TextAreaProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;

  return (
    <Col data-key={fieldName} span={24}>
      <ProFormTextArea
        width={'100%'}
        style={{ borderRadius: 6 }}
        name={fieldName}
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
        }}
        //    initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default TextArea;
