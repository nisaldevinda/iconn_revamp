import React, { useEffect, useState } from 'react';
import { FormInstance, Spin, Col } from 'antd';
import { getModel, ModelType } from '@/services/model';
import TextField from './TextField';
import NumberField from './NumberField';
import DateAndTimePicker from './DateAndTimePicker';
import Selector from './Selector';
import Model from './Model';
import MultiRecordTable from "./MultiRecordTable";
import Rate from "./Rate";
import TimeZoneSelector from "./TimeZoneSelector";
import Document from "./Document";
import TextArea from './TextArea';
import Switch from "./Switch";
import Tiny from "./Tiny";
import DynamicCheckbox from "./DynamicCheckbox";
import DynamicRadio from "./DynamicRadio";
import CurrencySelect from "./CurrencySelect";
import TagSelect from "./TagSelect";
import Avatar from "./Avatar";
import { RelationshipType } from "@/utils/model";
import ListView from './ListView';
import MobileInput from './MobileInput';
import DocumentView from './DocumentView';
import { useIntl } from "react-intl";
import MonthPicker from './MonthPicker';
import EmployeeNumber from './EmployeeNumber';
import WorkSchedule from './workSchedule';
import Label from './Label';
import EmployeeProfileJobSection from '../EmployeeJourney/JobSection';
import EmployeeProfileSalarySection from '../EmployeeJourney/SalarySection';
import OrgSelectorFormInput from './OrgSelectorFormInput';

