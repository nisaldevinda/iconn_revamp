import React from "react";
import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormSelect } from "@ant-design/pro-form";
import { Col } from "antd";
import { useIntl } from "react-intl";
import momentTZ from 'moment-timezone';
import _ from "lodash";

export type TimeZoneSelectorProps = {
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

const TimeZoneSelector: React.FC<TimeZoneSelectorProps> = (props) => {
    const intl = useIntl();
    const fieldName = props.fieldNamePrefix
        ? props.fieldNamePrefix.concat(props.fieldName)
        : props.fieldName;
    const label = intl.formatMessage({
        id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
        defaultMessage: props.fieldDefinition.defaultLabel,
    });
    const timeZoneList = momentTZ.tz.names();

    return (
        <Col data-key={fieldName} span={12}>
            <ProFormSelect
                showSearch
                width="md"
                name={fieldName}
                label={label}
                disabled={props.readOnly}
                placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
                    ? intl.formatMessage({
                        id: props.fieldDefinition.placeholderKey,
                        defaultMessage: props.fieldDefinition.defaultPlaceholder,
                    })
                    : 'Select '.concat(label)}
                request={async () => timeZoneList.map(value => {
                    return {
                        label: intl.formatMessage({
                            id: `model.${props.modelName}.${props.fieldName}.${value}`,
                            defaultMessage: value,
                        }),
                        value: value,
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
                        currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
                        props.setValues(currentValues);
                    }
                }}
                initialValue={props.fieldDefinition.defaultValue}
            />
        </Col>
    )
}

export default TimeZoneSelector