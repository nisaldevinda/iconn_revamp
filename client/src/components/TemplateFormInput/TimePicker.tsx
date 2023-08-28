import { generateProFormFieldValidation } from '@/utils/validator';
import { ProFormTimePicker } from '@ant-design/pro-form';
import { Col } from 'antd';
import React, { useEffect, useRef, useState, useContext } from 'react';
import { useIntl } from 'react-intl';
import _ from 'lodash';

export type TimePickerProps = {
  fieldName: string;
  answerDetails: any;
  fieldDefinition: {
    name: string;
    type: string;
    validations: {
      isRequired: boolean;
    };
  };
  readOnly: boolean;
  values: {};
  setValues: (values: any) => void;
};

const TimePicker: React.FC<TimePickerProps> = (props) => {
  const intl = useIntl();
  const fieldName = props.fieldName;
  const [timeModel, setTimeModel] = useState<moment.Moment>();

  return (
    <Col data-key={fieldName} span={6}>
      <ProFormTimePicker
        width="md"
        name={fieldName}
        rules={
          props.answerDetails.isRequired
            ? [
                {
                  required: true,
                  message: intl.formatMessage({
                    id: `${fieldName}.rules.required`,
                    defaultMessage: `Required`,
                  }),
                },
              ]
            : []
        }
        fieldProps={{
          format: 'hh:mm A',
          onSelect: (value) => {
            const currentValues = { ...props.values };
            currentValues[fieldName] =
              !_.isNull(value) && !_.isUndefined(value) ? value.format('hh:mm A') : null;
            props.setValues(currentValues);
            setTimeModel(value);
          }
        }}
        value={timeModel}
        // initialValue={props.fieldDefinition.defaultValue}
      />
    </Col>
  );
};

export default TimePicker;
