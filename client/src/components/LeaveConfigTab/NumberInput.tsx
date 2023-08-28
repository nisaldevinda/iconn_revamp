import { ProFormDigit, ProFormSelect, ProFormSwitch } from '@ant-design/pro-form';
import { Col, Divider, FormInstance, Row, Space, Switch, Typography } from 'antd';
import { countries } from 'currency-codes';
import _ from 'lodash';
import React, { useEffect, useState } from 'react';
import './styles.css'

export type propsType = {
    label?: string,
    yearInputName: string,
    monthInputName: string,
    helperText?: string,
    defaultSelectorVisibility: boolean
    form: FormInstance,

}


const NumberInput: React.FC<propsType> = (props) => {

    const { Text } = Typography;
    const [selectorVisibility, setSelectorVisibility] = useState(false);
    const [optionsForSelect, setOptionsForSelect] = useState([])

    useEffect(() => {
        setSelectorVisibility(props.defaultSelectorVisibility)
       // setOptionsForSelect(props.options)
    }, [props])

    // useEffect(() => {
    //     if (_.indexOf(props.form.getFieldValue(props.name), '*') !== -1) {
    //         addKeyToOptions()
    //     }
    //     else {
    //         removeKeyToOptions()


    //     }
    // }, [props.form.getFieldValue(props.name), props.options])



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

                    {selectorVisibility ?
                    <>
                    
                    <Row style={{ marginLeft: 30, marginBottom:12 }}>
                            <Col>
                                <Text style={{
                                    fontSize: 14,
                                    color: "#626D6C"
                                }}>{props.label}</Text>
                            </Col>

                        </Row >
                    <Row style={{ marginLeft: 30 }}>

                        <Row  className="digit-input-year">

                            <Col>
                                <div style={{
                                    position: "relative",
                                    color:'#626D6C',
                                    width: 60,
                                    height: 32,
                                    background: "#F1F3F6",
                                    border: "1px solid #E1E3E5",
                                    borderRadius: "6px 0px 0px 6px"
                                }}
                                    className="input-add-on-after"
                                >Years</div>
                            </Col>
                            <Col >
                                <ProFormDigit
                                    name={props.yearInputName}
                                    width={100}
                                    initialValue={props.form.getFieldValue(props.yearInputName)}
                                    placeholder=""



                                />
                            </Col>
                            <Col style={{marginLeft:24}}>
                                <div style={{
                                    color:'#626D6C',
                                    position: "relative",
                                    width: 60,
                                    height: 32,
                                    background: "#F1F3F6",
                                    border: "1px solid #E1E3E5",
                                    borderRadius: "6px 0px 0px 6px"
                                }}
                                    className="input-add-on-after"
                                >Months</div>
                            </Col>
                            <Col>
                                <ProFormDigit
                                    name={props.monthInputName}
                                    width={100}
                                    initialValue={props.form.getFieldValue(props.monthInputName)}
                                    placeholder=""
                                    max={11}

                                />
                            </Col>
                        </Row>
                    </Row></> : <></>}
                </Col>
                <Col span={6}>
                    <Switch
                        checked={selectorVisibility}
                        onChange={(checked) => { setSelectorVisibility(checked) }}
                    />
                </Col>
            </Row>
            <Divider style={{ margin: "18px 0px" }} />

        </>
    );
}

export default NumberInput;