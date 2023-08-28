import ProForm, { ProFormCheckbox, ProFormDigit, ProFormSelect, ProFormSwitch, ProFormText, ProFormTextArea } from '@ant-design/pro-form';
import { Button, Card, Col, Divider, Form, message, Row, Space, Empty, Tooltip, Popconfirm } from 'antd';
import React, { useEffect, useState } from 'react';
import { Typography } from 'antd';
import { getCountries } from '@/services/countryService';
import { createWhoCanApply, editLeaveType, getAllEmployeeGroups, getAllEmployeeGroupsByLeaveTypeId, deleteEmployeeGroup, updateEmployeeGroup } from '@/services/leave';
import { FormattedMessage, useIntl } from 'react-intl';
import { history } from "umi"
import './styles.css'
import { ReactComponent as PlusIcon } from '../../assets/Plus.svg'
import SwitchInput from './SwitchInput';
import NumberInput from './NumberInput';
import moment from 'moment';
import { ReactComponent as LeaveTypeEdit } from '../../assets/attendance/leaveTypeEdit.svg';
import { ReactComponent as Delete } from '../../assets/attendance/delete.svg';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getALlGender } from '@/services/gender';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import _ from 'lodash';
import { APIResponse } from '@/utils/request';

const EmployeeGroups: React.FC = (props) => {

    const { Text } = Typography;
    const [form] = Form.useForm();
    const [whoCanApply, setWhoCanApply] = useState({})
    const [formValues, setFormValues] = useState({})
    const [locationOptions, setLocationOptions] = useState([])
    const [jobTitleOptions, setJobTitleOptions] = useState([])
    const [employementStatusOptions, setEmploymentStatusOptions] = useState([])
    const [genderOptions, setGenderOptions] = useState([])
    const intl = useIntl();
    const [availableEmployeeGroups, setAvailableEmployeeGroups] = useState([])
    const [addButtonVisible, setAddButtonVisible]=useState(true)
    const [showEditForm, setShowEditForm]=useState(false)
    const [groupID, setGroupID]=useState('new')


    useEffect(() => {
        if (addButtonVisible) {
            loadData();
        }
    }, [addButtonVisible])

    useEffect(() => {
        getOptions()
        setFormValues({});
    }, [])

    const updateFormValues = (fieldName) => {
        console.log(fieldName);
        const key = {}
        key[fieldName] = null;
        form.setFieldsValue(key);
    }

    
    const visibility = (fieldName) => {

        if (fieldName != 'minPemenancyPeriod' && fieldName != 'minServicePeriod') {

            if(_.get(formValues, fieldName, [])==null){
                return false
            }
    
            return _.get(formValues, fieldName, []).length > 0
        } else {
            if (formValues[fieldName] != null && formValues[fieldName] != undefined && formValues[fieldName] != 0) {
                return true;
            } else {
                return false;
            }
        }

    }

    const convertToOptions = (data, valueField: string, labelField: string) => {
        const arr: { value: string | number; label: string | number; disabled?: boolean; }[] = []
        arr.push({ value: '*', label: "All" })

        data.forEach((element: { [x: string]: any; }) => {
            arr.push({ value: element[valueField], label: element[labelField] })
        });
        return arr
    }
    const loadData = async () => {
        let params = {
            leaveTypeId : props.data.id
        }

        const allGroups = await getAllEmployeeGroupsByLeaveTypeId(params);
        if (allGroups.data) {
            setAvailableEmployeeGroups(allGroups.data)
        }
    }
    const getOptions = async () => {
        try {
            // loadData()
                    // const existingConfig=await getWhoCanApply(props.data.id)
            // if(existingConfig.data && existingConfig){
            //     await setWhoCanApply(existingConfig.data)
            // }
            // await form.setFieldsValue({ 
            //     employmentStatuses: existingConfig.data.employmentStatuses === null ? []:existingConfig.data.employmentStatuses,
            //     genders: existingConfig.data.genders === null ? []:existingConfig.data.genders,
            //     jobTitles: existingConfig.data.jobTitles === null ? []:existingConfig.data.jobTitles,
            //     locations:existingConfig.data.locations === null ? []:existingConfig.data.locations,
            //     minPemenancyPeriodYear: moment.unix( existingConfig.data.minPemenancyPeriod).year()              ,
            //     minPemenancyPeriodMonth: moment.unix( existingConfig.data.minPemenancyPeriod).month() ,
            //     minServicePeriod: moment.unix( existingConfig.data.minServicePeriod).year() ,
            //     minServicePeriodMonth: moment.unix( existingConfig.data.minServicePeriod).month() ,
            //      })

            const locationData = await getAllLocations()
            if (locationData.data) {
                await setLocationOptions(convertToOptions(locationData.data, "id", "name"))
            }
            const jobTitleData = await getAllJobTitles()
            if (jobTitleData.data) {
                await setJobTitleOptions(convertToOptions(jobTitleData.data, "id", "name"))
            }
            const empStatusData = await getAllEmploymentStatus()
            if (empStatusData.data) {
                await setEmploymentStatusOptions(convertToOptions(empStatusData.data, "id", "name"))
            }
            const genderData = await getALlGender()
            if (genderData.data) {
                await setGenderOptions(convertToOptions(genderData.data, "id", "name"))
            }

        }
        catch (err) {
            console.error(err)
        }
    }
    const formContents = () => {
        return (<>
            <SwitchInput
                label="Job Titles"
                updateFormValues = {updateFormValues}
                name="jobTitles"
                options={jobTitleOptions}
                defaultSelectorVisibility={visibility("jobTitles")}
                helperText="Only employees with the selected Job Titles can apply for the specific Leave Type."
                form={form}
            />
            <SwitchInput
                label="Employment Statuses"
                updateFormValues = {updateFormValues}
                name="employmentStatuses"
                options={employementStatusOptions}
                defaultSelectorVisibility={visibility("employmentStatuses")}
                helperText="Only employees of the selected Employment Statuses can apply for the specific Leave Type."
                form={form}
            />
            <SwitchInput
                label="Genders"
                updateFormValues = {updateFormValues}
                name="genders"
                options={genderOptions}
                defaultSelectorVisibility={visibility("genders")}
                helperText="Only employees of the selected Genders can apply for the specific Leave Type."
                form={form}
            />
            <SwitchInput
                label="Locations"
                updateFormValues = {updateFormValues}
                name="locations"
                options={locationOptions}
                defaultSelectorVisibility={visibility("locations")}
                helperText="Only employees of the selected Locations can apply for the specific Leave Type."
                form={form}
            />
            <NumberInput
                label="Min Service Period"
                yearInputName="minServicePeriodYear"
                monthInputName="minServicePeriodMonth"
                defaultSelectorVisibility={visibility("minServicePeriod")}
                helperText="Employee minimum Service Period for applying Leave."
                form={form}
            />
            <NumberInput
                label="Min Permanency Period"
                yearInputName="minPemenancyPeriodYear"
                monthInputName="minPemenancyPeriodMonth"
                defaultSelectorVisibility={visibility("minPemenancyPeriod")}
                helperText="Employee minimum Permenancy Period for applying Leave."
                form={form}
            />
        </>)
    }


    const updateFormData = async (formData: any) => { 

        let key = 'updating';
        await updateEmployeeGroup(formData).then(async(response: APIResponse) => {    
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
            loadData();
            await  setAddButtonVisible(true);
            form.resetFields()

            message.success({
                content:
                    intl.formatMessage({
                        id: 'successfullyUpdated',
                        defaultMessage: 'Successfully Update',
                    }),
            });

        }).catch((error: APIResponse) => {
            console.log(error);
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
            if (error && error.data != null && Object.keys(error.data).length !== 0) {
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

    const saveFormData = async (formData: any) => {

        let key = 'Saving';
        await createWhoCanApply(formData)
        .then(async(response: APIResponse) => {
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
            await  setAddButtonVisible(true);

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

    const formOnFinish = async (data) => {
        
        data.id = groupID;
        const formData = data
        const minServicePeriod=moment.duration(formData.minServicePeriodYear,"years").asMonths() + moment.duration(formData.minServicePeriodMonth,"months").asMonths()
        const minPemenancyPeriod=moment.duration(formData.minPemenancyPeriodYear,"years").asMonths() + moment.duration(formData.minPemenancyPeriodMonth,"months").asMonths()
        formData["leaveTypeId"]=props.data.id
        formData["minServicePeriod"]=minServicePeriod
        formData["minPemenancyPeriod"]=minPemenancyPeriod

        delete formData.minPemenancyPeriodYear
        delete formData.minPemenancyPeriodMonth
        delete formData.minServicePeriodYear
        delete formData.minServicePeriodMonth
        //    const formData={
        //         jobTitles: JSON.stringify(data.jobTitles),
        //         employmentStatuses: JSON.stringify(data.employmentStatuses),
        //         genders:JSON.stringify(data.genders),
        //         leaveTypeId:props.data.id
        //     }

           // formData["whoCanApply"] = data
           // const response = await editLeaveType({ id: props.data.id, ...formData })
        console.log(formData.id);
        if (formData.id == 'new') {
            saveFormData(formData);
        } else {
            updateFormData(formData)
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

                <Col flex="200px">
                {
                    addButtonVisible?
                    <Button onClick={()=>{
                        let newObj = {}; 
                        form.setFieldsValue({
                            name: null, 
                            comment: null, 
                            jobTitles: [],
                            employmentStatuses: [],
                            genders: [],
                            locations: [],
                            minServicePeriodYear: null,
                            minServicePeriodMonth: null,
                            minPemenancyPeriodYear: null,
                            minPemenancyPeriodMonth: null,

                        })
                        setFormValues({});
                        setGroupID('new');
                        setShowEditForm(false);
                        setAddButtonVisible(false)
                        props.setAddButtonVisible(false)
                    }} type="primary" style={{ height: 40, fontSize: 18 }}>
                        <PlusIcon /> &nbsp; Add Employee Group
                    </Button>
                    :<></>
                }

                </Col>
            </Row>
            <Divider style={{ margin: "18px 0px 32px" }} />
            <Row>
                <Col span={24}>
                    {
                        addButtonVisible ? 
                            availableEmployeeGroups.length > 0 ? (
                                <div className='employee-groups'>
                                    <Row style={{ paddingTop: 16 }}>
                                        {
                                            availableEmployeeGroups.map((el) => {
                                                return (
                                                    <>
                                                        <Row style={{ marginLeft: 12 , width: '100%'}}>
                                                            <Col span={20}>
                                                                <div style={{ height: 60, padding: 8, alignItems: "center"}}>
                                                                    <Row style={{marginLeft: 10}}>
                                                                        <Text style={{ fontSize: 18, color: "#394241" }}>
                                                                            {el.name}
                                                                        </Text>
                                                                    </Row>
                                                                    <Row style={{marginLeft: 12}}>
                                                                        <Text style={{ fontSize: 14, color: "#909A99" }}>
                                                                            {el.comment}
                                                                        </Text>
                                                                    </Row>


                                                                </div>
                                                            </Col>
                                                            <Col span={4} style={{marginTop: 25}}>
                                                                <Row style={{marginLeft: 75}}>
                                                                <Tooltip
                                                                    placement={'bottom'}
                                                                    key="editrecord"
                                                                    title={intl.formatMessage({
                                                                        id: 'edit',
                                                                        defaultMessage: 'Edit',
                                                                    })}
                                                                >
                                                                    <a style={{marginRight: 20}} onClick={() => {
                                                                        let dd = {};
                                                                        getOptions();
                                                                        form.setFieldsValue({
                                                                            name: (el.name) ? el.name : null, 
                                                                            comment: (el.comment) ? el.comment : null, 
                                                                            jobTitles: (el.jobTitles && el.jobTitles.length > 0) ? el.jobTitles : [],
                                                                            employmentStatuses: (el.employmentStatuses && el.employmentStatuses.length > 0) ? el.employmentStatuses : [],
                                                                            genders: (el.genders && el.genders.length > 0) ? el.genders : [],
                                                                            locations: (el.locations && el.locations.length > 0) ? el.locations : [],
                                                                            minServicePeriodYear: (el.minServicePeriodYear) ? el.minServicePeriodYear : null,
                                                                            minServicePeriodMonth: (el.minServicePeriodMonth) ? el.minServicePeriodMonth : null,
                                                                            minPemenancyPeriodYear: (el.minPemenancyPeriodYear) ? el.minPemenancyPeriodYear : null,
                                                                            minPemenancyPeriodMonth: (el.minPemenancyPeriodMonth) ? el.minPemenancyPeriodMonth : null,

                                                                        })
                                                                        setGroupID(el.id);
                                                                        setShowEditForm(true);
                                                                        setFormValues({...el});
                                                                        setAddButtonVisible(false);
                                                                        props.setAddButtonVisible(false)
                                                                    }}><LeaveTypeEdit height={20} /></a>
                                                                </Tooltip>

                                                                <Popconfirm
                                                                    key="deleteRecordConfirm"
                                                                    title={intl.formatMessage({
                                                                        id: 'are_you_sure',
                                                                        defaultMessage: 'Are you sure?',
                                                                    })}
                                                                    onConfirm={async () => {
                                                                        const key = 'deleting';
                                                                        message.loading({
                                                                            content: intl.formatMessage({
                                                                                id: 'deleting',
                                                                                defaultMessage: 'Deleting...',
                                                                            }),
                                                                            key,
                                                                        });
                                                                        deleteEmployeeGroup(el.id)
                                                                            .then((response: APIResponse) => {
                                                                                if (response.error) {
                                                                                    message.error({
                                                                                        content:
                                                                                            response.message ??
                                                                                            intl.formatMessage({
                                                                                                id: 'failedToDelete',
                                                                                                defaultMessage: 'Failed to delete',
                                                                                            }),
                                                                                        key,
                                                                                    });
                                                                                    return;
                                                                                }

                                                                                message.success({
                                                                                    content:
                                                                                        response.message ??
                                                                                        intl.formatMessage({
                                                                                            id: 'successfullyDeleted',
                                                                                            defaultMessage: 'Successfully deleted',
                                                                                        }),
                                                                                    key,
                                                                                });

                                                                                loadData();
                                                                            })

                                                                            .catch((error: APIResponse) => {
                                                                                message.error({
                                                                                    content: 
                                                                                    error.message ??
                                                                                    intl.formatMessage({
                                                                                        id: 'failedToDelete',
                                                                                        defaultMessage: 'Failed to delete',
                                                                                    }),
                                                                                    key

                                                                                });
                                                                            });
                                                                    }}
                                                                    okText="Yes"
                                                                    cancelText="No"
                                                                >
                                                                    
                                                                    <Tooltip
                                                                        placement={'bottom'}
                                                                        key="deleteRecordTooltip"
                                                                        title={intl.formatMessage({
                                                                            id: 'delete',
                                                                            defaultMessage: 'Delete',
                                                                        })}
                                                                    >
                                                                        <a onClick={() => {}}><Delete height={20} /></a>
                                                                    </Tooltip>
                                                                </Popconfirm>
                                                                </Row>
                                                                
                                                            </Col>
                                                        </Row>
                                                        <Divider />
                                                    </>
                                                )
                                            })
                                        }
                                    </Row>
                                </div>

                            ) : (
                                <div style={{ height: 600, padding: 8, alignItems: "center" }}>
                                    <Empty style={{marginTop : 250}} description={'No any records to show'} image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                </div>
                            )
                        
                        : 
                        <div>
                            <ProForm
                                id={'generalForm'}
                                form={form}
                                onFinish={formOnFinish}

                                onValuesChange={(field, value) => {
                                    Object.keys(value).forEach((key) => {
                                        if (_.indexOf(value[key], '*') !== -1) {
                                            const fieldVal = {}
                                            fieldVal[key] = ['*']
                                            form.setFieldsValue(fieldVal)
                                        }

                                    })


                                }}
                                submitter={{
                                    render: (formProps, doms) => {
                                        return [
                                            <Row justify='end' gutter={[16, 16]}>
                                                <Col span={7}>
                                                    <Space>
                                                        <Button key="cancel" size="middle" onClick={() => {
                                                            setShowEditForm(false);
                                                            setAddButtonVisible(true);
                                                            props.setAddButtonVisible(true);
                                                        }} >
                                                            Cancel
                                                        </Button>
                                                        {
                                                            showEditForm ? (
                                                                <Button type="primary" key="submit" size="middle" onClick={() => formProps.form?.submit()}>
                                                                    Update
                                                                </Button>
                                                            ) : (
                                                                <Button type="primary" key="submit" size="middle" onClick={() => formProps.form?.submit()}>
                                                                    Save
                                                                </Button>
                                                            )

                                                        }

                                                        
                                                    </Space>
                                                </Col>
                                            </Row>

                                        ];
                                    },
                                }}
                            >
                                <Row >
                                    <ProFormText
                                    name={"name"}
                                    label="Group Name"
                                    width="lg"
                                    rules={[
                                        {
                                        required: true,
                                        message: intl.formatMessage({
                                            id: `employeegroups.name`,
                                            defaultMessage: `Required`,
                                        }),
                                        },
                                        { max: 100, message: 'Maximum length is 100 characters.' }
                        
                                    ]}   
                                    />
                                </Row>
                                <Row>
                                    <ProFormTextArea
                                    name={"comment"}
                                    label="Comment"
                                    width="lg"
                                    rules={[{ max: 500, message: 'Maximum length is 500 characters.' }]}
                                    />
                                </Row>
                                {formContents()}
                            </ProForm>
                        </div>
                    }
                </Col>
            </Row>

        </>

    )
}

export default EmployeeGroups