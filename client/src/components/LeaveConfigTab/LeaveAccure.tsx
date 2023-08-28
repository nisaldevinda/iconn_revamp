import ProForm, { ProFormCheckbox, ProFormDigit, ProFormSelect, ProFormSwitch, ProFormText, ProFormTextArea } from '@ant-design/pro-form';
import { Button, Card, Col, Divider, Form, message, Row, Space, Switch, Input, Select, InputNumber, Tooltip } from 'antd';
import { MinusCircleOutlined, PlusOutlined } from '@ant-design/icons';
import React, { useEffect, useState, useRef } from 'react';
import { Typography } from 'antd';
import ProTable, { ActionType } from '@ant-design/pro-table';
import './styles.css'
import { history } from "umi"
import _ from 'lodash';
import { getOverflowOptions } from 'antd/lib/tooltip/placements';
import { getModel, Models } from '@/services/model';
import { ReactComponent as PlusIcon } from '../../assets/Plus.svg'
import { ReactComponent as LeaveTypeEdit } from '../../assets/attendance/leaveTypeEdit.svg';
import { generateProFormFieldValidation } from '@/utils/validator';
import { useIntl } from 'umi';
import request, { APIResponse } from '@/utils/request';
import moment from 'moment';
import { getAllEmployeeGroupsByLeaveTypeId, saveLeaveTypeAcrueConfigs, getLeaveTypeAccrualConfigsByLeaveTypeId, getLeaveTypes, getLeaveTypeWiseAccruals, createLeaveTypeAcrueConfig, updateLeaveTypeAcrueConfig } from '@/services/leave';
import { getProRateFormulaList } from '@/services/proRateFormula';

