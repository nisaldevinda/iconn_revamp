


import React, { useEffect, useRef, useState } from 'react';
import ProTable, { ProColumns, ActionType } from '@ant-design/pro-table';
import { useAccess, Access } from 'umi';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import request from '@/utils/request';
import { getModel } from '@/services/model';
import moment from 'moment';
import { getMyLeaveEntitlement } from '@/services/leaveEntitlment';
import PermissionDeniedPage from '../403';

const MyLeaveEntitlement: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;
    const actionRef = useRef<ActionType>();
    const [leaveTypes, setLeaveTypes] = useState([])
    const [model, setModel] = useState([])
    const [allEntitlements, setAllEntitlements] = useState([])

    useEffect(() => {
        fetchLeaveTypes()
    }, [])

    const fetchMyEntitlements = async () => {
        const response = await getMyLeaveEntitlement()
        setAllEntitlements(response.data)
    }

    useEffect(() => {
        fetchMyEntitlements()

    }, [])

    const generateLeaveTypeEnum = () => {
        const enumV = {}
        if (leaveTypes) {
            leaveTypes.forEach(element => {
                enumV[element.value] = element.label

            })
        }

        return enumV
    }

    const fetchLeaveTypes = async () => {
        const actions = []
        const response = await getModel("leaveType")
        const modelResponse = await getModel("leaveEntitlement")
        setModel(modelResponse.data.modelDataDefinition.fields.type.values)
        let path: string
        if (!_.isEmpty(response.data)) {
            path = `/api${response.data.modelDataDefinition.path}`;
        }
        const res = await request(path);
        await res.data.forEach(async (element: any, i: number) => {
            await actions.push({ value: element['id'], label: element['name'] });
        });
        setLeaveTypes(actions)
    }

    const generateEnum = () => {
        const valueEnum = {}
        //const enumV=model
        model.forEach(element => {
            valueEnum[element.value] = {
                text: element.defaultLabel
            }
        });
        return valueEnum
    }

    const columns: ProColumns<any>[] = [
        {
            title: 'LeaveType',
            dataIndex: 'leaveTypeId',

            key: 'leaveType',
            valueType: 'select',
            defaultSortOrder: 'ascend',
            sorter: (a, b) => a.leaveTypeId - b.leaveTypeId,
            valueEnum: generateLeaveTypeEnum()
        },
        {
            title: 'Entitlement type',
            dataIndex: 'type',
            key: 'type',
            filters: true,
            onFilter: true,
            valueType: 'select',
            valueEnum: generateEnum()
        },
        {
            title: 'Leave Period',
            key: 'period',
            defaultSortOrder: 'descend',
            sorter: (a, b) => moment(a.leavePeriodFrom).unix() - moment(b.leavePeriodFrom).unix(),
            render: (e) => {
                return <div style={
                    {
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap'
                    }
                }>{`${moment(e.leavePeriodFrom, "YYYY-MM-DD").isValid() ? moment(e.leavePeriodFrom).format("DD-MM-YYYY") : null} to ${moment(e.leavePeriodTo, "YYYY-MM-DD").isValid() ? moment(e.leavePeriodTo).format("DD-MM-YYYY") : null}`}</div>
            }
        },
        {
            title: 'Effective Date',

            key: 'validFrom',
            defaultSortOrder: 'ascend',
            sorter: (a, b) => moment(a.validFrom).unix() - moment(b.validFrom).unix(),
            render: (e) => {
                return <div style={
                    {
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap'
                    }
                }>{moment(e.validFrom, "YYYY-MM-DD").isValid() ? moment(e.validFrom).format("DD-MM-YYYY") : null}</div>
            }
        },
        {
            title: 'Expiry Date ',
            key: 'validTo',
            render: (e) => {
                return <div style={
                    {
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap'
                    }
                }>{moment(e.validTo, "YYYY-MM-DD").isValid() ? moment(e.validTo).format("DD-MM-YYYY") : null}</div>
            }

        },
        {
            title: ' Number Of Days',
            key: 'entilementCount',
            render: (e) => {
                return <> {e.entilementCount > 1 ? `${e.entilementCount} days` : `${e.entilementCount} day`}</>
            }
        }
    ];

    return (
        <Access
            accessible={hasPermitted('my-leave-entitlements')}
            fallback={<PermissionDeniedPage />}
        >
            <PageContainer title="My Leave Entitlements">
                <ProTable
                    actionRef={actionRef}
                    rowKey="id"
                    search={false}
                    columns={columns}
                    style={{ width: '100%' }}
                    scroll={{ x: '200px' }}
                    pagination={{ pageSize: 10, defaultPageSize: 10 }}
                    dataSource={allEntitlements}
                />
            </PageContainer>
        </Access>
    );
};

export default MyLeaveEntitlement;
