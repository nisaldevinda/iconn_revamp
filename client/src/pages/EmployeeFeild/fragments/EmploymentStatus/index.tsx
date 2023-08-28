import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
  getAllEmploymentStatus,
  addEmploymentStatus,
  updateEmploymentStatus,
  removeEmploymentStatus
} from '@/services/employmentStatus';
import { Space, Typography } from 'antd';
import { Form, message, Spin } from 'antd';
import { DrawerForm, ModalForm, ProFormDependency, ProFormDigit, ProFormGroup, ProFormSelect, ProFormSwitch, ProFormText } from '@ant-design/pro-form';
import { useIntl } from 'umi';
import _ from 'lodash';

const EmploymentStatus: React.FC = () => {
  const { Text } = Typography;

  const intl = useIntl();
  const [addFormRef] = Form.useForm();
  const [editFormRef] = Form.useForm();

  const [loading, setLoading] = useState<boolean>(false);
  const [model, setModel] = useState<any>();

  const [addFormVisibility, setAddFormVisibility] = useState<boolean>(false);
  const [editFormVisibility, setEditFormVisibility] = useState<boolean>(false);
  const [initialFormData, setInitialFormData] = useState({});
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    init();
  }, [])

  const init = async () => {
    setLoading(true);

    // retrieve model
    const response = await getModel(Models.EmploymentStatus);

    if (response && response.data) {
      console.log('response.data >>>> ', response.data);
      setModel(response.data)
    }

    setLoading(false);
  }

  const calculateMaximumNoticePeriod = (noticePeriodUnit: string) => {
    switch (noticePeriodUnit) {
      case 'DAYS':
        return 30;
      case 'MONTHS':
        return 11;
      default:
        return 2;
    }
  }

  const formInputRender = model && <>
    <ProFormGroup>
      <ProFormText
        width="md"
        name="name"
        label={intl.formatMessage({
          id: 'employment_status.employment_status_name',
          defaultMessage: 'Employment Status Name',
        })}
        rules={[{ required: true, message: 'Required' }]}
      />
    </ProFormGroup>
    <ProFormSelect
      width="md"
      name="category"
      label={intl.formatMessage({
        id: 'employment_status.category',
        defaultMessage: 'Category',
      })}
      options={model.modelDataDefinition.fields.category.values.map(option => {
        return {
          value: option.value,
          label: intl.formatMessage({
            id: option.labelKey,
            defaultMessage: option.defaultLabel,
          })
        };
      })}
      rules={[{ required: true, message: 'Required' }]}
    />
    <Space style={{ marginBottom: 8 }}>
      <Text>
        {intl.formatMessage({
          id: 'employment_status.allow_to_define_employment_period',
          defaultMessage: 'Allow to define Employment Period',
        })}
      </Text>
      <ProFormSwitch
        width="md"
        name="allowEmploymentPeriod"
        formItemProps={{ style: { marginBottom: 0 } }}
      />
    </Space>
    <br />
    <ProFormDependency name={['allowEmploymentPeriod', 'period', 'periodUnit']}>
      {({ allowEmploymentPeriod, period, periodUnit }) => {
        return allowEmploymentPeriod == true && <>
          <Form.Item
            name='period'
            rules={[
              {
                message: 'Required',
                validator: () => {
                  if ((period != 0 && !period) || !periodUnit) {
                    return Promise.reject();
                  }

                  return Promise.resolve();
                }
              },
              {
                message: 'Invalid',
                validator: () => {
                  const max = calculateMaximumNoticePeriod(periodUnit);
                  if (periodUnit > max) {
                    return Promise.reject();
                  }

                  return Promise.resolve();
                }
              }
            ]}
            style={{ width: 200 }}
            className='period-input-field'
          >
            <ProFormDigit
              name='period'
              width="xs"
              min={0}
              max={calculateMaximumNoticePeriod(periodUnit)}
              addonBefore={
                <ProFormSelect
                  name='periodUnit'
                  options={model.modelDataDefinition.fields.periodUnit.values.map(option => {
                    return {
                      value: option.value,
                      label: intl.formatMessage({
                        id: option.labelKey,
                        defaultMessage: option.defaultLabel,
                      })
                    };
                  })}
                />}
            />
          </Form.Item>
        </>
      }}
    </ProFormDependency>
    <Space style={{ marginBottom: 8 }}>
      <Text>
        {intl.formatMessage({
          id: 'employment_status.enable_email_notification',
          defaultMessage: 'Enable Email Notification',
        })}
      </Text>
      <ProFormSwitch
        width="md"
        name="enableEmailNotification"
        formItemProps={{ style: { marginBottom: 0 } }}
      />
    </Space>
    <br />
    <ProFormDependency name={['enableEmailNotification', 'notificationPeriod', 'notificationPeriodUnit']}>
      {({ enableEmailNotification, notificationPeriod, notificationPeriodUnit }) => {
        return enableEmailNotification == true && <Form.Item
          name='notificationPeriod'
          rules={[
            {
              message: 'Required',
              validator: () => {
                if ((notificationPeriod != 0 && !notificationPeriod) || !notificationPeriodUnit) {
                  return Promise.reject();
                }

                return Promise.resolve();
              }
            },
            {
              message: 'Invalid',
              validator: () => {
                const max = calculateMaximumNoticePeriod(notificationPeriodUnit);
                if (notificationPeriodUnit > max) {
                  return Promise.reject();
                }

                return Promise.resolve();
              }
            }
          ]}
          style={{ width: 430 }}
          className='period-input-field'
        >
          <ProFormDigit
            name='notificationPeriod'
            width="xs"
            min={0}
            max={calculateMaximumNoticePeriod(notificationPeriodUnit)}
            addonBefore={
              <ProFormSelect
                name='notificationPeriodUnit'
                options={model.modelDataDefinition.fields.notificationPeriodUnit.values.map(option => {
                  return {
                    value: option.value,
                    label: intl.formatMessage({
                      id: option.labelKey,
                      defaultMessage: option.defaultLabel,
                    })
                  };
                })}
              />}
            addonAfter={<Text type='secondary'>prior to the Employment end date</Text>}
          />
        </Form.Item>
      }}
    </ProFormDependency>
  </>

  return (<Spin spinning={loading}>
    <BasicContainer
      rowId="id"
      titleKey="employment_status"
      defaultTitle="Employment Status"
      model={model}
      tableColumns={[
        { name: 'name', sortable: true, filterable: true },
        { name: 'category' },
      ]}
      recordActions={['add', 'edit', 'delete']}
      defaultSortableField={{ fildName: 'updatedAt', mode: 'descend' }}
      searchFields={['name']}
      refresh={refresh}
      addFormType="function"
      editFormType="function"
      getAllFunction={getAllEmploymentStatus}
      addFunction={async (record) => {
        setInitialFormData(null);
        setAddFormVisibility(true);
      }}
      editFunction={async (record) => {
        record.allowEmploymentPeriod = record.allowEmploymentPeriod || record.allowEmploymentPeriod == 1 ? true : false;
        record.enableEmailNotification = record.enableEmailNotification || record.enableEmailNotification == 1 ? true : false;
        setInitialFormData(record);
        setEditFormVisibility(true)
      }} deleteFunction={removeEmploymentStatus}
      permissions={{
        addPermission: 'master-data-write',
        editPermission: 'master-data-write',
        deletePermission: 'master-data-write',
        readPermission: 'master-data-write',
      }}
      rowWiseEditActionPermissionHandler={(record) => {
        const { isInvisible } = record;
        return !isInvisible;
      }}
      rowWiseDeleteActionPermissionHandler={(record) => {
        const { isInvisible } = record;
        return !isInvisible;
      }}
    />

    {model && <>
      <ModalForm
        form={addFormRef}
        title={intl.formatMessage({
          id: 'employment_status.add_employment_status',
          defaultMessage: 'Add Employment Status',
        })}
        visible={addFormVisibility}
        modalProps={{
          destroyOnClose: true
        }}
        submitter={{
          searchConfig: {
            submitText: intl.formatMessage({
              id: 'save',
              defaultMessage: 'Save',
            }),
            resetText: intl.formatMessage({
              id: 'cancel',
              defaultMessage: 'Cancel',
            }),
          },
        }}
        onVisibleChange={setAddFormVisibility}
        onFinish={async (values) => {
          const key = 'saving';
          message.loading({
            content: intl.formatMessage({
              id: 'saving',
              defaultMessage: 'Saving...',
            }),
            key,
          });

          addEmploymentStatus(values)
            .then(response => {
              setAddFormVisibility(false);
              setRefresh(prev => prev + 1);

              message.success({
                content:
                  response.message ??
                  intl.formatMessage({
                    id: 'successfullySaved',
                    defaultMessage: 'Successfully Saved',
                  }),
                key,
              });
            })
            .catch(error => {
              if (error.data) {
                for (const fieldName in error.data) {
                  addFormRef.setFields([
                    {
                      name: fieldName,
                      errors: error.data[fieldName]
                    }
                  ]);
                }
              }

              message.error({ content: error.message, key })
            });
        }}
        initialValues={initialFormData}
      >
        {formInputRender}
      </ModalForm>

      <DrawerForm
        form={editFormRef}
        title={intl.formatMessage({
          id: 'employment_status.edit_employment_status',
          defaultMessage: 'Edit Employment Status',
        })}
        visible={editFormVisibility}
        onVisibleChange={setEditFormVisibility}
        drawerProps={{
          destroyOnClose: true
        }}
        submitter={{
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
        }}
        onFinish={async (values) => {
          const key = 'updating';
          message.loading({
            content: intl.formatMessage({
              id: 'updating',
              defaultMessage: 'Updating...',
            }),
            key,
          });

          updateEmploymentStatus({ ...initialFormData, ...values })
            .then(response => {
              setEditFormVisibility(false);
              setRefresh(prev => prev + 1);

              message.success({
                content:
                  response.message ??
                  intl.formatMessage({
                    id: 'successfullyUpdated',
                    defaultMessage: 'Successfully Updated',
                  }),
                key,
              });
            })
            .catch(error => {
              if (error.data) {
                for (const fieldName in error.data) {
                  editFormRef.setFields([
                    {
                      name: fieldName,
                      errors: error.data[fieldName]
                    }
                  ]);
                }
              }

              message.error({ content: error.message, key })
            });
        }}
        drawerProps={{
          destroyOnClose: true,
        }}
        width="40vw"
        initialValues={initialFormData}
      >
        {formInputRender}
      </DrawerForm>
    </>}
  </Spin>
  );
}

