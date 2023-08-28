import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { Collapse, Typography } from 'antd';
import PromotionUpcomingItem from './PromotionUpcomingItem';
import ContactRenewalUpcomingItem from './ContactRenewalUpcomingItem';
import TransferUpcomingItem from './TransferUpcomingItem';
import ResignationUpcomingItem from './ResignationUpcomingItem';
import { useIntl } from 'umi';
import moment from 'moment';

interface EmployeeJourneyUpcomingProps {
    title?: string,
    data: any,
    records: any,
    employee: any,
    setEmployee: (values: any) => void
}

const EmployeeJourneyUpcoming: React.FC<EmployeeJourneyUpcomingProps> = (props) => {
    const intl = useIntl();
    const { Panel } = Collapse;

    const [records, setRecords] = useState<any>([]);

    useEffect(() => {
        if (_.isArray(props.records) && !_.isEmpty(props.records)) {
            const _records = props.records
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
            setRecords(_records);
        } else {
            setRecords([]);
        }
    }, [props.records]);

    return (<>
        {!_.isEmpty(records) && props.title &&
            <Typography.Title level={5} style={{ marginTop: 24 }}>
                {props.title}
            </Typography.Title>}
        {!_.isEmpty(records) &&
            <Collapse
                accordion
                expandIconPosition='right'
            >
                {records.map((record: any) => {
                    let title = '';
                    let className = '';
                    let header = '';
                    let content = undefined;
                    const subTitle = intl.formatMessage({
                        id: 'employee_journey.effective_from',
                        defaultMessage: 'Effective From',
                    }).concat(' ').concat(moment(record.effectiveDate, 'YYYY-MM-DD').format("DD MMM YYYY"));
                    const jobTitle = props.data?.jobTitles?.find(option => option.value == record?.jobTitleId)?.label ?? '';

                    switch (record.employeeJourneyType) {
                        case 'CONFIRMATION_CONTRACTS':
                            title = intl.formatMessage({
                                id: 'employee_journey.contract_renewal',
                                defaultMessage: 'Contract Renewal',
                            });
                            header = jobTitle ? `${title} - ${jobTitle}` : title;
                            className = 'contract-renewal';
                            content = <ContactRenewalUpcomingItem
                                title={header}
                                data={props.data}
                                record={record}
                                employee={props.employee}
                                setEmployee={props.setEmployee}
                            />;
                            break;
                        case 'TRANSFERS':
                            title = intl.formatMessage({
                                id: 'employee_journey.transfer',
                                defaultMessage: 'Transfer',
                            });
                            header = jobTitle ? `${title} - ${jobTitle}` : title;
                            className = 'transfer';
                            content = <TransferUpcomingItem
                                title={header}
                                data={props.data}
                                record={record}
                                employee={props.employee}
                                setEmployee={props.setEmployee}
                            />;
                            break;
                        case 'RESIGNATIONS':
                            title = intl.formatMessage({
                                id: 'employee_journey.resignation',
                                defaultMessage: 'Resignation',
                            });
                            header = jobTitle ? `${title} - ${jobTitle}` : title;
                            className = 'resignation';
                            content = <ResignationUpcomingItem
                                title={header}
                                data={props.data}
                                record={record}
                                employee={props.employee}
                                setEmployee={props.setEmployee}
                            />;
                            break;
                        default:
                            title = intl.formatMessage({
                                id: 'employee_journey.promotion',
                                defaultMessage: 'Promotion',
                            });
                            header = jobTitle ? `${title} - ${jobTitle}` : title;
                            className = 'promotion';
                            content = <PromotionUpcomingItem
                                title={header}
                                data={props.data}
                                record={record}
                                employee={props.employee}
                                setEmployee={props.setEmployee}
                            />;
                            break;
                    }

                    return (
                        <Panel
                            key={record.id}
                            header={header}
                            extra={subTitle}
                            className={`employee-journey employee-journey-upcoming-${className}`}
                        >
                            {content}
                        </Panel>
                    );
                })}
            </Collapse>}
    </>);
};

export default EmployeeJourneyUpcoming;
