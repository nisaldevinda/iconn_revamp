import React from 'react';
import { Checkbox ,Button } from 'antd';
import ProForm, { ModalForm, ProFormInstance, ProFormText } from '@ant-design/pro-form';
import { APIResponse } from '@/utils/request';
import { useIntl } from 'react-intl';

export type calanderDayOptions = {
  label: string;
  value: string | number;
  isChecked: boolean;
};
interface AddCalendarModelProps {
  cardTitle: string;
  cardVisiblity?: boolean;
  trigger?: React.ReactNode | undefined;
  addedState?: (addedState: boolean) => void;
  modelVisiblity: boolean;
  setModelVisiblity: (visible: boolean) => void;
  createCalanderFunction?: (
    formData: any,
    allDayOptions: calanderDayOptions[],
  ) => Promise<APIResponse | void>;
  formRef?:ProFormInstance;
}

const AddCalendarModel: React.FC<AddCalendarModelProps> = (props) => {
  const intl = useIntl();

  const options: calanderDayOptions[] = [
    { label: 'Monday', value: 'Monday', isChecked: false },
    { label: 'Tuesday', value: 'Tuesday', isChecked: false },
    { label: 'Wednesday', value: 'Wednesday', isChecked: false },
    { label: 'Thursday', value: 'Thursday', isChecked: false },
    { label: 'Friday', value: 'Friday', isChecked: false },
    { label: 'Saturday', value: 'Saturday', isChecked: false },
    { label: 'Sunday', value: 'Sunday', isChecked: false },
  ];

  return (
    <ModalForm
      form={props.formRef}
      onVisibleChange={props.setModelVisiblity}
      visible={props.modelVisiblity}
      trigger={props.trigger}
      title={props.cardTitle}
      onFinish={async (params: any) => {
        props.createCalanderFunction(params, options);
      }}
      submitter={{
        render: (props, defaultDoms) => {
            return [

              <Button
                key="Reset"
                onClick={() => {
                  props.reset();
                }}
              >
                {intl.formatMessage({
                  id: 'RESET',
                  defaultMessage: 'Reset',
                })}
              </Button>,

              <Button
                key="ok"
                onClick={() => {
                  props.submit();
                }}
                type={"primary"}
              >
                {intl.formatMessage({
                  id: 'ADD',
                  defaultMessage: 'Add',
                })}
              </Button>,
            ];
        },
    }}
    >
      <ProFormText
        width="md"
        name="name"
        label={intl.formatMessage({
          id: 'work-calendar-calendar-name-feild',
          defaultMessage: 'Calendar Name',
        })}
        rules={[
          {
            required: true,
            message: intl.formatMessage({
              id: `work-calendar-calendar-working-days-feild-validation`,
              defaultMessage: `Required`,
            }),
          },
          {
            max:100,
            message: intl.formatMessage({
              id: `work-calendar-calendar-working-days-feild-validation`,
              defaultMessage: `Maximum length is 100 characters.`,
            }),
          }
        ]}   
      />

      <ProForm.Item
        name="check"
        label={intl.formatMessage({
          id: 'work-calendar-calendar-working-days-feild',
          defaultMessage: 'Select Working Days',
        })}
        rules={[
          {
            required: true,
            message: intl.formatMessage({
              id: `work-calendar-calendar-working-days-feild-validation`,
              defaultMessage: `Required`,
            }),
          },
        ]}
        style={{marginTop:16}}
      >
        <Checkbox.Group options={options} style={{ width: '100%' }} />
      </ProForm.Item>
    </ModalForm>
  );
};

export default AddCalendarModel;
