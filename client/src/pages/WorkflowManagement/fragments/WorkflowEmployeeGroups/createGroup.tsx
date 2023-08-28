import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import ProForm, { ProFormTextArea } from '@ant-design/pro-form';
import _, { values } from "lodash";
import { Row, Col, FormInstance } from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { ProFormText } from "@ant-design/pro-form";
import { generateProFormFieldValidation } from "@/utils/validator";
import SwitchInput from './SwitchInput';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getAllJobTitles } from '@/services/jobTitle';
import { getAllLocations } from '@/services/location';
import { getAllDepartment } from '@/services/department';
import { getAllDivisions } from '@/services/divsion';
import { getAllManagers } from '@/services/user';
import { queryContextData } from '@/services/workflowServices';


export type CreateFormProps = {
    model: Partial<ModelType>;
    values: {};
    setValues: (values: any) => void;
    addGroupFormVisible: boolean;
    editGroupFormVisible: boolean;
    isEditView: boolean;
    form: FormInstance;
    emptySwitch: any
    
};
  

const CreateGroup: React.FC<CreateFormProps> = (props) => {
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [model, setModel] = useState<any>();
    const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
    const [locationOptions, setLocationOptions] = useState([])
    const [jobTitleOptions, setJobTitleOptions] = useState([])
    const [employementStatusOptions, setEmploymentStatusOptions] = useState([])
    const [departmentOptions, setDepartmentOptions] = useState([])
    const [divisionOptions, setDivisionOptions] = useState([])
    const [reportedPersonOptions, setReportedPersonOptions] = useState([])
    const [contextOptions, setContextOptions] = useState([])
    

    useEffect(() => {
        if (_.isEmpty(model)) {
            getModel('workflowEmployeeGroup').then((response) => {
            const groupModel = response.data;
            setModel(groupModel);
            })
        }
        getOptions();
    }, []);

    const updateValues = (fieldName:any, value:any) => {
        const currentValues = {...props.values};
        currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
        props.emptySwitch(fieldName);
        props.setValues(currentValues);
    }

    const visibility = (fieldName) => {

        if (props.values[fieldName] != null && props.values[fieldName] != undefined && props.values[fieldName] != 0) {
            return true;
        } else {
            return false;
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

    const getOptions = async () => {
        try {
            const contextData = await queryContextData()
            if (contextData.data) {
                const arr: { value: string | number; label: string | number; disabled?: boolean; }[] = []
                contextData.data.forEach((element: { [x: string]: any; }) => {
                    arr.push({ value: element['id'], label: element['contextName'] })
                });

                await setContextOptions(arr);
            }

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
            const departmentData = await getAllDepartment()
            if (departmentData.data) {
                await setDepartmentOptions(convertToOptions(departmentData.data, "id", "name"))
            }
            const divisionData = await getAllDivisions()
            if (divisionData.data) {
                await setDivisionOptions(convertToOptions(divisionData.data, "id", "name"))
            }
            
            const managerData = await getAllManagers()
            if (managerData.data) {
                await setReportedPersonOptions(convertToOptions(managerData.data, "id", "employeeName"))
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
                name="jobTitles"
                updateValues = {updateValues}
                options={jobTitleOptions}
                defaultSelectorVisibility={visibility("jobTitles")}
                helperText="Only employees with the selected Job Titles can use the workflow."
                form={props.form}
            />
            <SwitchInput
                label="Employment Statuses"
                name="employmentStatuses"
                updateValues = {updateValues}
                options={employementStatusOptions}
                defaultSelectorVisibility={visibility("employmentStatuses")}
                helperText="Only employees of the selected Employment Statuses can use the workflow."
                form={props.form}
            />
            <SwitchInput
                label="Locations"
                name="locations"
                updateValues = {updateValues}
                options={locationOptions}
                defaultSelectorVisibility={visibility("locations")}
                helperText="Only employees of the selected Locations can apply for the specific Leave Type."
                form={props.form}
            />
            <SwitchInput
                label="Department"
                name="departments"
                updateValues = {updateValues}
                options={departmentOptions}
                defaultSelectorVisibility={visibility("departments")}
                helperText="Only employees of the selected Departments can apply for the specific Leave Type."
                form={props.form} 
            />
            <SwitchInput
                label="Division"
                name="divisions"
                updateValues = {updateValues}
                options={divisionOptions}
                defaultSelectorVisibility={visibility("divisions")}
                helperText="Only employees of the selected Divisions can apply for the specific Leave Type."
                form={props.form}
            />
            <SwitchInput
                label="Reporting Person"
                name="reportingPersons"
                updateValues = {updateValues}
                options={reportedPersonOptions}
                defaultSelectorVisibility={visibility("reportingPersons")}
                helperText="Only employees of the selected Reporting Persons can apply for the specific Leave Type."
                form={props.form}
            />
            
           
        </>)
    }

    return (
        
        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
            <Col span={12}>
                <ProFormText
                    name={"name"}
                    label="Group Name"
                    width="md"
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
                    fieldProps={{
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['name'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            props.setValues(currentValues);
                        },
                        autoComplete: "none"
                    }}
                />
            </Col>
            <Col span={12}>
                <ProFormSelect
                    options={contextOptions}
                    width="md"
                    showSearch
                    name='context'
                    label='Context Name'
                    disabled={false}
                    placeholder={'Select Workflow Context'}
                    rules={[
                        {
                            required: true,
                            message: intl.formatMessage({
                                id: `employeegroups.context`,
                                defaultMessage: `Required`,
                            }),
                        }
                    ]}
                    fieldProps={{
                        mode:  undefined,
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['context'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                            props.setValues(currentValues);
                        }
                    }}
                    initialValue={null}
                />
            </Col>
            <Col span={12}>
                <ProFormTextArea
                    name={"comment"}
                    label="Comment"
                    width="md"
                    rules={[{ max: 500, message: 'Maximum length is 500 characters.' }]}
                    fieldProps={{
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['comment'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            props.setValues(currentValues);
                        },
                        autoComplete: "none"
                    }}
                />
            </Col>

            {
                props.isEditView ? (
                    <Col span={24}>
                        {formContents()}
                    </Col>
                ) : (
                    <Col span={24} style={{height: 300 , overflowY: 'auto'}}>
                        {formContents()}
                    </Col>
                )
            }
            
        </Row>
        
    );
};

export default CreateGroup;
