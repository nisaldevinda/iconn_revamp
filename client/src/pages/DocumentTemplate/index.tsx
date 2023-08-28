import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import { history } from 'umi';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import { deleteDocumentTemplate, getDocumentTemplates } from '@/services/documentTemplate';

const Notice: React.FC = () => {
  const [model, setModel] = useState<any>();

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel(Models.DocumentTemplate).then((response) => {
        setModel(response.data);
      });
    }
  }, []);

  return (
    <PageContainer>
      <BasicContainer
        rowId="id"
        titleKey="documentTemplate"
        defaultTitle="Letter Template Builder"
        model={model}
        tableColumns={[
          { name: 'name', filterable: true, sortable: true },
          { name: 'description', filterable: true, sortable: false },
        ]}
        recordActions={['add', 'edit', 'delete']}
        searchFields={['name', 'description']}
        addFormType="function"
        editFormType="function"
        getAllFunction={getDocumentTemplates}
        addFunction={async () => history.push('/settings/document-templates/new')}
        editFunction={async (record) => history.push(`/settings/document-templates/${record.id}`)}
        deleteFunction={deleteDocumentTemplate}
        permissions={{
          addPermission: 'document-template-read-write',
          editPermission: 'document-template-read-write',
          deletePermission: 'document-template-read-write',
          readPermission: 'document-template-read-write',
        }}
      />
    </PageContainer>
  );
};

export default Notice;

