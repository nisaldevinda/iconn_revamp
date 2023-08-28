import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

import { addPayGrades, queryPayGrades, removePayGrades, updatePayGrades } from '@/services/PayGradeService';

const PayGrade: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.PayGrades).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="pay-grades"
        defaultTitle="Pay Grades"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
          { name: 'salaryComponentIds' },
          // { name: 'minimumSalary', sortable: true, filterable: true },
          // { name: 'maximumSalary', sortable: true, filterable: true },
          { name: 'currency', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={queryPayGrades}
        addFunction={addPayGrades}
        editFunction={updatePayGrades}
        deleteFunction={removePayGrades}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default PayGrade