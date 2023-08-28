import React, { useEffect, useRef, useState } from "react";
import { FormattedMessage, useIntl } from "react-intl";
import ProTable, { ActionType, ProColumns } from "@ant-design/pro-table";
import { Button, Col, Drawer, FormInstance, Popconfirm, Space, Spin, Tooltip } from "antd";
import { DeleteOutlined, EditOutlined, PlusOutlined, QuestionCircleOutlined } from "@ant-design/icons";
import Modal from "antd/lib/modal/Modal";
import DynamicForm from "../DynamicForm";
import { getModel, ModelType } from "@/services/model";
import _ from "lodash";
import request from "@/utils/request";

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
      required: boolean,
      min: number,
      max: number
    },
    defaultValue: string,
  },
  parentModelName: string,
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
  form: FormInstance;
  recentlyChangedValue: any
};

const Document: React.FC<MultiRecordTableProps> = (props) => {
  const intl = useIntl();
  const actionRef = useRef<ActionType>();

  const [loading, setLoading] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [modelName, setModelName] = useState<string>();
  const [columns, setColumns] = useState<ProColumns[]>();
  const [currentIndex, setCurrentIndex] = useState<number>();
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [nestedModel, setNestedModel] = useState<ModelType>();

  async function setupTableColumns(fields: object) {
    let columnSet = [];

    for (const fieldName in fields) {
      const field = fields[fieldName];
      if (!field.isSystemValue) {
        let column = {
          title: intl.formatMessage({
            id: `model.${modelName}.${field.labelKey}`,
            defaultMessage: field.defaultLabel,
          }),
          dataIndex: field.name,
        };

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

        if (field.type == 'model' && !_.isEmpty(field.modelName)) {
          let path: string;

          await getModel(field.modelName).then((response) => {
            if (!_.isEmpty(response.data)) {
              path = `/api${response.data.modelDataDefinition.path}`;
            }
          })

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
          <Tooltip title={
            intl.formatMessage({
              id: 'edit',
              defaultMessage: 'Edit',
            })
          }>
            <a onClick={() => {
              setCurrentIndex(index);

              let modifiedRecord = {};
              for (let fieldName in record) {
                modifiedRecord[`${fieldNamePrefix}${fieldName}`] = record[fieldName];
              }

              setCurrentRecord(modifiedRecord);
              setModalVisible(true);
            }}>
              <EditOutlined />
            </a>
          </Tooltip>,
          <Popconfirm
            title={intl.formatMessage({
              id: 'are_you_sure',
              defaultMessage: 'Are you sure?'
            })}
            onConfirm={() => removeRecord(index)}
            okText="Yes"
            cancelText="No"
          >
            <Tooltip title={
              intl.formatMessage({
                id: 'delete',
                defaultMessage: 'Delete',
              })
            }>
              <a>
                <DeleteOutlined />
              </a>
            </Tooltip>
          </Popconfirm>
        ]
      })
      setColumns(columnSet);
    }
  }

  useEffect(() => {
    setLoading(true);

    if (!nestedModel) {
      getModel(props.fieldDefinition.modelName).then((model) => {
        if (model && model.data) {
          setNestedModel(model.data);
        }
      });
    }

    setLoading(false);
  }, []);

  useEffect(() => {
    if (!modelName && nestedModel && nestedModel.modelDataDefinition) {
      setModelName(nestedModel.modelDataDefinition.name);
    }

    if (!columns) {
      const fields = nestedModel && nestedModel.modelDataDefinition
        ? nestedModel.modelDataDefinition.fields
        : [];
      setupTableColumns(fields);
    }
  }, [nestedModel]);

  const addRecord = (values: any) => {
    setLoading(true);

    let currentValues = { ...props.values };
    let modifiedValues = {};

    for (let subFieldName in values) {
      const realFieldName = subFieldName.replace(fieldNamePrefix, '');
      modifiedValues[realFieldName] = values[subFieldName];
    }

    currentValues[props.fieldName].push(modifiedValues);
    props.setValues(currentValues);

    setLoading(false);
  }

  const updateRecord = (index: number, values: any) => {
    setLoading(true);

    let modifiedValues = {};
    for (let subFieldName in values) {
      const realFieldName = subFieldName.replace(fieldNamePrefix, '');
      modifiedValues[realFieldName] = values[subFieldName];
    }

    let currentValues = { ...props.values };
    let currentRecords = [...currentValues[props.fieldName]];

    currentRecords[index] = modifiedValues;
    currentValues[props.fieldName] = currentRecords;

    props.setValues(currentValues);

    setLoading(false);
  }

  const removeRecord = (index: number) => {
    setLoading(true);

    let currentValues = { ...props.values };
    let currentRecords = [...currentValues[props.fieldName]];

    currentRecords.splice(index, 1);
    currentValues[props.fieldName] = currentRecords;

    props.setValues(currentValues);

    setLoading(false);
  }

  const fieldName = props.fieldNamePrefix
    ? props.fieldNamePrefix.concat(props.fieldName)
    : props.fieldName;
  const fieldNamePrefix = fieldName.concat('_');


  return !_.isEmpty(columns) ? (
    <Col data-key={fieldName} span={24}>
      <ProTable
        pagination={{ pageSize: 20, defaultPageSize: 20, hideOnSinglePage: true}}
        id={fieldName}
        rowKey="id"
        columns={columns}
        dataSource={!loading ? props.values[props.fieldName] : null}
        options={false}
        search={false}
        actionRef={actionRef}
        toolBarRender={() => [
          <Button
            type="primary"
            key="add"
            onClick={() => {
              setCurrentIndex(null);
              setCurrentRecord(null);
              setModalVisible(true);
            }}
          >
            <PlusOutlined /> <FormattedMessage id="pages.user.new" defaultMessage="New" />
          </Button>]}
      />

      {_.isEmpty(currentIndex) && _.isEmpty(currentRecord)
        ? <Modal
          key={fieldName.concat('Modal')}
          title={`Add ${modelName}`}
          centered
          destroyOnClose={true}
          visible={modalVisible}
          okButtonProps={{
            form: `${props.fieldNamePrefix}${props.fieldName}-form`,
            htmlType: "submit"
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
            formId={`${props.fieldNamePrefix}${props.fieldName}-form`}
            formType={_.isEmpty(currentIndex) && _.isEmpty(currentRecord) ? 'add' : 'update'}
            fieldNamePrefix={fieldNamePrefix}
            model={nestedModel}
            initialValues={currentRecord}
            onFinish={async (values: any) => {
              if (_.isEmpty(currentIndex) && _.isEmpty(currentRecord)) {
                addRecord(values);
              } else {
                updateRecord(currentIndex, values);
              }
              setModalVisible(false);
            }}
            submitterType="none"
            submitbuttonLabel={intl.formatMessage({
              id: 'ADD',
              defaultMessage: 'Add'
            })}
            resetbuttonLabel={intl.formatMessage({
              id: 'RESET',
              defaultMessage: 'Reset'
            })}
          />
        </Modal>
        : <Drawer
          key={fieldName.concat('Modal')}
          title={`Edit ${modelName}`}
          destroyOnClose={true}
          visible={modalVisible}
          footer={<Space style={{ float: "right" }}>
            <Button onClick={() => setModalVisible(false)}>
              <FormattedMessage id="CANCEL" defaultMessage="Cancel" />
            </Button>
            <Button form={`${props.fieldNamePrefix}${props.fieldName}-form`} type="primary" key="submit" htmlType="submit">
              <FormattedMessage id="UPDATE" defaultMessage="Update" />
            </Button>
          </Space>}
          onClose={() => setModalVisible(false)}
          width="40vw"
        >
          <DynamicForm
            formId={`${props.fieldNamePrefix}${props.fieldName}-form`}
            formType={_.isEmpty(currentIndex) && _.isEmpty(currentRecord) ? 'add' : 'update'}
            fieldNamePrefix={fieldNamePrefix}
            model={nestedModel}
            initialValues={currentRecord}
            onFinish={async (values: any) => {
              if (_.isEmpty(currentIndex) && _.isEmpty(currentRecord)) {
                addRecord(values);
              } else {
                updateRecord(currentIndex, values);
              }
              setModalVisible(false);
            }}
            submitterType="none"
            submitbuttonLabel={intl.formatMessage({
              id: 'ADD',
              defaultMessage: 'Add'
            })}
            resetbuttonLabel={intl.formatMessage({
              id: 'RESET',
              defaultMessage: 'Reset'
            })}
          />
        </Drawer>
      }
    </Col>) : (<Spin />)
};

export default Document;
