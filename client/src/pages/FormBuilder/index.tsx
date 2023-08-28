import React, { useEffect, useState } from 'react';
import { getAllDynamicForm, addDynamicForm, removeDynamicForm } from '@/services/dynamicForm';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import _ from 'lodash';
import { history } from 'umi';
import { useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { ModalForm, ProFormRadio, ProFormText, ProFormTextArea } from '@ant-design/pro-form';
import { message, Spin } from 'antd';

const DynamicFormList: React.FC = () => {
  const intl = useIntl();
  const [loading, setLoading] = useState(false);
  const [model, setModel] = useState<any>();
  const [addFormVisibility, setAddFormVisibility] = useState<boolean>(false);

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel(Models.DynamicForm).then((response) => {
        setModel(response.data);
      });
    }
  }, []);

  return (
    <PageContainer>
      {loading
        ? <Spin />
        : <BasicContainer
          rowId="id"
          titleKey="form-builder"
          defaultTitle="Form Builder"
          model={model}
          tableColumns={[
            { name: 'title' },
            { name: 'description' }
          ]}
          searchFields={['title']}
          addFormType="function"
          editFormType="function"
          getAllFunction={getAllDynamicForm}
          addFunction={() => setAddFormVisibility(true)}
          editFunction={async (record) => history.push(`/settings/form-builder/${record.id}`)}
          deleteFunction={removeDynamicForm}
          permissions={{
            addPermission: 'leave-type-config',
            editPermission: 'leave-type-config',
            deletePermission: 'leave-type-config',
            readPermission: 'leave-type-config',
          }}
        />
      }

      <ModalForm
        title="Add New Form"
        visible={addFormVisibility}
        onVisibleChange={setAddFormVisibility}
        onFinish={async (values) => {
          setLoading(true);

          const messageKey = 'adding';
          message.loading({
            content: intl.formatMessage({
              id: 'pages.formbuilder.adding',
              defaultMessage: 'Adding...',
            }),
            key: messageKey,
          });

          const res = await addDynamicForm(values);

          if (res.error) {
            message.error({
              content:
                intl.formatMessage({
                  id: 'pages.formbuilder.adding.fail',
                  defaultMessage: 'Failed to adding.',
                }),
              key: messageKey,
            });
            return;
          }

          message.success({
            content:
              intl.formatMessage({
                id: 'pages.formbuilder.adding.succ',
                defaultMessage: 'Successfully added.',
              }),
            key: messageKey,
          });

          setAddFormVisibility(false);
          setLoading(false);
        }}
      >
        <ProFormText
          name="formName"
          label="Form Name"
          rules={[{ required: true, message: 'Required' }]}
        />
        <ProFormTextArea
          label="Description"
          name="description"
          rules={[{ required: true, message: 'Required' }]}
        />
        <ProFormRadio.Group
          label="Number of Tabs"
          name="numberOfTabs"
          initialValue="single"
          options={[{ label: 'Single Tab', value: 'single' }, { label: 'Multiple Tabs', value: 'multi' }]}
          rules={[{ required: true, message: 'Required' }]}
        />
      </ModalForm>
    </PageContainer>
  );
};

export default DynamicFormList;
