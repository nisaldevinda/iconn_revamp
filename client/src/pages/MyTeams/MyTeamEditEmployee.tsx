import React from 'react';
import EditEmployee from '../Employee/EditEmployee'
import MyTeamsService from '@/services/myTeams';
import { useParams, useAccess, Access } from 'umi';
import PermissionDeniedPage from './../403';


export type MyTeamEditEmployeeRouteParams = {
    id: string
};

const MyTeamEditEmployee: React.FC = () => {

    const { id } = useParams<MyTeamEditEmployeeRouteParams>();
    const access = useAccess();
    const { hasPermitted } = access;

    return (
        <Access
            accessible={(
                hasPermitted('my-teams-write') ||
                hasPermitted('my-teams-read')
            )}
            fallback={<PermissionDeniedPage />}
        >
            <EditEmployee
                id = {id}
                service = {MyTeamsService}
                returnRoute = "/manager-self-service/my-teams/employee"
                enableQuickSwitch = {true}
                isMyProfile={false}
                scope="MANAGER"
            />
        </Access>
    );
};

export default MyTeamEditEmployee;
