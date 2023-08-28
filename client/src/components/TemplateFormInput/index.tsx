import React, { useEffect, useState } from 'react';
import { useIntl, useParams, history, useAccess, Access } from 'umi';
import { FormInstance, Spin, Col } from 'antd';
import { getModel, ModelType } from '@/services/model';
import TextField from './TextField';
import Selector from './Selector';
import TextArea from './TextArea';
import DatePicker from './DatePicker';
import TimePicker from './TimePicker';
import DynamicRadio from './DynamicRadio';
import DynamicCheckbox from './DynamicCheckbox';
import LinearScale from './LinearScale';
import MultipleChoiceGrid from './MultipleChoiceGrid';
import CheckBoxGrid from './CheckBoxGrid';

export type FormInputProps = {
  fieldName: string;
  readOnly?: boolean;
  answerDetails: any;
  answerType: any;
  form: FormInstance;
  values: {};
  setValues: (values: any) => void;
  //   errors: {};
  //   setErrors: (values: any) => void;
  //   formSubmit: (values: any) => void;
  //   tabularDataCreator?: (parentId: string, multirecordAttribute: string, data: any) => Promise<boolean | void>;
  //   tabularDataUpdater?: (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => Promise<boolean | void>;
  //   tabularDataDeleter?: (parentId: string, multirecordAttribute: string, multirecordId: number) => Promise<boolean | void>;
  //   recentlyChangedValue: any;
  //   scope?: string;
};

const FormInput: React.FC<FormInputProps> = (props) => {
  const [modelName, setModelName] = useState<string>();
  const [fieldDefinition, setfieldDefinition] = useState<any>();
  const intl = useIntl();
  useEffect(() => {
    let definition = {
      type: props.answerType,
      isEditable: true,
      validations: {
        isRequired: props.answerDetails.isRequired,
      },
    };

    setfieldDefinition(definition);

    console.log(props.answerDetails);
  }, []);

  const commonProps = {
    fieldName: props.fieldName,
    answerDetails: props.answerDetails,
    fieldDefinition: fieldDefinition,
    values: props.values,
    readOnly: props.readOnly,
    setValues: props.setValues,
  };
  switch (props.answerType) {
    case 'enum':
      return <Selector {...commonProps} />;
    case 'textArea':
      return <TextArea {...commonProps} />;
    case 'date':
      return <DatePicker {...commonProps} />;
    case 'time':
      return <TimePicker {...commonProps} />;
    case 'radioGroup':
      return <DynamicRadio {...commonProps} />;
    case 'checkBoxesGroup':
      return <DynamicCheckbox {...commonProps} />;
    case 'linearScale':
      return <LinearScale {...commonProps} />;
    case 'multipleChoiceGrid':
      return <MultipleChoiceGrid {...commonProps} />;
    case 'checkBoxGrid':
      return <CheckBoxGrid {...commonProps} />;
    default:
      return <TextField {...commonProps} />;
  }
};

export default FormInput;
