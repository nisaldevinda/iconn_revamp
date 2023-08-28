import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllScheme,
    addScheme,
    updateScheme,
    removeScheme
} from '@/services/scheme';
import { Form, Col, Input, message } from 'antd';
import { DrawerForm, ModalForm, ProFormText } from '@ant-design/pro-form';
import { useIntl } from 'umi';
import _ from "lodash";

const Scheme: React.FC = () => {

    const intl = useIntl();
    const [formRef] = Form.useForm();
    const { TextArea } = Input;

    const [model, setModel] = useState<any>();
    const [addFormVisibility, setAddFormVisibility] = useState<boolean>(false);
    const [editFormVisibility, setEditFormVisibility] = useState<boolean>(false);
    const [initialFormData, setInitialFormData] = useState({});
    const [refresh, setRefresh] = useState(0);
    useEffect(() => {
        if (!model) {
            getModel(Models.Scheme).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })
    const form = model && <>
        <Col span={10} style={{ paddingLeft: 10 }}>
            <ProFormText
                name="name"
                label={intl.formatMessage({
                    id: 'scheme.name',
                    defaultMessage: 'Scheme Name',
                })}
                placeholder={intl.formatMessage({
                    id: 'scheme.placeholder',
                    defaultMessage: 'Scheme Name',
                })}
                rules={[
                    {
                        required: true,
                        message:
                            intl.formatMessage({
                                id: "scheme.required",
                                defaultMessage: "Required"
                            })
                    },
                    {
                        max: 100,
                        message:
                            intl.formatMessage({
                                id: "scheme.max",
                                defaultMessage: "Maximum length is 100 characters."
                            })
                        ,
                    }
                ]}
            //value={formInitialValues.name}

            />
        </Col>
        <Col span={16} style={{ paddingLeft: 10 }}>
            <Form.Item
                name="description"
                label={intl.formatMessage({
                    id: 'Description',
                    defaultMessage: 'Description',
                })}
                rules={
                    [
                        {
                            max: 250,
                            message: intl.formatMessage({
                                id: 'description',
                                defaultMessage: 'Maximum length is 250 characters.',
                            })
                        }
                    ]
                }>
                <TextArea rows={4}
                    style={{
                        width: 800,
                        background: '#FFFFFF',
                        boxSizing: 'border-box',
                        borderRadius: '6px'
                    }}
                />
            </Form.Item>
        </Col>

    </>

    return (
        <>
            <BasicContainer
                rowId="id"
                titleKey="Scheme"
                defaultTitle="Scheme"
                model={model}
                tableColumns={[
                    { name: 'name', sortable: true, filterable: true },
                    { name: 'description', sortable: false, filterable: false },
                ]}
                refresh={refresh}
                recordActions={['add', 'edit', 'delete']}
                defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
                searchFields={['name']}
                addFormType="function"
                editFormType="function"
                getAllFunction={getAllScheme}
                addFunction={async (record) => {

                    setAddFormVisibility(true);
                }}
                editFunction={async (record) => {
                    setInitialFormData(record);
                    setEditFormVisibility(true)
                }}
                deleteFunction={removeScheme}
                permissions={{
                    addPermission: 'master-data-write',
                    editPermission: 'master-data-write',
                    deletePermission: 'master-data-write',
                    readPermission: 'master-data-write',
                }}
            />
            {addFormVisibility && <ModalForm
                form={formRef}
                title="Add Scheme"
                visible={addFormVisibility}
                modalProps={{
                    destroyOnClose: true
                }}
                onVisibleChange={setAddFormVisibility}
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
                onFinish={async (values) => {
                    const key = 'saving';
                    message.loading({
                        content: intl.formatMessage({
                            id: 'saving',
                            defaultMessage: 'Saving...',
                        }),
                        key,
                    });

                    addScheme(values)
                        .then((response: any) => {
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
                                return;
                            }

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
                        .catch((error: any) => {
                            if (!_.isEmpty(error.data) && _.isObject(error.data)) {
                                for (const fieldName in error.data) {
                                    formRef.setFields([
                                        {
                                            name: fieldName,
                                            errors: error.data[fieldName]
                                        }
                                    ]);
                                }
                            }
                            return;
                        });
                }}
                //initialValues={initialFormData}
                width={700}
            >
                {form}
            </ModalForm>
            }
            {editFormVisibility && <DrawerForm
                form={formRef}
                title="Edit Scheme"
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
                    const requestData = values;
                    requestData.id = initialFormData.id;
                    updateScheme(requestData)
                        .then((response: any) => {
                            if (response.error) {
                                message.error({
                                    content:
                                        response.message ??
                                        intl.formatMessage({
                                            id: 'failedToUpdate',
                                            defaultMessage: 'Cannot Update',
                                        }),
                                    key,
                                });
                                return;
                            }

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
                        .catch((error: any) => {
                            if (!_.isEmpty(error.data) && _.isObject(error.data)) {
                                for (const fieldName in error.data) {
                                    formRef.setFields([
                                        {
                                            name: fieldName,
                                            errors: error.data[fieldName]
                                        }
                                    ]);
                                }
                            }
                            return;
                        });
                }}
                drawerProps={{
                    destroyOnClose: true,
                }}
                width="40vw"
                initialValues={initialFormData}
                width={550}
            >
                {form}
            </DrawerForm>
            }
        </>
    );
}

export default Scheme;
