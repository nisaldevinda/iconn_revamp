import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import _, { values } from "lodash";
import { Row, Col, FormInstance, Input, Tooltip, Button, Form, Space } from 'antd';
import { getAllDayTypes, getBaseDayTypes} from '@/services/workCalendarDayType';
import { useIntl } from 'react-intl';
import { hasGlobalAdminPrivileges } from '@/utils/permission'
import { Access, useAccess } from 'umi';
import { ProFormSelect } from '@ant-design/pro-form';
import { ProFormText, ProFormFieldSet } from "@ant-design/pro-form";
import { generateProFormFieldValidation } from "@/utils/validator";
import { SketchPicker,  } from 'react-color';
import { BgColorsOutlined } from '@ant-design/icons';
import './style.css';


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
    const [baseDayTypeOptions, setBaseDayTypeOptions] = useState([]);

    const handleChange = (color: any) => {
        setColor(color.hex.toUpperCase());
        setIconColor(color.hex.toUpperCase());
        const currentValues = {...props.values};
        currentValues['typeColor'] = !_.isNull(color.hex) && !_.isUndefined(color.hex) ? color.hex.toUpperCase() : null;
        props.setValues(currentValues);
        props.form.setFieldsValue({ typeColor: color.hex.toUpperCase()});
        setColorCode(color.hex.toUpperCase());
    };

    const popover = {
        position: 'absolute',
        zIndex: '2',
    }

    useEffect(() => {
        if (_.isEmpty(model)) {
            getModel(Models.User).then((response) => {
            const userModel = response.data;
            setModel(userModel);
            })
        }
        getOptions();
    }, []);

    const convertToOptions = (data, valueField: string, labelField: string) => {
        const arr: { value: string | number; label: string | number; disabled?: boolean; }[] = []

        data.forEach((element: { [x: string]: any; }) => {
            if (element[valueField] == 1 || element[valueField] == 2) {
                arr.push({ value: element[valueField], label: element[labelField], disabled: true })
            } else {
                arr.push({ value: element[valueField], label: element[labelField] })
            }
        });
        return arr
    }

    const getOptions = async () => {
        const baseDayTypes = await getBaseDayTypes();
        console.log(baseDayTypes);
        if (baseDayTypes.data) {
            await setBaseDayTypeOptions(convertToOptions(baseDayTypes.data, "id", "name"))
        }
    }

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

    const handleClose = (fieldName:any) => {
        setShowColorPicker(false);
    }

    const styles = {
        colorVal: {
            fontSize: '20px', color: iconColor
        },
        cover: {
          position: 'fixed',
          top: '0px',
          right: '0px',
          bottom: '0px',
          left: '0px',
        }
    };
    

    return (
        
        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
            <Col span={12}>
                <ProFormText
                    width="md"
                    name='name'
                    label= 'Day Type'
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
            <Col span={12}>
                <ProFormText
                    width="md"
                    name='shortCode'
                    label= 'Short Code'
                    rules={getRules('shortCode')}
                    fieldProps={{
                    onChange: (value) => {
                        const currentValues = {...props.values};
                        currentValues['shortCode'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                        props.setValues(currentValues);
                    },
                    autoComplete: "none"
                    }}
                    initialValue={null}
                />
            </Col>
            <Col span={12}>
                <div style={{display: 'flex'}}  className="color-input">
                    <ProFormText
                        width={195}
                        name='typeColor'
                        label= 'Color Code'
                        rules={getRules('typeColor')}
                        fieldProps={{
                            onChange: (value) => {
                                const currentValues = {...props.values};
                                currentValues['typeColor'] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
                                props.setValues(currentValues);

                                if (value.target.value) {
                                    let regex = /^#[0-9A-F]{6}$/i;

                                    if (!regex.test(value.target.value)) {
                                        props.form.setFields([{
                                                name: 'typeColor',
                                                errors: ['Invalid hex value'] 
                                            }
                                        ]);
                                    } else {
                                        props.form.setFields([{
                                                name: 'typeColor',
                                                errors: [] 
                                            }
                                        ]);
                                    }
                                    setColor(value.target.value);
                                    setIconColor(value.target.value);
                                } else {
                                    props.form.setFields([{
                                            name: 'typeColor',
                                            errors: [] 
                                        }
                                    ]);
                                    setColor('#000000');
                                    setIconColor('#000000');
                                }
                            },
                            autoComplete: "none"
                        }}
                        
                        initialValue={null}
                    />
                    <Button onClick={() => {
                            if (!showColorPicker) {
                                setShowColorPicker(true);
                            } else {
                                setShowColorPicker(false);
                            }
                        }} style={{borderTopLeftRadius: 0, borderBottomLeftRadius: 0, marginLeft: -4, marginTop: 30}} ><BgColorsOutlined style={styles.colorVal}  /></Button>
                        
                </div>

                {
                    showColorPicker ? (
                        <>
                            <div style={ styles.cover } onClick={ handleClose }/>
                            <div style={ popover }>
                                <SketchPicker color={color} onChange={handleChange} />
                            </div> 
                        </>

                    ) : (
                        <></>
                    )
                }
            </Col>
            <Col span={12}>
                <ProFormSelect
                    options={baseDayTypeOptions}
                    width="md"
                    showSearch
                    name='baseDayTypeId'
                    label='Base Day Type'
                    disabled={false}
                    placeholder={'Select Base Day Type'}
                    fieldProps={{
                        mode:  undefined,
                        onChange: (value) => {
                            const currentValues = {...props.values};
                            currentValues['baseDayTypeId'] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                            props.setValues(currentValues);
                        }
                    }}
                    initialValue={null}
                />
            </Col>
        </Row>
    );
};

export default CreateUser;