export default EmploymentStatus



























// import React, { useState, useEffect } from 'react'
// import BasicContainer from '@/components/BasicContainer';
// import { getModel, Models } from '@/services/model';
// import { getAllEmploymentStatus, addEmploymentStatus, updateEmploymentStatus, removeEmploymentStatus } from '@/services/employmentStatus'
// import _ from 'lodash'
// import { Button, Form, Input, List, message, Select, Space } from 'antd';
// import ProForm, { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
// import { useIntl } from 'umi';
// import { Option } from 'antd/lib/mentions';
// import { CloseOutlined, PlusOutlined } from '@ant-design/icons';

// const emptyEmploymentStatus = {
//   name: "",
//   data: [
//     {
//       "title": "",
//       "name": "",
//       "period": 0,
//       "periodUnit": "MONTHS",
//     }
//   ]
// };

// const EmploymentStatus: React.FC = () => {
//   const intl = useIntl();
//   const [formRef] = Form.useForm();

//   const [model, setModel] = useState<any>();
//   const [addFormVisibility, setAddFormVisibility] = useState<boolean>(false);
//   const [editFormVisibility, setEditFormVisibility] = useState<boolean>(false);
//   const [initialFormData, setInitialFormData] = useState();
//   const [refresh, setRefresh] = useState(0);

