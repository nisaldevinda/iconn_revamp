import React, { FC, useEffect, useState } from 'react';
import { Button, Modal, Space, Typography } from 'antd';
import PackageDetails from './PackageDetails';
import PackagePricing from './PackagePricing';

const { Title, Text } = Typography;

interface SubscribeOrUpgradeProps {
  isSubmitting: boolean;
  isModalOpen: boolean;
  handleOk: any;
  handleCancel: any;
}

const SubscribeOrUpgrade: FC<SubscribeOrUpgradeProps> = ({
  isSubmitting,
  isModalOpen,
  handleOk,
  handleCancel,
}) => {
  const [current, setCurrent] = useState<number>(0);
  const [numberOfLicences, setNumberOfLicences] = useState<number>(NUMBER_OF_FREE_LICENSES);

  const licencesOnChangeHandler = (value: number): void => {
    console.log(value);
    setNumberOfLicences(value);
  };

  useEffect(() => {

  }, []);

  return (
    <Modal
      title={
        <Space direction="horizontal">
          <Title level={4}>Upgrade a Plan</Title>
          <Title level={5} type="secondary">
            If you need more info, please check<Button type="link">Pricing Details</Button>
          </Title>
        </Space>
      }
      visible={isModalOpen}
      onCancel={handleCancel}
      width={1000}
      footer={
        <>
          <Button
            type="primary"
            hidden={current === 1}
            onClick={() => {
              setCurrent(1);
            }}
          >
            Get Started
          </Button>
          <Button
            type="default"
            hidden={current === 0}
            onClick={() => {
              setCurrent(0);
            }}
          >
            Previous
          </Button>
          <Button
            loading={isSubmitting}
            disabled={isSubmitting}
            type="primary"
            hidden={current === 0}
            onClick={() => {
              handleOk({ mode: 'subscription', quantity: numberOfLicences });
            }}
          >
            Subscribe
          </Button>
        </>
      }
    >
      {current === 0 ? (
        <PackageDetails
          minimumLicenceCount={NUMBER_OF_FREE_LICENSES}
          numberOfLicences={numberOfLicences}
          licencesOnChangeHandler={licencesOnChangeHandler}
        />
      ) : (
        <PackagePricing unitPrice={4.5} numberOfLicenses={numberOfLicences} />
      )}
    </Modal>
  );
};

export default SubscribeOrUpgrade;
