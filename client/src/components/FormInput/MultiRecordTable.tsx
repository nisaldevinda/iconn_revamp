import React, { useEffect, useRef, useState } from "react";
import { FormattedMessage, useIntl } from "react-intl";
import ProTable, { ActionType, ProColumns } from "@ant-design/pro-table";
import { Badge, Button, Col, Drawer, Form, FormInstance, message, Popconfirm, Space, Skeleton, Tooltip, Modal } from "antd";
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
// import Modal from "antd/lib/modal/Modal";
import DynamicForm from "../DynamicForm";
import { getModel, ModelType } from "@/services/model";
import _ from "lodash";
import request from "@/utils/request";
import { getRelationType, RelationshipType } from "@/utils/model";
import moment from 'moment';
import { genarateEmptyValuesObject } from "@/utils/utils";

export type MultiRecordTableProps = {
  fieldName: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: string,
    modelName: string,
    enumValueKey: string,
    enumLabelKey: string,
    dependOn: Array<any>,
    isEditable: string,
    isSystemValue: string,
    validations: {
      isRequired: boolean,
      min: number,
      max: number
    },
    defaultValue: string,
  },
  readOnly: boolean;
  permission: any;
  parentModelName: string,
  values: {};
  setValues: (values: any) => void;
  errors: {};
  setErrors: (values: any) => void;
  form: FormInstance;
  formSubmit: (values: any) => void;
  tabularDataCreator?: (parentId: string, multirecordAttribute: string, data: any) => Promise<boolean | void>;
  tabularDataUpdater?: (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => Promise<boolean | void>;
  tabularDataDeleter?: (parentId: string, multirecordAttribute: string, multirecordId: number) => Promise<boolean | void>;
  recentlyChangedValue: any
};

