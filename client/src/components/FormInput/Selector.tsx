import { ModelType } from "@/services/model";
import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormSelect } from "@ant-design/pro-form";
import { Col } from "antd";
import _ from "lodash";
import React, { useEffect, useState } from "react";
import { useIntl } from "react-intl";

export type SelectorProps = {
  model: Partial<ModelType>,
  modelName: string,
  fieldName: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: string,
    isEditable: string,
    isSystemValue: string,
    showOn: Array<any>,
    values: Array<{
      value: string,
      labelKey: string,
      defaultLabel: string
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
};

const Selector: React.FC<SelectorProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });

  const [isShowOnField, setIsShowOnField] = useState(false);
  const [fieldVisible, setFieldVisible] = useState(true);

  useEffect(() => {
    init();
  }, []);

  useEffect(() => {
    onShowHandler();
  }, [isShowOnField, props.values]);

  const init = async () => {
    if (props.fieldDefinition.showOn
      && Array.isArray(props.fieldDefinition.showOn)
      && props.fieldDefinition.showOn.length > 0) {
      setIsShowOnField(true);
    }
  }

  const onShowHandler = () => {
    if (isShowOnField) {
      let _fieldVisible = true;

      props.fieldDefinition.showOn.map((condition) => {
        let { dependentFieldName, operator, value } = condition;
        let dependentFieldType = props.model.modelDataDefinition?.fields[dependentFieldName]?.type;
        let dependentFieldRelationship = props.model.modelDataDefinition?.relations[dependentFieldName];

        if (dependentFieldType == 'model' && dependentFieldRelationship == RelationshipType.HAS_ONE) {
          dependentFieldName = dependentFieldName.concat('Id');
        }

        if (props.fieldNamePrefix) {
          dependentFieldName = props.fieldNamePrefix.concat(dependentFieldName);
        }

        const dependentFieldValue = props.values ? props.values[dependentFieldName] : null;

        if (operator) {
          switch (operator.toLowerCase()) {
            case 'null':
              if (!_.isUndefined(dependentFieldValue) || !_.isNull(dependentFieldValue)) {
                _fieldVisible = false;
              }
              break;
            case 'not_null':
              if (_.isUndefined(dependentFieldValue) || _.isNull(dependentFieldValue)) {
                _fieldVisible = false;
              }
              break;
            case 'eq':
              if (dependentFieldValue != value) {
                _fieldVisible = false;
              }
              break;
            case 'gt':
              if (dependentFieldValue <= value) {
                _fieldVisible = false;
              }
              break;
            case 'gte':
              if (dependentFieldValue < value) {
                _fieldVisible = false;
              }
              break;
            case 'lt':
              if (dependentFieldValue >= value) {
                _fieldVisible = false;
              }
              break;
            case 'lte':
              if (dependentFieldValue > value) {
                _fieldVisible = false;
              }
              break;
            default:
              break;
          }
        }
      })

      if (fieldVisible != _fieldVisible) {
        setFieldVisible(_fieldVisible);
        if (!_fieldVisible) {
          resetFieldValue();
        }
      }
    }
  }

  const resetFieldValue = () => {
    const currentValues = { ...props.values };
    currentValues[fieldName] = props.fieldDefinition.defaultValue ?? null;
    props.setValues(currentValues);
  }

  return (
    fieldVisible
      ? <Col data-key={fieldName} span={12}>
        <ProFormSelect
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
          request={async () => props.fieldDefinition.values.map(value => {
            return {
              label: intl.formatMessage({
                id: `model.${props.modelName}.${props.fieldName}.${value.labelKey}`,
                defaultMessage: value.defaultLabel,
              }),
              value: value.value,
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
      : <></>
  );
};

export default Selector;
