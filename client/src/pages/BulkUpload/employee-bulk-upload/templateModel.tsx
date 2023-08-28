import React from 'react';
import { message } from 'antd';
import { ProFormDigit } from '@ant-design/pro-form';
import { ModalForm } from '@ant-design/pro-form';
import { APIResponse } from '@/utils/request';
import { useIntl } from 'react-intl';
import { downloadBase64File } from '@/utils/utils';
import { Models } from '@/services/model';
import _ from 'lodash';

interface TemplateModalProps {
  cardTitle: string;
  cardVisiblity?: boolean;
  onSubmit: (formData: any) => Promise<APIResponse | void>;
  trigger?: React.ReactNode | undefined;
}

const TemplateModal: React.FC<TemplateModalProps> = (props) => {
  const intl = useIntl();

  return (
    <>
      <ModalForm
        trigger={props.trigger}
        title={props.cardTitle}
        onFinish={async (values: any) => {
          const key = 'saving';
          if (!_.isEmpty(values) || !_.isUndefined(values.employeeCount)) {
            const queryParams = {
              modelName: Models.Employee,
              feildCount: values.employeeCount,
            };
            await props
              .onSubmit(queryParams)
              .then((response: APIResponse) => {
                if (response.error) {
                  message.error({
                    content:
                      response.message ??
                      intl.formatMessage({
                        id: 'failedToDownload',
                        defaultMessage: 'Failed to download',
                      }),
                    key,
                  });
                  return;
                }
                if (!_.isUndefined(response.data) || !_.isEmpty(response.data)) {
                  downloadBase64File(
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    response.data,
                    'document.xlsx',
                  );
                }
                message.success({
                  content:
                    response.message ??
                    intl.formatMessage({
                      id: 'successfullyDownloaded',
                      defaultMessage: 'Successfully downloaded',
                    }),
                  key,
                });
              })
              .catch((error: APIResponse) => {
                message.error({
                  content:
                    error.message ??
                    intl.formatMessage({
                      id: 'failedToDownload',
                      defaultMessage: 'Failed to download',
                    }),
                  key,
                });
              });
          }
        }}
      >
        <ProFormDigit
          name="employeeCount"
          required
          label={intl.formatMessage({
            id: 'employeeCount  ',
            defaultMessage: 'Employee Count',
          })}
          rules={[{ required: true, message: 'Employee count required' }]}
          fieldProps={{
            type: 'number',
          }}
        />
      </ModalForm>
    </>
  );
};

export default TemplateModal;
