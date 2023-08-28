import { generateProFormFieldValidation } from "@/utils/validator";
import { ModalForm, ProFormSelect } from "@ant-design/pro-form";
import React, { useEffect, useState } from "react";
import { useIntl } from "react-intl";
import request, { APIResponse } from '@/utils/request';
import { getModel, ModelType } from "@/services/model";
import { Button, Col, FormInstance, Row, Space, Form, Spin, message } from "antd";
import { PlusOutlined, ReloadOutlined } from '@ant-design/icons';
import _ from "lodash";
import { RelationshipType } from "@/utils/model";
import FormInput from ".";
import { genarateEmptyValuesObject } from "@/utils/utils";

export type ModelProps = {
  model: Partial<ModelType>,
  modelName: string,
  fieldName: string,
  relationship: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: string,
    modelName: string,
    route: string,
    enumValueKey: string,
    enumLabelKey: string,
    dependOn: Array<any>,
    showOn: Array<any>,
    modelFilters: Object,
    isEditable: string,
    isSystemValue: string,
    validations: {
      isRequired: boolean,
      min: number,
      max: number
    },
    allowAddNewActionButton: boolean,
    placeholderKey: string,
    defaultPlaceholder: string,
    defaultValue: string,
  },
  permission: any;
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
  form: FormInstance;
  recentlyChangedValue: any;
  refreshMasterData?: () => Promise<void>;
};