export type FormInputProps = {
  fieldName: string;
  fieldNamePrefix?: string;
  model: Partial<ModelType>;
  readOnly: boolean;
  permission: any;
  form: FormInstance;
  values: {};
  setValues: (values: any) => void;
  errors: {};
  setErrors: (values: any) => void;
  formSubmit: (values: any) => void;
  tabularDataCreator?: (parentId: string, multirecordAttribute: string, data: any) => Promise<boolean | void>;
  tabularDataUpdater?: (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => Promise<boolean | void>;
  tabularDataDeleter?: (parentId: string, multirecordAttribute: string, multirecordId: number) => Promise<boolean | void>;
  recentlyChangedValue: any;
  scope?: string;
  refreshMasterData?: () => Promise<void>;
};

const FormInput: React.FC<FormInputProps> = (props) => {
  const [modelName, setModelName] = useState<string>();
  const [fieldDefinition, setfieldDefinition] = useState<any>();
  const intl = useIntl();
  useEffect(() => {
    if (props.model && props.model.modelDataDefinition && props.model.modelDataDefinition.fields) {
      const fieldNameSegments = props.fieldName.split('.');
      if (fieldNameSegments.length == 2) {
        const parentFieldName: string = fieldNameSegments[0];
        const childFieldName: string = fieldNameSegments[1];
        const parentFieldDefinition: { type: string; modelName: string } =
          props.model.modelDataDefinition.fields[parentFieldName];

        if (parentFieldDefinition.type == 'model') {
          getModel(parentFieldDefinition.modelName).then((model) => {
            if (
              model.data &&
              model.data.modelDataDefinition &&
              model.data.modelDataDefinition.fields &&
              model.data.modelDataDefinition.fields[childFieldName]
            ) {
              const childFieldDefinition = model.data.modelDataDefinition.fields[childFieldName];
              setfieldDefinition(childFieldDefinition);
              setModelName(parentFieldDefinition.modelName);
            }
          });
        }
      } else if (props.model.modelDataDefinition.fields[props.fieldName]) {
        setfieldDefinition(props.model.modelDataDefinition.fields[props.fieldName]);
        setModelName(props.model.modelDataDefinition.name);
      }
    }
  }, []);

  if (modelName && fieldDefinition) {
    const commonProps = {
      model: props.model,
      modelName: modelName,
      fieldName: props.fieldName,
      fieldNamePrefix: props.fieldNamePrefix,
      fieldDefinition: fieldDefinition,
      values: props.values,
      readOnly: props.readOnly,
      setValues: props.setValues,
      recentlyChangedValue: props.recentlyChangedValue
    }

    switch (fieldDefinition.type) {
      case 'timestamp':
        return (
          <DateAndTimePicker
            {...commonProps}
          />
        );
      case 'enum':
        return (
          <Selector
            {...commonProps}
          />
        );
      case 'model':
        const relationship = props.model.modelDataDefinition.relations[props.fieldName];
        if (relationship == RelationshipType.HAS_MANY) {
          return (
            <MultiRecordTable
              {...commonProps}
              parentModelName={modelName}
              form={props.form}
              permission={props.permission}
              errors={props.errors}
              setErrors={props.setErrors}
              formSubmit={props.formSubmit}
              tabularDataCreator={props.tabularDataCreator}
              tabularDataUpdater={props.tabularDataUpdater}
              tabularDataDeleter={props.tabularDataDeleter}
            />
          );
        }
        else {
          return (
            <Model
              {...commonProps}
              permission={props.permission}
              relationship={relationship}
              form={props.form}
              refreshMasterData={props.refreshMasterData}
            />
          );
        }
      case 'rate':
        return (
          <Rate
            {...commonProps}
          />
        );
      case 'textArea':
        return (
          <TextArea
            {...commonProps}
          />
        );
      case 'timeZone':
        return (
          <TimeZoneSelector
            {...commonProps}
          />
        );
      case "document":
        return (
          <Document
            fieldName={props.fieldName}
            fieldNamePrefix={props.fieldNamePrefix}
            fieldDefinition={fieldDefinition}
            parentModelName={modelName}
            values={props.values}
            setValues={props.setValues}
            form={props.form}
            recentlyChangedValue={props.recentlyChangedValue}
          />
        );
      case "switch":
        return (
          <Switch
            {...commonProps}
          />
        );
      case "WYSIWYG":
        return (
          <Tiny
            {...commonProps}
          />
        );
      case "radio":
        return (
          <DynamicRadio
            {...commonProps}
          />
        );
      case "checkbox":
        return (
          <DynamicCheckbox
            {...commonProps}
          />
        );
      case "currency":
        return (
          <CurrencySelect
            {...commonProps}
          />
        );
      case "tag":
        return (
          <TagSelect
            {...commonProps}
          />
        );
      case 'listView':
        const { dataMap, dataSourcs, disableLink, linkRoute, actions } = fieldDefinition;
        if (fieldDefinition.name === "documents") {
          return (
            <DocumentView
              values={props.values}
            />
          );
        } else {
          return (
            <ListView
              dataMap={dataMap}
              dataSourcs={dataSourcs}
              disableLink={disableLink}
              linkRoute={linkRoute}
              actions={actions}
            />
          );
        }
      case 'number':
        return (
          <NumberField
            {...commonProps}
          />
        );
      case "phone":
        return (
          <MobileInput
            {...commonProps}
            form={props.form}
          />
        );
      case "label":
        return (
          <Label
            {...commonProps}
          />
        );
      case "avatar":
        return (
          <Avatar
            {...commonProps}
          />
        );
      case "month":
        return (
          <MonthPicker
            {...commonProps}
          />
        );
      case "employeeNumber":
        return (
          <EmployeeNumber
            {...commonProps}
            form={props.form}
          />
        )
      case "workSchedule":
        return (
          <WorkSchedule values={props.values} />
        );
      case "jobSection":
        return (
          <EmployeeProfileJobSection values={props.values} setValues={props.setValues} permission={props.permission} scope={props.scope} />
        );
      case "salarySection":
        return (
          <EmployeeProfileSalarySection
            values={props.values}
            permission={props.permission}
            scope={props.scope}
            tabularDataCreator={props.tabularDataCreator}
            tabularDataUpdater={props.tabularDataUpdater}
            tabularDataDeleter={props.tabularDataDeleter}
          />
        );
      case "orgSelector":
        return (
          <OrgSelectorFormInput
            {...commonProps}
            permission={props.permission}
          />
        );
      case "textField":
      default:
        return (
          <TextField
            {...commonProps}
          />
        );
    }
  } else {
    return <Spin />;
  }
};

export default FormInput;