const LeaveAccure: React.FC = (props) => {

    const { Text } = Typography;
    const actionRef = useRef<ActionType>();
    const [form] = Form.useForm();
    const [cardVisible, setcardVisible] = useState(true)
    const [leaveAccrueId, setLeaveAccrueId] = useState('new');
    const [addButtonVisible, setAddButtonVisible]=useState(true)
    const [accuralFreaquencyOptions,setAccuralFrequencyOptions]=useState([])
    const [creditingOptions,setCreditingOptions] =useState([])
    const [proRateFormulaOptions,setProRateFormulaOptions] =useState([])
    const [validFromOptions,setValidFromOptions] =useState([])
    const [firstAccrualForMonthlyFrequencyOptions,setfirstAccrualForMonthlyFrequencyOptions] =useState([])
    const [firstAccrualForAnnualFrequencyOptions,setfirstAccrualForAnnualFrequencyOptions] =useState([])

    const[accrueEveryOptions,setAccrueEveryOptions]=useState([])
    const [accureFrequencyVal,serAccureFrequeencyVal]=useState("")
    const [annualFirstAccureFrequency,setAnnualFirstAccureFrequency]=useState("")
    const [monthLabel,setMonthLabel]=useState("Month")
    const [selectedAccrualFrequency,setSelectedAccrualFrequency]=useState("MONTHLY");
    const [dayOfCreditingForMonthlyFrequency,setDayOfCreditingForMonthlyFrequency]=useState(null);
    const [leaveAccureModel,setLeaveAccureModel]=useState()
    const [dayOfcredMonthlyOptions,setDayOfcredMonthlyOptions]=useState([])
    const [dateOptions,setDateOPtions]=useState([])
    const [employeeGroupOptions,setEmployeeGroupOptions]=useState([])
    const [employeeGroupList,setEmployeeGroupList]=useState([])

    const intl = useIntl();

    const dayOfcredAnnualOptions = [
        {
            value: 1,
            label: "January"
        },
        {
            value: 2,
            label: "February"
        },
        {
            value: 3,
            label: "March"
        },
        {
            value: 4,
            label: "April"
        },
        {
            value: 5,
            label: "May"
        },
        {
            value: 6,
            label: "June"
        },
        {
            value: 7,
            label: "July"
        },
        {
            value: 8,
            label: "August"
        },
        {
            value: 9,
            label: "September"
        },
        {
            value: 10,
            label: "October"
        },
        {
            value: 11,
            label: "November"
        },
        {
            value: 12,
            label: "December"
        },
    ]
    useEffect(() => {
        getOptions();
        
    },[])


    useEffect(() => {
        setEmployeeGroupOptions(props.employeeGroupOptions);

        console.log(form.getFieldValue('relatedEmployeeGroups'));
        if (form.getFieldValue('relatedEmployeeGroups')) {
            resetEmployeeGroupDropdown(props.employeeGroupOptions);  
        }
    },[props.employeeGroupOptions])

    const columns = [
        {
            title: 'Leave Employee Group',
            dataIndex: 'name',
        },
        {
            title: 'Accrual Frequency',
            dataIndex: 'accrualFrequency',
            render: (text, record, index) => {
                return <>
                    {
                        record.accrualFrequency == 'MONTHLY' ? 'Monthly' : 'Annual'                       
                    }
                </>
            }
        },
        {
            title: 'Actions',
            dataIndex: 'actions',
            render: (text, record, index) => {
                return <>
                    <Space>
                        <Tooltip
                            placement={'bottom'}
                            key="editrecord"
                            title={intl.formatMessage({
                                id: 'edit',
                                defaultMessage: 'Edit',
                            })}
                        >
                            <a onClick={() => {


                                if (record) {
                                    form.setFieldsValue(record);
                                    setSelectedAccrualFrequency(record.accrualFrequency);
                                    if (record['dayOfCreditingForAnnualFrequency']) {
                                        generateDateOptions(record['dayOfCreditingForAnnualFrequency']);
                                    }

                                    if (record['firstAccrualForAnnualfrequency']) {
                                        setAnnualFirstAccureFrequency(record['firstAccrualForAnnualfrequency']);
                                    }

                                    if (record['dayOfCreditingForMonthlyFrequency']) {
                                        setDayOfCreditingForMonthlyFrequency(record['dayOfCreditingForMonthlyFrequency']);
                                    } else {
                                        setDayOfCreditingForMonthlyFrequency(null);
                                    }

                                    // serAccureFrequeencyVal(accrualData.data['accrualFrequency']);
                                    serAccureFrequeencyVal(record.accrualFrequency);
                                } else {
                                    serAccureFrequeencyVal(record.accrualFrequency);
                                    form.setFieldsValue({
                                        relatedEmployeeGroups: [
                                            {
                                                'employeeGroup' : null,
                                                'amount': null   
                                            }
                                        ]
                                    })
                                }

                                setLeaveAccrueId(record.id);
                                setAddButtonVisible(false);
                                // setCurrentRecordId(record.id)
                                // setFormInitialValues({
                                //     id:record.Id,
                                //     name:record.name,
                                //     applicableCountryId:record.applicableCountryId,
                                //     comment:record.comment

                                // })
                                // setDrawerVisible(true)
                                
                                }}><LeaveTypeEdit height={16} /></a>
                        </Tooltip>
                    </Space></>
            }
        },

    ]
    const getOptions= async ()=>{
        try{
            const {data}=await getModel(Models.LeaveAccrual)
            
            if(data){
                setLeaveAccureModel(data)
                setAccuralFrequencyOptions(convertOptions(data.modelDataDefinition.fields.accrualFrequency.values))
                setCreditingOptions(convertOptions(data.modelDataDefinition.fields.accrualFrequency.values))
                setValidFromOptions(convertOptions(data.modelDataDefinition.fields.accrualValidFrom.values))
                setfirstAccrualForMonthlyFrequencyOptions(convertOptions(data.modelDataDefinition.fields.firstAccrualForMonthlyFrequency.values))
                setfirstAccrualForAnnualFrequencyOptions(convertOptionsForFirstAccrualForAnnualFrequency(data.modelDataDefinition.fields.firstAccrualForAnnualfrequency.values));

                setAccrueEveryOptions(convertOptions(data.modelDataDefinition.fields.accrueEvery.values))
               // setDayOfcredAnnualOptions(convertOptions(data.modelDataDefinition.fields.dayOfCreditingForAnnualFrequency.values))
                setDayOfcredMonthlyOptions(convertOptions(data.modelDataDefinition.fields.dayOfCreditingForMonthlyFrequency.values))

                const proRateFormula = await getProRateFormulaList()
                if (proRateFormula.data) {
                    await setProRateFormulaOptions(convertToOptions(proRateFormula.data, 'id', 'name'))
                }
            }

            let params = {
                leaveTypeId: props.data.id
            }
            const employeeGroups=await getAllEmployeeGroupsByLeaveTypeId(params);

            const employeeGroupActions = employeeGroups.data.map((empGrp: any) => {
                return {
                  label: empGrp.name,
                  value: empGrp.id,
                };
              });
              setEmployeeGroupList(employeeGroupActions);
            

            form.setFieldsValue({
                'accrualFrequency' : selectedAccrualFrequency
            });
            let param = {
                leaveTypeId : props.data.id,
                accrualFrequency: selectedAccrualFrequency
            }

            // const accrualData = await getLeaveTypeAccrualConfigsByLeaveTypeId(param);

            // if (accrualData.data) {
            //     form.setFieldsValue(accrualData.data);
            //     if (accrualData.data['dayOfCreditingForAnnualFrequency']) {
            //         generateDateOptions(accrualData.data['dayOfCreditingForAnnualFrequency']);
            //     }

            //     if (accrualData.data['firstAccrualForAnnualfrequency']) {
            //         setAnnualFirstAccureFrequency(accrualData.data['firstAccrualForAnnualfrequency']);
            //     }

            //     // serAccureFrequeencyVal(accrualData.data['accrualFrequency']);
            //     serAccureFrequeencyVal(selectedAccrualFrequency);
            // } else {
            //     serAccureFrequeencyVal(selectedAccrualFrequency);
            //     form.setFieldsValue({
            //         relatedEmployeeGroups: [
            //             {
            //                 'employeeGroup' : null,
            //                 'amount': null   
            //             }
            //         ]
            //     })
            // }

            resetEmployeeGroupDropdown(props.employeeGroupOptions);

        }
        catch(err){
            console.error(err)
        }
       
    }
    const convertOptions=(values)=>{
        const arr=[]
        values.forEach(element => {
            arr.push({
                value:element.value,
                label:element.defaultLabel
            })
        });
        return arr

    }

    const convertToOptions = (data, valueField: string, labelField: string) => {
        const arr: { value: string | number; label: string | number; disabled?: boolean; }[] = []

        data.forEach((element: { [x: string]: any; }) => {
            arr.push({ value: element[valueField], label: element[labelField] })
        });
        return arr
    }

    const convertOptionsForFirstAccrualForAnnualFrequency=(values)=>{
        const arr=[]
        values.forEach(element => {
            if (props.data.leavePeriod != 'STANDARD') {
                if (element.value != 'PRO_RATE') {
                    arr.push({
                        value:element.value,
                        label:element.defaultLabel
                    })
                }

            } else {

                arr.push({
                    value:element.value,
                    label:element.defaultLabel
                })
            }

        });
        return arr

    }


    // const getRelatedAccrualData= async (val:any)=>{

    //     let param = {
    //         leaveTypeId : props.data.id,
    //         accrualFrequency: val
    //     }
    //     const accrualData = await getLeaveTypeAccrualConfigsByLeaveTypeId(param);

    //     if (accrualData.data) {
    //         form.setFieldsValue(accrualData.data);
    //         if (accrualData.data['dayOfCreditingForAnnualFrequency']) {
    //             generateDateOptions(accrualData.data['dayOfCreditingForAnnualFrequency']);
    //         }

    //         if (accrualData.data['firstAccrualForAnnualfrequency']) {
    //             setAnnualFirstAccureFrequency(accrualData.data['firstAccrualForAnnualfrequency']);
    //         }

    //         // serAccureFrequeencyVal(accrualData.data['accrualFrequency']);
    //         serAccureFrequeencyVal(val);
    //     } else {
    //         form.setFieldsValue({
    //             relatedEmployeeGroups: [
    //                 {
    //                     'employeeGroup' : null,
    //                     'amount': null   
    //                 }
    //             ]
    //         })
    //     }

    //     resetEmployeeGroupDropdown(props.employeeGroupOptions);

    // }

    const generateDateOptions=(month)=>{
        const dates=moment(month, "MM").daysInMonth()
        const arr=[]
        for (let i = 1; i <= dates; i++) {
        arr.push({value:i,label:i})
        }
        setDateOPtions(arr)
    }


    const resetEmployeeGroupDropdown=(groupOptions:any)=>{
        const arr = groupOptions.map(el=>{
            let groupList = form.getFieldValue('relatedEmployeeGroups');
            let selectedCount = 0;

            groupList.map(subEl=>{
                
                if (subEl != undefined && subEl.employeeGroup == el.value) {
                    selectedCount++;
                }
                
            });

            if (selectedCount > 0) {
                el.disabled = true;
                return el;
            } else {
                el.disabled = false;
                return el;
            }
            
        });
        setEmployeeGroupOptions(arr)
    }

    const formOnFinish= async (formDataV)=>{

        const key = 'saving';
        message.loading({
            content: intl.formatMessage({
            id: 'saving',
            defaultMessage: 'Saving...',
            }),
            key,
        });

        let formData=formDataV;
        const dayOf=formData.dayOfCreditingForAnnualFrequency
        const dayValueO =formData.dayValue

        if (dayOf && dayValueO) {
            formData['dayOfCreditingForAnnualFrequency']=moment(`${dayOf}-${dayValueO}`,"MM-DD").format("MM-DD");
        }

        formData['leaveTypeId'] = props.data.id;
        formData['id'] = leaveAccrueId;
        delete formData.dayValue;

        if (leaveAccrueId == 'new') {
            await createLeaveTypeAcrueConfig(formData)
            .then((response: APIResponse) => {
                if (response.error) {
                    message.error({
                    content:
                        response.message ??
                        intl.formatMessage({
                        id: 'failedToSave',
                        defaultMessage: 'Cannot Save',
                        }),
                    key,
                    });
                    if (response.data && Object.keys(response.data).length !== 0) {
                    for (const feildName in response.data) {
                        const errors = response.data[feildName];
                        form.setFields([
                        {
                            name: feildName,
                            errors: errors,
                        },
                        ]);
                    }
                    }
                    return;
                }

                message.success({
                    content:
                    intl.formatMessage({
                        id: 'successfullySaved',
                        defaultMessage: 'Successfully Saved',
                    }),
                    key,
                });

            }).catch((error: APIResponse) => {
                let errorMessage;
                let errorMessageInfo;
                if (error.message.includes('.')) {
                    let errorMessageData = error.message.split('.');
                    errorMessage = errorMessageData.slice(0, 1);
                    errorMessageInfo = errorMessageData.slice(1).join('.');
                }
                message.error({
                    content: error.message ? (
                    <>
                        {errorMessage ?? error.message}
                        <br />
                        <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                        {errorMessageInfo ?? ''}
                        </span>
                    </>
                    ) : (
                    intl.formatMessage({
                        id: 'failedToSave',
                        defaultMessage: 'Cannot Save',
                    })
                    ),
                    key,
                });
                if (error && Object.keys(error.data).length !== 0) {
                    for (const feildName in error.data) {
                    const errors = error.data[feildName];
                    form.setFields([
                        {
                        name: feildName,
                        errors: errors,
                        },
                    ]);
                    }
                }
            });
        } else {
            await updateLeaveTypeAcrueConfig(formData)
            .then((response: APIResponse) => {
                if (response.error) {
                    message.error({
                    content:
                        response.message ??
                        intl.formatMessage({
                        id: 'failedToSave',
                        defaultMessage: 'Cannot Save',
                        }),
                    key,
                    });
                    if (response.data && Object.keys(response.data).length !== 0) {
                    for (const feildName in response.data) {
                        const errors = response.data[feildName];
                        form.setFields([
                        {
                            name: feildName,
                            errors: errors,
                        },
                        ]);
                    }
                    }
                    return;
                }

                message.success({
                    content:
                    intl.formatMessage({
                        id: 'successfullySaved',
                        defaultMessage: 'Successfully Saved',
                    }),
                    key,
                });

            }).catch((error: APIResponse) => {
                let errorMessage;
                let errorMessageInfo;
                if (error.message.includes('.')) {
                    let errorMessageData = error.message.split('.');
                    errorMessage = errorMessageData.slice(0, 1);
                    errorMessageInfo = errorMessageData.slice(1).join('.');
                }
                message.error({
                    content: error.message ? (
                    <>
                        {errorMessage ?? error.message}
                        <br />
                        <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                        {errorMessageInfo ?? ''}
                        </span>
                    </>
                    ) : (
                    intl.formatMessage({
                        id: 'failedToSave',
                        defaultMessage: 'Cannot Save',
                    })
                    ),
                    key,
                });
                if (error && Object.keys(error.data).length !== 0) {
                    for (const feildName in error.data) {
                    const errors = error.data[feildName];
                    form.setFields([
                        {
                        name: feildName,
                        errors: errors,
                        },
                    ]);
                    }
                }
            });
        }    
    }
    return (
        <>

            <Row>
                <Col flex="auto">
                    <Row>
                        <Text style={{ fontSize: 22, color: "#394241" }}>
                            {props.type}
                        </Text>

                    </Row>
                    <Row>
                        <Text style={{ fontSize: 16, color: "#909A99" }}>
                            {props.sub}
                        </Text>
                    </Row>
                </Col>

                {/* <Col flex="100px">
                    <Switch
                        checked={cardVisible}
                        onChange={(checked) => { setcardVisible(checked) }}
                    />
                </Col> */}
                <Col flex="200px">
                {
                    addButtonVisible?
                    <Button onClick={()=>{
                        let newObj = {}; 
                        form.setFieldsValue({
                            accureFrequency : 'MONTHLY'
                        });
                        // setFormValues({});
                        // setGroupID('new');
                        // setShowEditForm(false);
                        serAccureFrequeencyVal('MONTHLY');
                        setAddButtonVisible(false);

                        // props.setAddButtonVisible(false)
                    }} type="primary" style={{ height: 40, fontSize: 18 }}>
                        <PlusIcon /> &nbsp; Add Accrual
                    </Button>
                    :<></>
                }

                </Col>
            </Row>
            <Divider style={{ margin: "18px 0px 32px" }} />
            <Row>
                <Col span={22}>
                    {!addButtonVisible ?
                        <div >
                            <ProForm
                                id={'generalForm'}
                                form={form}
                                 onFinish={formOnFinish}
                                submitter={{

                                    render: (formProps, doms) => {
                                        return [
                                            <Row justify='end' gutter={[16, 16]}>
                                                <Col span={7}>
                                                    <Space>
                                                        <Button key="cancel" size="middle" onClick={() => {
                                                            setAddButtonVisible(true);
                                                            form.resetFields();
                                                            setLeaveAccrueId('new');
                                                        }} >
                                                            Cancel
                                                        </Button>

                                                        <Button type="primary" key="submit" size="middle" onClick={() => formProps.form?.submit()}>
                                                            Save
                                                        </Button>
                                                    </Space>
                                                </Col>
                                            </Row>

                                        ];
                                    },
                                }}
                            >

                                <Col xs={24} sm={24}  md={24}  lg={20} xl={16} xxl={12}>
                                    <Row>
                                    <Col flex={0.2}>
                                        <ProFormSelect
                                            name={'leaveEmployeeGroupId'}
                                            label="Employee Group"
                                            width={360}
                                            options={employeeGroupList}
                                            fieldProps={{
                                                onChange:(val)=>{
                                                    // serAccureFrequeencyVal(val);
                                                    // setSelectedAccrualFrequency(val);

                                                    // getRelatedAccrualData(val);
                                                }
                                                
                                            }}
                                            placeholder="Select Accrual Frequency"
                                            rules={
                                                [{
                                                    required: true,
                                                    message: intl.formatMessage({
                                                    id: `leaveAccure.rules.required`,
                                                    defaultMessage: `Required`,
                                                    }),
                                                }]
                                            }
                                        />
                                    </Col>

                                    </Row>
                                    <Row>
                                        <Col flex={0.2}>
                                            <ProFormSelect
                                                name={'accrualFrequency'}
                                                label="Accrual Frequency "
                                                width={360}
                                                options={accuralFreaquencyOptions}
                                                fieldProps={{
                                                    onChange:(val)=>{
                                                        serAccureFrequeencyVal(val);
                                                        setSelectedAccrualFrequency(val);

                                                        // getRelatedAccrualData(val);



                                                    }
                                                    
                                                }}
                                                placeholder="Select Accrual Frequency"
                                                rules={
                                                    [{
                                                        required: true,
                                                        message: intl.formatMessage({
                                                        id: `leaveAccure.rules.required`,
                                                        defaultMessage: `Required`,
                                                        }),
                                                    }]
                                                }
                                            />
                                        </Col>
                                        {accureFrequencyVal ==="MONTHLY"?
                                        <Col >
                                            <Row className="digit-input-all">

                                                <Col>
                                                    <div style={{
                                                        position: "relative",
                                                        color: '#626D6C',
                                                        width: 100,
                                                        height: 32,
                                                        top:30,
                                                        background: "#F1F3F6",
                                                        border: "1px solid #E1E3E5",
                                                        borderRadius: "6px 0px 0px 6px"
                                                    }}
                                                        className="input-add-on-after"
                                                    >Accrue Every</div>
                                                </Col>
                                                <Col >
                                                    <ProFormSelect
                                                        label='&nbsp;'
                                                        name={'accrueEvery'}
                                                        width={50}
                                                        placeholder=""
                                                        options={accrueEveryOptions}
                                                        fieldProps={{
                                                            onChange:(val)=>{
                                                                if (val != undefined && val > 1 ) {
                                                                    setMonthLabel('Months');
                                                                } else {
                                                                    setMonthLabel('Month');

                                                                }
                                                            }
                                                        }}
                                                    />
                                                </Col>
                                                <Col>
                                                    <div style={{
                                                        color: '#626D6C',
                                                        position: "relative",
                                                        top:30,
                                                        width: 60,
                                                        height: 32,
                                                        background: "#F1F3F6",
                                                        border: "1px solid #E1E3E5",
                                                        borderRadius: "0px 6px 6px 0px"
                                                    }}
                                                        className="input-add-on-after"
                                                    >{monthLabel}</div>
                                                </Col>

                                            </Row>
                                        </Col>
                                        :<></>}
                                    </Row>
                                    {
                                        accureFrequencyVal == "MONTHLY" ?
                                        <Row style={{marginBottom: 20}}>
                                            <Col flex={0.2}>
                                                <ProFormCheckbox noStyle name="isAllocatedOnlyForJoinedYear">
                                                    {'Allocate only for the joined year'}
                                                </ProFormCheckbox>
                                            </Col>
                                        </Row> : <></>
                                    }

                                    {
                                        accureFrequencyVal == "MONTHLY" ?
                                        <Row style={{marginBottom: 20}}>
                                            <Col flex={0.2}>
                                                <ProFormCheckbox noStyle name="isAllowToAllocateAfterMidYearConfirm">
                                                    {'Allow To Allocate After Mid Year Confirm'}
                                                </ProFormCheckbox>
                                            </Col>
                                        </Row> : <></>
                                    }

                                   { accureFrequencyVal!=="MONTHLY" ? 

                                        props.data.leavePeriod== "STANDARD" ? 
                                        <Row >
                                            <Col flex={0.2}>
                                                <ProFormSelect
                                                    name='dayOfCreditingForAnnualFrequency'
                                                    label="Day of Crediting to Employee"
                                                    width={360}
                                                    options={dayOfcredAnnualOptions}
                                                    placeholder="Select Day of Crediting to Employee"

                                                rules={
                                                    [ {
                                                        required: true,
                                                        message: intl.formatMessage({
                                                        id: `leaveAccure.rules.required`,
                                                        defaultMessage: `Required`,
                                                        }),
                                                    }]
                                                }
                                                fieldProps={{
                                                    onChange:(val)=>{
                                                        generateDateOptions(val)
                                                    }
                                                }}

                                                />
                                            </Col>
                                            <Col>
                                                <ProFormSelect
                                                    name={'dayValue'}
                                                    label="&nbsp;"
                                                    width={210}
                                                    options={dateOptions}
                                                    
                                                />
                                            </Col>
                                        </Row> :
                                        <></>
                                    : 
                                    
                                    <Row >
                                        <Col flex={0.2}>
                                            <ProFormSelect
                                                name='dayOfCreditingForMonthlyFrequency'
                                                label="Day of Crediting to Employee"
                                                width={360}
                                                options={dayOfcredMonthlyOptions}
                                                placeholder="Select Day of Crediting to Employee"

                                                rules={
                                                    [ {
                                                        required: true,
                                                        message: intl.formatMessage({
                                                        id: `leaveAccure.rules.required`,
                                                        defaultMessage: `Required`,
                                                        }),
                                                    }]
                                                }

                                                fieldProps={{
                                                    onChange:(val)=>{
                                                        setDayOfCreditingForMonthlyFrequency(val);
                                                        if (val != 'FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES') {
                                                            form.setFieldsValue({
                                                                'firstAccrueAfterNoOfDates' : null
                                                            });
                                                        }
                                                    }
                                                }}

                                            />
                                        </Col>
                                        {
                                            dayOfCreditingForMonthlyFrequency == 'FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES' ? 
                                            <Col>
                                                <Form.Item  name={'firstAccrueAfterNoOfDates'} label="No Of Dates"  
                                                    rules={[dayOfCreditingForMonthlyFrequency == 'FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES' ? { required: true, message: 'Required' } : {}]}
                                                >    
                                                        <InputNumber 
                                                        min={1}
                                                        style={{width: 220}}
                                                        precision={0}
                                                        // step={0.1}
                                                        max={365}
                                                        />
                                                </Form.Item>
                                            </Col> : <></>
                                        }
                                        
                                    </Row>
                                    }
                                    <Row>
                                        <ProFormSelect
                                            name={'accrualValidFrom'}
                                            label="Accrual Valid From"
                                            width={360}
                                            options={validFromOptions}
                                            placeholder="Select Accrual Valid From"

                                            rules={
                                                [ {
                                                     required: true,
                                                     message: intl.formatMessage({
                                                       id: `leaveAccure.rules.required`,
                                                       defaultMessage: `Required`,
                                                     }),
                                                   }]
                                             }

                                        />

                                    </Row>
                                   { accureFrequencyVal =="MONTHLY"?
                                   
                                        <Row>
                                            <ProFormSelect
                                                name={'firstAccrualForMonthlyFrequency'}
                                                label="First Accrual of New Employee"
                                                width={360}
                                                options={firstAccrualForMonthlyFrequencyOptions}
                                                placeholder="Select First Accrual of New Employee"

                                                rules={
                                                    [ {
                                                            required: true,
                                                            message: intl.formatMessage({
                                                            id: `leaveAccure.rules.required`,
                                                            defaultMessage: `Required`,
                                                            }),
                                                        }]
                                                    }

                                            />

                                        </Row>:
                                        <Row>
                                            <Col flex={0.2}>
                                                <ProFormSelect
                                                    name={'firstAccrualForAnnualfrequency'}
                                                    label="First Accrual of New Employee"
                                                    width={360}
                                                    options={firstAccrualForAnnualFrequencyOptions}
                                                    placeholder="Select First Accrual of New Employee"
                                                    fieldProps={{
                                                        onChange:(val)=>{
                                                            setAnnualFirstAccureFrequency(val);
                                                            form.setFieldsValue({proRateMethodFirstAccrualForAnnualFrequency: null})
                                                        }
                                                    }}
                                                    rules={
                                                        [ {
                                                                required: true,
                                                                message: intl.formatMessage({
                                                                id: `leaveAccure.rules.required`,
                                                                defaultMessage: `Required`,
                                                                }),
                                                            }]
                                                        }

                                                />
                                            </Col>
                                            <Col>
                                                {
                                                    (annualFirstAccureFrequency == 'PRO_RATE') ? (
                                                        <ProFormSelect
                                                            name={'proRateMethodFirstAccrualForAnnualFrequency'}
                                                            label="Pro Rate Formula"
                                                            width={210}
                                                            options={proRateFormulaOptions}
                                                            placeholder="Select Pro rate formula"
        
                                                            rules={
                                                                [ {
                                                                        required: true,
                                                                        message: intl.formatMessage({
                                                                        id: `leaveAccure.rules.required`,
                                                                        defaultMessage: `Required`,
                                                                        }),
                                                                    }]
                                                                }
        
                                                        />
                                                    ) : (
                                                        <></>
                                                    )
                                                }
                                               
                                            </Col>

                                        </Row> 
                                    }
                                    <Row>
                                        {
                                            annualFirstAccureFrequency != 'PRO_RATE' ?
                                            <Col flex={0.2}>
                                                
                                                <Form.Item  name={'amount'} label="Amount"  
                                                rules={[annualFirstAccureFrequency != 'PRO_RATE' ? { required: true, message: 'Required' } : {}]}
                                                >    
                                                    <InputNumber 
                                                    min={0}
                                                    style={{width: 360}}
                                                    disabled={annualFirstAccureFrequency == 'PRO_RATE' ? true : false}
                                                    precision={2}
                                                    step={0.1}
                                                    max={365}
                                                    />
                                                </Form.Item>

                                            </Col> : <></>
                                        }
                                    </Row>

                                </Col>
                                <></>
                                {/* <Row style={{width: '100%'}}>
                                    <Col span={6}>
                                        <Text style={{
                                            fontSize: 16,
                                            color: "#3394241",
                                            fontWeight: 'bold'
                                        }}>
                                            {
                                                intl.formatMessage({
                                                    id: `accrueEmpGroupTitle`,
                                                    defaultMessage: `Define Accrue Amount for Employee Groups`,
                                                    })
                                            }
                                        </Text>
                                    </Col>
                                    <Col span={4}>
                                        <Divider style={{ margin: "18px 0px 32px" }} />
                                    </Col>
                                </Row> */}
                                   

                                {/* <Form.List name="relatedEmployeeGroups">
                                    {(fields, { add, remove }) => (
                                    <>
                                        {fields.map(({ key, name, ...restField }) => (
                                        <Space key={key} style={{ display: 'flex', marginBottom: 8 }} align="baseline">

                                            <Row>
                                                <Col style={{marginRight: 25}}>
                                                    <Form.Item  name={[name, 'employeeGroup']} label="Employee Group"  
                                                    rules={[{ required: true, message: 'Required' }]}
                                                    >
                                                        <Select
                                                            placeholder="Select Employee Group"
                                                            onChange={(val) => {
                                                                resetEmployeeGroupDropdown(employeeGroupOptions);
                                                            }}
                                                            style = {{width: 360}}
                                                            allowClear
                                                        >
                                                            
                                                            {employeeGroupOptions.map(item => (
                                                                <Select.Option key={item.value} value={item.value} disabled={item.disabled}>
                                                                    {item.label}
                                                                </Select.Option>
                                                            ))}
                                                        </Select>
                                                    </Form.Item>

                                                </Col>
                                                {
                                                    annualFirstAccureFrequency != 'PRO_RATE' ?
                                                    <Col>
                                                       
                                                        <Form.Item  name={[name, 'amount']} label="Amount"  
                                                        rules={[annualFirstAccureFrequency != 'PRO_RATE' ? { required: true, message: 'Required' } : {}]}
                                                        >    
                                                            <InputNumber 
                                                            min={0}
                                                            style={{width: 210}}
                                                            disabled={annualFirstAccureFrequency == 'PRO_RATE' ? true : false}
                                                            precision={2}
                                                            step={0.1}
                                                            max={365}
                                                            />
                                                        </Form.Item>

                                                    </Col> : <></>
                                                }
                                                <Col style={{marginTop: 35, marginLeft: 10}}>
                                                    {
                                                        key > 0 ? (
                                                            <MinusCircleOutlined onClick={() => {
                                                                remove(name)
                                                                resetEmployeeGroupDropdown(employeeGroupOptions);
                                                            }} />
                                                        ) : (
                                                            <></>
                                                        )
                                                    }
                                                    
                                                </Col>
                                            </Row>
                                        </Space>
                                        ))}
                                        <Row>
                                            <Col style={{width: 500}}>
                                                <Button  type="dashed" style={{ backgroundColor: '#E4eff1', borderColor: '#E4eff1', borderRadius: 6 }} onClick={() => {
                                                    add()
                                                } } block icon={<PlusOutlined />}>
                                                    Add Employee Group
                                                </Button>
                                            </Col>
                                        </Row>
                                    </>
                                    )}
                                </Form.List> */}
                            </ProForm>
                        </div>
                    : 
                        <ProTable
                            actionRef={actionRef}
                            search={false}
                            columns={columns}
                            request={async (params, filter) => {
                                let sorter = { name: 'name', order: 'ASC' };
                                let leaveTypeId = props.data.id;

                                console.log(params);
                                const response = await getLeaveTypeWiseAccruals({ ...params , leaveTypeId});

                                return {
                                    data: response.data.data,
                                    success: true,
                                    total: response.data.total
                                }
                            }}
                        />
                    }
                </Col>
            </Row>
        </>
    )
}

export default LeaveAccure