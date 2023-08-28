import { getStatus, setup, getFieldMap, getConfig, storeAuthConfig, storeUserProvisioningConfig } from "@/services/importAzureUsers";
import { getModel, Models } from "@/services/model";
import { queryUserRoles } from "@/services/userRole";
import { ImportOutlined, UserSwitchOutlined } from "@ant-design/icons";
import ProForm, { ProFormDependency, ProFormSelect, ProFormSwitch, ProFormText } from "@ant-design/pro-form";
import { PageContainer } from "@ant-design/pro-layout";
import ProTable from "@ant-design/pro-table";
import { Card, message as Message, Divider, Modal, Tabs } from "antd";
import _ from "lodash";
import { useEffect, useState } from "react";
import { useIntl } from 'react-intl';
import { history } from "umi";

const ImportAzureUsers: React.FC = () => {
    const intl = useIntl();

    const [config, setConfig] = useState();
    const [users, setUsers] = useState([]);
    const [columns, setColumns] = useState([]);
    const [loading, setLoading] = useState<boolean>(false);
    const [status, setStatus] = useState<'PENDING' | 'PROCESSING' | 'SUCCESS' | 'ERROR'>();
    const [processedPercent, setProcessedPercent] = useState<number>(0);
    const [successPercent, setSuccessPercent] = useState<number>(0);

    useEffect(() => {
        init();
    }, []);

    const init = async () => {
        setLoading(true);
        await refresh();

        const configRes = await getConfig();
        setConfig(configRes.data ?? undefined);

        const fieldMap = await getFieldMap();
        const model = await getModel(Models.Employee);

        setupTableColumns(model.data, fieldMap.data);
        setLoading(false);

        const interval = setInterval(() => {
            refresh();
        }, 60000);

        return () => clearInterval(interval);
    }

    const refresh = async () => {
        const { error, message, data } = await getStatus();

        if (error) {
            Message.error(message);
        }

        if (data.azureUsers) {
            setUsers(data.azureUsers.map(user => {
                return {
                    ...user,
                    sourceObject: JSON.parse(user.sourceObject)
                };
            }));
        }

        delete data.azureUsers;

        if (_.isEmpty(data)) {
            setProcessedPercent(100);
            setSuccessPercent(100);
            setStatus('SUCCESS');
            return;
        }

        const _processedPercent = ((data.successCount + data.errorCount) / data.totalCount) * 100;
        const _successPercent = (data.successCount / data.totalCount) * 100;

        setStatus(data.status);
        setProcessedPercent(_processedPercent);
        setSuccessPercent(_successPercent);
    }

    const setupTableColumns = (model, fieldMap) => {
        let _columns = [];

        _columns = fieldMap.map(field => {
            return {
                title: field.azureFieldTitle,
                key: field.employeeFieldName,
                dataIndex: ['sourceObject', field.azureKey],
                // render: (_, record) => (
                //   <Space>{JSON.stringify(record.sourceObject)}</Space>
                // )
            };
        });

        _columns.push(
            {
                title: 'Email',
                key: 'email',
                dataIndex: 'email',
                fixed: 'left'
            }
        );
        _columns.push(
            {
                title: 'Status',
                key: 'status',
                dataIndex: 'status',
                fixed: 'right'
            }
        )
        _columns.push({
            title: 'Actions',
            key: 'actions',
            valueType: 'option',
            fixed: 'right',
            render: (_, record) => [
                <>{record.status == 'SUCCESS' && <a key="viewEmployee" onClick={() => history.push(`/employees/${record.employeeId}`)}>View Employee</a>}</>,
                <>{record.status == 'ERROR' && <a key="viewError" onClick={() => Modal.error({
                    width: '75vw',
                    title: 'Error occurrs due to,',
                    content: renderErrors(record.responseData)
                })}>View Error</a>}</>,
            ]
        });

        setColumns(_columns);
    }

    const renderErrors = (data) => {
        const errors = typeof data == 'object' ? data : JSON.parse(data)?.data;

        return <ul>
            {Object.keys(errors).map(fieldName =>
                <li>
                    {fieldName} :
                    {_.isArray(errors[fieldName]) && errors[fieldName]?.map(error => _.isString(error) ? error : renderErrors(error))}
                </li>
            )}
        </ul>
    }

    return (
        <PageContainer loading={loading}>
            <Card>
                <ProForm
                    submitter={{
                        searchConfig: {
                            resetText: intl.formatMessage({
                                id: 'IMPORT_AZURE_USERS.RESET',
                                defaultMessage: 'Reset',
                            }),
                            submitText: intl.formatMessage({
                                id: 'IMPORT_AZURE_USERS.SAVE',
                                defaultMessage: 'Save',
                            }),
                        },
                    }}
                    onFinish={async (values) => {
                        const key = 'saving';
                        Message.loading({
                            content: intl.formatMessage({
                                id: 'saving',
                                defaultMessage: 'Saving...',
                            }),
                            key,
                        });
                        storeAuthConfig(values)
                            .then(res => {
                                if (res.error) {
                                    Message.error({
                                        content: res.message ?? intl.formatMessage({
                                            id: 'failedToSave',
                                            defaultMessage: 'Failed to save.',
                                        }),
                                        key,
                                    });
                                    return;
                                }

                                Message.success({
                                    content: intl.formatMessage({
                                        id: 'successfullySaved',
                                        defaultMessage: 'Successfully Saved',
                                    }),
                                    key,
                                });
                            })
                            .catch(error => Message.error({
                                content: intl.formatMessage({
                                    id: 'failedToSave',
                                    defaultMessage: 'Failed to save.',
                                }),
                                key,
                            }));
                    }}
                    initialValues={config}
                >
                    <ProForm.Group>
                        <ProFormText
                            width="md"
                            name="azure_tenant_id"
                            label={intl.formatMessage({
                                id: 'IMPORT_AZURE_USERS.TENANT_ID',
                                defaultMessage: 'Tenant ID',
                            })}
                            rules={[{ required: true, message: 'Required' }]}
                        />
                        <ProFormText
                            width="md"
                            name="azure_client_id"
                            label={intl.formatMessage({
                                id: 'IMPORT_AZURE_USERS.CLIENT_ID',
                                defaultMessage: 'Application (client) ID',
                            })}
                            rules={[{ required: true, message: 'Required' }]}
                        />
                        <ProFormText.Password
                            width="md"
                            name="azure_client_secret"
                            label={intl.formatMessage({
                                id: 'IMPORT_AZURE_USERS.CLIENT_SECRET',
                                defaultMessage: 'Client Secret (Secret Value)',
                            })}
                            rules={[{ required: true, message: 'Required' }]}
                        />
                    </ProForm.Group>
                </ProForm>
            </Card>

            <Divider />

            <Tabs defaultActiveKey="import">
                <Tabs.TabPane tab={<><ImportOutlined />Import</>} key="import">
                    <Card>
                        <ProForm
                            submitter={{
                                searchConfig: {
                                    resetText: intl.formatMessage({
                                        id: 'IMPORT_AZURE_USERS.RESET',
                                        defaultMessage: 'Reset',
                                    }),
                                    submitText: intl.formatMessage({
                                        id: 'IMPORT_AZURE_USERS.IMPORT',
                                        defaultMessage: 'Import',
                                    }),
                                },
                            }}
                            onFinish={async (values) => {
                                const key = 'importing';
                                Message.loading({
                                    content: intl.formatMessage({
                                        id: 'placing_import_job',
                                        defaultMessage: 'Placing import job ...',
                                    }),
                                    key,
                                });
                                setup(values)
                                    .then(res => {
                                        if (res.error) {
                                            Message.error({
                                                content: res.message ?? intl.formatMessage({
                                                    id: 'failed_to_place_import_job',
                                                    defaultMessage: 'Failed to place import job.',
                                                }),
                                                key,
                                            });
                                            return;
                                        }
        
                                        Message.success({
                                            content: intl.formatMessage({
                                                id: 'successfully_placed_import_job',
                                                defaultMessage: 'Successfully placed import job.',
                                            }),
                                            key,
                                        });
                                    })
                                    .catch(error => Message.error({
                                        content: intl.formatMessage({
                                            id: 'failed_to_place_import_job',
                                            defaultMessage: 'Failed to place import job.',
                                        }),
                                        key,
                                    }));
                            }}
                        >
                            <ProForm.Group>
                                <ProFormSelect
                                    width="md"
                                    name="employeeRoleId"
                                    label={intl.formatMessage({
                                        id: 'IMPORT_AZURE_USERS.EMPLOYEE_ROLE',
                                        defaultMessage: 'Employee Role',
                                    })}
                                    rules={[{ required: true, message: 'Required' }]}
                                    request={async () => {
                                        const employeeRoles = await queryUserRoles({ filter: { type: ['EMPLOYEE'] } });

                                        if (employeeRoles.error) return [];

                                        return employeeRoles.data.map(role => {
                                            return {
                                                value: role.id,
                                                label: role.title
                                            }
                                        });
                                    }}
                                    showSearch
                                />
                            </ProForm.Group>
                        </ProForm>

                        <Divider />

                        <ProTable
                            scroll={{ x: '100%' }}
                            columns={columns}
                            rowKey="id"
                            headerTitle="Azure Users"
                            dataSource={users}
                            toolBarRender={false}
                            search={false}
                        />
                    </Card>
                </Tabs.TabPane>
                <Tabs.TabPane tab={<><UserSwitchOutlined />User Provisioning</>} key="user_provisioning">
                    <Card>
                        <ProForm
                            submitter={{
                                searchConfig: {
                                    resetText: intl.formatMessage({
                                        id: 'IMPORT_AZURE_USERS.RESET',
                                        defaultMessage: 'Reset',
                                    }),
                                    submitText: intl.formatMessage({
                                        id: 'IMPORT_AZURE_USERS.SAVE',
                                        defaultMessage: 'Save',
                                    }),
                                },
                            }}
                            onFinish={async (values) => {
                                const key = 'saving';
                                Message.loading({
                                    content: intl.formatMessage({
                                        id: 'saving',
                                        defaultMessage: 'Saving...',
                                    }),
                                    key,
                                });
                                storeUserProvisioningConfig(values)
                                    .then(res => {
                                        if (res.error) {
                                            Message.error({
                                                content: res.message ?? intl.formatMessage({
                                                    id: 'failedToSave',
                                                    defaultMessage: 'Failed to save.',
                                                }),
                                                key,
                                            });
                                            return;
                                        }
        
                                        Message.success({
                                            content: intl.formatMessage({
                                                id: 'successfullySaved',
                                                defaultMessage: 'Successfully Saved',
                                            }),
                                            key,
                                        });
                                    })
                                    .catch(error => Message.error({
                                        content: intl.formatMessage({
                                            id: 'failedToSave',
                                            defaultMessage: 'Failed to save.',
                                        }),
                                        key,
                                    }));
                            }}
                            initialValues={config}
                        >
                            <ProForm.Group>
                                <ProFormSwitch name="is_active_azure_user_provisioning" label="Activate User Provisioning" />
                            </ProForm.Group>
                            <ProFormDependency name={['is_active_azure_user_provisioning']}>
                                {({ is_active_azure_user_provisioning }) => {
                                    return is_active_azure_user_provisioning && <ProForm.Group>
                                        <ProFormText
                                            width="md"
                                            name="azure_domain_name"
                                            label={intl.formatMessage({
                                                id: 'IMPORT_AZURE_USERS.DOMAIN_NAME',
                                                defaultMessage: 'Domain Name',
                                            })}
                                            rules={[{ required: true, message: 'Required' }]}
                                        />
                                        <ProFormText.Password
                                            width="md"
                                            name="azure_default_password"
                                            label={intl.formatMessage({
                                                id: 'IMPORT_AZURE_USERS.DEFAULT_PASSWORD',
                                                defaultMessage: 'Default Password',
                                            })}
                                            rules={[{ required: true, message: 'Required' }]}
                                        />
                                    </ProForm.Group>
                                }}
                            </ProFormDependency>
                        </ProForm>
                    </Card>
                </Tabs.TabPane>
            </Tabs>
        </PageContainer>
    );
};

export default ImportAzureUsers;
