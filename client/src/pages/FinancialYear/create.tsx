import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import ProForm, { ProFormTextArea } from '@ant-design/pro-form';
import _, { values } from 'lodash';
import { Row, Col, FormInstance } from 'antd';
import { useIntl } from 'react-intl';
import { Access, useAccess } from 'umi';
import { ProFormText, ProFormDatePicker, ProFormSwitch } from '@ant-design/pro-form';

export type CreateFormProps = {
  model: Partial<ModelType>;
  values: {};
  setValues: (values: any) => void;
  addGroupFormVisible: boolean;
  editGroupFormVisible: boolean;
  isEditView: boolean;
  form: FormInstance;
  emptySwitch: any;
};

const CreatePool: React.FC<CreateFormProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();

  useEffect(() => {
    // getOptions();
  }, []);

  return (
    <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
      <Col span={12}>
        <ProFormDatePicker
          width="md"
          format="YYYY/MMMM"
          picker="month"
          name="fromYearAndMonth"
          label={intl.formatMessage({
            id: 'fromYearAndMonth',
            defaultMessage: 'From',
          })}
          // disabled={props.hasUpcomingJobs}
          placeholder={intl.formatMessage({
            id: 'selectfromYearAndMonth',
            defaultMessage: 'Select From Year And Month',
          })}
          rules={[
            {
              required: true,
              message: intl.formatMessage({
                id: 'required',
                defaultMessage: 'Required',
              }),
            },
          ]}
          fieldProps={{
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues['fromYearAndMonth'] = value;
              props.setValues(currentValues);
            },
            autoComplete: 'none',
          }}
        />
      </Col>
      <Col span={12}>
        <ProFormDatePicker
          width="md"
          format="YYYY/MMMM"
          picker="month"
          name="toYearAndMonth"
          label={intl.formatMessage({
            id: 'toYearAndMonth',
            defaultMessage: 'To',
          })}
          // disabled={props.hasUpcomingJobs}
          placeholder={intl.formatMessage({
            id: 'selectToYearAndMonth',
            defaultMessage: 'Select To Year And Month',
          })}
          rules={[
            {
              required: true,
              message: intl.formatMessage({
                id: 'required',
                defaultMessage: 'Required',
              }),
            },
          ]}
          fieldProps={{
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues['toYearAndMonth'] = value;
              props.setValues(currentValues);
            },
            autoComplete: 'none',
          }}
        />
      </Col>
      <Col span={24}>
        <Row>
          <span style={{ marginRight: 15, marginTop: 5 }}>{'Set As Default'}</span>
          <ProFormSwitch
            name="isSetAsDefault"
            fieldProps={{
              onChange: (value) => {
                const currentValues = { ...props.values };
                currentValues['isSetAsDefault'] = value;
                props.setValues(currentValues);
              },
            }}
          />
        </Row>
      </Col>
    </Row>
  );
};

export default CreatePool;
