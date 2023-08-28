import React, { useRef, useState } from 'react';
import _ from 'lodash';
import { FormattedMessage, useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import ProTable, { ActionType } from '@ant-design/pro-table';
import OrgSelector from '@/components/OrgSelector';
import { Card, Row, Col, Space, DatePicker, List } from 'antd';
import Button from 'antd/lib/button';
import ProFormItem from '@ant-design/pro-form/lib/components/FormItem';
import { getAllAuditTrail } from '@/services/auditTrail';
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from '../403';
import { getModel } from '@/services/model';

const AuditTrail: React.FC = () => {
    const intl = useIntl();
    const actionRef = useRef<ActionType>();
    const { RangePicker } = DatePicker;
    const access = useAccess();
    const { hasPermitted } = access;

    const [loading, setLoading] = useState(false);
    const [modelList, setModelList] = useState({});
    const [orgStructureEntityId, setOrgStructureEntityId] = useState<number>();
    const [dateRange, setDateRange] = useState<any>();

    const columns = [
        {
            title: intl.formatMessage({
                id: 'audit_trail.date_and_time',
                defaultMessage: "Date And Time",
            }),
            dataIndex: 'timestamp',
            sorter: true
        },
        {
            title: intl.formatMessage({
                id: 'audit_trail.action_owner',
                defaultMessage: "Action Owner",
            }),
            dataIndex: 'userName',
        },
        {
            title: intl.formatMessage({
                id: 'audit_trail.action',
                defaultMessage: "Action",
            }),
            dataIndex: 'action',
            valueEnum: {
                CREATE: 'Create',
                UPDATE: 'Update',
                DELETE: 'Delete'
            }
        },
        {
            title: intl.formatMessage({
                id: 'audit_trail.employee',
                defaultMessage: "Employee",
            }),
            dataIndex: 'employeeName',
        },
        {
            title: intl.formatMessage({
                id: 'audit_trail.section',
                defaultMessage: "Section",
            }),
            dataIndex: 'modelTitle'
        },
        {
            title: intl.formatMessage({
                id: 'audit_trail.action_description',
                defaultMessage: "Action Description",
            }),
            dataIndex: 'actionDescription',
            render: (actionDescription: any[], record: any) => {
                return actionDescription.length > 0
                    ? <List
                        dataSource={actionDescription}
                        renderItem={(item) => item.currentValue == "private_data"
                            ? <>{item.attributeName} (Private Data) was changed!</>
                            : item.previousValue == null
                                ? <List.Item style={{ padding: '2px 0' }}>
                                    {item.attributeName} was initialized to {item.currentValue}
                                </List.Item>
                                : <List.Item style={{ padding: '2px 0' }}>
                                    {item.attributeName} was changed from {item.previousValue} to {item.currentValue}
                                </List.Item>
                        }
                    />
                    : <List><List.Item style={{ padding: '2px 0' }}>-</List.Item></List>
            }
        }
    ];

    return (
        <Access
            accessible={hasPermitted('reports-read-write')}
            fallback={<PermissionDeniedPage />}
        >
            <PageContainer loading={loading}>
                <Card style={{ marginBottom: 24 }}>
                    <Row gutter={24}>
                        <OrgSelector
                            span={8}
                            value={orgStructureEntityId}
                            setValue={(value: number) => setOrgStructureEntityId(value)}
                        />
                    </Row>
                    <Row gutter={24}>
                        <Col span={12}>
                            <ProFormItem label={intl.formatMessage({
                                id: 'audit_trail.date_range',
                                defaultMessage: "Date",
                            })}>
                                <RangePicker onChange={(value) => setDateRange(value)} />
                            </ProFormItem>
                        </Col>
                    </Row>
                    <Space style={{ float: 'right' }}>
                        <Button type='primary' onClick={() => actionRef.current?.reload()}>
                            <FormattedMessage id="audit_trail.filter" defaultMessage="Filter" />
                        </Button>
                    </Space>
                </Card>
                <ProTable
                    actionRef={actionRef}
                    search={false}
                    options={{
                        reload: () => {
                            actionRef.current?.reset();
                            actionRef.current?.reload();
                        }
                    }}
                    columns={columns}
                    request={async (params, filter) => {
                        const queryParams = {
                            ...params,
                            ...filter,
                            orgStructureEntityId,
                            startDate: (!_.isEmpty(dateRange) && dateRange[0]) ? dateRange[0].format("YYYY-MM-DD") : undefined,
                            endDate: (!_.isEmpty(dateRange) && dateRange[1]) ? dateRange[1].format("YYYY-MM-DD") : undefined
                        };

                        const response = await getAllAuditTrail(queryParams);

                        const ignoreModels = ['dashboardLayout'];
                        let _data: any[] = [];
                        for (let i = 0; i < response?.data?.data?.length; i++) {
                            let model;
                            let record = response?.data?.data[i];
                            record.modelTitle = record.modelName
                                .replace(/([A-Z])/g, ' $1')
                                .replace(/^./, function (str) { return str.toUpperCase(); });
                            record.actionDescription = [];

                            if (!ignoreModels.includes(record.modelName) && record.modelName && modelList[record.modelName]) {
                                model = modelList[record.modelName];
                            } else if (!ignoreModels.includes(record.modelName) && record.modelName) {
                                const modelRes = await getModel(record.modelName);
                                let _modelList = { ...modelList };
                                _modelList[record.modelName] = modelRes?.data?.modelDataDefinition;
                                setModelList(_modelList);
                                model = modelRes?.data?.modelDataDefinition;
                            }

                            if (model && record.action != 'DELETE') {
                                const currentState = JSON.parse(record.currentState);
                                const previousState = JSON.parse(record.previousState);

                                for (const filedName in currentState) {
                                    const hasFieldInPreviousState = _.has(previousState, filedName);
                                    const hasFieldInCurrentState = _.has(currentState, filedName);
                                    const fieldDetails = model?.fields?.[filedName];

                                    if ((!hasFieldInPreviousState && !hasFieldInCurrentState) || !hasFieldInCurrentState) continue;
                                    if (_.isEmpty(fieldDetails)) continue;

                                    if (!hasFieldInPreviousState || currentState[filedName] !== previousState[filedName]) {
                                        let attributeName = _.has(fieldDetails, 'defaultLabel') ? fieldDetails.defaultLabel : filedName;
                                        if (_.has(fieldDetails, 'isSystemValue') && fieldDetails?.isSystemValue) continue;

                                        let previousValue = _.has(previousState, filedName) ? previousState[filedName] : undefined;
                                        let currentValue = _.has(currentState, filedName) ? currentState[filedName] : undefined;

                                        switch (fieldDetails?.type) {
                                            case 'boolean':
                                                previousValue = hasFieldInPreviousState ? previousValue == 1 ? 'True' : 'False' : undefined;
                                                currentValue = currentValue == 1 ? 'True' : 'False';
                                                break;
                                            case 'switch':
                                                previousValue = hasFieldInPreviousState ? previousValue == 1 ? 'Switch On' : 'Switch Off' : undefined;
                                                currentValue = currentValue == 1 ? 'Switch On' : 'Switch Off';
                                                break;
                                            case 'enum':
                                                previousValue = previousValue
                                                    ? fieldDetails.values?.find((option: any) => option.value == previousValue)?.defaultLabel
                                                    : undefined;
                                                currentValue = currentValue
                                                    ? fieldDetails.values?.find((option: any) => option.value == currentValue)?.defaultLabel
                                                    : undefined;
                                                break;
                                        }

                                        if (fieldDetails?.isSensitiveData) {
                                            currentValue = 'private_data';
                                            previousValue = null;
                                        }

                                        if (record.action == 'CREATE' && !currentValue && !previousValue) continue;

                                        record.actionDescription.push({
                                            attributeName,
                                            currentValue,
                                            previousValue
                                        });
                                    }
                                }
                            }

                            _data.push(record);
                        }

                        return {
                            data: _data,
                            success: true,
                            total: response.data.total
                        }
                    }
                    }
                />
            </PageContainer>
        </Access>
    );
};

export default AuditTrail;
