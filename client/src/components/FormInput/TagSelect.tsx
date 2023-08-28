import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormSelect, ProFormTextArea } from "@ant-design/pro-form";
import { Col } from "antd";
import React from "react";
import { useIntl } from "react-intl";
import _ from "lodash";
import { getModel, ModelType } from "@/services/model";
import request, { APIResponse } from "@/utils/request";

export type TextAreaProps = {
    modelName: string,
    fieldName: string,
    fieldNamePrefix?: string;
    fieldDefinition: {
        labelKey: string,
        defaultLabel: string,
        type: string,
        isEditable: string,
        enumValueKey: string,
        enumLabelKey: string,
        isSystemValue: string,
        validations: {
            isRequired: boolean,
            min: number,
            max: number
        },
        placeholderKey: string,
        defaultPlaceholder: string,
        defaultValue: string,
    },
    readOnly: boolean;
    values: {},
    setValues: (values: any) => void,
    recentlyChangedValue: any
};

const TagSelect: React.FC<TextAreaProps> = (props) => {
    const intl = useIntl();
    const fieldName = props.fieldNamePrefix
        ? props.fieldNamePrefix.concat(props.fieldName)
        : props.fieldName;
    const label = intl.formatMessage({
        id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
        defaultMessage: props.fieldDefinition.defaultLabel,
    });

    const getActions = async () => {
        const actions: any = []
        const response = await getModel(props.fieldDefinition.modelName)
        let path: string

        if (!_.isEmpty(response.data)) {
            path = `/api${response.data.modelDataDefinition.path}`;
        }
        const res = await request(path)

        await res.data.forEach(async (element: any) => {
            await actions.push({ value: element[props.fieldDefinition.enumValueKey], label: element[props.fieldDefinition.enumLabelKey] });

        });

        return actions;

    }


    return (
        <Col data-key={fieldName} span={12}>
            <ProFormSelect
                width="md"
                name={fieldName}
                label={label}
                mode="multiple"
                disabled={props.readOnly}
                placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
                    ? intl.formatMessage({
                        id: props.fieldDefinition.placeholderKey,
                        defaultMessage: props.fieldDefinition.defaultPlaceholder,
                    })
                    : 'Select '.concat(label)}
                request={ props.fieldDefinition.values ? async () => props.fieldDefinition.values.map(value => {
                    return {
                      label: intl.formatMessage({
                        id: `model.${props.modelName}.${props.fieldName}.${value.labelKey}`,
                        defaultMessage: value.labelKey,
                      }),
                      value: value.value,
                    };
                  }) : getActions}
                rules={generateProFormFieldValidation(
                    props.fieldDefinition,
                    props.modelName,
                    props.fieldName,
                    props.values
                )}
                fieldProps={{
                    onChange: (value) => {
                        const currentValues = { ...props.values };

                        currentValues[props.fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                        props.setValues(currentValues);
                    }
                }}

            />


        </Col>
    );
};

export default TagSelect;
