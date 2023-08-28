import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getALlGender } from '@/services/gender';
import { getAllJobTitles } from '@/services/jobTitle';
import { createWhoCanApply, editLeaveType, getWhoCanApply } from '@/services/leave';
import { getAllLocations } from '@/services/location';
import ProForm, { ProFormSelect } from '@ant-design/pro-form';
import { Button, Col, Divider, Form, message, Row, Space, Typography } from 'antd';
import _ from 'lodash';
import moment from 'moment';
import React, { useEffect, useState } from 'react';
import { useIntl } from 'umi';
import NumberInput from './NumberInput';
import SwitchInput from './SwitchInput';

const WhoCanApply = (props) => {

    const { Text } = Typography;
    const [form] = Form.useForm();
    const [whoCanApply, setWhoCanApply] = useState({})
    const [locationOptions, setLocationOptions] = useState([])
    const [jobTitleOptions, setJobTitleOptions] = useState([])
    const [employementStatusOptions, setEmploymentStatusOptions] = useState([])
    const [genderOptions, setGenderOptions] = useState([])
    const intl = useIntl();

    useEffect(() => {
        getOptions()
    }, [])

    // useEffect(() => {
    //     if (props.data) {

    //         setFormData()
    //     }
    // }, [props])



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
            const existingConfig=await getWhoCanApply(props.data.id)
            if(existingConfig.data && existingConfig){
                await setWhoCanApply(existingConfig.data)
            }
            await form.setFieldsValue({ 
                employmentStatuses: existingConfig.data.employmentStatuses === null ? []:existingConfig.data.employmentStatuses,
                genders: existingConfig.data.genders === null ? []:existingConfig.data.genders,
                jobTitles: existingConfig.data.jobTitles === null ? []:existingConfig.data.jobTitles,
                locations:existingConfig.data.locations === null ? []:existingConfig.data.locations,
                minPemenancyPeriodYear: moment.unix( existingConfig.data.minPemenancyPeriod).year()              ,
                minPemenancyPeriodMonth: moment.unix( existingConfig.data.minPemenancyPeriod).month() ,
                minServicePeriod: moment.unix( existingConfig.data.minServicePeriod).year() ,
                minServicePeriodMonth: moment.unix( existingConfig.data.minServicePeriod).month() ,
                 })

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
        return _.get(whoCanApply, fieldName, []).length > 0
    }

    const formContents = () => {
        return (<>
            <SwitchInput
                label="Job Titles"
                name="jobTitles"
                options={jobTitleOptions}
                defaultSelectorVisibility={visibility("jobTitles")}
                helperText="Only employees with the selected Job Titles can apply for the specific Leave Type."
                form={form}
            />
            <SwitchInput
                label="Employment Statuses"
                name="employmentStatuses"
                options={employementStatusOptions}
                defaultSelectorVisibility={visibility("employmentStatuses")}
                helperText="Only employees of the selected Employment Statuses can apply for the specific Leave Type."
                form={form}
            />
            <SwitchInput
                label="Genders"
                name="genders"
                options={genderOptions}
                defaultSelectorVisibility={visibility("genders")}
                helperText="Only employees of the selected Genders can apply for the specific Leave Type."
                form={form}
            />
            <SwitchInput
                label="Locations"
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
                label="Min Pemenancy Period"
                yearInputName="minPemenancyPeriodYear"
                monthInputName="minPemenancyPeriodMonth"
                defaultSelectorVisibility={visibility("minPemenancyPeriod")}
                helperText="Employee minimum Permenancy Period for applying Leave."
                form={form}
            />
        </>)
    }

    const formOnFinish = async (data) => {
        try {
            const formData = data
           const minServicePeriod=moment(formData.minServicePeriodYear,"Y").add(formData.minServicePeriodMonth,'months').unix()
           const minPemenancyPeriod=moment(formData.minPemenancyPeriodYear,"Y").add(formData.minPemenancyPeriodMonth,'months').unix()
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
            const response =await createWhoCanApply(formData)
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
                                        <Button key="cancel" size="middle" onClick={() => history.push(`/leave/leave-types`)} >
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
                {formContents()}
            </ProForm>
        </>
    );
}

export default WhoCanApply;