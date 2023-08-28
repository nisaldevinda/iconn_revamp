import React, { useState, useEffect, useRef } from 'react';
import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormRadio } from '@ant-design/pro-form';
import { Col, Form, Radio, Space, Checkbox, Row } from 'antd';
import { useIntl } from 'react-intl';
import _ from 'lodash';
import type { CheckboxValueType } from 'antd/es/checkbox/Group';
import { ExclamationCircleOutlined } from '@ant-design/icons';

export type CheckBoxGridProps = {
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

const CheckBoxGrid: React.FC<CheckBoxGridProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;
  const [validateCheckBoxGrid, setValidateCheckBoxGrid] = useState<"" | "error" | "warning">("");
  const [helpCheckBoxGrid, setHelpCheckBoxGrid] = useState('');

  return (
    <Col data-key={fieldName} className={'templateBuilderCheckBox'} span={24}>
        <Form.Item
        // label={label}
        name={fieldName}
        validateStatus={validateCheckBoxGrid}
        help={helpCheckBoxGrid}

        rules={[
            {
                validator: (rule, value) => {
                    const currentValues = { ...props.values };

                    let isHaveEmptyRows = false;
    
                    for (var subIndex in currentValues[props.fieldName]) {
                        if (currentValues[props.fieldName][subIndex].length == 0) {
                            isHaveEmptyRows = true; 
                        }
                    }

                    if (isHaveEmptyRows) {
                        setValidateCheckBoxGrid('error');
                        setHelpCheckBoxGrid(<p style={{color: 'red'}}> <ExclamationCircleOutlined style={{marginRight: 10}} />{'This question requires at least one response per row'}</p>);
                        return Promise.reject();
                    } else {
                        setValidateCheckBoxGrid('');
                        setHelpCheckBoxGrid('');
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
                <td style={{ width: 'auto', padding: 15, fontSize: 14, color: 'black' }}>{header}</td>
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
                        <Checkbox 
                            checked={props.values[props.fieldName][row.key].includes(col.value) ? true : false} 
                            value={col.value} 
                            onChange={(value) => {
                            const currentValues = { ...props.values };

                            if (!currentValues[props.fieldName][row.key].includes(col.value)) {
                                currentValues[props.fieldName][row.key].push(col.value);
                            } else {
                                let index = currentValues[props.fieldName][row.key].indexOf(col.value);

                                if (index !== -1) {
                                    currentValues[props.fieldName][row.key].splice(index, 1);
                                }
                            }
                            props.setValues(currentValues);
                            }}
                        >
                        </Checkbox>
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

export default CheckBoxGrid;
