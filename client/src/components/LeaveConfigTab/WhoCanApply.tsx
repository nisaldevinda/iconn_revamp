import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getALlGender } from '@/services/gender';
import { getAllJobTitles } from '@/services/jobTitle';
import { editLeaveType } from '@/services/leave';
import { getAllLocations } from '@/services/location';
import ProForm, { ProFormSelect } from '@ant-design/pro-form';
import { FormattedMessage } from 'react-intl';
import { Button, Col, Divider, Form, message, Row, Space, Typography, Radio } from 'antd';
import _ from 'lodash';
import moment from 'moment';
import React, { useEffect, useState } from 'react';
import { useIntl } from 'umi';
import NumberInput from './NumberInput';
import SwitchInput from './SwitchInput';
import { history } from "umi"

const WhoCanApply = (props) => {

    const { Text } = Typography;
    const [form] = Form.useForm();
    const [whoCanApply, setWhoCanApply] = useState({})
    const [locationOptions, setLocationOptions] = useState([])
    const [jobTitleOptions, setJobTitleOptions] = useState([])
    const [employementStatusOptions, setEmploymentStatusOptions] = useState([])
    const [genderOptions, setGenderOptions] = useState([])
    const [visibleArr, setVisibleArr] = useState([])
    const intl = useIntl();
    const [radioVal, setRadioVal] = useState<any | null>(true);

    useEffect(() => {
        getOptions()
        
        if (props.data.isAllEmployeesCanApply !== undefined) {
            let state = (props.data.isAllEmployeesCanApply) ? true : false;
            changeLeavePeriod(state);
        }

    }, [])

    useEffect(() => {
        if (props.data) {

            setFormData()
        }
    }, [props])


    const changeLeavePeriod = (event) => {
        setRadioVal(event);

        if (event) {
            setWhoCanApply({ 
                employmentStatuses: [],
                genders: [],
                jobTitles: [],
                locations: [],
                minPemenancyPeriodYear: null,
                minPemenancyPeriodMonth: null,
                minServicePeriodYear:  null,
                minServicePeriodMonth:  null,
            });

            form.setFieldsValue({ 
                employmentStatuses: [],
                genders:[],
                jobTitles:[],
                locations:[],
                minPemenancyPeriodYear: null,
                minPemenancyPeriodMonth:  null,
                minServicePeriodYear:  null,
                minServicePeriodMonth:  null,
            });
        }
    };

    const setFormData = async () => {
        const formData = JSON.parse(props.data.whoCanApply)
        await setWhoCanApply(formData)

        await form.setFieldsValue({ 
            employmentStatuses: _.get(formData,'employmentStatuses',null) === null ? []:formData.employmentStatuses,
            genders:_.get(formData,'genders',null) === null ? []:formData.genders,
            jobTitles:_.get(formData,'jobTitles',null) === null ? []:formData.jobTitles,
            locations:_.get(formData,'locations',null) === null ? []:formData.locations,
            minPemenancyPeriodYear: moment.duration( _.get(formData,'minPemenancyPeriod',null),"months").years(),
            minPemenancyPeriodMonth:  moment.duration( _.get(formData,'minPemenancyPeriod',null),"months").months(),
            minServicePeriodYear:  moment.duration( _.get(formData,'minServicePeriod',null),"months").years(),
            minServicePeriodMonth:  moment.duration( _.get(formData,'minServicePeriod',null),"months").months(),
             })

    }

    const convertToOptions = (data, valueField: string, labelField: string) => {
        const arr: { value: string | number; label: string | number; disabled?: boolean; }[] = []
        arr.push({ value: '*', label: "All" })

        data.forEach((element: { [x: string]: any; }) => {
            arr.push({ value: element[valueField], label: element[labelField] })
        });
        return arr
    }





    const getOptions = async () => {
        try {
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

    const visibility = (fieldName) => {
        if(_.get(whoCanApply, fieldName, [])==null){
            return false
        }
        return _.get(whoCanApply, fieldName, []).toString().length > 0
    }
    const updateFormValues = (fieldName) => {
        const key = {}
        key[fieldName] = []
        form.setFieldsValue(key);
    }

    const formContents = () => {
        return (<>
            <SwitchInput
                label= {intl.formatMessage({
                    id: 'jobTitles',
                    defaultMessage: "Job Titles",
                })}
                name="jobTitles"
                updateFormValues = {updateFormValues}
                options={jobTitleOptions}
                defaultSelectorVisibility={visibility("jobTitles")}
                helperText={
                    intl.formatMessage({
                        id: 'jobTitlesDescription',
                        defaultMessage: "Only employees with the selected Job Titles can apply for the specific Leave Type.",
                    })
                }
                form={form}
            />
            <SwitchInput
                label= {intl.formatMessage({
                    id: 'employementStatus',
                    defaultMessage: "Employment Statuses",
                })}
                updateFormValues = {updateFormValues}
                name="employmentStatuses"
                options={employementStatusOptions}
                defaultSelectorVisibility={visibility("employmentStatuses")}
                helperText= {
                    intl.formatMessage({
                        id: 'employementStatusDescription',
                        defaultMessage: "Only employees of the selected Employment Statuses can apply for the specific Leave Type.",
                    })
                }
                form={form}
            />
            <SwitchInput
                label= {intl.formatMessage({
                    id: 'genders',
                    defaultMessage: "Genders",
                })}
                updateFormValues = {updateFormValues}
                name="genders"
                options={genderOptions}
                defaultSelectorVisibility={visibility("genders")}
                helperText= {intl.formatMessage({
                    id: 'gendersDescripiton',
                    defaultMessage: "Only employees of the selected Genders can apply for the specific Leave Type.",
                })}
                form={form}
            />
            <SwitchInput
                label={intl.formatMessage({
                    id: 'locations',
                    defaultMessage: "Locations",
                })}
                updateFormValues = {updateFormValues}
                name="locations"
                options={locationOptions}
                defaultSelectorVisibility={visibility("locations")}
                helperText={intl.formatMessage({
                    id: 'locationsDescriptions',
                    defaultMessage: "Only employees of the selected Locations can apply for the specific Leave Type.",
                })}
                form={form}
            />

            <NumberInput
                label={intl.formatMessage({
                    id: 'minServicePeriod',
                    defaultMessage: "Min Service Period",
                })}
                yearInputName="minServicePeriodYear"
                monthInputName="minServicePeriodMonth"
                defaultSelectorVisibility={visibility("minServicePeriod")}
                helperText={intl.formatMessage({
                    id: 'minServicePeriodDescription',
                    defaultMessage: "Employee minimum Service Period for applying Leave.",
                })}
                form={form}
            />
            <NumberInput
                label={intl.formatMessage({
                    id: 'minPermenancyPeriod',
                    defaultMessage: "Min Permanency Period",
                })}
                yearInputName="minPemenancyPeriodYear"
                monthInputName="minPemenancyPeriodMonth"
                defaultSelectorVisibility={visibility("minPemenancyPeriod")}
                helperText={intl.formatMessage({
                    id: 'minPermenancyPeriodDescription',
                    defaultMessage: "Employee minimum Permanency Period for applying Leave.",
                })}
                form={form}
            />
        </>)
    }

    const formOnFinish = async (data) => {
        try {
            const formData = data
            // const minServicePeriod=moment(formData.minServicePeriodYear,"Y").add(formData.minServicePeriodMonth,'months').unix()
            // const minPemenancyPeriod=moment(formData.minPemenancyPeriodYear,"Y").add(formData.minPemenancyPeriodMonth,'months').unix()

            const minServicePeriod=moment.duration(formData.minServicePeriodYear,"years").asMonths() + moment.duration(formData.minServicePeriodMonth,"months").asMonths()
            const minPemenancyPeriod=moment.duration(formData.minPemenancyPeriodYear,"years").asMonths() + moment.duration(formData.minPemenancyPeriodMonth,"months").asMonths()
            formData["leaveTypeId"]=props.data.id
             formData["minServicePeriod"]=minServicePeriod
             formData["minPemenancyPeriod"]=minPemenancyPeriod
 
             delete formData.minPemenancyPeriodYear
             delete formData.minPemenancyPeriodMonth
             delete formData.minServicePeriodYear
             delete formData.minServicePeriodMonth
           // formData["whoCanApply"] = data
            const finalData={}
            finalData["whoCanApply"]=formData
            finalData["isAllEmployeesCanApply"] = radioVal;

            if (finalData["isAllEmployeesCanApply"]) {
                finalData["whoCanApply"] = {};
            }

            let fieldsDetails = form.getFieldsValue();

            
            if (!finalData["isAllEmployeesCanApply"]) {
                console.log(Object.keys(fieldsDetails).length);

                if (Object.keys(fieldsDetails).length == 0) {
                    message.error({
                        content: 'Please switch on at least one option before save',
                    });
                    return;
                }
            }

            const response = await editLeaveType({ id: props.data.id,...finalData })
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
            console.error(err)

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
            <Divider />

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
                                        <Button key="cancel" size="middle" onClick={() => history.push(`/settings/leave-types`)} >
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

            <Radio.Group style={{marginBottom: 50}} value={radioVal} onChange={(event) => changeLeavePeriod(event.target.value)} >
                <Radio value={true}>{
                    intl.formatMessage({
                        id: 'allEmployeesCanApply',
                        defaultMessage: 'All Employees',
                    })
                }</Radio>
                <Radio value={false}>{
                    intl.formatMessage({
                        id: 'customEmployeesCanApply',
                        defaultMessage: 'Custom Employees',
                    })
                }</Radio>
            </Radio.Group>
            {
                !radioVal ? (
                    formContents()
                ) : (
                    <></>
                )
            }
            </ProForm>
        </>
    );
}

export default WhoCanApply;