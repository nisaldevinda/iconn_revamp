import { Select } from 'antd';
import React, { useState, useRef, Key, useEffect } from 'react';
import { useIntl, FormattedMessage } from 'umi';

import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';

interface IProps {
  tableData?: any;
  employeeModle?: any;
  editableFields: any[];
  readableFields: any[];
  fieldKey: any;
  // changeTable:(type:String,name:String)=>retun(type,name);
  changeTable: any;
}

interface TableType {
  reload: (resetPageIndex?: boolean) => void;
  reloadAndRest: () => void;
  reset: () => void;
  clearSelected?: () => void;
  startEditable: (rowKey: Key) => boolean;
  cancelEditable: (rowKey: Key) => boolean;
}

const CollapseTable: React.FC<IProps> = ({
  tableData,
  employeeModle,
  editableFields,
  readableFields,
  changeTable,
  fieldKey,
}) => {
  const { Option } = Select;
  const tableRef = useRef<TableType>();
  const intl = useIntl();

  useEffect(() => {
    tableRef.current?.reload();
  }, [tableData, employeeModle, editableFields, readableFields, changeTable, fieldKey]);
  const columns: ProColumns<any>[] = [
    {
      title: 'Data Field',
      dataIndex: 'title',
      sorter: true,
      valueType: 'text',
    },

    {
      title: 'Access Controls',
      key: 'previousState',
      render: (record: any, dom) => (
        <Select
          defaultValue={
            editableFields.includes(record.name)
              ? 'CanEdit'
              : readableFields.includes(record.name)
              ? 'ViewOnly'
              : 'NoAccess'
          }
          style={{ width: 120 }}
          onChange={(e) =>
            changeTable(
              record.name,
              editableFields.includes(record.name)
                ? 'CanEdit'
                : readableFields.includes(record.name)
                ? 'ViewOnly'
                : 'NoAccess',
              fieldKey,
              e,
            )
          }
        >
          <Option value="ViewOnly">View Only</Option>
          <Option value="CanEdit">Can Edit</Option>
          <Option value="NoAccess">No Access</Option>
        </Select>
      ),
    },
  ];

  return (
    <>
      <ProTable<any, { keyWord?: string }>
        columns={columns}
        request={(params, sorter) => {
          return Promise.resolve({
            data: tableData.map((element: any) => {
              for (let key in employeeModle.fields) {
                if (element == key) {
                  return { name: element, title: employeeModle.fields[key].defaultLabel };
                }
              }
            }),
            success: true,
          });
        }}
        options={{
          search: false,
        }}
        rowKey="key"
        search={false}
        dateFormatter="string"
      />
    </>
  );
};

export default CollapseTable;
