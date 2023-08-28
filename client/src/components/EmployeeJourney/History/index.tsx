import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { Card, Empty, Timeline, Typography } from 'antd';
import PromotionHistoryItem from './PromotionHistoryItem';
import ContactRenewalHistoryItem from './ContactRenewalHistoryItem';
import TransferHistoryItem from './TransferHistoryItem';
import ResignationHistoryItem from './ResignationHistoryItem';
import JoiningHistoryItem from './JoiningHistoryItem';
import { getEntity } from '@/services/department';
import RejoiningHistoryItem from './RejoiningHistoryItem';
import ReactiveHistoryItem from './ReactiveHistoryItem';

interface EmployeeJourneyHistoryProps {
    title?: string,
    data: any,
    records: any,
    employee: any
}

const EmployeeJourneyHistory: React.FC<EmployeeJourneyHistoryProps> = (props) => {
    const [records, setRecords] = useState<any>([]);

    useEffect(() => {
        arrangeJobsData();
    }, [props.records]);

    const arrangeJobsData = async () => {
        if (_.isArray(props.records) && !_.isEmpty(props.records)) {
            let _records = props.records
                .filter((record) => {
                    return !record.isRollback;
                })
                .sort((recordA, recordB) => {
                    var recordAEffectiveDate = new Date(recordA.effectiveDate);
                    var recordBEffectiveDate = new Date(recordB.effectiveDate);
                    return recordBEffectiveDate - recordAEffectiveDate;
                });
            // .map((record, index, sortedRecordSet) => {
            //     if (index < sortedRecordSet.length) {
            //         return {
            //             ...record,
            //             previousRecord: sortedRecordSet[index + 1]
            //         };
            //     }

            //     return record;
            // });


            const getEntityCallStack = [];
            let entityList = {};

            _records.forEach(job => {
                if (job.orgStructureEntityId) {
                    getEntityCallStack.push(getEntity(job.orgStructureEntityId).then(data => {
                        entityList[job.orgStructureEntityId] = data.data;
                    }));
                }
            });

            if (getEntityCallStack.length > 0) {
                Promise.all(getEntityCallStack).then(() => {
                    _records = _records.map(job => {
                        return {
                            ...job,
                            orgStructureEntity: entityList[job.orgStructureEntityId]
                        };
                    });

                    const preGetEntityCallStack = [];
                    let preEntityList = {};

                    _records.forEach(job => {
                        if (job.previousRecord) {
                            preGetEntityCallStack.push(getEntity(job?.previousRecord?.orgStructureEntityId).then(data => {
                                preEntityList[job?.previousRecord?.orgStructureEntityId] = data.data;
                            }));
                        }
                    });

                    if (preGetEntityCallStack.length > 0) {
                        Promise.all(preGetEntityCallStack).then(() => {

                            _records = _records.map(job => {

                                let preRec = {
                                    ...job.previousRecord,
                                    orgStructureEntity: preEntityList[job?.previousRecord?.orgStructureEntityId]
                                };

                                return {
                                    ...job,
                                    previousRecord: preRec
                                };
                            });

                            setRecords(_records);
                        });
                    } else {
                        setRecords(_records);
                    }

                });
            } else {
                setRecords(_records);
            }

        } else {
            setRecords([]);
        }
    }



    return (<>
        {props.title &&
            <Typography.Title level={5} style={{ marginTop: 24 }}>
                {props.title}
            </Typography.Title>}
        <Card>
            {!_.isEmpty(records)
                ? <Timeline>
                    {records.map(record =>
                        record.employeeJourneyType == 'JOINED'
                            ? <JoiningHistoryItem data={props.data} record={record} employee={props.employee} />
                            : record.employeeJourneyType == 'CONFIRMATION_CONTRACTS'
                                ? <ContactRenewalHistoryItem data={props.data} record={record} employee={props.employee} />
                                : record.employeeJourneyType == 'TRANSFERS'
                                    ? <TransferHistoryItem data={props.data} record={record} employee={props.employee} />
                                    : record.employeeJourneyType == 'RESIGNATIONS'
                                        ? <ResignationHistoryItem data={props.data} record={record} employee={props.employee} />
                                        : record.employeeJourneyType == 'REJOINED'
                                            ? <RejoiningHistoryItem data={props.data} record={record} employee={props.employee} />
                                            : record.employeeJourneyType == 'REACTIVATED'
                                                ? <ReactiveHistoryItem data={props.data} record={record} employee={props.employee} />
                                                : <PromotionHistoryItem data={props.data} record={record} employee={props.employee} />
                    )}
                </Timeline>
                : <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
            }
        </Card>
    </>);
};

export default EmployeeJourneyHistory;
