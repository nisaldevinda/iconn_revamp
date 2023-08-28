import React, { useEffect, useRef, useState } from 'react';
import { FormattedMessage, useIntl } from 'react-intl';
import ProTable, { ActionType, ProColumns } from '@ant-design/pro-table';
import { Button, Form, message, Popconfirm, Row, Space, Spin, Tag, Tooltip, Skeleton, Card } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined, EyeOutlined } from '@ant-design/icons';
import { getModel, ModelType } from '@/services/model';
import _ from 'lodash';
import request, { APIResponse } from '@/utils/request';
import { DrawerForm, ModalForm } from '@ant-design/pro-form';
import FormInput from '../FormInput';
import { genarateEmptyValuesObject } from '@/utils/utils';
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from './../../pages/403';

export type BasicContainerProps = {
  titleKey: string;
  defaultTitle: string;
  rowId: 'id' | string;
  refresh?: number;
  isforceReload?: boolean;
  model: ModelType;
  tableColumns: Array<{
    name: string;
    filterable?: boolean;
    sortable?: boolean;
    valueEnum?: any;
    render?: (data?: any, record?: any) => JSX.Element;
  }>;
  defaultSortableField: { fildName: string; mode: 'ascend' | 'descend' };
  readableFields?: Array<string>;
  editableFields?: Array<string>;
  searchFields: Array<String>;
  recordActions?: Array<'add' | 'edit' | 'delete' | 'view' | { (record: any, tableRef: any): JSX.Element }>;
  addFormType: 'none' | 'model' | 'drawer' | 'function';
  editFormType: 'none' | 'model' | 'drawer' | 'function';
  viewFormType?: 'none' | 'model' | 'drawer' | 'function';
  getAllFunction: (params: any) => Promise<boolean | void>;
  addFunction: (record?: any) => Promise<APIResponse | void>;
  editFunction: (record: any) => Promise<APIResponse | void>;
  viewFunction?: (record: any) => Promise<APIResponse | void>;
  rowWiseEditActionPermissionHandler?: (record?: any) => boolean;
  deleteFunction: (record: string) => Promise<APIResponse | void>;
  rowWiseDeleteActionPermissionHandler?: (record?: any) => boolean;
  permissions?: {
    addPermission?: string;
    editPermission?: string;
    deletePermission?: string;
    readPermission?: string;
  };
  fieldPermission?: any;
  disableSearch?: boolean;
};

