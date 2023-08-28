import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Form,
  Row,
  Col,
  Input,
  Button,
  Card,
  Typography,
  message,
  Select,
  Menu,
  Dropdown,
} from 'antd';
import { useParams, history, useIntl,useAccess, Access} from 'umi';
import {  IParams ,  IWorkPatternForm } from './data';
import PermissionDeniedPage from './../403';
import { getAllDayTypes } from '@/services/workCalendarDayType';
import { getAllPayTypes } from '@/services/payType';
import _, { transform } from 'lodash';
import PayTypeDetailRow from './components/payTypeDetailRow';
import {setWorkShiftPayConfigs, getWorkShiftPayConfigs} from '@/services/workShiftPayConfiguration';
import request, { APIResponse } from '@/utils/request';



export default (): React.ReactNode => {
    const access = useAccess();
    const { hasPermitted } = access;
    const { TextArea } = Input;
    const { Text } = Typography;
    const { Option } = Select;
    const { id } = useParams<IParams>();
    const [form] = Form.useForm();
    const [load, setload] = useState(false);
    const [dayTypeList, setDayTypeList] = useState([]);
    const [dayTypeEnumList, setDayTypeEnumList] = useState([]);
    const [payTypeEnumList, setPayTypeEnumList] = useState([]);
    const [selectedDayTypes, setSelectedDayTypes] = useState<any>([]);
    const [selectedDayTypeIds, setSelectedDayTypeIds] = useState<any>([]);
    const [refresh, setRefresh] = useState(0);
    // const [payConfigDataSet, setPayConfigDataSet] = useState([]);


    useEffect (() =>{
        
        getPayCofigurationsForWorkShift();
        
    },[id]);


    const menu = (
        <Menu>

            {
                dayTypeEnumList.map((el) => {
                    return (
                        <Menu.Item disabled={el.disabled}  key={el.shortCode} >
                            <a onClick={(event) => performMenuItemAction(el)}>
                                {el.name}
                            </a>
                        </Menu.Item>
                        
                    )
                })
            }
               
        </Menu>
    );

    const fetchDayTypes = async(disabeledOptios: any) => {
        try {
            const res = await getAllDayTypes();
            if (res.data) {
                let valueEnum = res.data.map((value) => {
                    return {
                        'dayTypeId': value.id,
                        'name' : value.name,
                        'disabled' : (disabeledOptios.includes(value.id)) ? true : false,
                        'shortCode' : value.shortCode,
                        'payTypeDetails': []
                    };
                });

                setDayTypeEnumList(valueEnum);
                setDayTypeList(res.data);
            }

        } catch (err) {

            console.log(err);
        }
    }


    const getPayCofigurationsForWorkShift = async() => {
        try {
            const res = await getWorkShiftPayConfigs(id);
            
            if (res.data.data.length > 0) {
                setSelectedDayTypes(res.data.data);
                fetchDayTypes(res.data.selectedOptionArr);
                let filtereIdList = [];

                res.data.data.map((daytype) => {
                    filtereIdList.push(daytype.shortCode);
                })  

                setSelectedDayTypeIds(filtereIdList);

            } else {
                fetchDayTypes([]);
            }
            
        } catch (err) {
            console.log(err);
        }
    }

    
    const fetchPayTypes = async() => {
        try {
            const res = await getAllPayTypes();
            console.log(res.data);
            if (res.data) {
                let valueEnum = res.data.map((value) => {
                    return {
                        'payTypeId': value.id,
                        'name' : value.name,
                        'disabled' : false,
                        'code' : value.code
                    };
                });

                setPayTypeEnumList(valueEnum);
            }

        } catch (err) {
            console.log(err);
        }
    }

    const performMenuItemAction = async(dayTypeData:any) => {
        try {
            const tempDaytypeArr = [];

            const res = await getAllPayTypes();
            let valueEnum = [];
            if (res.data) {
                valueEnum = res.data.map((value) => {
                    return {
                        'payTypeId': value.id,
                        'name' : value.name,
                        'disabled' : false,
                        'code' : value.code
                    };
                });
            }

            if (selectedDayTypes.length > 0) {
                selectedDayTypeIds.push(dayTypeData.shortCode);
                let enumData = dayTypeList.map((el: any, index) => {
                    let shortCode = el.shortCode;
                    let tempObj = {
                        'id': 'new',
                        'dayTypeId': el.id,
                        'name' : el.name,
                        'disabled' : false,
                        'shortCode' : el.shortCode,
                        'payTypeDetails': [],
                        'payTypeEnumList': valueEnum
                    };

                    if (selectedDayTypeIds.includes(shortCode)) {
                        let thresholdKey = tempObj.payTypeDetails.length + 1;
                        let payTypeObj = {
                            'id': 'new',
                            'payTypeId': null,
                            'thresholdType': 'Upto',
                            'hoursPerDay': null,
                            'thresholdKey': thresholdKey,
                            'showAddBtn' : true
                        }
                        tempObj.disabled = true; 
                        tempObj.payTypeDetails.push(payTypeObj); 
                    }

                    return tempObj;
                    
                });
                setDayTypeEnumList(enumData);

                let selectedData = [...selectedDayTypes];
                let newPayTypeObj = {
                    'id': 'new',
                    'payTypeId': null,
                    'thresholdType': 'Upto',
                    'hoursPerDay': null,
                    'thresholdKey': 1,
                    'showAddBtn' : true
                }

                let newObj = {
                    'id': 'new',
                    'dayTypeId': dayTypeData.dayTypeId,
                    'name' : dayTypeData.name,
                    'disabled' : false,
                    'shortCode' : dayTypeData.shortCode,
                    'payTypeDetails': [],
                    'payTypeEnumList': valueEnum
                };


                newObj.payTypeDetails.push(newPayTypeObj);

                selectedData.push(newObj);
                setSelectedDayTypes(selectedData);
            } else {
                selectedDayTypeIds.push(dayTypeData.shortCode);
                let enumData = dayTypeList.map((el: any, index) => {
                    let shortCode = el.shortCode;
                    let tempObj = {
                        'id': 'new',
                        'dayTypeId': el.id,
                        'name' : el.name,
                        'disabled' : false,
                        'shortCode' : el.shortCode,
                        'payTypeDetails': [],
                        'payTypeEnumList': valueEnum
                    };

                    if (selectedDayTypeIds.includes(shortCode)) {
                        let thresholdKey = tempObj.payTypeDetails.length + 1;
                        let payTypeObj = {
                            'id': 'new',
                            'payTypeId': null,
                            'thresholdType': 'Upto',
                            'hoursPerDay': null,
                            'thresholdKey': thresholdKey,
                            'showAddBtn' : true
                        }
                        tempObj.disabled = true; 
                        tempObj.payTypeDetails.push(payTypeObj); 
                        tempDaytypeArr.push(tempObj);
                    }

                    return tempObj;
                    
                });
                setDayTypeEnumList(enumData);
                setSelectedDayTypes(tempDaytypeArr);
            }
        } catch (err) {
            console.log(err);
        }
    }


    const getDisableState = (code:any) => {
        try {

            console.log(selectedDayTypes);
            return false;

        } catch (err) {
            console.log(err);
        }
    }


  
    return (
        <Access
          accessible={hasPermitted('work-shifts-read-write')}
          fallback={<PermissionDeniedPage />}
        > 
            <PageContainer>
                <>   
                    <Card>
                        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }} style={{marginBottom: 50}}>
                            <Col span={24}>
                            <Dropdown overlay={menu} placement="bottomLeft" arrow>
                                    <Button type="primary"   
                                    >+ Add Day Type </Button>
                                </Dropdown> 
                            </Col>
                        </Row>

                        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }} style={{marginLeft: 2}}>
                            
                            {
                                selectedDayTypes.map((el:object, dayTypeIndex:any) => {
                                    return (
                                        <Col span={18} style={{marginBottom: 10}}>
                                            <div style={{ backgroundColor: '#f0f2f5', borderRadius: 10}}>
                                                <div onClick={()=> {
                                                
                                                    let selectedListCopy = [...selectedDayTypes];
                                                    let dayTypeEnulListCopy = [...dayTypeEnumList];
                                                    let filteredList = [];
                                                    let filtereIdList = [];

                                                    selectedListCopy.map((daytype) => {
                                                        if (daytype.dayTypeId != el.dayTypeId) {
                                                            filteredList.push(daytype);
                                                            filtereIdList.push(daytype.shortCode);
                                                        }
                                                    })  


                                                let filteredEnumList =  dayTypeEnulListCopy.map((enumItem) => {
                                                        if (enumItem.dayTypeId == el.dayTypeId) {
                                                            enumItem.disabled = false;
                                                        }
                                                        return enumItem;
                                                    })  

                                                    setSelectedDayTypes(filteredList);
                                                    setSelectedDayTypeIds(filtereIdList);
                                                    setDayTypeEnumList(filteredEnumList);

                                                    setRefresh(prev => prev + 1);
                                                }} style={{float:'right', paddingRight: 15, paddingTop: 10, cursor: 'pointer'}}>X</div>
                                                <Row style={{marginLeft: 20, marginBottom: 20}}>
                                                    <span style={{marginTop: 10, marginBottom: 10, fontWeight: 'bold'}}>{el.name}</span>
                                                </Row>
                                                <PayTypeDetailRow refresh={refresh} payTypeEnumList={payTypeEnumList}  dayTypeArrIndex={dayTypeIndex} selectedDayTypes = {selectedDayTypes} setValues={setSelectedDayTypes} />
                                            </div>
                                        </Col>
                                    )
                                })
                            }
                            {
                                (selectedDayTypes.length > 0) ? (
                                    <Col span={18}>
                                        <div style={{float:'right'}}>
                                            <Button style={{marginRight: 5}}  size="middle" onClick={() => {
                                                history.push(`/settings/work-shifts`);
                                            }} >
                                                Cancel
                                            </Button>

                                            <Button type="primary"  size="middle" onClick={async() => {

                                                let payTypeNullCount = 0;
                                                let hourPerDayNullCount = 0;
                                                let filterData = [];
                                                let selectedOldDayTypeIds = [];

                                                selectedDayTypes.map((data)=> {
                                                    if (data.id != 'new') {
                                                        selectedOldDayTypeIds.push(data.id);
                                                    }

                                                    let selectedOldPayTypeIds = [];
                                                    data.payTypeDetails.map((el)=>{
                                                        
                                                        if (el.payTypeId == null) {
                                                            payTypeNullCount ++;
                                                        }
                                                        if (el.hoursPerDay == null) {
                                                            hourPerDayNullCount ++;
                                                        }

                                                        if (el.id != 'new') {
                                                            selectedOldPayTypeIds.push(el.id);
                                                        } 
                                                    })

                                                    let tempObj = {
                                                        'id' : data.id,
                                                        'dayTypeId' : data.dayTypeId,
                                                        'payTypeThresholdDetails': data.payTypeDetails,
                                                        'selectedOldThresholdIds': selectedOldPayTypeIds
                                                    }
                                                    filterData.push(tempObj);

                                                })

                                                if (payTypeNullCount > 0) {
                                                    message.error('Pay type field is required field , So it must be define in each threshold that you create');
                                                    return;
                                                }
                                                if (hourPerDayNullCount > 0) {
                                                    message.error('Hours per day field is required field , So it must be define in each threshold that you create');
                                                    return;
                                                }

                                            
                                                let dataSet = {
                                                    'id': id,
                                                    'payConfigData': JSON.stringify(filterData),
                                                    'selectedOldDayTypeIds' : selectedOldDayTypeIds
                                                }

                                                await setWorkShiftPayConfigs(dataSet)
                                                .then((response: APIResponse) => {
                                                    if (response.error) {
                                                        message.error('unable to set config data');
                                                        return;
                                                    }
                                                    message.success('Pay config data sucessfully saved', 1).then(() => {
                                                        history.push('/settings/work-shifts');
                                                    });

                                                }).catch((error: APIResponse) => {
                                                    console.log('Error..............');
                                                    let errorMessage;
                                                    errorMessage = error.message;
                                                    message.error(errorMessage);
                                                    
                                                });
                                            }}>
                                                Save
                                            </Button>
                                        </div>
                                    
                                    </Col>
                                ) : (
                                    <></>
                                )
                            }
                            
                        </Row>
                    </Card>
                </>
           </PageContainer>
        </Access>  
    );
};

