import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import _, { values } from "lodash";
import { Row, Col, FormInstance, Alert} from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { ProFormText } from "@ant-design/pro-form";
import { generateProFormFieldValidation } from "@/utils/validator";

export type EditFormProps = {
    model: Partial<ModelType>;
    values: {};
    setValues: (values: any) => void;
    addUserFormVisible: boolean;
    editUserFormVisible: boolean;
    employees: any;
    employeeRoles: any;
    managerRoles: any;
    adminRoles: any;
    employeeChanged: any
    setEmployeeChange:(employeeChanged: any) => void;
    form: FormInstance;
    unAssignEmployees: any;
    
};
  

const EditUser: React.FC<EditFormProps> = (props) => {
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [model, setModel] = useState<any>();
    const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
    const [changeLinkedEmployee, setChangeLinkedEmployee] = useState<boolean>(false);
    

    useEffect(() => {
        if (_.isEmpty(model)) {
            getModel(Models.User).then((response) => {
            const userModel = response.data;
            if (!hasGlobalAdminPrivileges()) {
                // remove admin role field & relation if not a global admin
                setIsGlobalAdmin(false);
                delete userModel.modelDataDefinition.fields.adminRole;
                delete userModel.modelDataDefinition.relations.adminRole;
            } else {
                setIsGlobalAdmin(true);
            }
            setModel(userModel);
            })
        }
    }, []);

    const getRules = (fieldName:any) => {
        if (props.addUserFormVisible || props.editUserFormVisible) {
            return generateProFormFieldValidation(
                props.model.modelDataDefinition.fields[fieldName],
                'user',
                fieldName,
                props.values
            );
        } else {
            return [];
        }
        
    }

    return (
        <Row>
            {
                changeLinkedEmployee ? (
                    <Row style={{marginBottom: 20}}>
                        <Alert
                            message="If You Change Employee, User's Email Address, First Name, Middle Name,Last Name is Set to Employee's Work Email Address, First Name, Middle Name, Last Name"
                            type="warning"
                            closable
                        />
                    </Row>
                ) : (
                    <></>
                )
            }
            
            <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
                <Col span={12}>
                    <ProFormText
                        width="md"
                        name='email'
                        label= 'Email'
                        disabled={props.employeeChanged}
                        placeholder={
                        intl.formatMessage({
                            id: 'USER.EMAIL',
                            defaultMessage: 'john@abc.com',
                        })}
                        rules={getRules('email')}
                        fieldProps={{
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['email'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            props.setValues(currentValues);
                        },
                        autoComplete: "none"
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormSelect
                        options={props.employees}
                        width="md"
                        showSearch
                        name='employeeId'
                        label='Employee'
                        disabled={false}
                        placeholder={'Select Employee'}
                        rules={getRules('employee')}
                        fieldProps={{
                        mode:  undefined,
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['employeeId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                            
                            if (!_.isNull(value) && !_.isUndefined(value) ) {
                                setChangeLinkedEmployee(true);
                                const employeeEmailArray = props.unAssignEmployees.filter(function (el){
                                    return (el['id'] == value);
                                });
                                const employeeFirstName = (_.isArray(employeeEmailArray) && employeeEmailArray.length > 0) ? employeeEmailArray[0]['firstName'] : undefined;
                                const employeeMiddleName = (_.isArray(employeeEmailArray) && employeeEmailArray.length > 0) ? employeeEmailArray[0]['middleName'] : undefined;
                                const employeeLastName = (_.isArray(employeeEmailArray) && employeeEmailArray.length > 0) ? employeeEmailArray[0]['lastName'] : undefined;
                                const employeeEmail = (_.isArray(employeeEmailArray) && employeeEmailArray.length > 0) ? employeeEmailArray[0]['workEmail'] : undefined;
                                currentValues['email'] = employeeEmail;
                                currentValues['firstName'] = employeeFirstName;
                                currentValues['middleName'] = employeeMiddleName;
                                currentValues['lastName'] = employeeLastName;
                                props.form.setFieldsValue({ email: employeeEmail, firstName: employeeFirstName, middleName: employeeMiddleName, lastName: employeeLastName});
                                props.setEmployeeChange(true);
                            } else {
                                props.setEmployeeChange(false);
                                setChangeLinkedEmployee(false);
                            }
                            props.setValues(currentValues);
                        }
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormText
                        width="md"
                        name='firstName'
                        label= 'First Name'
                        disabled={props.employeeChanged}
                        rules={getRules('firstName')}
                        fieldProps={{
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['firstName'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            props.setValues(currentValues);
                        },
                        autoComplete: "none"
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormText
                        width="md"
                        name='middleName'
                        label= 'Middle Name'
                        disabled={props.employeeChanged}
                        rules={getRules('middleName')}
                        fieldProps={{
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['middleName'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            props.setValues(currentValues);
                        },
                        autoComplete: "none"
                        }}
                        initialValue={null}
                    />
                </Col>
                <Col span={12}>
                    <ProFormText
                        width="md"
                        name='lastName'
                        label= 'Last Name'
                        disabled={props.employeeChanged}
                        rules={getRules('lastName')}
                        fieldProps={{
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['lastName'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            props.setValues(currentValues);
                        },
                        autoComplete: "none"
                        }}
                        initialValue={null}
                    />
   
                </Col>
                {
                props.employeeChanged ? (
                    <>
                    <Col span={12}>
                        <ProFormSelect
                            options={props.employeeRoles}
                            width="md"
                            showSearch
                            name='employeeRoleId'
                            label='Employee Role'
                            disabled={false}
                            placeholder={'Select Employee Role'}
                            rules={getRules('employeeRole')}
                            fieldProps={{
                                mode:  undefined,
                                onChange: (value) => {
                                    const currentValues = {...props.values};
                                    currentValues['employeeRoleId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                    props.setValues(currentValues);
                                }
                            }}
                            initialValue={null}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            options={props.managerRoles}
                            width="md"
                            showSearch
                            name='managerRoleId'
                            label='Manager Role'
                            disabled={false}
                            placeholder={'Select Manager Role'}
                            rules={getRules('managerRole')}
                            fieldProps={{
                                mode:  undefined,
                                onChange: (value) => {
                                    const currentValues = {...props.values};
                                    currentValues['managerRoleId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                    props.setValues(currentValues);
                                }
                            }}
                            initialValue={null}
                        />
                    </Col>
                    </>

                ) : (
                    <></>
                )
                }

                {
                isGlobalAdmin ? (
                    <Col span={12}>
                        <ProFormSelect
                            options={props.adminRoles}
                            width="md"
                            showSearch
                            name='adminRoleId'
                            label='Admin Role'
                            disabled={false}
                            placeholder={'Select Admin Role'}
                            rules={getRules('adminRole')}
                            fieldProps={{
                            mode:  undefined,
                            onChange: (value) => {
                                const currentValues = {...props.values};
                                currentValues['adminRoleId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                                props.setValues(currentValues);
                            }
                            }}
                            initialValue={null}
                        />
                    </Col>
                ) : (
                    <></>
                )

                }
                
                <Col span={12}>
                    <ProFormSelect
                        width="md"
                        showSearch
                        name='inactive'
                        label='Status'
                        disabled={false}
                        placeholder={'Select Status'}
                        rules={getRules('inactive')}
                        fieldProps={{
                        mode:  undefined,
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['inactive'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                            props.setValues(currentValues);
                        }
                        }}
                        initialValue={null}
                        request={async () => props.model.modelDataDefinition.fields['inactive'].values.map(value => {
                        return {
                            label: intl.formatMessage({
                            id: `model.user.inactive.${value.labelKey}`,
                            defaultMessage: value.labelKey,
                            }),
                            value: value.value,
                        };
                        })}
                    />
                </Col>
            </Row>
        </Row>    
    );
};

export default EditUser;
