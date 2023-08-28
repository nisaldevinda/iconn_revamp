import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import { PageContainer } from '@ant-design/pro-layout';
import BasicContainer from '@/components/BasicContainer';
import {
  queryUserRoles,
  deleteByUserRolesID,
} from '@/services/userRole';
import { history } from 'umi';

const AddUserRole: React.FC = () => {
  const [model, setModel] = useState<any>();
  useEffect(() => {
    if (!model) {
      getModel(Models.UserRole).then((model) => {
        if (model && model.data) {
          setModel(model.data)
        }
      })
    }
  })
  return (
    <PageContainer>
      <BasicContainer
        rowId="id"
        titleKey="user-role"
        defaultTitle="User Role"
        model={model}
        tableColumns={[
          { name: 'title', sortable: true, filterable: true },
          { name: 'type', sortable: true, filterable: true },
        ]}
        searchFields={['title', 'type']}
        addFormType="function"
        editFormType="function"
        getAllFunction={queryUserRoles}
        addFunction={async () => history.push('/settings/accesslevels/createrole')}
        editFunction={async (record) =>
          history.push(`/settings/accesslevels/editrole/${record.id}`)
        }
        deleteFunction={deleteByUserRolesID}
        rowWiseEditActionPermissionHandler={(record) => {
          const { isEditable } = record;
          return isEditable;
        }}
        rowWiseDeleteActionPermissionHandler={(record) => {
          const { isEditable } = record;
          return isEditable;
        }}
        permissions={{
          addPermission: 'access-levels-read-write',
          editPermission: 'access-levels-read-write',
          deletePermission: 'access-levels-read-write',
          readPermission: 'access-levels-read-write',
        }}
      />
    </PageContainer>
  );
}

export default AddUserRole;
