import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormRadio } from '@ant-design/pro-form';
import { Col, Form, Radio, Space } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';

export type RadioProps = {
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

const DynamicRadio: React.FC<RadioProps> = (props) => {
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
    <Col data-key={fieldName} className={'templateBuilderRadio'} span={14}>
      <Form.Item>
        <Radio.Group
          buttonStyle="solid"
          onChange={(value) => {
            const currentValues = { ...props.values };
            currentValues[props.fieldName] =
              !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                ? value.target.value
                : null;
            props.setValues(currentValues);
          }}

           defaultValue={_.get(props.values,fieldName,"")}
        >
          <Space direction="vertical">
            {options.map((data) => {
              return <Radio value={data.value}>{data.label}</Radio>;
            })}
          </Space>
        </Radio.Group>
      </Form.Item>
    </Col>
  );
};

export default DynamicRadio;
