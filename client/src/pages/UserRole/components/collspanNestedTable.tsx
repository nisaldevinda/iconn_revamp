import { Select } from 'antd';
import React, { useState, useRef, Key, useEffect } from 'react';
import { useIntl, FormattedMessage } from 'umi';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import _ from 'lodash';
interface IProps {
  tableData?: any;
  employeeModle?: any;
  editableFields: any[];
  readableFields: any[];
  fieldKey: any;

  changeTable: any;
  oneToManyFielNameMode: any;
  oneToManyFielName: any;
  modelsObject: any;
}

interface TableType {
  reload: (resetPageIndex?: boolean) => void;
  reloadAndRest: () => void;
  reset: () => void;
  clearSelected?: () => void;
  startEditable: (rowKey: Key) => boolean;
  cancelEditable: (rowKey: Key) => boolean;
}

const CollapseNestedTable: React.FC<IProps> = ({
  tableData,
  employeeModle,
  editableFields,
  readableFields,
  changeTable,
  fieldKey,
  oneToManyFielName,
  oneToManyFielNameMode,
  modelsObject,
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
      dataIndex: 'defaultLabel',
      sorter: true,
      valueType: 'text',
    },

    {
      title: 'Access Controls',
      key: 'previousState',
      render: (record: any, dom) => (
        <Select
          defaultValue={
            editableFields[record.modelName].includes(record.name)
              ? 'CanEdit'
              : readableFields[record.modelName].includes(record.name)
              ? 'ViewOnly'
              : 'NoAccess'
          }
          style={{ width: 120 }}
          onChange={(e) => changeTable(record, e)}
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
        request={async () => {
          let dataSource = [];
          tableData.map((element: any) => {
            let fieldDefinition = employeeModle.fields[element];
            try {
              if (!fieldDefinition) return false;

              // employee model -> check field type is model and relation ship is OneToMany
              if (!oneToManyFielName.includes(fieldDefinition.modelName)) {
                fieldDefinition.modelName = 'employee';
                dataSource.push(fieldDefinition);
              }
              else {
                for (let key in modelsObject[fieldDefinition.modelName]) {
                  let Object = {
                    ...modelsObject[fieldDefinition.modelName][key],
                    modelName: fieldDefinition.modelName,
                  };
                  dataSource.push(Object);
                }
              }
            } catch (error) {}
          });

          return {
            data: dataSource,
            success: true,
          };
        }}
        rowKey="key"
        options={{
          search: false,
        }}
        search={false}
        dateFormatter="string"
      />
    </>
  );
};

export default CollapseNestedTable;