//   useEffect(() => {
//     if (!model) {
//       getModel(Models.EmploymentStatus).then((model) => {
//         if (model && model.data) {
//           setModel(model.data)
//         }
//       })
//     }
//   })

//   const _getAllEmploymentStatus = async (params?: any) => {
//     const employmentStatusRes = await getAllEmploymentStatus(params);
//     let data = employmentStatusRes?.data?.data?.filter(status => !status.isInvisible) ?? [];

//     data = _.groupBy(data, 'name');
//     data = Object.keys(data).map(key => {
//       return {
//         name: key,
//         data: data[key]
//       };
//     });

//     return { ...employmentStatusRes, data: { ...employmentStatusRes.data, total: data.length, data: data } };
//   };

//   const form = model && <>
//     <ProFormSelect
//       width="md"
//       name="name"
//       label={intl.formatMessage({
//         id: 'employment_status_name',
//         defaultMessage: 'Employment Status Name',
//       })}
//       options={model.modelDataDefinition.fields.name.values
//         .filter(option => option.value != 'PERMANENT')
//         .map(value => {
//           return {
//             value: value.value,
//             label: intl.formatMessage({
//               id: value.labelKey,
//               defaultMessage: value.defaultLabel,
//             })
//           };
//         })}
//       rules={[{ required: true, message: 'Required' }]}
//     />

