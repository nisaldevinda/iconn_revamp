import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    querySalaryComponent,
    addSalaryComponent,
    updateSalaryComponent,
    removeSalaryComponent
} from '@/services/SalaryComponentService'


const SalaryComponent: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.SalaryComponents).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
        <BasicContainer
            rowId="id"
            titleKey="salaryComponents"
            defaultTitle="Salary Component"
            model={model}
            tableColumns={[
                { name: 'name', sortable: true, filterable: true },
                { name: 'salaryType', sortable: true, filterable: true },
                {
                    name: 'valueType', sortable: true, filterable: true,
                    render: (_, record) => {
                        if (record.salaryType == 'FIXED_ALLOWANCE') {
                            return "Amount";
                        }

                        const option = model.modelDataDefinition.fields.valueType.values?.find((value: any) => value.value == record.valueType);
                        return option && option.defaultLabel ? option.defaultLabel : '-';
                    }
                },
            ]}
            recordActions={['add', 'edit', 'delete']}
            defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
            searchFields={['name']}
            addFormType="model"
            editFormType="drawer"
            getAllFunction={querySalaryComponent}
            addFunction={addSalaryComponent}
            editFunction={updateSalaryComponent}
            deleteFunction={removeSalaryComponent}
            permissions={{
                addPermission: 'master-data-write',
                editPermission: 'master-data-write',
                deletePermission: 'master-data-write',
                readPermission: 'master-data-write',
            }}
        />
    );
}

export default SalaryComponent
