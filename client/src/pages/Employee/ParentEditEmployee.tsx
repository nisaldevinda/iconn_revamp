import React from 'react';
import EditEmployee from './EditEmployee'
import EmployeeService from '@/services/employee';
import { useParams, useAccess, Access } from 'umi';
import PermissionDeniedPage from './../403';


export type ParentEditEmployeeRouteParams = {
    id: string
};

const ParentEditEmployee: React.FC = () => {

    const { id } = useParams<ParentEditEmployeeRouteParams>();
    const access = useAccess();
    const { hasPermitted } = access;

    return (

        <Access
            accessible={(
                hasPermitted('employee-read') ||
                hasPermitted('employee-write') 
            )}
            fallback={<PermissionDeniedPage />}
        >

            <EditEmployee
                id = {id}
                service = {EmployeeService}
                returnRoute = "/employees"
                enableQuickSwitch = {true}
                isMyProfile={false}
                scope="ADMIN"
            />

        </Access>

    )
};

export default ParentEditEmployee;
