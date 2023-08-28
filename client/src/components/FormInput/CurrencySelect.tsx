import React from "react";
import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormSelect } from "@ant-design/pro-form";
import { Col } from "antd";
import { useIntl } from "react-intl";
import cc from 'currency-codes'


export type CurrencySelectorProps = {
    modelName: string,
    fieldName: string,
    fieldNamePrefix?: string;
    fieldDefinition: {
        labelKey: string,
        defaultLabel: string,
        type: string,
        isEditable: string,
        isSystemValue: string,
        values: Array<{
            value: string,
            labelKey: string,
        }>,
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
}

const CurrencySelector: React.FC<CurrencySelectorProps> = (props) => {
    const intl = useIntl();
    const fieldName = props.fieldNamePrefix
        ? props.fieldNamePrefix.concat(props.fieldName)
        : props.fieldName;
    const label = intl.formatMessage({
        id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
        defaultMessage: props.fieldDefinition.defaultLabel,
    });

    return (
        <Col data-key={fieldName} span={12}>
            <ProFormSelect
                width="md"
                name={fieldName}
                label={label}
                disabled={props.readOnly}
                showSearch
                placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
                    ? intl.formatMessage({
                        id: props.fieldDefinition.placeholderKey,
                        defaultMessage: props.fieldDefinition.defaultPlaceholder,
                    })
                    : 'Select '.concat(label)}
                request={async () => cc.data.map((value, index) => {
                    return {
                        label: intl.formatMessage({
                            id: `model.${props.modelName}.${props.fieldName}.${value}`,
                            defaultMessage: value.currency.concat(" - ", value.code),
                        }),
                        value: value.code,
                    };
                })}
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
                initialValue={props.fieldDefinition.defaultValue}
            />
        </Col>
    )
}

export default CurrencySelector