import React, { FC, useState } from 'react';
import { Table, Typography } from 'antd';

const { Text } = Typography;

interface PackagePricingProps {
  numberOfLicenses: number;
  unitPrice: number;
}

const columns = [
  {
    title: 'Key',
    dataIndex: 'key',
    key: 'key',
    render: (text: string) => <Text strong>{text}</Text>,
  },
  {
    title: 'Value',
    dataIndex: 'value',
    key: 'value',
  },
];

const PackagePricing: FC<PackagePricingProps> = ({ numberOfLicenses, unitPrice }) => {
  const [tableData, setTableData] = useState([
    { key: 'Selected Plan:', value: 'Startup - Monthly Billing' },
    { key: 'Licenses:', value: numberOfLicenses },
    { key: 'Monthly Charge:', value: `$ ${unitPrice * numberOfLicenses}` },
  ]);

  return (
    <Table columns={columns} dataSource={tableData} size="small" pagination={false} showHeader={false} />
  );
};

export default PackagePricing;