const MultiRecordTable: React.FC<MultiRecordTableProps> = (props) => {
  const intl = useIntl();
  const actionRef = useRef<ActionType>();
  const multiRecordForm = Form.useForm();

  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [modelName, setModelName] = useState<string>();
  const [modelSingularLabel, setModelSingularLabel] = useState<string>();
  const [modelPluralLabel, setModelPluralLabel] = useState<string>();
  const [columns, setColumns] = useState<ProColumns[]>();
  const [currentIndex, setCurrentIndex] = useState<number>();
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [currentModalType, setCurrentModalType] = useState<'add' | 'edit'>('add');
  const [nestedModel, setNestedModel] = useState<ModelType>();
  const [rowErrors, setRowErrors] = useState([]);
  const [currentRecordOnEffectiveDate, setCurrentRecordOnEffectiveDate] = useState<number>();
  const [refreshMasterData, setRefreshMasterData] = useState<boolean>(false);

  const hasViewPermission = (fieldName: string) => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    if (_.isObject(props?.permission) && _.has(props?.permission, 'readOnly') && props?.permission?.readOnly == '*') return true;

    const modelName = nestedModel?.modelDataDefinition.name;
    const relation = nestedModel?.modelDataDefinition?.relations[fieldName];

    if (relation == 'HAS_MANY') {
      const fieldModelName = nestedModel?.modelDataDefinition.fields[fieldName]?.modelName;
      const modelPermission = props?.permission[fieldModelName];
      return !_.isEmpty(modelPermission.viewOnly) || !_.isEmpty(modelPermission.canEdit);
    }

    if (relation == 'HAS_ONE') fieldName = fieldName + 'Id';

    return props?.permission[modelName]?.viewOnly.includes(fieldName)
      || props?.permission[modelName]?.canEdit.includes(fieldName);
  }

  const hasEditPermission = () => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;

    const modelName = nestedModel?.modelDataDefinition.name;
    return !_.isEmpty(props?.permission[modelName]?.canEdit);
  }

  const hasCreatePermission = () => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;

    const modelName = nestedModel?.modelDataDefinition.name;

    if (_.isEmpty(props?.permission[modelName]?.canEdit)) {
      return false;
    }
    const modelHasRequiredField = Object.values(nestedModel?.modelDataDefinition?.fields)
      .filter(field => {
        return field?.validations?.isRequired;
      });
    if (_.isEmpty(modelHasRequiredField)) {
      return true;
    }

    let hasWritePermissionForEveryMandatoryFields = true;
    Object.values(nestedModel?.modelDataDefinition?.fields)
      .map(field => {
        const fieldName = field.type == 'model' && nestedModel?.modelDataDefinition?.relations?.[field.name] == 'HAS_ONE'
          ? field.name.concat('Id')
          : field.name;

        if (field?.validations?.isRequired && !props?.permission[modelName]?.canEdit.includes(fieldName)) {
          hasWritePermissionForEveryMandatoryFields = false;
          return;
        }
      });

    return hasWritePermissionForEveryMandatoryFields;
  }

  async function setupTableColumns() {
    setLoading(true);

    const fields = nestedModel && nestedModel.modelDataDefinition
      ? nestedModel.modelDataDefinition.fields
      : [];

    let columnSet = [];
    let badgeFields: any[] = [];

    for (const fieldName in fields) {
      try {
        if (!hasViewPermission(fieldName)) continue;

        const field = fields[fieldName];
        if (!field.isSystemValue) {
          if (field.showAsBadgeOnMultiRecordTable) {
            badgeFields.push(field);
            continue;
          }

          let column = {
            title: intl.formatMessage({
              id: `model.${modelName}.${field.labelKey}`,
              defaultMessage: field.defaultLabel,
            }),
            dataIndex: field.name,
          };
          if (field.type == 'timestamp') {
            column['render'] = (record) => {
              let dateObj = moment(record, "YYYY-MM-DD");
              return (dateObj.isValid()) ? dateObj.format("DD-MM-YYYY") : null;
            }
          }
          if (field.type == 'enum' && _.isArray(field.values)) {
            let valueEnum = {};
            if (_.isArray(field.values)) {
              field.values.map((value) => {
                valueEnum[value.value] = intl.formatMessage({
                  id: `model.${modelName}.${field.name}.${value.labelKey}`,
                  defaultMessage: value.defaultLabel
                });
              })
            }
            column['valueEnum'] = valueEnum;
          }

          if (field.type == 'model') {
            const relation = getRelationType(nestedModel?.modelDataDefinition, field.name);

            switch (relation) {
              case RelationshipType.HAS_ONE:
                column['dataIndex'] = field.name + 'Id';
                break;
              case RelationshipType.HAS_MANY_TO_MANY:
                column['dataIndex'] = field.name + 'Ids';
                break;
            }

            let path: string;

            if (field.route) {
              path = field.route;
            } else {
              await getModel(field.modelName).then((response) => {
                if (!_.isEmpty(response.data)) {
                  path = `/api${response.data.modelDataDefinition.path}`;
                }
              })
            }

            await request(path).then(function (response) {
              if (response && response.data && Array.isArray(response.data)) {
                let valueEnum = {};

                response.data.map(data => {
                  valueEnum[data[field.enumValueKey]] = data[field.enumLabelKey];
                });

                column['valueEnum'] = valueEnum;
              }
            });
          }

          columnSet.push(column);
        }
      } catch (error) {
        continue;
      }
    }

    if (columnSet.length > 0) {
      columnSet.push({
        title: intl.formatMessage({
          id: 'actions',
          defaultMessage: 'Actions',
        }),
        valueType: 'option',
        align: 'center',
        fixed: 'right',
        width: 80,
        render: (text, record, index, action) => [
          hasEditPermission()
            ? <Tooltip title={
              intl.formatMessage({
                id: 'edit',
                defaultMessage: 'Edit',
              })
            }>
              <a data-key={`${fieldName}.${record.id}.edit`} onClick={() => {
                setCurrentIndex(index);

                let modifiedRecord = {};
                for (let fieldName in record) {
                  modifiedRecord[`${fieldNamePrefix}${fieldName}`] = record[fieldName];
                }

                setCurrentModalType('edit');
                setCurrentRecord(modifiedRecord);
                setModalVisible(true);
              }}>
                <EditOutlined />
              </a>
            </Tooltip>
            : <></>,
          hasEditPermission() && record.recordCanDelete
            ? <Popconfirm
              title={intl.formatMessage({
                id: 'are_you_sure',
                defaultMessage: 'Are you sure?'
              })}
              onConfirm={() => removeRecord(index, record)}
              okText="Yes"
              cancelText="No"
            >
              <Tooltip title={
                intl.formatMessage({
                  id: 'delete',
                  defaultMessage: 'Delete',
                })
              }>
                <a data-key={`${fieldName}.${record.id}.delete`}> <DeleteOutlined /> </a>
              </Tooltip>
            </Popconfirm>
            : <></>
        ]
      });

      columnSet.push({
        valueType: 'option',
        fixed: 'left',
        width: 1,
        render: (text, record, index, action) => {
          let error = undefined;

          if (!_.isEmpty(record.rowError)) {
            let errorTooltipTitle: Array<string> = [];

            for (let fieldname in record.rowError) {
              const field = nestedModel?.modelDataDefinition?.fields[fieldname];
              const title = intl.formatMessage({
                id: `model.${modelName}.${field.labelKey}`,
                defaultMessage: field.defaultLabel,
              });

              errorTooltipTitle.push(title.concat(': ').concat(record.rowError[fieldname].join(', ')));
            }

            error = errorTooltipTitle.join(', ');
          }

          return <Space>
            {error ? <Tooltip title={error} placement='left' visible={true}><Badge status='error' dot={true} /></Tooltip> : null}
            {badgeFields.map((field: any) => {
              const value = record[field.name] ?? undefined;
              if (value) {
                return <Tooltip title={field.defaultLabel} placement='left'>
                  <Badge status='warning' dot={true} />
                </Tooltip>
              }

              return null;
            })}
            {record.isCurrentRecordOnEffectiveDate ? <Badge status='success' dot={true} /> : null}
          </Space>
        }
      });

      setColumns(columnSet);
    }

    setLoading(false);
  }

  useEffect(() => {
    if (!nestedModel) {
      getModel(props.fieldDefinition.modelName).then((model) => {
        if (model && model.data) {
          setNestedModel(model.data);
        }
      });
    }
  }, []);

  useEffect(() => {
    if (!modelName && nestedModel && nestedModel.modelDataDefinition) {
      setModelName(nestedModel.modelDataDefinition.name);

      setModelSingularLabel(intl.formatMessage({
        id: nestedModel.modelDataDefinition.singularLabelKey,
        defaultMessage: nestedModel.modelDataDefinition.singularDefaultLabel,
      }));

      setModelPluralLabel(intl.formatMessage({
        id: nestedModel.modelDataDefinition.pluralLabelKey,
        defaultMessage: nestedModel.modelDataDefinition.pluralDefaultLabel,
      }));
    }

    if (!columns) {
      setupTableColumns();
    }
  }, [nestedModel]);

  useEffect(() => {
    setRowErrors(props.errors && props.errors[props.fieldName] ? props.errors[props.fieldName] : []);
  }, [props.errors]);

  useEffect(() => {
    let currentId = props.values['current' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Id'] ?? null;
    setCurrentRecordOnEffectiveDate(currentId);
  }, [props.values]);

  useEffect(() => {
    if (!modalVisible && refreshMasterData) {
      setupTableColumns();
      setRefreshMasterData(false);
    }
  }, [modalVisible]);

  const addRecord = async (values: any) => {
    const key = 'saving';
    message.loading({
      content: intl.formatMessage({
        id: 'saving',
        defaultMessage: 'Saving...',
      }),
      key,
    });
    setSubmitting(true);

    let currentValues = { ...props.values };
    let modifiedValues = {};

    for (let subFieldName in values) {
      const realFieldName = subFieldName.replace(fieldNamePrefix, '');
      modifiedValues[realFieldName] = (nestedModel?.modelDataDefinition.fields[realFieldName] && nestedModel?.modelDataDefinition.fields[realFieldName].type === "timestamp") && moment(values[subFieldName], "DD-MM-YYYY", true).isValid() ? moment(values[subFieldName], 'DD-MM-YYYY').format("YYYY-MM-DD") : values[subFieldName];
    }

    let checkModifiedValues = Object.values(modifiedValues).some(x => x !== null && x !== '');

    if (checkModifiedValues && props.tabularDataCreator) {
      props.tabularDataCreator(currentValues['id'], fieldName, modifiedValues)
        .then(success => {
          message.success({
            content:
              success?.message ??
              intl.formatMessage({
                id: 'successfullySaved',
                defaultMessage: 'Successfully Saved',
              }),
            key,
          });

          setModalVisible(false);
          setSubmitting(false);
        })
        .catch(error => {
          if (_.isObject(error?.data)) {
            message.destroy(key);
            for (var fieldname in error?.data) {
              console.log(fieldNamePrefix.concat(fieldname), error?.data[fieldname]);
              multiRecordForm[0].setFields([
                {
                  name: fieldNamePrefix.concat(fieldname),
                  errors: error?.data[fieldname]
                }
              ]);
            }
          } else {
            message.error({
              content:
                error?.message ??
                intl.formatMessage({
                  id: 'failedToSave',
                  defaultMessage: 'Cannot Save',
                }),
              key,
            });
          }

          setModalVisible(true);
          setSubmitting(false);
        });
    } else if (checkModifiedValues) {
      currentValues[fieldName].push(modifiedValues);
      props.setValues(currentValues);

      const instanceData = { 'id': currentValues['id'] };
      instanceData[fieldName] = currentValues[fieldName];

      props.formSubmit(instanceData)
        .then(success => {
          message.success({
            content:
              success?.message ??
              intl.formatMessage({
                id: 'successfullySaved',
                defaultMessage: 'Successfully Saved',
              }),
            key,
          });

          setModalVisible(false);
          setSubmitting(false);
        })
        .catch(error => {
          message.error({
            content:
              error?.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              }),
            key,
          });

          if (_.isObject(error?.data)) {
            for (var fieldname in error?.data) {
              console.log(fieldNamePrefix.concat(fieldname), error?.data[fieldname]);
              multiRecordForm[0].setFields([
                {
                  name: fieldNamePrefix.concat(fieldname),
                  errors: error?.data[fieldname]
                }
              ]);
            }
          }

          setModalVisible(true);
          setSubmitting(false);
        });
    } else {
      setModalVisible(true);
      setSubmitting(false);
    }
  }

  const updateRecord = (index: number, values: any) => {
    const key = 'updating';
    message.loading({
      content: intl.formatMessage({
        id: 'updating',
        defaultMessage: 'Updating...',
      }),
      key,
    });
    setSubmitting(true);

    let modifiedValues = {};
    for (let subFieldName in values) {
      const realFieldName = subFieldName.replace(fieldNamePrefix, '');
      let newVal = !_.isUndefined(values[subFieldName]) ? values[subFieldName] : null;
      modifiedValues[realFieldName] = (nestedModel?.modelDataDefinition.fields[realFieldName] && nestedModel?.modelDataDefinition.fields[realFieldName].type === "timestamp") && moment(newVal, "DD-MM-YYYY", true).isValid() ? moment(newVal, "DD-MM-YYYY").format("YYYY-MM-DD") : newVal;
    }

    let currentValues = { ...props.values };
    let currentRecords = [...currentValues[fieldName]];

    let checkModifiedValues = Object.values(modifiedValues).some(x => x !== null && x !== '');

    if (checkModifiedValues && props.tabularDataUpdater) {
      props.tabularDataUpdater(currentValues['id'], fieldName, currentRecords[index]['id'], modifiedValues)
        .then(success => {
          message.success({
            content:
              success?.message ??
              intl.formatMessage({
                id: 'successfullySaved',
                defaultMessage: 'Successfully Saved',
              }),
            key,
          });

          setModalVisible(false);
          setSubmitting(false);
        })
        .catch(error => {
          message.error({
            content:
              error?.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              }),
            key,
          });

          if (_.isObject(error?.data)) {
            for (var fieldname in error?.data) {
              console.log(fieldNamePrefix.concat(fieldname), error?.data[fieldname]);
              multiRecordForm[0].setFields([
                {
                  name: fieldNamePrefix.concat(fieldname),
                  errors: error?.data[fieldname]
                }
              ]);
            }
          }

          setModalVisible(true);
          setSubmitting(false);
        });
    } else if (checkModifiedValues) {
      currentRecords[index] = modifiedValues;
      currentValues[fieldName] = currentRecords;

      props.setValues(currentValues);

      const instanceData = { 'id': currentValues['id'] };
      instanceData[fieldName] = currentValues[fieldName];
      props.formSubmit(instanceData)
        .then(success => {
          message.success({
            content:
              success?.message ??
              intl.formatMessage({
                id: 'successfullySaved',
                defaultMessage: 'Successfully Saved',
              }),
            key,
          });

          setModalVisible(false);
          setSubmitting(false);
        })
        .catch(error => {
          message.error({
            content:
              error?.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              }),
            key,
          });

          if (_.isObject(error?.data)) {
            for (var fieldname in error?.data) {
              console.log(fieldNamePrefix.concat(fieldname), error?.data[fieldname]);
              multiRecordForm[0].setFields([
                {
                  name: fieldNamePrefix.concat(fieldname),
                  errors: error?.data[fieldname]
                }
              ]);
            }
          }

          setModalVisible(true);
          setSubmitting(false);
        });
    } else {
      setModalVisible(true);
      setSubmitting(false);
    }
  }

  const removeRecord = (index: number, record: any) => {
    setLoading(true);

    let currentValues = { ...props.values };
    let currentRecords = [...currentValues[fieldName]];

    if (props.tabularDataDeleter) {
      props.tabularDataDeleter(currentValues['id'], fieldName, record['id']);
    } else {
      currentRecords.splice(index, 1);
      currentValues[fieldName] = currentRecords;

      props.setValues(currentValues);

      const instanceData = { 'id': currentValues['id'] };
      instanceData[fieldName] = currentValues[fieldName];
      props.formSubmit(instanceData);
    }

    setLoading(false);
  }

  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const fieldNamePrefix = fieldName.concat('_');

  return loading ? (<Skeleton active className='dynamic-form-skeleton' />) : _.isEmpty(columns) ? <FormattedMessage id="noAccess" defaultMessage="No Access" /> : (
    <Col data-key={fieldName} span={24}>
      <ProTable
        pagination={{ pageSize: 20, defaultPageSize: 20, hideOnSinglePage: true }}
        id={fieldName}
        rowKey="id"
        columns={columns}
        dataSource={!loading ? props.values[fieldName].map((record: any, index: number) => {
          return {
            ...record,
            rowError: rowErrors[index] ?? [],
            isCurrentRecordOnEffectiveDate: currentRecordOnEffectiveDate == record.id ? true : false,
            recordCanDelete: props.fieldDefinition.validations?.isRequired ? props.values[fieldName].length > 1 : true
          };
        }) : null}
        options={false}
        search={false}
        actionRef={actionRef}
        toolBarRender={() => [
          hasCreatePermission()
            ? <Button
              data-key={`${fieldName}.add`}
              type="primary"
              key="add"
              onClick={() => {
                setCurrentIndex(null);
                const record = nestedModel ? genarateEmptyValuesObject(nestedModel) : undefined;
                let modifiedRecord = {};
                for (let fieldName in record) {
                  modifiedRecord[`${fieldNamePrefix}${fieldName}`] = record[fieldName];
                }

                setCurrentModalType('add');
                setCurrentRecord(modifiedRecord);
                setModalVisible(true);
                multiRecordForm[0].setFieldsValue(modifiedRecord);
              }}
            >
              <PlusOutlined /> <FormattedMessage id="pages.user.new" defaultMessage="New" />
            </Button>
            : <></>
        ]}
      />

      <Space style={{ marginTop: 24 }}>
        {
          !_.isEmpty(rowErrors) ? <>
            <Badge style={{ marginLeft: 16 }} status='error' dot={true} />
            {intl.formatMessage({ id: 'errors', defaultMessage: 'Errors' })}
          </> : <></>
        }
        {
          currentRecordOnEffectiveDate ? <>
            <Badge style={{ marginLeft: 16 }} status='success' dot={true} />
            {intl.formatMessage({ id: 'current', defaultMessage: 'Current' }) + ' ' + modelSingularLabel}
          </> : <></>
        }
      </Space>

      {currentModalType == 'add'
        ? <Modal
          key={fieldName.concat('Modal')}
          title={`Add ${modelSingularLabel}`}
          centered
          destroyOnClose={true}
          visible={modalVisible}
          okButtonProps={{
            form: `${props.fieldNamePrefix}${props.fieldName}-form`,
            htmlType: "submit",
            className: "submit-btn",
            loading: submitting
          }}
          onCancel={() => setModalVisible(false)}
          width="60vw"
          bodyStyle={{
            maxHeight: "60vh",
            overflowY: "auto"
          }}
          okText={intl.formatMessage({
            id: 'ADD',
            defaultMessage: 'Add'
          })}
          cancelText={intl.formatMessage({
            id: 'CANCEL',
            defaultMessage: 'Cancel'
          })}
        >
          <DynamicForm
            form={multiRecordForm}
            formId={`${props.fieldNamePrefix}${props.fieldName}-form`}
            formType={_.isEmpty(currentIndex) && _.isEmpty(currentRecord) ? 'add' : 'update'}
            fieldNamePrefix={fieldNamePrefix}
            model={nestedModel}
            permission={props.permission}
            initialValues={currentRecord}
            onFinish={async (values: any) => addRecord(values)}
            submitterType="none"
            refreshMasterData={async () => setRefreshMasterData(true)}
          />
        </Modal>
        : <Drawer
          key={fieldName.concat('Modal')}
          title={`Edit ${modelSingularLabel}`}
          destroyOnClose={true}
          visible={modalVisible}
          footer={<Space style={{ float: "right" }}>
            <Button data-key="cancel-btn" onClick={() => setModalVisible(false)}>
              <FormattedMessage id="CANCEL" defaultMessage="Cancel" />
            </Button>
            <Button loading={submitting} data-key="update-btn" form={`${props.fieldNamePrefix}${props.fieldName}-form`} type="primary" key="submit" htmlType="submit">
              <FormattedMessage id="UPDATE" defaultMessage="Update" />
            </Button>
          </Space>}
          onClose={() => setModalVisible(false)}
          width="40vw"
        >
          <DynamicForm
            formId={`${props.fieldNamePrefix}${props.fieldName}-form`}
            formType={'update'}
            fieldNamePrefix={fieldNamePrefix}
            model={nestedModel}
            permission={props.permission}
            initialValues={currentRecord}
            onFinish={async (values: any) => updateRecord(currentIndex, values)}
            submitterType="none"
            refreshMasterData={async () => setRefreshMasterData(true)}
          />
        </Drawer>
      }
    </Col>)
};

export default MultiRecordTable;
