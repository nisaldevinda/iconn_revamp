import _ from "lodash";
import React, { useEffect, useState } from "react";
import OrgSelector from "../OrgSelector";

export type OrgSelectorFormInputProps = {
  modelName: string,
  fieldName: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: string,
    isEditable: string,
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
  recentlyChangedValue: any,
  permission: any
};

const OrgSelectorFormInput: React.FC<OrgSelectorFormInputProps> = (props) => {
  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;

  const _value = () => (props.values[fieldName]) ?  props.values[fieldName] : 1;

  useEffect(() => {
    if (props.values.id == null) {
      let currentValues = { ...props.values };
      currentValues[fieldName] = 1;
      props.setValues(currentValues);
      console.log('currentValues', currentValues);
    }
  }, [props.values.id]);

  const _setValue = (input) => {
    let currentValues = { ...props.values };
    currentValues[fieldName] = input;
    props.setValues(currentValues);
    console.log('currentValues', currentValues);
  }

  const hasEditPermission = (): boolean => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    return props?.permission['employee']?.canEdit.includes('employeeJourney');
  }

  return (
    <OrgSelector
      value={_value()}
      setValue={_setValue}
      readOnly={!hasEditPermission()}
      className="org-select-form-input pro-field-md"
    />
  );
};

export default OrgSelectorFormInput;
