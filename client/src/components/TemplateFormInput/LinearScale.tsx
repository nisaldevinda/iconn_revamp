import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormRadio } from '@ant-design/pro-form';
import { Col, Form, Radio, Space, Checkbox, Row } from 'antd';
import React from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';
import type { CheckboxValueType } from 'antd/es/checkbox/Group';

export type LinearScaleProps = {
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

const LinearScale: React.FC<LinearScaleProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;

  const options = _.get(props, 'answerDetails.linearScaleOptionArr').map((data) => {
    return {
      label: intl.formatMessage({
        id: `pages.${data.value}`,
        defaultMessage: data.label,
      }),
      value: data.value,
    };
  });

  return (
    <Col data-key={fieldName} span={24} className={'templateBuilderRadio'}>
      <div style={{ display: 'flex' }}>
        <Col>
          <div style={{ marginTop: 40, marginRight: 10, fontSize: 16 }}>
            {props.answerDetails.linearLowerLimitLabel}
          </div>
        </Col>
        <Col>
          <Form.Item
            // label={label}
            name={fieldName}
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
          >
            <Radio.Group
              buttonStyle="solid"
              size="large"
              onChange={(value) => {
                const currentValues = { ...props.values };
                currentValues[props.fieldName] =
                  !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                    ? value.target.value
                    : null;
                props.setValues(currentValues);
              }}
            >
              <Space>
                <div style={{ display: 'flex' }}>
                  {options.map((data) => {
                    return (
                      <Col style={{ marginRight: 10, marginLeft: 10, width: 25 }}>
                        <Row
                          style={{
                            width: '100%',
                            color: 'black',
                            fontSize: 14,
                            paddingLeft: 3,
                            marginBottom: 15,
                          }}
                        >
                          {data.label}
                        </Row>
                        <Row style={{ width: '100%' }}>
                          <Radio value={data.value}></Radio>
                        </Row>
                      </Col>
                    );
                  })}
                </div>
              </Space>
            </Radio.Group>
          </Form.Item>
        </Col>
        <Col>
          <div style={{ marginTop: 40, marginLeft: 10, fontSize: 16 }}>
            {props.answerDetails.linearUpperLimitLabel}
          </div>
        </Col>
      </div>
    </Col>
  );
};

export default LinearScale;
