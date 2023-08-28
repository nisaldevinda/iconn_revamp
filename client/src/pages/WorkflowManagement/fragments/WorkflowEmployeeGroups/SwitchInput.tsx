import { ProFormSelect, ProFormSwitch } from '@ant-design/pro-form';
import { Col, Divider, FormInstance, Row, Switch, Typography } from 'antd';
import { countries } from 'currency-codes';
import _ from 'lodash';
import React, { useEffect, useState } from 'react';

export type propsType = {
    label?: string,
    name: string,
    options: options[],
    helperText?: string,
    defaultSelectorVisibility: boolean
    form: FormInstance,
    updateValues: any

}
type options = {
    value: string | number,
    label: string
}

const SwitchInput: React.FC<propsType> = (props) => {

    const { Text } = Typography;
    const [selectorVisibility, setSelectorVisibility] = useState(false);
    const [optionsForSelect, setOptionsForSelect] = useState([])

    useEffect(() => {
        setSelectorVisibility(props.defaultSelectorVisibility)
        setOptionsForSelect(props.options)
    }, [props.options])

    useEffect(() => {
        if (_.indexOf(props.form.getFieldValue(props.name), '*') !== -1) {
            addKeyToOptions()
        }
        else {
            removeKeyToOptions()
        }
    }, [props.form.getFieldValue(props.name), props.options])

    const addKeyToOptions = async () => {
        const optionsArr = props.options

        await optionsArr.forEach(element => {
            if (element.value !== '*') {
                element.disabled = true
            }
        });
        setOptionsForSelect(optionsArr)
    }

    const removeKeyToOptions = async () => {
        const optionsArr = props.options
        await optionsArr.forEach(element => {
            if (element.value !== '*') {
                element.disabled = false
            }
        });
        setOptionsForSelect(optionsArr)

    }


    return (
        <>
            <Row >
                <Col span={18}>
                    <Row>
                        <Text style={{
                            fontSize: 18,
                            color: "#3394241"
                        }}>{props.label}</Text>
                    </Row>
                    <Row style={{ marginBottom: 22 }}><Text disabled>{props.helperText} </Text></Row >

                    {selectorVisibility ? <Row style={{ marginLeft: 30 }}>
                        <ProFormSelect
                            name={props.name}
                            label={props.label}
                            options={optionsForSelect}
                            width="lg"
                            mode='multiple'
                            showSearch
                            // initialValue={props.form.getFieldValue(props.name)}
                            fieldProps={{
                                onChange: async (value) => {
                                    const key = {}
                                    props.updateValues(props.name, value);
                                    key[props.name] = value
                                    props.form.setFieldsValue(key)
                                    if (_.indexOf(value, '*') !== -1) {

                                        addKeyToOptions()
                                    }
                                    else {
                                        removeKeyToOptions()
                                    }
                                }
                            }}
                            rules={[
                                {
                                  required: true,
                                  message: 'Required',
                                },
                            ]}

                        />
                    </Row> : <></>}
                </Col>
                <Col span={6}>
                    <Switch
                        checked={selectorVisibility}
                        onChange={(checked) => {
                            if (!checked) {
                                const key = {}
                                props.updateValues(props.name, []);
                                key[props.name] = [];
                                props.form.setFieldsValue(key)

                            }
                            setSelectorVisibility(checked) 
                        
                        }}
                    />
                </Col>
            </Row>
            <Divider style={{ margin: "18px 0px" }} />

        </>
    );
}

export default SwitchInput;