import ProForm, { ProFormCheckbox, ProFormDigit, ProFormSelect, ProFormSwitch } from '@ant-design/pro-form';
import { Button, Card, Col, Divider, Form, message, Row, Space, Switch ,Spin} from 'antd';
import React, { useEffect, useState } from 'react';
import { Typography } from 'antd';
import { getCountries } from '@/services/countryService';
import { editLeaveType, getLeaveTypesWorkingDays, getAllEmployeeGroupsByLeaveTypeId } from '@/services/leave';
import { getAdminRoles } from '@/services/userRole';
import { FormattedMessage, useIntl } from 'react-intl';
import {history} from "umi"
import './styles.css'
import SwitchInput from './SwitchInput';

const General: React.FC = (props) => {

    const { Text } = Typography;
    const [form] = Form.useForm();
    const [countries, setCountries] = useState([])
    const intl = useIntl();
    const [dayTypes,setDayTypes]=useState([])
    const [adminRoles,setAdminRoles]=useState([])
    const [isAttachmentMandotaryEnable,setIsAttachmentMandatoryEnable]=useState<boolean>(true)
    const [selectorVisibility, setSelectorVisibility] = useState(false);
    const [coveringPersonVisibility, setCoveringPersonVisibility] = useState(false);
    const [employeeGroupOptions,setEmployeeGroupOptions]=useState([])
    const [loading , setLoading] = useState(false);

    useEffect(() => {
        if (props.data) {

            props.data.whoCanUseCoveringPerson = (props.data.whoCanUseCoveringPerson) ? props.data.whoCanUseCoveringPerson : [];
            props.data.whoCanAssign = (props.data.whoCanAssign) ? props.data.whoCanAssign : [];


            form.setFieldsValue({ ...props.data })
           

            if (props.data) {

                if (props.data.allowCoveringPerson) {
                    setCoveringPersonVisibility(true);
                } else {
                    setCoveringPersonVisibility(false);
                }

                if (props.data.adminsCanAssign) {
                    setSelectorVisibility(true);
                } else {
                    setSelectorVisibility(false);
                }

                if (props.data.allowAttachment === 1) {
                    setIsAttachmentMandatoryEnable(false);
                } else if (props.data.allowAttachment === 0) {
                    setIsAttachmentMandatoryEnable(true);
                }
            }
        }
    }, [props.data])

    useEffect(() => {
        fetchData()
    }, [])

    const fetchData = async () => {
        try {
            setLoading(true);
            const countryList = await getCountries();
            if (countryList.data) {
                const countriesArr = []
                countryList.data.forEach(element => {
                    countriesArr.push({ value: element.id, label: element.name })

                })
                setCountries(countriesArr)
            }

            const leaveTypeWorkingDays = await getLeaveTypesWorkingDays()
            const workingdaysarr = []
            if (leaveTypeWorkingDays.data) {
                leaveTypeWorkingDays.data.forEach(element => {
                    workingdaysarr.push({ value: element.id, label: element.name })
                });
            }
            setDayTypes(workingdaysarr)

            const adminRoleData = await getAdminRoles()
            if (adminRoleData.data) {
                await setAdminRoles(convertToOptions(adminRoleData.data, "id", "title"))
            }
            setLoading(false);
        }
        catch (err) {
            setLoading(false);
            console.error(err)
        }
    }

    const convertToOptions = (data, valueField: string, labelField: string) => {
        const arr: { value: string | number; label: string | number; disabled?: boolean; }[] = []
        // arr.push({ value: '*', label: "All" })

        data.forEach((element: { [x: string]: any; }) => {
            arr.push({ value: element[valueField], label: element[labelField] })
        });
        return arr
    }

    const formOnFinish = async (data) => {
        try {
            const key = 'saving';
            const formData = data
            // eslint-disable-next-line no-restricted-syntax
            for (const key of Object.keys(formData)) {
                if (typeof (formData[key]) === "number" && key !== "applicableCountryId" && key !=='maximumConsecutiveLeaveDays') {
                    formData[key] = !!formData[key];
                }
            }
            const response = await editLeaveType({ id: props.data.id, ...formData })

            props.fetchLeaveTypeData();
            message.success({
                content:
                    response.message ??
                    intl.formatMessage({
                        id: 'successfullySaved',
                        defaultMessage: 'Successfully Saved',
                    }),
            });
        }
        catch (err) {
            console.error(err);
            message.error({
                content:
                err.message ??
                    intl.formatMessage({
                    id: 'failedToSave',
                    defaultMessage: 'Cannot Save',
                    }),
                });

        }
    }


    const formContents = () => {
        return (
            <>
                <Row >
                    <ProFormSelect
                        name={"leavePeriod"}
                        label="Leave Period"
                        options={[{ value: "STANDARD", label: "STANDARD" }, { value: "HIRE_DATE_BASED", label: "HIRE DATE BASED" }]}
                        width="md"
                        showSearch
                        disabled={props.data.isLinkedWithEntitlement}
                    />
                </Row>
                {/* <Row>
                    <ProFormSelect
                        name={"applicableCountryId"}
                        label="Applicable Country"
                        options={countries}
                        width="md"
                        showSearch
                    />
                </Row> */}

                <Row >
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'employeeCanApply',
                                    defaultMessage: 'Employees can apply for this leave',
                                })
                            }</Text>
                        </Row>
                        <Row><Text disabled>{
                            intl.formatMessage({
                                id: 'employeeCanApplyDescription',
                                defaultMessage: 'Employees can apply for leave under this Leave Type',
                            })
                        }</Text></Row>
                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="employeesCanApply"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/>
                <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'adminCanAssign',
                                    defaultMessage: 'Admins can assign leave to employees',
                                })
                            }</Text>
                        </Row>
                        <Row style={{ marginBottom: 22 }}>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'adminCanAssignDescription',
                                    defaultMessage: 'Only the selected admin roles can assign leaves to employees',
                                })
                            } </Text>
                        </Row>

                        {selectorVisibility ? <Row style={{ marginLeft: 30 }}>
                        <ProFormSelect
                            name='whoCanAssign'
                            label='Admin Roles'
                            options={adminRoles}
                            width="lg"
                            mode='multiple'
                            showSearch
                            initialValue={form.getFieldValue('whoCanAssign')}
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
                        <ProFormSwitch
                            name="adminsCanAssign"
                            fieldProps={{    
                                onChange: async (checked) => {
                                    setSelectorVisibility(checked)
                                }
                            }}
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >

                <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'fullDayAllowed',
                                    defaultMessage: 'Full Day is Allowed',
                                })
                            }</Text>
                        </Row>
                        <Row>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'employeeCanApplyFullDay',
                                    defaultMessage: 'Employees can apply for full-day leaves.',
                                })
                            }</Text>
                        </Row>
                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="fullDayAllowed"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'halfDayAllowed',
                                    defaultMessage: 'Half-day is allowed',
                                })
                            }</Text>
                        </Row>
                        <Row>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'employeeCanApplyHalfDay',
                                    defaultMessage: 'Employees can apply for half-day leaves.',
                                }) 
                            }</Text>
                        </Row>

                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="halfDayAllowed"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                {/* <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'shortLeaveAllowed',
                                    defaultMessage: 'Short Leave is Allowed',
                                }) 
                            }</Text>
                        </Row>
                        <Row>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'employeeCanApplyShortLeave',
                                    defaultMessage: 'Employees can apply for short leaves.',
                                }) 
                            }</Text>
                        </Row>
                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="shortLeaveAllowed"
                        />
                    </Col>
                </Row> */}
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'timeDurationAllowed',
                                    defaultMessage: 'Specific time duration is allowed',
                                }) 
                            }</Text>
                        </Row>
                        <Row>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'timeDurationAllowedDescription',
                                    defaultMessage: 'Only employees who meet the dependent criteria can apply for the specific Leave Type.The admin can specify the conditions that should be met by the employees in order to apply for this leave type.',
                                }) 
                            }</Text>
                        </Row>
                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="timeDurationAllowed"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'adminCanAdjustEntitlement',
                                    defaultMessage: 'Admin can add/adjust Entitlements',
                                }) 
                            }</Text>
                        </Row>
                        <Row><Text disabled>
                            {
                                intl.formatMessage({
                                    id: 'adminCanAdjustEntitlementDescription',
                                    defaultMessage: 'Employees who have worked for a certain period of time (based on joined date) can apply for the specific Leave Type. The admin can configure the service period which is applicable.',
                                }) 
                            }</Text>
                        </Row>

                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="adminCanAdjustEntitlements"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <Col span={18}>                
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'allowExceedingBalance',
                                    defaultMessage: 'Allow exceeding balance',
                                }) 
                            }</Text>
                        </Row>
                        <Row>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'allowExceedingBalanceDescription',
                                    defaultMessage: 'Employees who have worked for a period of time after becoming a permanent employee can apply for the specific Leave Type. The admin can configure the permanency period.',
                                })   
                            }</Text>
                        </Row>
                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="allowExceedingBalance"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <Col span={18}>                
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'allowAttachment',
                                    defaultMessage: 'Allow attachment',
                                })
                            }</Text>                   
                        </Row>

                        <Row>    
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'allowAttachmentDescription',
                                    defaultMessage: 'Employees can attach important images when apply for leaves.',
                                })
                            }</Text>                   
                        </Row>

                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            name="allowAttachment"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <Col span={18}>                
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'attachmentMandatory',
                                    defaultMessage: 'Attachment Mandatory',
                                })
                            }</Text>                   
                        </Row>

                        <Row>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'attachmentMandatoryDescription',
                                    defaultMessage: 'Employees can attach important images when apply for leaves.',
                                })
                            }</Text>                   
                        </Row>

                    </Col>
                    <Col span={6}>
                        <ProFormSwitch
                            disabled={isAttachmentMandotaryEnable}
                            name="attachmentManadatory"
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/>
                <Row>
                    <Col span={18}>
                        <Row>
                            <Text style={{
                                fontSize: 18,
                                color: "#394241"
                            }}>{
                                intl.formatMessage({
                                    id: 'enableCoveringPerson',
                                    defaultMessage: 'Enable Covering Person',
                                })
                            }</Text>
                        </Row>
                        <Row style={{ marginBottom: 22 }}>
                            <Text disabled>{
                                intl.formatMessage({
                                    id: 'enableCoveringPersonDescription',
                                    defaultMessage: 'A covering person is mandatory when apply for a leave. Selected covering person will work behalf of the employee.',
                                })
                            } </Text>
                        </Row>

                        {coveringPersonVisibility ? <Row style={{ marginLeft: 30 }}>
                        <ProFormSelect
                            name='whoCanUseCoveringPerson'
                            label='Employee Groups'
                            options={props.employeeGroupOptions}
                            width="lg"
                            mode='multiple'
                            showSearch
                            initialValue={form.getFieldValue('whoCanUseCoveringPerson')}
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
                        <ProFormSwitch
                            name="allowCoveringPerson"
                            fieldProps={{    
                                onChange: async (checked) => {
                                    if (!checked) {
                                        form.setFieldsValue({
                                            whoCanUseCoveringPerson: []
                                        })
                                    }
                                    setCoveringPersonVisibility(checked);
                                }
                            }}
                        />
                    </Col>
                </Row>
                <Divider style={{margin:"18px 0px"}}/ >
                <Row>
                    <ProFormSelect
                    name="leaveTypeWorkingDays" 
                    label="Consider following Day Types  as Working Days"
                    options={dayTypes}
                    width="md"
                    showSearch
                    mode="multiple"
                    allowClear
                    />
                </Row>
                <Row  className="digit-input">
                    <Col>
                    <ProFormDigit
                    name="maximumConsecutiveLeaveDays" 
                    label="Maximum Consecutive Leaves"
                    width="sm"
                    max={365}
                    style={{borderRadius:0}}
                   
                    />
                    </Col>
                    <Col>
                    <div style={{
                        position:"relative",
                        top:30,
                        width:60,
                        height:32,
                        background:"#F1F3F6",
                        border: "1px solid #E1E3E5",
                        borderRadius: "0px 6px 6px 0px"}}
                        className="input-add-on-after"
                        >Days</div>
                    </Col>
                   
                    
                </Row>

            </>)

    }

    const onFormChange = async (val,oth) => {
        for (const [key, value] of Object.entries(val)) {
            if (key === "shortLeaveAllowed" && val[key] == true) {
                form.setFieldsValue({
                    fullDayAllowed: false,
                    halfDayAllowed: false
                });
            }

            if (key === "allowAttachment" && val[key] === true) {
                setIsAttachmentMandatoryEnable(false);
            } else if (key === "allowAttachment" && val[key] === false) {
                form.setFieldsValue({
                    attachmentManadatory: false
                });
                setIsAttachmentMandatoryEnable(true);
            } 

            if ((key === "fullDayAllowed" && val[key] == true) || (key === "halfDayAllowed" && val[key] == true)) {
                form.setFieldsValue({
                    shortLeaveAllowed: false,
                });
            }
        }
    }


    return (
        <>
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
            <Divider style={{margin:"18px 0px"}}/>
            {loading ? ( 
              <Spin spinning={loading}/> ) : (
            <ProForm
                id={'generalForm'}
                form={form}
                onFinish={formOnFinish}
                //  onValuesChange={onFormChange}
                onValuesChange={onFormChange}
                submitter={{

                    render: (formProps, doms) => {
                        return [
                            <Row justify='end' gutter={[16, 16]}>
                                <Col span={7}>
                                    <Space>
                                    <Button  key="cancel" size="middle" onClick={() =>history.push(`/settings/leave-types`)} >
                                        {intl.formatMessage({
                                            id: 'cancel',
                                            defaultMessage: "Cancel",
                                        })}
                                    </Button>
                               
                                    <Button type="primary" key="submit" size="middle" onClick={() => formProps.form?.submit()}>
                                        {intl.formatMessage({
                                            id: 'save',
                                            defaultMessage: "Save",
                                        })}
                                    </Button>
                                    </Space>
                                    </Col>
                            </Row>

                        ];
                    },
                }}
            >
                {formContents()}
            </ProForm>
           )}
        </>
    );
}

export default General;