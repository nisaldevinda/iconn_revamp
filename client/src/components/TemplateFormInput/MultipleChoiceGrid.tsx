import React, { useState, useEffect, useRef } from 'react';
import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormRadio } from '@ant-design/pro-form';
import { Col, Form, Radio, Space, Checkbox, Row } from 'antd';
import { useIntl } from 'react-intl';
import _ from 'lodash';
import type { CheckboxValueType } from 'antd/es/checkbox/Group';
import { ExclamationCircleOutlined } from '@ant-design/icons';

export type MultipleChoiceGridProps = {
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

const MultipleChoiceGrid: React.FC<MultipleChoiceGridProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;
  const [hasEmpryRows, setHasEmpryRows] = useState(false);
  const [validateMultipleChoiceGrid, setValidateMultipleChoiceGrid] = useState<
    '' | 'error' | 'warning'
  >('');
  const [helpMultipleChoiceGrid, setHelpMultipleChoiceGrid] = useState('');

  return (
    <Col data-key={fieldName} className={'multipleChoiceRadio'} span={24}>
      <Form.Item
        // label={label}
        name={fieldName}
        validateStatus={validateMultipleChoiceGrid}
        help={helpMultipleChoiceGrid}
        rules={[
          {
            validator: (rule, value) => {
              const currentValues = { ...props.values };

              let isHaveEmptyRows = false;

              for (var subIndex in currentValues[props.fieldName]) {
                if (currentValues[props.fieldName][subIndex] == null) {
                  isHaveEmptyRows = true;
                }
              }

              if (isHaveEmptyRows) {
                setValidateMultipleChoiceGrid('error');
                setHelpMultipleChoiceGrid(
                  <p style={{ color: 'red' }}>
                    {' '}
                    <ExclamationCircleOutlined style={{ marginRight: 10 }} />
                    {'This question requires at least one response per row'}
                  </p>,
                );
                return Promise.reject();
              } else {
                setValidateMultipleChoiceGrid('');
                setHelpMultipleChoiceGrid('');
                return Promise.resolve();
              }
            },
          },
        ]}
      >
        <table>
          <tr>
            {props.answerDetails.headerList.map((header) => {
              return (
                <td style={{ width: 'auto', padding: 15, fontSize: 14, color: 'black' }}>
                  {header}
                </td>
              );
            })}
          </tr>
          {props.answerDetails.subRadioGroupData.map((row, rowKey) => {
            return (
              <tr>
                <td
                  style={{
                    width: 150,
                    paddingTop: 15,
                    paddingBottom: 15,
                    fontSize: 14,
                    color: 'black',
                  }}
                >
                  {row.label}
                </td>
                {row.options.map((col) => {
                  return (
                    <td
                      style={{
                        width: 'auto',
                        padding: 15,
                        fontSize: 14,
                        color: 'black',
                        textAlign: 'center',
                      }}
                    >
                      <Radio
                        checked={props.values[props.fieldName][row.key] == col.value ? true : false}
                        onChange={(value) => {
                          const currentValues = { ...props.values };
                          currentValues[props.fieldName][row.key] =
                            !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                              ? value.target.value
                              : null;

                          props.setValues(currentValues);
                        }}
                        value={col.value}
                      ></Radio>
                    </td>
                  );
                })}
              </tr>
            );
          })}
        </table>
      </Form.Item>
    </Col>
  );
};

export default MultipleChoiceGrid;