//     <ProForm.Item label="Employment Period" rules={[{ required: true, message: 'Required' }]} style={{ width: "30%" }}>
//       <Form.List name="data">
//         {(fields, { add, remove }) => (
//           <>
//             {fields.map(({ key, name, ...restField }) => (
//               <Space key={key} style={{ display: 'flex', marginBottom: 8 }} align="baseline">
//                 <Form.Item
//                   {...restField}
//                   name={[name, 'period']}
//                   rules={[{ required: true, message: 'Required' },
//                   {
//                     validator: (_, value) => {
//                       return value > 0
//                         ? Promise.resolve()
//                         : Promise.reject(
//                           new Error('Invalid number'),
//                         );
//                     }
//                   }]}
//                   style={{ width: 200 }}
//                 >
//                   <Input
//                     name='period'
//                     addonAfter={
//                       <Form.Item
//                         {...restField}
//                         name={[name, 'periodUnit']}
//                         rules={[{ required: true, message: 'Required' }]}
//                         style={{ margin: 0 }}
//                       >
//                         <Select className="select-after" defaultValue={fields.periodUnit}>
//                           {model.modelDataDefinition.fields.periodUnit.values.map(value =>
//                             <Option value={value.value}>{
//                               intl.formatMessage({
//                                 id: value.labelKey,
//                                 defaultMessage: value.defaultLabel,
//                               })}
//                             </Option>)}
//                         </Select>
//                       </Form.Item>
//                     }
//                     type="number"
//                   />
//                 </Form.Item>

//                 {formRef.getFieldValue('data').length == (name + 1) &&
//                   <Button
//                     type="primary"
//                     onClick={() => add()}
//                     icon={<PlusOutlined />}
//                     style={{ background: '#FCAE30', borderColor: '#FCAE30' }}
//                   />}

//                 {formRef.getFieldValue('data').length > 1 &&
//                   <Button
//                     color='red'
//                     type="primary"
//                     onClick={() => remove(name)} icon={<CloseOutlined />}
//                     style={{ background: '#626D6C', borderColor: '#626D6C' }}
//                   />}
//               </Space>
//             ))}
//           </>
//         )}
//       </Form.List>
//     </ProForm.Item>
//   </>

