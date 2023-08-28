import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Form,
  Row,
  Col,
  Input,
  Button,
  Select,
  InputNumber
} from 'antd';

import { PlusOutlined, CloseOutlined } from '@ant-design/icons';
import styles from '../index.less';


interface PayRowProps {
    dayTypeArrIndex: any,
    setValues: (values: any) => void;
    selectedDayTypes: any,
    payTypeEnumList: any,
    refresh: any
}

const PayTypeDetailRow: React.FC<PayRowProps> = (props) => {

    const [payThresholdList, setPayThresholdList] = useState([]);
    const [payTypeList, setPayTypeList] = useState([]);
    const [payTypeIds, setPayTypeIds] = useState([]);

    useEffect (() =>{
        setPayThresholdList(props.selectedDayTypes[props.dayTypeArrIndex].payTypeDetails);
        setPayTypeList(props.payTypeEnumList);
    },[]);


    useEffect (() =>{
        setPayThresholdList(props.selectedDayTypes[props.dayTypeArrIndex].payTypeDetails);
        setPayTypeList(props.payTypeEnumList);
    },[props.refresh]);
  

return (
    <>
        {
            payThresholdList.map((childEl:object, childIndex:any) => {
                return (
                    <Row style={{marginLeft: 20}}>
                    <Col span={18} style={{ backgroundColor: 'white', borderRadius: 10, marginBottom: 10}}>
                        <Row style={{marginBottom: 20}}>
                            <Col span={12} style={{marginTop: 20, marginLeft: 20}}>
                                <Row className={styles.formLabel}>Pay Type<span style={{color: 'red', paddingLeft: 5}}>*</span></Row>
                                <Select
                                    placeholder="Select Pay Type"
                                    allowClear= {true}
                                    value={childEl.payTypeId}
                                    onChange={(event:any) => {

                                        const parentArrValues = [...props.selectedDayTypes];
                                        let oldPayId = parentArrValues[props.dayTypeArrIndex].payTypeDetails[childIndex].payTypeId;
                                        parentArrValues[props.dayTypeArrIndex].payTypeDetails[childIndex].payTypeId = event;

                                        props.setValues(parentArrValues);
                                        let newPayTypeEnumList = props.selectedDayTypes[props.dayTypeArrIndex].payTypeEnumList.map((data:object, ind:any) => {

                                            if (event == undefined && oldPayId == data.payTypeId) {
                                                data.disabled = false;
                                            }

                                            if (event != undefined && event == data.payTypeId &&  !data.disabled) {
                                                data.disabled = true;
                                            }  

                                            if (event != undefined && oldPayId == data.payTypeId &&  data.disabled) {
                                                data.disabled = false;
                                            }  
                                            return data;
                                        });
                                        
                                        const mainArrValues = [...props.selectedDayTypes];
                                        mainArrValues[props.dayTypeArrIndex].payTypeEnumList = newPayTypeEnumList;
                                        props.setValues(mainArrValues);

                                        console.log(props.selectedDayTypes);
                                    }}
                                    style={{ width: '100%' }}
                                    >
                                    {props.selectedDayTypes[props.dayTypeArrIndex].payTypeEnumList.map(item => (
                                        <Select.Option key={item.payTypeId} value={item.id} disabled={item.disabled}>
                                            {item.name}
                                        </Select.Option>
                                    ))}
                                </Select>
                            </Col>
                            <Col span={10} style={{marginTop: 20, marginLeft: 20}}>
                                <Row className={styles.formLabel}>Hours Per Day<span style={{color: 'red', paddingLeft: 5}}>*</span></Row>
                                <Row style={{display: 'flex'}}>
                                    <Input disabled={true} className={styles.hourInput} defaultValue={childEl.thresholdType} />
                                    <InputNumber value={childEl.hoursPerDay} min={1} max={24} style={{marginLeft: -5, borderTopRightRadius: 6, borderBottomRightRadius: 6}}  onChange={(event) => {
                                        const parentArrValues = [...props.selectedDayTypes];
                                        parentArrValues[props.dayTypeArrIndex].payTypeDetails[childIndex].hoursPerDay = event;
                                        props.setValues(parentArrValues);
                                    }} />
                                </Row>
                            </Col>
                        </Row>
                    </Col>
                    <Col span={6} style={{paddingLeft: 20, marginTop: 30}}>
                        
                        {
                            childEl.showAddBtn ? (
                                <Button
                                    type="primary"
                                    style= {{backgroundColor: '#FFA500', borderColor: '#FFA500', fontSize: 25, marginRight: 15}}
                                    size="large"
                                    icon={<PlusOutlined />}
                                    onClick={() => {
                                        const currentValues = [...payThresholdList];
                                        let thresholdKey = currentValues.length + 1;
                                        currentValues[childIndex].showAddBtn = false;

                                        if (currentValues.length < 3) {

                                            let payTypeObj = {
                                                'id' : 'new',
                                                'payTypeId': null,
                                                'thresholdType': 'After',
                                                'hoursPerDay': null,
                                                'thresholdKey': thresholdKey,
                                                'showAddBtn' : true
                                            }

                                            if (currentValues.length == 2) {
                                                payTypeObj.showAddBtn = false;
                                            }
                                            currentValues.push(payTypeObj);


                                            const parentArrValues = [...props.selectedDayTypes];
                                            parentArrValues[props.dayTypeArrIndex].payTypeDetails = currentValues;
                                            props.setValues(parentArrValues);
                                            setPayThresholdList(currentValues);
                                        }
                                    }}
                                ></Button>
                            ) :(
                            <></>
                            )
                        }
        
                        {
                        
                            (childEl.thresholdKey != 1) ? (
                                <Button
                                    type="primary"
                                    style= {{backgroundColor: '#505050', borderColor: '#505050', fontSize: 25}}
                                    size="large"
                                    icon={<CloseOutlined />}
                                    onClick={() => {
                                        const currentValues = [...payThresholdList];
                                        // let thresholdKey = currentValues.length + 1;
                                        // currentValues[childIndex].showAddBtn = false;
                                        let newPayDetailsArr = [];
                                        let oldPayId = null;

                                        
                                        currentValues.map((data:object, ind:any) => {
                                            if (childIndex !== ind) {
                                                console.log(ind, data);
                                                newPayDetailsArr.push(data);
                                            } else {
                                                oldPayId = data.payTypeId;
                                            }
                                        });


                                        let sizeOfNewArr = newPayDetailsArr.length;

                                        if (sizeOfNewArr == 2) {
                                            newPayDetailsArr[0].showAddBtn = false;
                                            newPayDetailsArr[1].showAddBtn = true;
                                            newPayDetailsArr[1].thresholdKey = 2;
                                        } else if (sizeOfNewArr == 1) {
                                            newPayDetailsArr[0].showAddBtn = true;
                                        }

                                        let newPayTypeEnumList = props.selectedDayTypes[props.dayTypeArrIndex].payTypeEnumList.map((data:object, ind:any) => {

                                            if (oldPayId == data.payTypeId) {
                                                data.disabled = false;
                                            }  
                                            return data;
                                        });


                                        const parentArrValues = [...props.selectedDayTypes];
                                        parentArrValues[props.dayTypeArrIndex].payTypeDetails = newPayDetailsArr;
                                        parentArrValues[props.dayTypeArrIndex].payTypeEnumList = newPayTypeEnumList;
                                        props.setValues(parentArrValues);

                                        setPayThresholdList(newPayDetailsArr);
                                        
                                       
                                    }}
                                ></Button>
                            ): (
                                <></>
                            )
                        
                        }
                    </Col>
                </Row>
                )                                                
            })
        }
    </>
    
    
)
};

export default PayTypeDetailRow;