const BasicContainer: React.FC<BasicContainerProps> = (props) => {
  const intl = useIntl();
  const actionRef = useRef<ActionType>();

  const [addFormReference] = Form.useForm();
  const [addFormVisible, setAddFormVisible] = useState(false);
  const [addFormChangedValue, setAddFormChangedValue] = useState({});

  const [editFormReference] = Form.useForm();
  const [editFormVisible, setEditFormVisible] = useState(false);
  const [viewFormVisible, setViewFormVisible] = useState(false);
  const [editFormChangedValue, setEditFormChangedValue] = useState({});

  const [viewFormReference] = Form.useForm();

  const [loading, setLoading] = useState(false);
  const [modelName, setModelName] = useState<string>();
  const [columns, setColumns] = useState<ProColumns[]>();
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [tagData, setTagData] = useState();
  const [currentField, setCurrentField] = useState();
  const [tagName, setTagName] = useState();
  const [searchPlaceholder, setSearchPlaceholder] = useState<string>();
  const [totalTablePages, setTotalTablePages] = useState<number>();
  const access = useAccess();
  const { hasPermitted } = access;
  const { permissions } = props;
  const { addPermission, editPermission, deletePermission, readPermission } = permissions || {};
  const [searchText, setSearchText] = useState("");

  useEffect(() => {
    if (!modelName && props.model && props.model.modelDataDefinition) {
      setModelName(props.model.modelDataDefinition.name);
    }

    const fields =
      props.model && props.model.modelDataDefinition ? props.model.modelDataDefinition.fields : {};

    if (!_.isEmpty(fields) && !columns) {
      setupTableColumns(fields);
    }

    if (!_.isEmpty(fields) && !searchPlaceholder) {
      setupSearchPlaceholder(fields);
    }
  }, [props.model]);


  useEffect(() => {
    const fields = props.model && props.model.modelDataDefinition
      ? props.model.modelDataDefinition.fields
      : {};

    if (!_.isEmpty(fields)) {
      setupTableColumns(fields);
    }

    actionRef?.current?.reload();
  }, [props.refresh]);

  const getTagData = async (_field) => {
    let path: string;
    const response = await getModel(_field.modelName);
    if (!_.isEmpty(response.data)) {
      path = `/api${response.data.modelDataDefinition.path}`;
    }
    const res = await request(path);
    await setTagData(res.data);
    return res.data;
  };

  const rowWiseEditActionPermissionHandler = (record: any) => {
    return props.rowWiseEditActionPermissionHandler
      ? props.rowWiseEditActionPermissionHandler(record)
      : true;
  };

  const rowWiseDeleteActionPermissionHandler = (record: any) => {
    return props.rowWiseDeleteActionPermissionHandler
      ? props.rowWiseDeleteActionPermissionHandler(record)
      : true;
  };

  async function setupTableColumns(fields: object) {
    let tableColumns = [];
    let userDefinedColums = [];

    if (props.tableColumns) {
      userDefinedColums = props.tableColumns;
    } else {
      userDefinedColums = Object.values(fields)
        .filter(field => {
          return field.type == 'string';
        })
        .map(field => {
          return {
            name: field.name,
            sortable: true,
            filterable: true
          };
        });
    }

    for (let index in userDefinedColums) {
      const _column = userDefinedColums[index];

      let _field = {};
      if (_column.name.includes('.')) {
        let [parentFieldName, childFieldName] = _column.name.split('.');
        const parentField = fields[parentFieldName];
        await getModel(parentField.modelName).then((response) => {
          if (!_.isEmpty(response.data)) {
            _field = response?.data?.modelDataDefinition?.fields[childFieldName];
          }
        });
      } else {
        _field = fields[_column.name];
      }

      if (_.isEmpty(_field)) {
        continue;
      }

      if (_.isArray(props.readableFields) && !props.readableFields.includes(_column.name)) {
        continue;
      }

      let column = {};
      let fieldname = null;
      //todo handle realtionships fro column type "model"
      if (_column.render) {
        fieldname = _field.type === 'model' ? _field.name + 'Id' : _field.name;
        column = {
          title: intl.formatMessage({
            id: `model.${modelName}.${_field.labelKey}`,
            defaultMessage: _field.defaultLabel,
          }),
          key: fieldname,
          dataIndex: fieldname,
          render: (data, record) => _column.render(data, record),
          ellipsis: true,
        };
      } else {
        fieldname = _column.name.includes('.')
          ? [
            _column.name.split('.')[0],
            _field.type === 'model' ? _field.name + 'Id' : _field.name,
          ]
          : _field.type === 'model'
            ? _field.name + 'Id'
            : _field.name;
        column = {
          title: intl.formatMessage({
            id: `model.${modelName}.${_field.labelKey}`,
            defaultMessage: _field.defaultLabel,
          }),
          key: fieldname,
          dataIndex: fieldname,
          ellipsis: true,
        };
        if (_column.valueEnum) {
          column['valueEnum'] = _column.valueEnum;
        } else {
          if (_field.type == 'enum' && _.isArray(_field.values)) {
            let valueEnum = {};
            if (_field.values && _.isArray(_field.values)) {
              _field.values.map((value) => {
                valueEnum[value.value] = intl.formatMessage({
                  id: `model.${modelName}.${_field.name}.${value.labelKey}`,
                  defaultMessage: value.defaultLabel,
                });
              });
            }
            column['valueEnum'] = valueEnum;
          }

          // to show clour in day type List view
          if (_field.type === 'string' && _field.name === 'typeColor') {
            column['render'] = (_record) => {
              return (
                <Space>
                  <div style={{ height: '15px', backgroundColor: _record.props.children, width: '15px', borderRadius: '6px' }} />
                  <span>{_record.props.children}</span>
                </Space>
              )
            }
          }
          if (_field.type === 'model' && !_.isEmpty(_field.modelName)) {
            let path: string;

            if (_field.route) {
              path = _field.route;
            } else {
              await getModel(_field.modelName).then((response) => {
                if (!_.isEmpty(response.data)) {
                  path = `/api${response.data.modelDataDefinition.path}`;
                }
              });
            }

            const valueEnum = {};
            await request(path).then((response) => {
              if (response && response.data && Array.isArray(response.data)) {
                response.data.forEach((data) => {
                  valueEnum[data[_field.enumValueKey]] = data[_field.enumLabelKey];
                });
              }
            });

            column['valueEnum'] = valueEnum;
          }
        }

        if (_field.type === 'tag' && !_.isEmpty(_field.modelName)) {
          setTagName(_field.name);
          getTagData(_field).then((el) => {
            column['render'] = (dom, record) => {
              const fieldName = _field.name;

              return JSON.parse(record[fieldName]).map((element: any) => {
                return (
                  <Tag>
                    {_.get(_.find(el, [_field.enumValueKey, element]), _field.enumLabelKey, '')}{' '}
                  </Tag>
                );
              });
            };
          });
        }
      }

      if (_column.sortable) {
        column['sorter'] = true;
      }

      if (_column.filterable) {
        column['filters'] = true;
        column['onFilter'] = true;
      }

      tableColumns.push(column);
    }

    if (tableColumns.length > 0) {
      tableColumns.push({
        title: intl.formatMessage({
          id: 'actions',
          defaultMessage: 'Actions',
        }),
        key: 'actions',
        valueType: 'option',
        align: 'center',
        fixed: 'right',
        width: 80,
        render: (text, record, index, action) => {
          let actions: Array<JSX.Element> = [];

          if (
            !props.recordActions ||
            (_.isArray(props.recordActions) && props.recordActions.includes('edit'))
          ) {
            if (record.isReadOnly == undefined || record.isReadOnly == null || !record.isReadOnly) {
              actions.push(
                <Access accessible={hasPermitted(editPermission)}>
                  <Tooltip
                    placement={'bottom'}
                    key="editRecordTooltip"
                    title={intl.formatMessage({
                      id: 'edit',
                      defaultMessage: 'Edit',
                    })}
                  >
                    <Button
                      type="link"
                      style={{ padding: 0 }}
                      disabled={!rowWiseEditActionPermissionHandler(record)}
                      key="editRecordButton"
                      onClick={async () => {
                        if (props.addFormType == 'function') {
                          await props.editFunction(record);
                          actionRef?.current?.reload();
                        } else {
                          const intialValues = genarateEmptyValuesObject(props.model);
                          setCurrentRecord({ intialValues, ...record });
                          setEditFormVisible(true);
                        }
                      }}
                    >
                      <EditOutlined />
                    </Button>
                  </Tooltip>
                </Access>,
              );
            }
          }

          if (
            !props.recordActions ||
            (_.isArray(props.recordActions) && props.recordActions.includes('delete'))
          ) {

            if (record.isReadOnly == undefined || record.isReadOnly == null || !record.isReadOnly) {

              actions.push(
                <Access accessible={hasPermitted(deletePermission)}>
                  <div onClick={(e) => e.stopPropagation()}>
                    <Popconfirm
                      disabled={!rowWiseDeleteActionPermissionHandler(record)}
                      key="deleteRecordConfirm"
                      title={intl.formatMessage({
                        id: 'are_you_sure',
                        defaultMessage: 'Are you sure?',
                      })}
                      onConfirm={async () => {
                        const key = 'deleting';
                        message.loading({
                          content: intl.formatMessage({
                            id: 'deleting',
                            defaultMessage: 'Deleting...',
                          }),
                          key,
                        });

                        await props
                          .deleteFunction(record)
                          .then((response: APIResponse) => {
                            if (response.error) {
                              message.error({
                                content:
                                  response.message ??
                                  intl.formatMessage({
                                    id: 'failedToDelete',
                                    defaultMessage: 'Failed to delete',
                                  }),
                                key,
                              });
                              return;
                            }

                            message.success({
                              content:
                                response.message ??
                                intl.formatMessage({
                                  id: 'successfullyDeleted',
                                  defaultMessage: 'Successfully Deleted',
                                }),
                              key,
                            });

                            actionRef?.current?.reload();
                          })

                          .catch((error: APIResponse) => {
                            let errorMessage;
                            let errorMessageInfo;
                            if (error.message.includes('.')) {
                              let errorMessageData = error.message.split('.');
                              errorMessage = errorMessageData.slice(0, 1);
                              errorMessageInfo = errorMessageData.slice(1).join('.');
                            }

                            message.error({
                              content: error.message ? (
                                <>
                                  {errorMessage ?? error.message}
                                  <br />
                                  <span
                                    style={{
                                      fontWeight: 150,
                                      color: '#A9A9A9',
                                      fontSize: '14px',
                                      paddingLeft: '8px',
                                    }}
                                  >
                                    {errorMessageInfo ?? ''}
                                  </span>
                                </>
                              ) : (
                                intl.formatMessage({
                                  id: 'failedToDelete',
                                  defaultMessage: 'Cannot Delete',
                                })
                              ),
                              key,
                            });
                          });
                      }}
                      okText="Yes"
                      cancelText="No"
                    >
                      <Tooltip
                        placement={'bottom'}
                        key="deleteRecordTooltip"
                        title={intl.formatMessage({
                          id: 'delete',
                          defaultMessage: 'Delete',
                        })}
                      >
                        <Button
                          type="link"
                          style={{ padding: 0 }}
                          disabled={!rowWiseDeleteActionPermissionHandler(record)}
                          key="deleteRecordButton"
                        >
                          <DeleteOutlined />
                        </Button>
                      </Tooltip>
                    </Popconfirm>
                  </div>
                </Access>,
              );
            }
          }


          if (
            !props.recordActions ||
            (_.isArray(props.recordActions) && props.recordActions.includes('view'))
          ) {
            if (record.isReadOnly != undefined && record.isReadOnly != null && record.isReadOnly) {
              actions.push(
                <Access accessible={hasPermitted(editPermission)}>
                  <Tooltip
                    placement={'bottom'}
                    key="viewRecordTooltip"
                    title={intl.formatMessage({
                      id: 'view',
                      defaultMessage: 'View',
                    })}
                  >
                    <Button
                      type="link"
                      style={{ padding: 0 }}
                      // disabled={!rowWiseEditActionPermissionHandler(record)}
                      key="viewRecordButton"
                      onClick={async () => {
                        if (props.addFormType == 'function') {
                          await props.viewFunction(record);
                          actionRef?.current?.reload();
                        } else {
                          const intialValues = genarateEmptyValuesObject(props.model);
                          setCurrentRecord({ intialValues, ...record });
                          setViewFormVisible(true);
                        }
                      }}
                    >
                      <EyeOutlined />
                    </Button>
                  </Tooltip>
                </Access>,
              );
            }
          }

          const customActions: Array<JSX.Element> =
            props.recordActions && _.isArray(props.recordActions)
              ? props.recordActions.filter((action) => !['add', 'edit', 'delete', 'view'].includes(action))
              : [];

          for (let index in customActions) {
            const action = customActions[index];
            actions.push(action(record, actionRef?.current));
          }

          return actions;
        },
      });

      setColumns(tableColumns);
    }
  }

  const setupSearchPlaceholder = (fields: object) => {
    let searchItemName: Array<string> = [];

    if (props.searchFields) {
      props.searchFields.forEach((item) => {
        const field = fields[item] ?? {};
        const label = intl.formatMessage({
          id: `model.${modelName}.${field.labelKey}`,
          defaultMessage: field.defaultLabel,
        });
        searchItemName.push(label);
      });
    }
    setSearchPlaceholder(
      intl
        .formatMessage({
          id: 'Search',
          defaultMessage: 'Search By',
        })
        .concat(' ')
        .concat(searchItemName.join(', ')),
    );
  };

  const convertTagObject = (record) => {
    const convRecord = {};
    for (const key in record) {
      if (hasJsonStructure(record[key])) {
        convRecord[key] = JSON.parse(record[key]);
      } else convRecord[key] = record[key];
    }
    return convRecord;
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
  const hasJsonStructure = (str) => {
    if (typeof str !== 'string') return false;
    try {
      const result = JSON.parse(str);
      const type = Object.prototype.toString.call(result);
      return type === '[object Object]' || type === '[object Array]';
    } catch (err) {
      return false;
    }
  };
  const addFormFieldSet = () => {
    let selectedFields = [];
    const fields = props.model.modelDataDefinition.fields;
    if (_.isArray(props.editableFields)) {
      props.editableFields.forEach((field) => {
        if (!_.isEmpty(fields[field])) {
          selectedFields.push(fields[field]);
        }
      });
    } else {
      selectedFields = Object.values(fields).filter((field) => {
        return !field.isSystemValue && !field.isComputedProperty;
      });
    }

    return (
      <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
        {selectedFields.map((inputField) => (
          <FormInput
            key={inputField.name}
            fieldName={inputField.name}
            model={props.model}
            form={addFormReference}
            values={currentRecord}
            setValues={setCurrentRecord}
            recentlyChangedValue={addFormChangedValue}
            permission={props.fieldPermission}
          />
        ))}
      </Row>
    );
  };

  const addViewProps = {
    title: intl.formatMessage({
      id: `add_${props.titleKey}`,
      defaultMessage: `Add ${props.defaultTitle}`,
    }),
    key: `add_${props.titleKey}`,
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

      await props
        .addFunction(convertTagString(currentRecord))
        .then((response: APIResponse) => {
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

          const fields =
            props.model && props.model.modelDataDefinition
              ? props.model.modelDataDefinition.fields
              : {};

          if (!_.isEmpty(fields)) {
            setupTableColumns(fields);
          }

          actionRef?.current?.reload();
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

  const editFormFieldSet = (record: any) => {
    let selectedFields: Array<any> = [];
    const fields = props.model.modelDataDefinition.fields;

    if (_.isArray(props.readableFields)) {
      props.readableFields.forEach((field) => {
        if (!_.isEmpty(fields[field])) {
          selectedFields.push(fields[field]);
        }
      });
    } else {
      selectedFields = Object.values(fields).filter((field) => {
        return !field.isSystemValue && !field.isComputedProperty;
      });
    }

    return (
      <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
        {selectedFields.map((inputField) => (
          <FormInput
            key={inputField.name}
            fieldName={inputField.name}
            model={props.model}
            form={addFormReference}
            values={currentRecord}
            setValues={setCurrentRecord}
            recentlyChangedValue={editFormChangedValue}
            permission={props.fieldPermission}
          />
        ))}
      </Row>
    );
  };

  const editViewProps = {
    title: intl.formatMessage({
      id: `edit_${props.titleKey}`,
      defaultMessage: `Edit ${props.defaultTitle}`,
    }),
    key: `edit_${props.titleKey}`,
    visible: editFormVisible,
    onVisibleChange: setEditFormVisible,
    form: editFormReference,
    onValuesChange: setEditFormChangedValue,
    submitter: {
      searchConfig: {
        submitText: intl.formatMessage({
          id: 'update',
          defaultMessage: 'Update',
        }),
        resetText: intl.formatMessage({
          id: 'cancel',
          defaultMessage: 'Cancel',
        }),
      },
    },
    onFinish: async () => {
      const key = 'updating';
      message.loading({
        content: intl.formatMessage({
          id: 'updating',
          defaultMessage: 'Updating...',
        }),
        key,
      });

      await props
        .editFunction(convertTagString(currentRecord))
        .then((response: APIResponse) => {
          if (response.error) {
            message.error({
              content:
                response.message ??
                intl.formatMessage({
                  id: 'failedToUpdate',
                  defaultMessage: 'Failed to Update',
                }),
              key,
            });
            if (response.data && Object.keys(response.data).length !== 0) {
              for (const feildName in response.data) {
                const errors = response.data[feildName];
                editFormReference.setFields([
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
                id: 'successfullyUpdated',
                defaultMessage: 'Successfully Updated',
              }),
            key,
          });

          actionRef?.current?.reload();
          setEditFormVisible(false);
        })

        .catch((error: APIResponse) => {
          let errorMessage;
          let errorMessageInfo;
          if (error.message.includes('.')) {
            let errorMessageData = error.message.split('.');
            errorMessage = errorMessageData.slice(0, 1);
            errorMessageInfo = errorMessageData.slice(1).join('.');
          }

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
                id: 'failedToUpdate',
                defaultMessage: 'Cannot Update',
              })
            ),
            key,
          });
          if (error.data && Object.keys(error.data).length !== 0) {
            for (const feildName in error.data) {
              const errors = error.data[feildName];
              editFormReference.setFields([
                {
                  name: feildName,
                  errors: errors,
                },
              ]);
            }
          }
        });
    },
    initialValues: convertTagObject(currentRecord),
  };

  const viewFormProps = {
    title: intl.formatMessage({
      id: `view_${props.titleKey}`,
      defaultMessage: `View ${props.defaultTitle}`,
    }),
    key: `view_${props.titleKey}`,
    visible: viewFormVisible,
    onVisibleChange: setViewFormVisible,
    form: viewFormReference,
    onValuesChange: setEditFormChangedValue,
    submitter: {
      render: (props, doms) => {
        return [
          <Button key="cancel" size="middle" onClick={() => {
            setViewFormVisible(false);
          }} >
            Cancel
          </Button>
        ]
      }
    },
    initialValues: convertTagObject(currentRecord),
  };



  useEffect(() => {
    actionRef?.current?.reload();
  }, [props.toolbarFilterId]);

  const handleDisableSearch = () => {
    if (props.disableSearch) {
      return false;
    }
    return {
      className: 'basic-container-search',
      placeholder: searchPlaceholder,
      onChange: (value: any) => {
        setSearchText(value.target.value);
        if (_.isEmpty(value.target.value)) {
          actionRef.current?.reset();
          actionRef.current?.reload();
        }
      },
      value: searchText
    };
  };

  return !_.isEmpty(columns) ? (
    <Access accessible={hasPermitted(readPermission)} fallback={<PermissionDeniedPage />}>
      <ProTable
        actionRef={actionRef}
        rowKey="id"
        search={false}
        onLoad={() => {
          if (props.isforceReload) {
            actionRef.current?.reset();
            actionRef.current?.reload();
          }
        }}
        columns={columns}
        pagination={{
          pageSize: 10,
          defaultPageSize: 10,
          hideOnSinglePage: totalTablePages > 10 ? false : true,
        }}
        options={{
          search: true,
          reload: () => {
            actionRef.current?.reset();
            actionRef.current?.reload();
            setSearchText("");
          }
        }}
        toolbar={{
          search: handleDisableSearch(),
        }}
        toolBarRender={() => {
          let renders: Array<JSX.Element> = [];

          if (
            !props.recordActions ||
            (_.isArray(props.recordActions) && props.recordActions.includes('add'))
          ) {
            renders.push(
              <Access accessible={hasPermitted(addPermission)}>
                <Button
                  type="primary"
                  key="add"
                  onClick={async () => {
                    if (props.addFormType == 'function') {
                      await props.addFunction();
                      actionRef?.current?.reload();
                    } else {
                      const intialValues = genarateEmptyValuesObject(props.model);
                      setCurrentRecord(intialValues);
                      setAddFormVisible(true);
                    }
                  }}
                  data-key="add"
                >
                  <PlusOutlined /> <FormattedMessage id="new" defaultMessage="New" />
                </Button>
              </Access>,
            );
          }
          if (props.toolbarFilter) {
            renders.push(props.toolbarFilterFunction);
          }

          return renders;
        }}
        request={async (params = {}, sorter, _filter) => {
          const filter = Object.keys(_filter)
            .filter((key) => !_.isEmpty(_filter[key]))
            .reduce((obj, key) => {
              obj[key] = _filter[key];
              return obj;
            }, {});

          if (!_.isEmpty(props.searchFields)) {
            params = { ...params, searchFields: props.searchFields };
          }

          if (!_.isEmpty(filter)) {
            params = { ...params, filter };
          }

          if (!_.isEmpty(sorter)) {
            params = { ...params, sorter };
          } else if (props.defaultSortableField) {
            const sorter = {};
            sorter[props.defaultSortableField.fildName] = props.defaultSortableField.mode;
            params = { ...params, sorter };
          }
          if (props.toolbarFilter) {
            params = { ...params, filterBy: props.toolbarFilterId };
          }
          const response = await props.getAllFunction(params);
          setTotalTablePages(response?.data?.total);
          return {
            data: response?.data?.data,
            success: true,
            total: response?.data?.total,
          };
        }}
        // click row
        onRow={(record, rowIndex) => {
          return {
            onClick: async () => {
              if (
                rowWiseDeleteActionPermissionHandler(record) &&
                (!props.recordActions ||
                  (_.isArray(props.recordActions) && props.recordActions.includes('edit')))
              ) {
                if (props.addFormType == 'function') {
                  if (hasPermitted(editPermission)) {
                    if (record.isReadOnly == undefined || record.isReadOnly == null || !record.isReadOnly) {
                      if (props.model.modelDataDefinition.name != 'user') {
                        await props.editFunction(record);
                      }
                    }
                    actionRef?.current?.reload();
                  }
                } else {
                  if (hasPermitted(editPermission)) {
                    if (record.isReadOnly == undefined || record.isReadOnly == null || !record.isReadOnly) {
                      const intialValues = genarateEmptyValuesObject(props.model);
                      setCurrentRecord({ intialValues, ...record });
                      setEditFormVisible(true);
                    }
                  }
                }

              }
            }
          }
        }}
      />

      {props.addFormType == 'drawer' ? (
        <DrawerForm
          drawerProps={{
            destroyOnClose: true,
          }}
          width="40vw"
          {...addViewProps}
        >
          {props.model.modelDataDefinition ? addFormFieldSet() : <Spin />}
        </DrawerForm>
      ) : (
        <ModalForm
          modalProps={{
            destroyOnClose: true,
          }}
          {...addViewProps}
        >
          {props.model.modelDataDefinition ? addFormFieldSet() : <Spin />}
        </ModalForm>
      )}

      {props.editFormType == 'model' ? (
        <ModalForm
          modalProps={{
            destroyOnClose: true,
          }}
          {...editViewProps}
        >
          {editFormFieldSet(currentRecord)}
        </ModalForm>
      ) : (
        <DrawerForm
          width="40vw"
          drawerProps={{
            destroyOnClose: true,
          }}
          {...editViewProps}
        >
          {editFormFieldSet(currentRecord)}
        </DrawerForm>
      )}

      {props.viewFormType == 'model' ? (
        <ModalForm
          modalProps={{
            destroyOnClose: true,
          }}
          {...viewFormProps}
        >
          {editFormFieldSet(currentRecord)}
        </ModalForm>
      ) : (
        <DrawerForm
          width="40vw"
          drawerProps={{
            destroyOnClose: true,
          }}
          {...viewFormProps}
        >
          {editFormFieldSet(currentRecord)}
        </DrawerForm>
      )}
    </Access>
  ) : (
    <Card><Skeleton active /></Card>
  );
};

export default BasicContainer;