//   return (
//     <>
//       <BasicContainer
//         rowId="id"
//         titleKey="employment-status"
//         defaultTitle="Employment status"
//         model={model}
//         tableColumns={[
//           { name: 'name', sortable: true, filterable: true },
//           {
//             name: 'period',
//             render: (_, record) =>
//               <List
//                 size="small"
//                 dataSource={record.data}
//                 renderItem={item => <List.Item style={{ paddingLeft: 0 }}>
//                   {item.title.split(' - ').length > 1 ? item.title.split(' - ')[1] : undefined}
//                 </List.Item>}
//               />
//           }
//         ]}
//         refresh={refresh}
//         recordActions={['add', 'edit']}
//         defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
//         searchFields={['name']}
//         addFormType="function"
//         editFormType="function"
//         getAllFunction={_getAllEmploymentStatus}
//         addFunction={async (record) => {
//           setInitialFormData(emptyEmploymentStatus);
//           setAddFormVisibility(true);
//         }}
//         editFunction={async (record) => {
//           console.log(record);
//           setInitialFormData(record);
//           setEditFormVisibility(true)
//         }}
//         rowWiseEditActionPermissionHandler={(record) => !record.isUneditable}
//         deleteFunction={removeEmploymentStatus}
//         rowWiseDeleteActionPermissionHandler={(record) => !record.isUneditable}
//         permissions={{
//           addPermission: 'master-data-write',
//           editPermission: 'master-data-write',
//           deletePermission: 'master-data-write',
//           readPermission: 'master-data-write',
//         }}
//       />

//       <ModalForm
//         form={formRef}
//         title="Add Employment Status"
//         visible={addFormVisibility}
//         modalProps={{
//           destroyOnClose: true
//         }}
//         onVisibleChange={setAddFormVisibility}
//         onFinish={async (values) => {
//           const key = 'saving';
//           message.loading({
//             content: intl.formatMessage({
//               id: 'saving',
//               defaultMessage: 'Saving...',
//             }),
//             key,
//           });

//           const response = await addEmploymentStatus(values);

//           if (response.error) {
//             message.error({
//               content:
//                 response.message ??
//                 intl.formatMessage({
//                   id: 'failedToSave',
//                   defaultMessage: 'Cannot Save',
//                 }),
//               key,
//             });
//             return;
//           }

//           setAddFormVisibility(false);
//           setRefresh(prev => prev + 1);

//           message.success({
//             content:
//               response.message ??
//               intl.formatMessage({
//                 id: 'successfullySaved',
//                 defaultMessage: 'Successfully Saved',
//               }),
//             key,
//           });
//         }}
//         initialValues={initialFormData}
//       >
//         {form}
//       </ModalForm>

//       <DrawerForm
//         form={formRef}
//         title="Edit Employment Status"
//         visible={editFormVisibility}
//         onVisibleChange={setEditFormVisibility}
//         drawerProps={{
//           destroyOnClose: true
//         }}
//         submitter={{
//           searchConfig: {
//             submitText: intl.formatMessage({
//               id: 'update',
//               defaultMessage: 'Update',
//             }),
//             resetText: intl.formatMessage({
//               id: 'cancel',
//               defaultMessage: 'Cancel',
//             }),
//           },
//         }}
//         onFinish={async (values) => {
//           const key = 'updating';
//           message.loading({
//             content: intl.formatMessage({
//               id: 'updating',
//               defaultMessage: 'Updating...',
//             }),
//             key,
//           });

//           const response = await updateEmploymentStatus(values);

//           if (response.error) {
//             message.error({
//               content:
//                 response.message ??
//                 intl.formatMessage({
//                   id: 'failedToUpdate',
//                   defaultMessage: 'Cannot Update',
//                 }),
//               key,
//             });
//             return;
//           }

//           setEditFormVisibility(false);
//           setRefresh(prev => prev + 1);

//           message.success({
//             content:
//               response.message ??
//               intl.formatMessage({
//                 id: 'successfullyUpdated',
//                 defaultMessage: 'Successfully Updated',
//               }),
//             key,
//           });
//         }}
//         drawerProps={{
//           destroyOnClose: true,
//         }}
//         width="40vw"
//         initialValues={initialFormData}
//       >
//         {form}
//       </DrawerForm>
//     </>
//   );
// }

// export default EmploymentStatus
