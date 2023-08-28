import React, { useState,useEffect } from 'react';
import { Table, Select, Typography, Button, Space, Tag } from 'antd';
import { FieldPermission } from '../userRoleData';
import _ from 'lodash';
import styles from '../subpage/styles.less'
const { Option } = Select;

interface IProps {
  dataKey: string;
  fields: any[];
  fieldAccessLevels: FieldPermission[];
  handleOnChangePermission: any;
  handleOnSelectChange: any;
  selectedRole: any;
  isCheckedAddEmployee:any;
}

const FieldTable: React.FC<IProps> = ({
  dataKey,
  fields,
  fieldAccessLevels,
  handleOnChangePermission,
  handleOnSelectChange,
  selectedRole,
  isCheckedAddEmployee
}) => {
  const [selectedRowKeys, setSelectedRowKeys] = useState<any>([]);
  const [tableData, setSetTableData] = useState<any>([]);
  const [bulkAction, setBulkAction] = useState<string>('');

  const columnsWithFieldType = [
    {
      title: 'Field',
      dataIndex: 'field',
      key: 'field',
      width: '35%',
      render: (text, record) => <Typography>{record.label}</Typography>,
    
    },
    {
      title: 'Field Type',
      dataIndex: 'fieldType',
      key: 'fieldType',
      width: '30%',
      render: (text, record) => (
        <Space>
            {
              record.fieldType == 'Optional' ? (
                <Tag style={{borderRadius : 20, fontSize: 14, width: 130, textAlign: 'center' , paddingRight: 20, paddingLeft : 20, paddingTop: 4, paddingBottom: 4 }} color="orange">{record.fieldType}</Tag>
              ) : 
              (
                <Tag style={{borderRadius : 20, fontSize: 14, width: 130, textAlign: 'center' , paddingRight: 20, paddingLeft : 20, paddingTop: 4, paddingBottom: 4 }} color="red">{record.fieldType}</Tag>
              )
            }

        </Space>
      ),
    },
    {
      title: 'Permission Level',
      dataIndex: 'permissionLevel',
      key: 'permissionLevel',
      width: '35%',
      render: (text, record) => (
        <Select
          defaultValue="noAccess"
          style={{ width: 120 }}
          value={record.accessLevel}
          onChange={(e) => {
            handleOnChangePermission(record, e);
          }}
          data-key={record.key}
        >
          <Option data-key="viewOnly" value="viewOnly">View Only</Option>
          <Option data-key="canEdit" value="canEdit" disabled={record.readOnly}>Can Edit</Option>
          <Option data-key="noAccess" value="noAccess">No Access</Option>
        </Select>
      ),
    },
  ];

  const columnsWithoutFieldType = [
    {
      title: 'Field',
      dataIndex: 'field',
      key: 'field',
      width: '50%',
      render: (text, record) => <Typography>{record.label}</Typography>,
    },
    {
      title: 'Permission Level',
      dataIndex: 'permissionLevel',
      key: 'permissionLevel',
      width: '50%',
      render: (text, record) => (
        <Select
          defaultValue="noAccess"
          style={{ width: 120 }}
          value={record.accessLevel}
          onChange={(e) => {
            handleOnChangePermission(record, e);
          }}
          data-key={record.key}
        >
          <Option data-key="viewOnly" value="viewOnly">View Only</Option>
          <Option data-key="canEdit" value="canEdit" disabled={record.readOnly}>Can Edit</Option>
          <Option data-key="noAccess" value="noAccess">No Access</Option>
        </Select>
      ),
    },
  ];


  const data = fields.map((field) => {
    const accessLevel = _.find(fieldAccessLevels, function (fieldAccessLevel) {
      return fieldAccessLevel.key == field.key;
    });
    return {
      key: field.key,
      label: field.value,
      readOnly: field.readOnly,
      fieldType: (accessLevel) ? accessLevel.fieldType: 'Optional',
      accessLevel: accessLevel ? accessLevel.permission : 'noAccess',
    };
  });  

  const onSelectChange = (selectedRowKeys: string[], selectedRows: any) => {
    setSelectedRowKeys([...selectedRowKeys]);
    setBulkAction('');
  };

  const rowSelection = {
    selectedRowKeys,
    onChange: onSelectChange,
  };
  const hasSelected = selectedRowKeys.length > 0;

  return (
    <>
      <div style={{ marginBottom: 16 }}>
        <span style={{ marginRight: 12 }}>
          {hasSelected ? `Selected ${selectedRowKeys.length} Fields` : ''}
        </span>
        <Select
          style={{ width: 120 }}
          disabled={!hasSelected}
          value={bulkAction}
          onChange={(value) => {
            setBulkAction(value);
            handleOnSelectChange(value, selectedRowKeys);
          }}
        >
          <Option value="">Bulk Action</Option>
          <Option value="viewOnly">View Only</Option>
          <Option value="canEdit">Can Edit</Option>
          <Option value="noAccess">No Access</Option>
        </Select>
      </div>
      <Table
        rowSelection={rowSelection}
        columns={ selectedRole == 'ADMIN' ? columnsWithFieldType : columnsWithoutFieldType }
        dataSource={data}
        pagination={false}
        size="small"
        data-key={dataKey}
        className= {styles.tableField}
      />
    </>
  );
};

export default FieldTable;
