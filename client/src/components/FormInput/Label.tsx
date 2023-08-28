import _ from "lodash";
import { Col, Space } from "antd";
import React, { useEffect, useState } from "react";
import { useIntl } from "react-intl";
import { Typography, Skeleton } from 'antd';
import CustomIcon from "../CustomIcon";
import { getEmploymentStatus } from "@/services/employmentStatus";

export type LabelProps = {
    modelName: string,
    fieldName: string,
    fieldNamePrefix?: string;
    fieldDefinition: {
        labelKey: string,
        defaultLabel: string,
        type: string,
        labelSpan?: number,
        icon: string,
        isEditable: string,
        isSystemValue: string,
        validations: {
            isRequired: boolean,
            min: number,
            max: number
        },
        isDynamicLabel?: boolean,
        dynamicLabelCondition?: any[],
        placeholderKey: string,
        defaultPlaceholder: string,
        defaultValue: string,
    },
    readOnly: boolean;
    values: {},
    setValues: (values: any) => void,
    recentlyChangedValue: any
};

const Label: React.FC<LabelProps> = (props) => {
    const [label, setLabel] = useState<string>();

    const { Text } = Typography;
    const intl = useIntl();
    const fieldName = props.fieldNamePrefix
        ? props.fieldNamePrefix.concat(props.fieldName)
        : props.fieldName;


    useEffect(() => {
        setupLabel();
    }, [])

    const setupLabel = async () => {
        let _label = intl.formatMessage({
            id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
            defaultMessage: props.fieldDefinition.defaultLabel,
        });

        if (props.fieldDefinition.isDynamicLabel && props.fieldDefinition.dynamicLabelCondition) {
            for (const index in props.fieldDefinition.dynamicLabelCondition) {
                const condition = props.fieldDefinition.dynamicLabelCondition[index];

                let dependentFieldValue;
                if (condition.dependentFieldName == 'currentJobs.employmentStatus.category') {
                    let currentJob = props.values?.jobs?.filter((job: any) => job.id == props.values?.currentJobsId);
                    currentJob = currentJob.length > 0 ? currentJob[0] : undefined;
                    if (!currentJob) continue;
                    const employmentStatus = await getEmploymentStatus(currentJob.employmentStatusId);
                    dependentFieldValue = employmentStatus?.data?.category;
                } else {
                    dependentFieldValue = props.values[condition.dependentFieldName];
                }

                let conditionStisfied = false;
                switch (condition.operator) {
                    case 'not_null':
                        if (!_.isUndefined(dependentFieldValue) || !_.isNull(dependentFieldValue)) {
                            conditionStisfied = true;
                        }
                        break;
                    case 'null':
                        if (_.isUndefined(dependentFieldValue) || _.isNull(dependentFieldValue)) {
                            conditionStisfied = true;
                        }
                        break;
                    case 'eq':
                        console.log('eq');
                        if (dependentFieldValue == condition.value) {
                            conditionStisfied = true;
                        }
                        break;
                    case 'gt':
                        if (dependentFieldValue > condition.value) {
                            conditionStisfied = true;
                        }
                        break;
                    case 'gte':
                        if (dependentFieldValue >= condition.value) {
                            conditionStisfied = true;
                        }
                        break;
                    case 'lt':
                        if (dependentFieldValue < condition.value) {
                            conditionStisfied = true;
                        }
                        break;
                    case 'lte':
                        if (dependentFieldValue <= condition.value) {
                            conditionStisfied = true;
                        }
                        break;
                    default:
                        break;
                }

                if (conditionStisfied) {
                    _label = intl.formatMessage({
                        id: `model.${props.modelName}.${condition.labelKey}`,
                        defaultMessage: condition.defaultLabel,
                    });

                    break;
                }
            }
        }

        console.log(_label);

        setLabel(_label);
    }

    return (
        <Col data-key={fieldName} span={props.fieldDefinition.labelSpan ?? 12}>
            {label ? <>
                <Space style={{ display: "inline-block", verticalAlign: "super", marginRight: 4 }}>
                    <CustomIcon icon={props.fieldDefinition.icon} width={16} height={16} />
                </Space>
                <label className="ant-form-item-label">
                    <Text type="secondary">{label.concat(': ')}</Text>
                    {props.values[fieldName] ?? '-'}
                </label>
            </> : <Skeleton paragraph={{ rows: 0 }} active />}
        </Col>
    );
};

export default Label;
