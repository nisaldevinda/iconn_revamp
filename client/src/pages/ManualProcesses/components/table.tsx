import ProTable, { ProColumns } from '@ant-design/pro-table';
import React from 'react';

export type TableProps = {
  tableColumns: ProColumns[];
  request?: dataRequest;
};
export type TableListItem = {
  task: string;
  description: string;
  action: string;
  username: string;
  startTime: Date;
  endTime: Date;
  status: string;
};

const Table: React.FC<TableProps> = (props) => {
  return (
    <ProTable<TableListItem>
      columns={props.columns}
      search={false}
      request={props.request}
    />
  );
};

export default Table;