const Model: React.FC<ModelProps> = (props) => {
  const intl = useIntl();
  const [loading, setLoading] = useState(true);
  const [fieldVisible, setFieldVisible] = useState(true);
  const [nestedModel, setNestedModel] = useState<ModelType>();
  const [valuesSet, setValuesSet] = useState<undefined | Array<{ label: string, value: string }>>(undefined);
  const [isDependedField, setIsDependedField] = useState(false);
  const [isShowOnField, setIsShowOnField] = useState(false);
  const [lastPathParams, setLastPathParams] = useState({});

  const [currentRecord, setCurrentRecord] = useState<any>();
  const [addFormReference] = Form.useForm();
  const [addFormVisible, setAddFormVisible] = useState(false);
  const [addFormChangedValue, setAddFormChangedValue] = useState({});

  useEffect(() => {
    init();
  }, []);

  useEffect(() => {
    refreshdependOnParams();
  }, [isDependedField]);

  useEffect(() => {
    onShowHandler();
  }, [isShowOnField]);

  useEffect(() => {
    refreshdependOnParams();
    onShowHandler();
  }, [props.values]);

  useEffect(() => {
    if (props.values.id == null) {
      let currentValues = { ...props.values };
      currentValues[fieldName] = props.fieldDefinition.defaultValue ? props.fieldDefinition.defaultValue : null;
      props.setValues(currentValues);
    }
  }, [props.values.id]);

  useEffect(() => {
    loadOptions();
  }, [nestedModel, lastPathParams]);

  const init = async () => {
    setLoading(true);

    if (props.fieldDefinition.dependOn
      && Array.isArray(props.fieldDefinition.dependOn)
      && props.fieldDefinition.dependOn.length > 0) {
      setIsDependedField(true);
    }

    if (props.fieldDefinition.showOn
      && Array.isArray(props.fieldDefinition.showOn)
      && props.fieldDefinition.showOn.length > 0) {
      setIsShowOnField(true);
    }

    if (props.fieldDefinition.modelName && !nestedModel) {
      getModel(props.fieldDefinition.modelName).then((model) => {
        if (model && model.data) {
          setNestedModel(model.data);
        }
      });
    } else if (!props.fieldDefinition.modelName) {
      setNestedModel({ modelDataDefinition: {}, frontEndDefinition: {} });
    }

    setLoading(false);
  }

  const loadOptions = async () => {
    setLoading(true);
    setValuesSet([{
      label: intl.formatMessage({
        id: 'loading',
        defaultMessage: 'Loading...',
      }),
      value: props.values[fieldName] ?? 0,
      disabled: true
    }]);

    if (nestedModel) {
      let incompletePath = false;
      let path: string = props.fieldDefinition.route ?? `/api${nestedModel.modelDataDefinition.path}`;
      const dependOnParams = props.fieldDefinition.dependOn;

      let dependedFieldParams = {};
      if (isDependedField) {
        dependOnParams.forEach(param => {
          if (!lastPathParams[param.paramKey] && !lastPathParams[param.filterKey]) {
            incompletePath = true;
          } else if (param.filterKey) {
            dependedFieldParams[param.filterKey] = [lastPathParams[param.filterKey]];
          } else {
            path = path.replaceAll(':'.concat(param.paramKey), lastPathParams[param.paramKey]);
          }
        });
      }

      const params = {};
      if (!_.isEmpty(props.fieldDefinition.modelFilters) && _.isObject(props.fieldDefinition.modelFilters)) {
        params['filter'] = props.fieldDefinition.modelFilters;
      }

      if (!_.isEmpty(dependedFieldParams)) {
        params['filter'] = params['filter']
          ? { ...params['filter'], ...dependedFieldParams }
          : dependedFieldParams;
      }

      if (props.modelName === "user") {
        if (props.fieldDefinition.modelName === "employee") {
          if (props.values.employeeId) {
            params['employeeId'] = props.values.employeeId;
          }
        }
      }

      if (!incompletePath) {
        try {
          await request(path, { params }).then((response: APIResponse) => {
            if (response && response.data && Array.isArray(response.data)) {
              const dataSet = response.data?.map(data => {
                return {
                  label: data[props.fieldDefinition.enumLabelKey],
                  value: data[props.fieldDefinition.enumValueKey]
                };
              });
              setValuesSet(dataSet);
            }
          });
        } catch (error) {
          setValuesSet(undefined);
        }
      }
    }

    setLoading(false);
  }

  const refreshdependOnParams = () => {
    if (isDependedField) {
      const dependOnParams = props.fieldDefinition.dependOn;

      if (dependOnParams) {
        for (let index in dependOnParams) {
          const dependOnParam = dependOnParams[index];
          let lastPathParamsClone = { ...lastPathParams };
          lastPathParamsClone[dependOnParam.filterKey ?? dependOnParam.paramKey] = props?.values?.[
            props.fieldNamePrefix
              ? props.fieldNamePrefix.concat(dependOnParam.modelKey)
              : dependOnParam.modelKey
          ];
          setLastPathParams(lastPathParamsClone);
        }
      }
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

  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName).concat(props.relationship == RelationshipType.HAS_MANY_TO_MANY ? 'Ids' : 'Id')
    : props.fieldName.concat(props.relationship == RelationshipType.HAS_MANY_TO_MANY ? 'Ids' : 'Id');
  const label = intl.formatMessage({
    id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
    defaultMessage: props.fieldDefinition.defaultLabel,
  });

  const addFormFieldSet = () => {
    let selectedFields = [];
    const fields = nestedModel.modelDataDefinition.fields;
    selectedFields = Object.values(fields).filter((field) => {
      return !field.isSystemValue && !field.isComputedProperty;
    });

    return (
      <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
        {selectedFields.map((inputField) => (
          <FormInput
            key={inputField.name}
            fieldName={inputField.name}
            model={nestedModel}
            form={addFormReference}
            values={currentRecord}
            setValues={setCurrentRecord}
            recentlyChangedValue={addFormChangedValue}
          />
        ))}
      </Row>
    );
  };

  const convertTagString = (record) => {
    const convRecord = {};
    for (const key in record) {
      if (_.isArray(record[key])) {
        convRecord[key] = JSON.stringify(record[key]);
      } else convRecord[key] = record[key];
    }
    return convRecord;
  };

  const addFunction = async (_nestedModel: any, values: any) => {
    return request(`/api${_nestedModel.modelDataDefinition.path}`, {
      method: 'POST',
      data: { ...values }
    },
      true
    );
  }

  const addViewProps = {
    title: intl.formatMessage({
      id: `add_${props.fieldName}`,
      defaultMessage: `Add ${label}`,
    }),
    key: `add_${props.fieldName}`,
    visible: addFormVisible,
    onVisibleChange: setAddFormVisible,
    form: addFormReference,
    onValuesChange: setAddFormChangedValue,
    submitter: {
      searchConfig: {
        submitText: intl.formatMessage({
          id: 'add',
          defaultMessage: 'Add',
        }),
        resetText: intl.formatMessage({
          id: 'cancel',
          defaultMessage: 'Cancel',
        }),
      },
    },
    onFinish: async () => {
      const key = 'saving';
      message.loading({
        content: intl.formatMessage({
          id: 'saving',
          defaultMessage: 'Saving...',
        }),
        key,
      });

      await addFunction(nestedModel, convertTagString(currentRecord))
        .then((response: APIResponse) => {
          if (props.refreshMasterData) props.refreshMasterData();

          if (response.error) {
            message.error({
              content:
                response.message ??
                intl.formatMessage({
                  id: 'failedToSave',
                  defaultMessage: 'Cannot Save',
                }),
              key,
            });
            if (response.data && Object.keys(response.data).length !== 0) {
              for (const feildName in response.data) {
                const errors = response.data[feildName];
                addFormReference.setFields([
                  {
                    name: feildName,
                    errors: errors,
                  },
                ]);
              }
            }
            return;
          }

          message.success({
            content:
              response.message ??
              intl.formatMessage({
                id: 'successfullySaved',
                defaultMessage: 'Successfully Saved',
              }),
            key,
          });

          loadOptions();
          setAddFormVisible(false);
        })

        .catch((error: APIResponse) => {
          let errorMessage;
          let errorMessageInfo;
          if (error.message.includes('.')) {
            let errorMessageData = error.message.split('.');
            errorMessage = errorMessageData.slice(0, 1);
            errorMessageInfo = errorMessageData.slice(1).join('.');
          }
          console.log('sdsd');
          message.error({
            content: error.message ? (
              <>
                {errorMessage ?? error.message}
                <br />
                <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                  {errorMessageInfo ?? ''}
                </span>
              </>
            ) : (
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              })
            ),
            key,
          });
          if (error && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              addFormReference.setFields([
                {
                  name: feildName,
                  errors: errors,
                },
              ]);
            }
          }
        });
    },
  };

  return (
    fieldVisible
      ? <Col data-key={fieldName} span={12}>
        <ProFormSelect
          options={valuesSet}
          width="md"
          showSearch
          name={fieldName}
          label={label}
          disabled={props.readOnly}
          placeholder={props.fieldDefinition.placeholderKey || props.fieldDefinition.defaultPlaceholder
            ? intl.formatMessage({
              id: props.fieldDefinition.placeholderKey,
              defaultMessage: props.fieldDefinition.defaultPlaceholder,
            })
            : 'Select '.concat(label)}
          rules={generateProFormFieldValidation(
            props.fieldDefinition,
            props.modelName,
            props.fieldName,
            props.values
          )}
          fieldProps={{
            mode: props.relationship == RelationshipType.HAS_MANY_TO_MANY ? 'tags' : undefined,
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues[fieldName] = !_.isNull(value) && !_.isUndefined(value) ? value : null;
              props.setValues(currentValues);
            },
            autoComplete: "none",
            // loading: loading
          }}
          initialValue={
            props.relationship == RelationshipType.HAS_MANY_TO_MANY
              ? props.fieldDefinition.defaultValue ?? []
              : props.fieldDefinition.defaultValue
          }
        />
        {nestedModel &&
          <Space className='dynamic-form-input-model'>
            <Button loading={loading} shape="circle" icon={<ReloadOutlined />} size='small' onClick={loadOptions} />
            {props.fieldDefinition.allowAddNewActionButton && <Button loading={!nestedModel} shape="circle" icon={<PlusOutlined />} size='small' onClick={() => {
              if (!nestedModel) return
              const intialValues = genarateEmptyValuesObject(nestedModel);
              setCurrentRecord(intialValues);
              setAddFormVisible(true)
            }}
            />}
            <ModalForm
              modalProps={{
                destroyOnClose: true,
              }}
              {...addViewProps}
            >
              {props.model.modelDataDefinition ? addFormFieldSet() : <Spin />}
            </ModalForm>
          </Space>}
      </Col>
      : <></>
  );
};

export default Model;
