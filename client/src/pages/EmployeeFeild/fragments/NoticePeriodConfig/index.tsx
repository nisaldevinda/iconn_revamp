import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllNoticePeriodConfigs,
    addNoticePeriodConfig,
    updateNoticePeriodConfig,
    removeNoticePeriodConfig
} from '@/services/noticePeriodConfig';
import { Form, message, Spin } from 'antd';
import { DrawerForm, ModalForm, ProFormDependency, ProFormDigit, ProFormGroup, ProFormSelect } from '@ant-design/pro-form';
import { useIntl } from 'umi';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import _ from 'lodash';

const NoticePeriodConfig: React.FC = () => {
    const intl = useIntl();
    const [addFormRef] = Form.useForm();
    const [editFormRef] = Form.useForm();

    const [loading, setLoading] = useState<boolean>(false);
    const [model, setModel] = useState<any>();
    const [jobCategory, setJobCategory] = useState();
    const [employmentStatus, setEmploymentStatus] = useState();

    const [addFormVisibility, setAddFormVisibility] = useState<boolean>(false);
    const [editFormVisibility, setEditFormVisibility] = useState<boolean>(false);
    const [initialFormData, setInitialFormData] = useState({});
    const [refresh, setRefresh] = useState(0);

    useEffect(() => {
        init();
    }, [])

    const init = async () => {
        setLoading(true);
        let callStack = [];

        // retrieve model
        if (!model) {
            callStack.push(getModel(Models.NoticePeriodConfig)
                .then(response => {
                    if (response && response.data) {
                        setModel(response.data)
                    }
                })
                .catch(error => message.error(error.message)));
        }

        // retrieve all job categories
        if (!jobCategory) {
            callStack.push(getAllJobCategories()
                .then(response => {
                    if (response && response.data) {
                        setJobCategory(response.data.map(option => {
                            return {
                                value: option.id,
                                label: option.name
                            };
                        }))
                    }
                })
                .catch(error => message.error(error.message)));
        }

        // retrieve all job categories
        if (!employmentStatus) {
            callStack.push(getAllEmploymentStatus()
                .then(response => {
                    if (response && response.data) {
                        setEmploymentStatus(response.data.map(option => {
                            return {
                                value: option.id,
                                label: option.name
                            };
                        }))
                    }
                })
                .catch(error => message.error(error.message)));
        }

        Promise.all(callStack).then(() => {
            setLoading(false);
        });
    }

    const calculateMaximumNoticePeriod = (noticePeriodUnit: string) => {
        switch (noticePeriodUnit) {
            case 'Days':
                return 30;
            case 'Months':
                return 11;
            default:
                return 2;
        }
    }

    return (<Spin spinning={loading}>
        <BasicContainer
            rowId="id"
            titleKey="notice-period-config"
            defaultTitle="Notice Period Config"
            model={model}
            tableColumns={[
                { name: 'jobCategory', sortable: true, filterable: true },
                { name: 'employmentStatus', sortable: true, filterable: true },
                { name: 'noticePeriodString' },
            ]}
            recordActions={['add', 'edit', 'delete']}
            defaultSortableField={{ fildName: 'updatedAt', mode: 'descend' }}
            searchFields={['jobCategory']}
            refresh={refresh}
            addFormType="function"
            editFormType="function"
            getAllFunction={getAllNoticePeriodConfigs}
            addFunction={async (record) => {
                setInitialFormData(null);
                setAddFormVisibility(true);
            }}
            editFunction={async (record) => {
                setInitialFormData(record);
                setEditFormVisibility(true)
            }} deleteFunction={removeNoticePeriodConfig}
            permissions={{
                addPermission: 'master-data-write',
                editPermission: 'master-data-write',
                deletePermission: 'master-data-write',
                readPermission: 'master-data-write',
            }}
        />

        {model && <>
            <ModalForm
                form={addFormRef}
                title={intl.formatMessage({
                    id: 'notice_period_config.add_notice_period_configuration',
                    defaultMessage: 'Add Notice Period Configuration',
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

                    addNoticePeriodConfig(values)
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
                <ProFormGroup>
                    <ProFormSelect
                        width="md"
                        name="jobCategoryId"
                        label={intl.formatMessage({
                            id: 'notice_period_config.job_category',
                            defaultMessage: 'Job Category',
                        })}
                        options={jobCategory}
                        rules={[{ required: true, message: 'Required' }]}
                    />
                    <ProFormSelect
                        width="md"
                        name="employmentStatusId"
                        label={intl.formatMessage({
                            id: 'notice_period_config.employment_status',
                            defaultMessage: 'Employment Status',
                        })}
                        options={employmentStatus}
                        rules={[{ required: true, message: 'Required' }]}
                    />
                </ProFormGroup>
                <ProFormDependency name={['noticePeriod', 'noticePeriodUnit']}>
                    {({ noticePeriod, noticePeriodUnit }) => (
                        <Form.Item
                            name='period'
                            label={intl.formatMessage({
                                id: 'notice_period_config.notice_period',
                                defaultMessage: 'Notice Period',
                            })}
                            rules={[
                                {
                                    message: 'Required',
                                    validator: () => {
                                        if ((noticePeriod != 0 && !noticePeriod) || !noticePeriodUnit) {
                                            return Promise.reject();
                                        }

                                        return Promise.resolve();
                                    }
                                },
                                {
                                    message: 'Invalid',
                                    validator: () => {
                                        const max = calculateMaximumNoticePeriod(noticePeriodUnit);
                                        if (noticePeriod > max) {
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
                                name='noticePeriod'
                                width="xs"
                                min={0}
                                max={calculateMaximumNoticePeriod(noticePeriodUnit)}
                                addonBefore={
                                    <ProFormSelect
                                        name='noticePeriodUnit'
                                        options={model.modelDataDefinition.fields.noticePeriodUnit.values.map(option => {
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
                    )}
                </ProFormDependency>
            </ModalForm>

            <DrawerForm
                form={editFormRef}
                title={intl.formatMessage({
                    id: 'notice_period_config.edit_notice_period_configuration',
                    defaultMessage: 'Edit Notice Period Configuration',
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

                    updateNoticePeriodConfig({ ...initialFormData, ...values })
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
                <ProFormSelect
                    width="md"
                    name="jobCategoryId"
                    label={intl.formatMessage({
                        id: 'notice_period_config.job_category',
                        defaultMessage: 'Job Category',
                    })}
                    options={jobCategory}
                    rules={[{ required: true, message: 'Required' }]}
                />
                <ProFormSelect
                    width="md"
                    name="employmentStatusId"
                    label={intl.formatMessage({
                        id: 'notice_period_config.employment_status',
                        defaultMessage: 'Employment Status',
                    })}
                    options={employmentStatus}
                    rules={[{ required: true, message: 'Required' }]}
                />
                <ProFormDependency name={['noticePeriod', 'noticePeriodUnit']}>
                    {({ noticePeriod, noticePeriodUnit }) => (
                        <Form.Item
                            name='period'
                            label={intl.formatMessage({
                                id: 'notice_period_config.notice_period',
                                defaultMessage: 'Notice Period',
                            })}
                            rules={[
                                {
                                    message: 'Required',
                                    validator: () => {
                                        if ((noticePeriod != 0 && !noticePeriod) || !noticePeriodUnit) {
                                            return Promise.reject();
                                        }

                                        return Promise.resolve();
                                    }
                                },
                                {
                                    message: 'Invalid',
                                    validator: () => {
                                        const max = calculateMaximumNoticePeriod(noticePeriodUnit);
                                        if (noticePeriod > max) {
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
                                name='noticePeriod'
                                width="xs"
                                min={0}
                                max={calculateMaximumNoticePeriod(noticePeriodUnit)}
                                addonBefore={
                                    <ProFormSelect
                                        name='noticePeriodUnit'
                                        options={model.modelDataDefinition.fields.noticePeriodUnit.values.map(option => {
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
                    )}
                </ProFormDependency>
            </DrawerForm>
        </>}
    </Spin>
    );
}

export default NoticePeriodConfig
