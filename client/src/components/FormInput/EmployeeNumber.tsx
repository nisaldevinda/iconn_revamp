import { ProFormText } from '@ant-design/pro-form';
import _ from 'lodash';
import { Col, FormInstance } from 'antd';
import React, { useEffect, useState } from 'react';
import { useIntl } from 'react-intl';
// import { getEmployeeMetaId } from '@/services/employee';
// import { generateProFormFieldValidation } from "@/utils/validator";
// import { getEmployeeNumberFormat } from '@/services/employee';
export type TextFieldProps = {
  modelName: string;
  fieldName: string;
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string;
    defaultLabel: string;
    type: string;
    isEditable: string;
    isSystemValue: string;
    validations: {
      isRequired: boolean;
      min: number;
      max: number;
    };
    placeholderKey: string;
    defaultPlaceholder: string;
    defaultValue: string;
  };
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
  recentlyChangedValue: any;
  form: FormInstance;
};

const TextField: React.FC<TextFieldProps> = (props) => {

  const intl = useIntl();
  // const [employeeNumber, setEmployeeNumber] = useState<string>('');
  // const [currentValue, setCurrentValue] = useState<string>('');
  // const [empNumberFormat , setEmpNumberFormat] = useState([]);
  // const [validatedStatus, setValidateStatus] = useState<string>('');
  // const [help, setHelp] = useState('');
  // const isRequired = props.fieldDefinition.validations?.isRequired;

  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });

  // useEffect(() => {

  //   if (_.isNull(props.values[fieldName])) {
  //     getEmployeeMetaId().then((res: any) => {
  //       if (_.isUndefined(res.data) || !_.isNull(res.data)) {
  //         setEmployeeNumber(res.data);
  //       }
  //     });
  //   }

  //   getEmployeeNumberFormat().then ((response) => {
  //     setEmpNumberFormat(response.data);
  //   })
  // },[employeeNumber]);

  // useEffect(() => {
  //   const currentValues = {...props.values};
  //   currentValues[fieldName] = _.isNull(currentValues[fieldName]) || _.isEmpty(currentValues[fieldName]) ? employeeNumber : currentValues[fieldName]
  //   setCurrentValue(currentValues[fieldName]);
  //   props.setValues(currentValues);
  // }, [employeeNumber]);

  // const generateEmployeeNumberFieldValidation = (
  //   fieldDefinition: any,
  //   modelName: string,
  //   fieldName: string,
  //   values: any,
  //   empNumberFormat: any
  // ): Array<any> => {
  //   const intl = useIntl();
  //   let validations: Array<any> = [];
  //   const type = fieldDefinition.type;
  //   const modelFieldValidation = fieldDefinition.validations;
  //   const regexExp = new RegExp(`^${empNumberFormat['prefix']}[0-9]{${empNumberFormat['length']}}$`);


  //   if (modelFieldValidation) {
  //     // required
  //     if (modelFieldValidation.isRequired) {
  //       validations.push({
  //         required: true,
  //         message: intl.formatMessage({
  //           id: `model.${modelName}.${fieldName}.rules.required`,
  //           defaultMessage: `Required`,
  //         }),
  //       });

  //       if (empNumberFormat.length > 0 ) {
  //          validations.push({
  //           pattern: regexExp,
  //           message: intl.formatMessage({
  //             id: `model.${modelName}.${fieldName}.rules.format`,
  //             defaultMessage: `Format should be ${empNumberFormat['employeeNumber']}`,
  //           }),
  //         });
  //       }
  //     }
  //   }

  //   return validations;
  // };

  return (
    <Col data-key={fieldName} span={12}>
      <ProFormText
        width="md"
        disabled={true}
        // disabled={props.readOnly || (empNumberFormat.length !== 0 && empNumberFormat['prefix'] !== '')}
        name={fieldName}
        label={label}
        placeholder={intl.formatMessage({
          id: 'employee_profile.employee_number.auto_generated_placeholder',
          defaultMessage: 'Auto Generated Value',
        })}
        // required={isRequired}
        // rules={generateEmployeeNumberFieldValidation(
        //   props.fieldDefinition,
        //   props.modelName,
        //   props.fieldName,
        //   props.values,
        //   empNumberFormat
        // )}
        fieldProps={{
          autoComplete: 'none',
          value: props.values['employeeNumber'],
          onChange: (value) => {
            const currentValues = { ...props.values };
            currentValues[fieldName] = !_.isNull(value.target.value) && !_.isUndefined(value.target.value) ? value.target.value : null;
            props.setValues(currentValues);
          },

        }}
        initialValue={props.fieldDefinition.defaultValue}
      />

    </Col>
  );
};

export default TextField;
