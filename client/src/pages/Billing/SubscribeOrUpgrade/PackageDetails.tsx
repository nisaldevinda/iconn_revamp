import React, { FC, useState, ChangeEvent } from 'react';
import { Button, Row, Col, Modal, Space, Form, InputNumber, Typography, Card } from 'antd';
import { CheckCircleTwoTone } from '@ant-design/icons';

const { Title, Text } = Typography;

interface PackageDetailsProps {
  minimumLicenceCount: number;
  numberOfLicences: number;
  licencesOnChangeHandler: void;
}
 
const PackageDetails: FC<PackageDetailsProps> = ({
  minimumLicenceCount,
  numberOfLicences,
  licencesOnChangeHandler,
}) => {
  return (
    <Row gutter={[16, 16]}>
      <Col span={12}>
        <Title level={5}>Startup Package</Title>
        <Space align="center">
          <Form.Item label="Number of Licences">
            <InputNumber
              min={minimumLicenceCount}
              max={1000000}
              defaultValue={minimumLicenceCount}
              onChange={licencesOnChangeHandler}
              value={numberOfLicences}
            />
          </Form.Item>
        </Space>
      </Col>
      <Col span={12}>
        <Space direction="vertical">
          <Title level={5}>What’s in Startup Plan?</Title>
          <Card>
            <Space direction="vertical">
              <Space>
                <CheckCircleTwoTone twoToneColor="#52c41a" />
                <Text>Up to 99 Active Users</Text>
              </Space>
              <Space>
                <CheckCircleTwoTone twoToneColor="#52c41a" />
                <Text>Work Time Monitoring</Text>
              </Space>
              <Space>
                <CheckCircleTwoTone twoToneColor="#52c41a" />
                <Text>Offline/Manual task creation</Text>
              </Space>
              <Space>
                <CheckCircleTwoTone twoToneColor="#52c41a" />
                <Text>Screenshots (Up to 15 per Hour)</Text>
              </Space>
              <Space>
                <CheckCircleTwoTone twoToneColor="#52c41a" />
                <Text>Projects and Tasks</Text>
              </Space>
            </Space>
          </Card>
        </Space>
      </Col>
    </Row>
  );
};
 
export default PackageDetails;