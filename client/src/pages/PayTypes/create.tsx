import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import _, { values } from "lodash";
import { Row, Col, FormInstance, Input, Tooltip, Button, Form, Space, Radio } from 'antd';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { ProFormText, ProFormFieldSet, ProFormRadio, ProFormDigit } from "@ant-design/pro-form";
import { generateProFormFieldValidation } from "@/utils/validator";
import { SketchPicker,  } from 'react-color';
import { BgColorsOutlined } from '@ant-design/icons';



export type CreateFormProps = {
    model: Partial<ModelType>;
    values: {};
    setValues: (values: any) => void;
    addDayTypeFormVisible: boolean;
    editDayTypeFormVisible: boolean;
    form: FormInstance;
};
  

const CreateUser: React.FC<CreateFormProps> = (props) => {
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [model, setModel] = useState<any>();
    const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
    const [color, setColor] = useState('#000000');
    const [iconColor, setIconColor] = useState('#000000');
    const [colorCode, setColorCode] = useState('');
    const [showColorPicker, setShowColorPicker] = useState<boolean>(false);
    const [isOverTimeSelected, setIsOverTimeSelected] = useState<boolean>(false);

   
    useEffect(() => {
        if (_.isEmpty(model)) {
            getModel(Models.User).then((response) => {
            const userModel = response.data;
            setModel(userModel);
            })
        }
    }, []);

    const getRules = (fieldName:any) => {
        if (props.addDayTypeFormVisible || props.editDayTypeFormVisible) {
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
        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
            <Col span={24}>
                <ProFormText
                    width="md"
                    name='name'
                    label= 'Pay Type Name'
                    rules={getRules('name')}
                    fieldProps={{
                    onChange: (value) => {
                        const currentValues = {...props.values};
                        currentValues['name'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                        props.setValues(currentValues);
                    },
                    autoComplete: "none"
                    }}
                    initialValue={null}
                />
            </Col>
            <Col span={24}>
                <ProFormText
                    width={185}
                    name='code'
                    label= 'Code'
                    rules={getRules('code')}
                    fieldProps={{
                    onChange: (value) => {
                        const currentValues = {...props.values};
                        currentValues['code'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                        props.setValues(currentValues);
                    },
                    autoComplete: "none"
                    }}
                    initialValue={null}
                />
            </Col>
            <Col span={24}>
                <ProFormRadio.Group
                    name="type"
                    label="Type"
                    radioType="radio"
                    // rules={getRules('type')}
                    initialValue={'GENERAL'}
                    style={{ borderRadius: 10 }}
                    options={[
                        {
                            label: 'General',
                            value: 'GENERAL',
                        },
                        {
                            label: 'Overtime',
                            value: 'OVERTIME',
                        },
                    ]}

                    fieldProps={{
                        onChange: (value) => {
                            console.log(value);
                            if (value.target.value == 'OVERTIME') {
                                setIsOverTimeSelected(true);
                            } else {
                                setIsOverTimeSelected(false);
                            }
                            const currentValues = {...props.values};
                            currentValues['type'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                            currentValues['rate'] = null;
                            props.form.setFieldsValue({'rate': null});
                            props.setValues(currentValues);
                        },
                        
                    }}
                    
                />

            </Col>
            <Col span={12}>
                {
                    isOverTimeSelected ? (
                        <ProFormDigit
                            width= {120}
                            name={'rate'}
                            label={'Rate'}
                            placeholder={'Select rate'}
                            rules={getRules('rate')}
                            fieldProps={{
                                onChange: (value: any) => {
                                    if (value) {
                                        let regex = /^(?:\d*\.\d{1,2}|\d+)$/;
                                        
                                        if (!regex.test(value)) {
                                            props.form.setFields([{
                                                    name: 'rate',
                                                    errors: ['only 2 decimal places allowed'] 
                                                }
                                            ]);
                                        } else {
                                            props.form.setFields([{
                                                    name: 'rate',
                                                    errors: [] 
                                                }
                                            ]);
                                        }
                                    } else {
                                        props.form.setFields([{
                                                name: 'rate',
                                                errors: [] 
                                            }
                                        ]);
                                    }
                                    
                                    const currentValues = {...props.values};
                                    currentValues['rate'] = !_.isNull(value) && !_.isUndefined(value) ? value : 0;
                                    props.setValues(currentValues);
                                },
                                type:"number"
                            }}
                            initialValue={null}
                        />
                    ) : (
                        <></>
                    )
                }  
            </Col>
        </Row>
    );
};

export default CreateUser;
