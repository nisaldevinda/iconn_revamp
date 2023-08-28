import React, { useEffect, useState } from 'react';
import ProForm from '@ant-design/pro-form';
import DynamicFieldSet from './DynamicFieldSet';
import { getModel, ModelType } from '@/services/model';
import { FooterToolbar } from '@ant-design/pro-layout';
import { Button, Form, FormInstance, message, Popconfirm, Spin } from 'antd';
import { genarateEmptyValuesObject, parseToFormValuesFromDbRecord } from '@/utils/utils';
import { useIntl } from 'react-intl';
import _ from "lodash";
import { RelationshipType } from '@/utils/model';

export type DynamicFormProps = {
  formId?: string,
  formType?: 'add' | 'update',
  fieldNamePrefix?: string,
  submitterType?: 'none' | 'basic' | 'fixed',
  submitbuttonLabel?: string,
  resetbuttonLabel?: string,
  model: ModelType;
  permission: any;
  onFinish: (formData: any) => Promise<boolean | void>;
  tabularDataCreator?: (parentId: string, multirecordAttribute: string, data: any) => Promise<boolean | void>;
  tabularDataUpdater?: (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => Promise<boolean | void>;
  tabularDataDeleter?: (parentId: string, multirecordAttribute: string, multirecordId: number) => Promise<boolean | void>;
  initialValues?: {};
  setTabActiveKey?: (values: any) => void;
  defaultActiveKey?: string;
  form?: [FormInstance];
  scope?: string;
  refreshMasterData?: () => Promise<void>;
};

const DynamicForm: React.FC<DynamicFormProps> = (props) => {
  const intl = useIntl();

  const [loading, setLoading] = useState(false);
  const [initialValues, setInitialValues] = useState({});
  const [values, setValues] = useState({});
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [recentlyChangedValue, setRecentlyChangedValue] = useState({});
  const [tabActiveKey, setTabActiveKey] = useState<string>();
  const [tabSubmitterList, setTabSubmitterList] = useState<Array<string>>([]);
  const [cardSubmitterList, setCardSubmitterList] = useState<Array<string>>([]);
  const [tabSubmitterVisibility, setTabSubmitterVisibility] = useState(true);
  const [urgentRelationalModels, setUrgentRelationalModels] = useState({});
  const [form] = props.form ?? Form.useForm();

  let submitter = {};
  if (!_.isEmpty(props.submitterType) && props.submitterType == 'none') {
    submitter['render'] = () => null;
  } else {
    const genarateSubmitter = () => {
      return [
        <Popconfirm
          key="reset"
          title={intl.formatMessage({
            id: 'are_you_sure',
            defaultMessage: 'Are you sure?'
          })}
          onConfirm={() => {
            if (props.formType === "update") {
              const _initialValues = genarateInitialValues();
              setValues(_initialValues);
              setInitialValues(_initialValues);
              form.setFieldsValue(_initialValues);
            } else {
              const _initialValues = genarateEmptyValuesObject(props.model, props.fieldNamePrefix);
              setValues(_initialValues);
              setInitialValues(_initialValues);
              form.setFieldsValue(_initialValues);
            }
          }}
          okText="Yes"
          cancelText="No"
        >
          <Button data-key='reset'>
            {
              props.resetbuttonLabel ?? intl.formatMessage({
                id: 'RESET',
                defaultMessage: 'Reset'
              })
            }
          </Button>
        </Popconfirm>,
        <Button
          type="primary"
          key="submit"
          loading={submitting}
          onClick={mainFormSubmit}
          onKeyDown={(e)=> e.keyCode == 13 ? mainFormSubmit(): ''}
          data-key="submit"
        >
          {
            props.submitbuttonLabel ?? intl.formatMessage({
              id: 'SUBMIT',
              defaultMessage: 'Submit'
            })
          }
        </Button>,
      ];
    };

    submitter['render'] = () =>
      !_.isEmpty(props.submitterType) && props.submitterType == 'basic'
        ? genarateSubmitter()
        : <FooterToolbar data-key='footerSubmitter' style={{display: tabSubmitterVisibility ? '' : 'none'}}>{genarateSubmitter()}</FooterToolbar>;
  }

  useEffect(() => {
    if (props.setTabActiveKey) {
      props.setTabActiveKey(tabActiveKey);
    }

    if (tabActiveKey
      && props.model.modelDataDefinition.relations
      && props.model.frontEndDefinition
      && props.model.frontEndDefinition.structure
      && props.model.frontEndDefinition.topLevelComponent === 'tab') {
        let _tabSubmitterVisibility = tabSubmitterList.includes(tabActiveKey);
        setTabSubmitterVisibility(_tabSubmitterVisibility);
    } else {
      setTabSubmitterVisibility(true);
    }
  }, [tabActiveKey]);

  const mainFormSubmit = async (tab?: string) => {
    let _values = {...values};

    if (tabActiveKey
      && props.model.modelDataDefinition.relations
      && props.model.frontEndDefinition
      && props.model.frontEndDefinition.structure
      && props.model.frontEndDefinition.topLevelComponent === 'tab') {
        let tab = props.model.frontEndDefinition.structure.find(tab => tab.key === tabActiveKey);
        _values = {'id': values['id']};

        tab.content.map(card => {
          card.content.map(field => {
            let relation = props.model.modelDataDefinition.relations[field];
            if (relation == RelationshipType.HAS_ONE) {
              _values[field.concat('Id')] = values[field.concat('Id')];
            } else {
              _values[field] = values[field];
            }
          });
        });
    }

    formSubmit(_values);
  }

  const formSubmit = async (values: any) => {
    setSubmitting(true);

    await form.validateFields().then(async () => {
      for (var key in values) {
        const value = values[key];

        const keySegment = key.split('.');
        if (keySegment.length == 2) {
          delete values[key];
          if (values.hasOwnProperty(keySegment[0])
            && values[keySegment[0]].length > 0) {
              values[keySegment[0]][0][keySegment[1]] = value;
          } else {
            let obj = {};
            obj[keySegment[1]] = value;
            values[keySegment[0]] = [];
            values[keySegment[0]].push(obj);
          }
        }
      }

      await props.onFinish(values)
        .then(res => {
          setErrors({});
        })
        .catch(error => {
          if (!_.isEmpty(error.data) && _.isObject(error.data)) {
            setErrors(error.data);
            for (const fieldName in error.data) {
              form.setFields([
                {
                  name: fieldName,
                  errors: error.data[fieldName]
                }
              ]);
            }
          }
        });
    }).catch(errors => {
      message.error(intl.formatMessage({
        id: 'validationError',
        defaultMessage: 'Validation error',
      }));
    });

    setSubmitting(false);
  }

  const hasViewPermission = (fieldName: string) => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    if (_.isObject(props?.permission) && _.has(props?.permission, 'readOnly') && props?.permission?.readOnly == '*') return true;

    let modelDataDefinition = props.model.modelDataDefinition;
    let modelName = modelDataDefinition.name;

    if (fieldName.includes('.')) {
      const [parentFieldName, childFieldName] = fieldName.split('.');
      modelName = modelDataDefinition.fields[parentFieldName]?.modelName;
      fieldName = childFieldName;
      modelDataDefinition = urgentRelationalModels[modelName]?.modelDataDefinition;
    }

    const relation = modelDataDefinition?.relations[fieldName];

    if (relation == 'HAS_MANY') {
      const fieldModelName = modelDataDefinition.fields[fieldName]?.modelName;
      const modelPermission = props?.permission[fieldModelName];
      return !_.isEmpty(modelPermission?.viewOnly) || !_.isEmpty(modelPermission?.canEdit);
    }

    if (relation == 'HAS_ONE') fieldName = fieldName + 'Id';

    return props?.permission?.[modelName]
      ? props.initialValues && props.initialValues[props.fieldNamePrefix ? props.fieldNamePrefix + 'id' : 'id']
        ? props?.permission[modelName]?.viewOnly.includes(fieldName)
          || props?.permission[modelName]?.canEdit.includes(fieldName)
        : props?.permission[modelName]?.canEdit.includes(fieldName)
      : false;
  }

  const hasWritePermission = (fieldName: string) => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    let modelDataDefinition = props.model.modelDataDefinition;
    let modelName = modelDataDefinition.name;

    if (fieldName.includes('.')) {
      const [parentFieldName, childFieldName] = fieldName.split('.');
      modelName = modelDataDefinition.fields[parentFieldName]?.modelName;
      fieldName = childFieldName;
      modelDataDefinition = urgentRelationalModels[modelName]?.modelDataDefinition;
    }

    const relation = modelDataDefinition?.relations[fieldName];

    if (relation == 'HAS_ONE') fieldName = fieldName + 'Id';

    return props?.permission[modelName]?.canEdit.includes(fieldName);
  }

  useEffect(() => {
    setLoading(true);
    const _initialValues = genarateInitialValues();
    setInitialValues(_initialValues);
    setValues(_initialValues);
    setLoading(false);
  }, [props.model, props.initialValues])

  useEffect(() => {
    init();
  }, [props.model])

  const genarateInitialValues = () => {
    const _initialValues = genarateEmptyValuesObject(props.model, props.fieldNamePrefix);

    if (props.initialValues) {
      const parseInitialValues = parseToFormValuesFromDbRecord(props.model, props.initialValues);

      for (let key in parseInitialValues) {
        if (parseInitialValues[key] && _initialValues.hasOwnProperty(key)) {
          _initialValues[key] = parseInitialValues[key];
        }
      }

      // TODO: need to handle in dynamicaly
      // _initialValues['retirementDate'] = parseInitialValues['retirementDate'];
      // _initialValues['noticePeriod'] = parseInitialValues['noticePeriod'];
    }

    return _initialValues;
  }

  const init = async () => {
    setLoading(true);
    await setupUrgentRelationalModels();
    await setupTabAndCardPermission();
    setLoading(false);
  }

  const setupUrgentRelationalModels = async () => {
    let _urgentRelationalModels = {};
    // let _urgentRelationalModels: Array<String> = [];

    if (props.model.modelDataDefinition.relations && props.model.frontEndDefinition) {
      if (props.model.frontEndDefinition.topLevelComponent === 'tab') {
        await Promise.all(props.model.frontEndDefinition.structure.map(async tab => {
          await Promise.all(tab.content.map(async card => {
            await Promise.all(card.content.map(async fieldName => {
              if (fieldName.includes('.')) {
                const [parentFieldName, childFieldName] = fieldName.split('.');
                const _modelName = props.model.modelDataDefinition.fields[parentFieldName]?.modelName;

                if (_.isEmpty(_urgentRelationalModels[_modelName])) {
                  const _model = await getModel(_modelName);
                  _urgentRelationalModels[_modelName] = _model.data;
                }
              }
            }));
          }));
        }));
      } else if (props.model.frontEndDefinition.topLevelComponent === 'section') {
        await Promise.all(props.model.frontEndDefinition.structure.map(async card => {
          await Promise.all(card.content.map(async fieldName => {
            if (fieldName.includes('.')) {
              const [parentFieldName, childFieldName] = fieldName.split('.');
              const _modelName = props.model.modelDataDefinition.fields[parentFieldName]?.modelName;

              if (_.isEmpty(_urgentRelationalModels[_modelName])) {
                const _model = await getModel(_modelName);
                _urgentRelationalModels[_modelName] = _model.data;
              }
            }
          }));
        }));
      }
    }

    setUrgentRelationalModels(_urgentRelationalModels);
  }

  const setupTabAndCardPermission = async () => {
    if (props.model.modelDataDefinition.relations
      && props.model.frontEndDefinition
      && props.model.frontEndDefinition.topLevelComponent === 'tab') {
        let _tabSubmitterList: Array<string> = [];
        let _cardSubmitterList: Array<string> = [];

        props.model.frontEndDefinition.structure.map(tab => {
          let hasTabularAttributeOnTab = false;
          let cardSubmitterListOnThisTab: Array<string> = [];
          let tabContainsEditableField = false;

          tab.content.map(card => {
            let hasTabularAttributeOnCard = false;
            let cardContainsEditableField = false;

            card.content.map(inputField => {
              console.log('card.submitter >>> ', card.submitter, card.submitter !== undefined && card.submitter === null);
              if (card.submitter !== undefined && card.submitter === null) {
                hasTabularAttributeOnTab = true;
                hasTabularAttributeOnCard = true;
              } else if (props.model.modelDataDefinition.relations[inputField] == 'HAS_MANY') {
                hasTabularAttributeOnTab = true;
                hasTabularAttributeOnCard = true;
              }

              if (hasWritePermission(inputField)) {
                tabContainsEditableField = true;
                cardContainsEditableField = true;
              }
            });

            if (!hasTabularAttributeOnCard && cardContainsEditableField) {
              cardSubmitterListOnThisTab.push(tab.key + '.' + card.key);
            }
          });

          if (!hasTabularAttributeOnTab && tabContainsEditableField) {
            _tabSubmitterList.push(tab.key);
          } else if (tabContainsEditableField) {
            _cardSubmitterList = _cardSubmitterList.concat(cardSubmitterListOnThisTab);
          }
        });

        setTabSubmitterList(_tabSubmitterList);
        setCardSubmitterList(_cardSubmitterList);
    }
  }

  return !loading && props.permission && props.model && !_.isEmpty(values) ? (
      <ProForm
        id = {props.formId}
        form={form}
        initialValues={values}
        submitter={submitter}
        onFinish={formSubmit}
        onValuesChange={setRecentlyChangedValue}
        omitNil={false}
      >
        <DynamicFieldSet
          form={form}
          fieldNamePrefix={props.fieldNamePrefix}
          initialValues={initialValues}
          values={values}
          setValues={setValues}
          errors={errors}
          setErrors={setErrors}
          formSubmit={formSubmit}
          tabActiveKey={tabActiveKey}
          setTabActiveKey={setTabActiveKey}
          tabSubmitterList={tabSubmitterList}
          cardSubmitterList={cardSubmitterList}
          tabularDataCreator={props.tabularDataCreator}
          tabularDataUpdater={props.tabularDataUpdater}
          tabularDataDeleter={props.tabularDataDeleter}
          hasViewPermission={hasViewPermission}
          hasWritePermission={hasWritePermission}
          model={props.model}
          permission={props.permission}
          recentlyChangedValue={recentlyChangedValue}
          defaultActiveKey={props.defaultActiveKey}
          scope={props.scope}
          refreshMasterData={props.refreshMasterData}
        />
      </ProForm>
  ) : (<Spin/>);
};

export default DynamicForm;
