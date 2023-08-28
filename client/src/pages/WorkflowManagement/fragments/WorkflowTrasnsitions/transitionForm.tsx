import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import ProForm, { ProFormTextArea } from '@ant-design/pro-form';
import _, { values } from "lodash";
import { Row, Col, FormInstance, Spin } from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { getAllManagers, getWorkflowPermittedManagers } from '@/services/user';
import { queryContextData, getWorkflowActions, getWorkflows, getWorkflowStates } from '@/services/workflowServices';


export type CreateFormProps = {
    model: Partial<ModelType>;
    values: {};
    setValues: (values: any) => void;
    addGroupFormVisible: boolean;
    editGroupFormVisible: boolean;
    isEditView: boolean;
    form: FormInstance;
    isEdit: boolean;
};
  

const TransitionForm: React.FC<CreateFormProps> = (props) => {
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [model, setModel] = useState<any>();
    const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
    const [workflowOptions, setWorkflowOptions] = useState([])
    const [workflowActionOptions, setWorkflowActionOptions] = useState([])
    const [preStatesOptions, setPreStatesOptions] = useState([])
    const [postStatesOptions, setPostStatesOptions] = useState([])
    const [permissionTypeOptions, setPermissionTypeOptions] = useState([])
    const [permittedRolesOptions, setPermittedRolesOptions] = useState([])
    const [employeeOptions, setEmployeeOptions] = useState([])
    const [loading, setLoading] = useState<boolean>(false);
   

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

        data.forEach((element: { [x: string]: any; }) => {
            arr.push({ value: element[valueField], label: element[labelField] })
        });
        return arr
    }

    const getWorkflowPermittedEmployees = async (workflowId: any) => {
        let params = {
            workflowId: workflowId
        }
        const employees = await getWorkflowPermittedManagers(params);
        if (employees.data) {
            setEmployeeOptions(convertToOptions(employees.data, "id", "employeeName"))
        }


    }
    const getOptions = async () => {
        try {
            setLoading(true);
            const workflowData = await getWorkflows()
            if (workflowData.data) {
                await setWorkflowOptions(convertToOptions(workflowData.data, "id", "workflowName"))
            }
            const workflowActionData = await getWorkflowActions()
            if (workflowActionData.data) {
                await setWorkflowActionOptions(convertToOptions(workflowActionData.data, "id", "actionName"))
            }
            const workflowStateData = await getWorkflowStates()
            if (workflowStateData.data) {
                await setPreStatesOptions(convertToOptions(workflowStateData.data, "id", "stateName"))
                await setPostStatesOptions(convertToOptions(workflowStateData.data, "id", "stateName"))
            }

        
            const permissionTypes = [
                {
                    value : 'ROLE_BASE',
                    label : 'User Roles Base'   
                },
                {
                    value : 'EMPLOYEE_BASE',
                    label : 'Employee Base'  
                }
            ];
            setPermissionTypeOptions(permissionTypes);

            const permittedRoles = [
                {
					value: 1,
					label: "Admin",
				},
				{
					value: 2,
					label: "Employee",
				},
				{
					value: 3,
					label: "Manager",
				},
				{
					value: 4,
					label: "Managers Manager",
				}
            ]
            setPermittedRolesOptions(permittedRoles);

            if (props.isEdit) {
                getWorkflowPermittedEmployees(props.values['workflowId']);
            }

            setLoading(false);

        }
        catch (err) {
            console.error(err)
        }
    }

    

    return (
        <>
            {
                !loading ?  (
                    <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
                <Col span={12}>
                    <ProFormSelect
                        options={workflowOptions}
                        width="md"
                        showSearch
                        name='workflowId'
                        label='Workflow Name'
                        disabled={false}
                        placeholder={'Select Workflow'}
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
                                console.log(value);
                                
                                const currentValues = {...props.values};
                                currentValues['workflowId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                props.setValues(currentValues);
                                if (!_.isNull(value) && !_.isUndefined(value)) {
                                    getWorkflowPermittedEmployees(value);
                                } else {
                                    setEmployeeOptions([]);
                                }
                                
                            }
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormSelect
                        options={workflowActionOptions}
                        width="md"
                        showSearch
                        name='actionId'
                        label='Action Name'
                        disabled={false}
                        placeholder={'Select Workflow Action'}
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
                                currentValues['actionId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                props.setValues(currentValues);
                            }
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormSelect
                        options={preStatesOptions}
                        width="md"
                        showSearch
                        name='priorStateId'
                        label='Prior State Name'
                        disabled={false}
                        placeholder={'Select Prior State'}
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
                                currentValues['priorStateId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                props.setValues(currentValues);
                            }
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormSelect
                        options={postStatesOptions}
                        width="md"
                        showSearch
                        name='postStateId'
                        label='Post State Name'
                        disabled={false}
                        placeholder={'Select Post State'}
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
                                currentValues['postStateId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                props.setValues(currentValues);
                            }
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormSelect
                        options={permissionTypeOptions}
                        width="md"
                        showSearch
                        name='permissionType'
                        label='Permission Type'
                        disabled={false}
                        placeholder={'Select Permission Type'}
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
                                currentValues['permissionType'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;

                                if (value == 'ROLE_BASE') {
                                    currentValues['permittedEmployees'] = [];
                                } else if (value == 'EMPLOYEE_BASE') {
                                    currentValues['permittedRoles'] = [];
                                }

                                props.setValues(currentValues);

                            }
                        }}
                        initialValue={null}
                    />
                </Col>
                {
                    props.form.getFieldValue('permissionType') == 'ROLE_BASE' ? (
                        <Col span={12}>
                            <ProFormSelect
                                options={permittedRolesOptions}
                                width="md"
                                showSearch
                                name='permittedRoles'
                                label='Permitted Roles'
                                disabled={false}
                                placeholder={'Select User Role'}
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
                                    mode :"multiple",
                                    onChange: (value) => {
                                        const currentValues = {...props.values};
                                        currentValues['permittedRoles'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                        props.setValues(currentValues);
                                    }
                                }}
                                initialValue={[]}
                            />
                        </Col>

                    ) : (
                        <></>
                    )
                }

                {
                    props.form.getFieldValue('permissionType') == 'EMPLOYEE_BASE' ? (
                        <Col span={12}>
                            <ProFormSelect
                                options={employeeOptions}
                                width="md"
                                showSearch
                                name='permittedEmployees'
                                label='Permitted Employees'
                                disabled={false}
                                placeholder={'Select Employee'}
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
                                    mode :"multiple",
                                    onChange: (value) => {
                                        const currentValues = {...props.values};
                                        currentValues['permittedEmployees'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                        props.setValues(currentValues);
                                    }
                                }}
                                initialValue={[]}
                            />
                        </Col>

                    ) : (
                        <></>
                    )
                }
                
            </Row>
                ) : (
                   <Col style={{ alignItems: 'center' }}><Spin ></Spin></Col>
                )
            } 
        </>
    );
};

export default TransitionForm;
