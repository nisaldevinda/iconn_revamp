import { Row, Checkbox } from 'antd';
import React, { useEffect } from 'react';
import { useIntl } from 'umi';

interface IProps {
  customCriteriaData?: any;
  employeeModle?: any;
  customCriteria: any;
  selectedCustomCriteria: any;
  objectKey?: any;
  onHandlerCriteriaCheckBox?: any;
}

const CollapseCustomCriteria: React.FC<IProps> = ({
  customCriteriaData,
  onHandlerCriteriaCheckBox,
  objectKey,
  selectedCustomCriteria,
  customCriteria,
}) => {
  const intl = useIntl();

  useEffect(() => {}, []);

  return (
    <>
      {customCriteriaData.content.map((customItem: any, id: any) => {
        return (
          <Row key={id}>
            <Checkbox
              key={id}
              onChange={(e) => onHandlerCriteriaCheckBox(objectKey, customItem.value, e)}
              checked={selectedCustomCriteria.includes(customItem.value)}
            >
              {customItem.label}
            </Checkbox>
          </Row>
        );
      })}
    </>
  );
};

export default CollapseCustomCriteria;
