import { ProFormDependency, ProFormDigit, ProFormGroup, ProFormList, ProFormSelect, ProFormSwitch, ProFormText } from '@ant-design/pro-form';
import { Divider } from 'antd';
import { ReactComponent as TextField } from '../../assets/formbuilder/textField.svg';
import { getAllDynamicForm } from '@/services/dynamicForm';
import { getModel } from '@/services/model';

export default {
    string: {
        key: 'string',
        title: 'Text Field',
        icon: TextField,
        config: {
            name: "textField",
            defaultLabel: "Text Field ",
            labelKey: "TEXT_FIELD_",
            type: "string",
            defaultValue: null,
            validations: {
                isRequired: false,
                isUnique: false,
                isWhitespace: false,
                min: 0,
                max: 255
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
            <ProFormSwitch name={['validations', 'isUnique']} label="Unique" />
            <ProFormSwitch name={['validations', 'isWhitespace']} label="Allow White Spaces" />
            <ProFormDigit name={['validations', 'min']} label="Min (Character Count )" />
            <ProFormDigit name={['validations', 'max']} label="Max (Character Count )" />
        </div>
    },
    // employeeNumber: {
    //     key: 'employeeNumber',
    //     title: 'Employee Number',
    //     icon: TextField,
    //     config: {
    //         name: "employeeNumber",
    //         defaultLabel: "Employee Number ",
    //         labelKey: "EMPLOYEE_NUMBER_",
    //         type: "employeeNumber",
    //         validations: {
    //             isRequired: false,
    //             isUnique: false
    //         }
    //     },
    //     configForm: <div>
    //         <ProFormText
    //             name="defaultLabel"
    //             label="Label"
    //             rules={[
    //                 { required: true, message: 'Please enter field title!' }
    //             ]}
    //         />
    //         <Divider orientation="left">Validation</Divider>
    //         <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
    //         <ProFormSwitch name={['validations', 'isUnique']} label="Unique" />
    //     </div>
    // },
    timestamp: {
        key: 'timestamp',
        title: 'Date And Time Picker',
        icon: TextField,
        config: {
            name: "dateAndTime",
            defaultLabel: "Date And Time ",
            labelKey: "DATE_AND_TIME_",
            type: "timestamp",
            defaultValue: null,
            validations: {
                isRequired: false,
                maxDate: "today"
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    enum: {
        key: 'enum',
        title: 'Fixed Option Selector',
        icon: TextField,
        config: {
            name: "fixedOption",
            defaultLabel: "Fixed Option ",
            labelKey: "FIXED_OPTION_",
            type: "enum",
            defaultValue: null,
            values: [],
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    modelHasOne: {
        key: 'modelHasOne',
        title: 'Model-base Selector',
        icon: TextField,
        config: {
            name: "modelBaseSelector",
            defaultLabel: "Model-base Selector ",
            labelKey: "MODEL_BASE_SELECTOR_",
            type: "modelHasOne",
            modelName: null,
            enumValueKey: "id",
            enumLabelKey: "name",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormSelect
                name="modelName"
                label="Model"
                request={async () => {
                    const models = await getAllDynamicForm();
                    return models.error
                        ? []
                        : models.data.map(model => {
                            return {
                                value: model.modelName,
                                label: model.title
                            };
                        });
                }}
            />
            <ProFormDependency name={['modelName']}>
                {({ modelName }) => modelName && <>
                    <ProFormSelect
                        name="enumValueKey"
                        label="Selector Value Key"
                        request={async () => {
                            const dependencyModel = await getModel(modelName);
                            return dependencyModel.error
                                ? []
                                : Object.values(dependencyModel?.data?.modelDataDefinition?.fields ?? {})
                                    .filter(field => field.name == 'id' || (field.type != 'model' && !field.isSystemValue && !field.isNonRecordableField))
                                    .map(field => {
                                        return {
                                            value: field.name,
                                            label: field.defaultLabel
                                        };
                                    });
                        }}
                    />
                    <ProFormSelect
                        name="enumLabelKey"
                        label="Selector Label Key"
                        request={async () => {
                            const dependencyModel = await getModel(modelName);
                            return dependencyModel.error
                                ? []
                                : Object.values(dependencyModel?.data?.modelDataDefinition?.fields ?? {})
                                    .filter(field => field.name == 'id' || (field.type != 'model' && !field.isSystemValue && !field.isNonRecordableField))
                                    .map(field => {
                                        return {
                                            value: field.name,
                                            label: field.defaultLabel
                                        };
                                    });
                        }}
                    />
                </>}
            </ProFormDependency>
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    modelHasMany: {
        key: 'modelHasMany',
        title: 'Model-base Multi-Record Table',
        icon: TextField,
        config: {
            name: "modelBaseSelector",
            defaultLabel: "Model-base Multi-Record Table ",
            labelKey: "MODEL_BASE_SELECTOR_",
            type: "model",
            modelName: null,
            enumValueKey: "id",
            enumLabelKey: "name",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormSelect
                name="modelName"
                label="Model"
                request={async () => {
                    const models = await getAllDynamicForm();
                    return models.error
                        ? []
                        : models.data.map(model => {
                            return {
                                value: model.modelName,
                                label: model.title
                            };
                        });
                }}
            />
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    textArea: {
        key: 'textArea',
        title: 'Text Area',
        icon: TextField,
        config: {
            name: "textArea",
            defaultLabel: "Text Area ",
            labelKey: "TEXT_AREA_",
            type: "textArea",
            defaultValue: null,
            validations: {
                min: 0,
                max: 255
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    timeZone: {
        key: 'timeZone',
        title: 'TimeZone',
        icon: TextField,
        config: {
            name: "timeZone",
            defaultLabel: "TimeZone ",
            labelKey: "TIMEZONE_",
            type: "timeZone",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    switch: {
        key: 'switch',
        title: 'Switch',
        icon: TextField,
        config: {
            name: "switch",
            defaultLabel: "Switch ",
            labelKey: "SWITCH_",
            type: "switch",
            defaultValue: null
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    radio: {
        key: 'radio',
        title: 'Radio',
        icon: TextField,
        config: {
            name: "radio",
            defaultLabel: "Radio ",
            labelKey: "RADIO_",
            type: "radio",
            defaultValue: null,
            options: [
                {
                    defaultLabel: "Option 01",
                    labelKey: "OPTION_01",
                    value: "option01"
                },
                {
                    defaultLabel: "Option 02",
                    labelKey: "OPTION_02",
                    value: "option02"
                }
            ],
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    checkbox: {
        key: 'checkbox',
        title: 'Checkbox',
        icon: TextField,
        config: {
            name: "checkbox",
            defaultLabel: "Checkbox ",
            labelKey: "CHECKBOX_",
            type: "checkbox",
            defaultValue: null,
            values: [
                {
                    value: "option01",
                    labelKey: "OPTION_01",
                    defaultLabel: "Option 01",
                },
                {
                    value: "option02",
                    labelKey: "OPTION_02",
                    defaultLabel: "Option 02",
                },
                {
                    value: "option03",
                    labelKey: "OPTION_03",
                    defaultLabel: "Option 03",
                }
            ],
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    currency: {
        key: 'currency',
        title: 'Currency',
        icon: TextField,
        config: {
            name: "currency",
            defaultLabel: "Currency ",
            labelKey: "CURRENCY_",
            type: "currency",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    tag: {
        key: 'tag',
        title: 'Tag',
        icon: TextField,
        config: {
            name: "tag",
            defaultLabel: "Tag ",
            labelKey: "TAG_",
            type: "tag",
            modelName: "gender",
            enumValueKey: "id",
            enumLabelKey: "name",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    number: {
        key: 'number',
        title: 'Number',
        icon: TextField,
        config: {
            name: "number",
            defaultLabel: "Number ",
            labelKey: "NUMBER_",
            type: "number",
            defaultValue: null,
            validations: {
                isRequired: false,
                min: 1,
                max: 10
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    phone: {
        key: 'phone',
        title: 'Phone',
        icon: TextField,
        config: {
            name: "phone",
            defaultLabel: "Phone ",
            labelKey: "PHONE_",
            type: "phone",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    label: {
        key: 'label',
        title: 'Label',
        icon: TextField,
        config: {
            name: "label",
            defaultLabel: "Label ",
            labelKey: "LABEL_",
            type: "label",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    },
    month: {
        key: 'month',
        title: 'Month Picker',
        icon: TextField,
        config: {
            name: "month",
            defaultLabel: "Month Picker ",
            labelKey: "MONTH_PICKER_",
            type: "month",
            defaultValue: null,
            validations: {
                isRequired: false
            }
        },
        configForm: <div>
            <ProFormText
                name="defaultLabel"
                label="Label"
                rules={[
                    { required: true, message: 'Please enter field title!' }
                ]}
            />
            <ProFormText
                name="defaultValue"
                label="Default Value"
            />
            <ProFormList
                name="values"
                label="Options"
                creatorButtonProps={{
                    creatorButtonText: 'Add new option'
                }}
                copyIconProps={false}
            >
                <ProFormGroup key="group" >
                    <ProFormText name="defaultLabel" label="" />
                </ProFormGroup >
            </ProFormList >
            <Divider orientation="left">Validation</Divider>
            <ProFormSwitch name={['validations', 'isRequired']} label="Required" />
        </div>
    }
}
