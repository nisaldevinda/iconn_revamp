import { generateProFormFieldValidation } from "@/utils/validator";
import { ProFormDigit } from "@ant-design/pro-form";
import _ from "lodash";
import { Col } from "antd";
import React, { useEffect, useState } from "react";
import { useIntl } from "react-intl";
import { RelationshipType } from "@/utils/model";

export type NumberFieldProps = {
  modelName: string,
  fieldName: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: number,
    isEditable: string,
    isSystemValue: string,
    showOn: Array<any>,
    validations: {
      isRequired: boolean,
      min: number,
      max: number,
      isDecimal: boolean
    },
    placeholderKey: string,
    defaultPlaceholder: string,
    defaultValue: number,
  },
  readOnly: boolean;
  values: {},
  setValues: (values: any) => void,
  recentlyChangedValue: any
};

const NumberField: React.FC<NumberFieldProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });
  const allowDecimal = props.fieldDefinition.validations && props.fieldDefinition.validations.isDecimal;
  const isAllowPrecision = props.fieldDefinition.validations && props.fieldDefinition.validations.precision;

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
        <ProFormDigit
          width="md"
          name={fieldName}
          label={label}
          disabled={props.readOnly}
          placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
            ? intl.formatMessage({
              id: props.fieldDefinition.placeholderKey,
              defaultMessage: props.fieldDefinition.defaultPlaceholder,
            })
            : ''}
          rules={generateProFormFieldValidation(
            props.fieldDefinition,
            props.modelName,
            props.fieldName,
            props.values
          )}

          fieldProps={{
            type: "number",
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
              props.setValues(currentValues);
            },
            autoComplete: "none",
            step: allowDecimal ? "0.01" : '',
            precision: isAllowPrecision ? props.fieldDefinition.validations.precision : undefined,
            onKeyDown: (evt) => ((evt.key === 'e') && evt.preventDefault())
          }}
          initialValue={props.fieldDefinition.defaultValue}
        />
      </Col>
      : <></>
  );
};

export default NumberField;
