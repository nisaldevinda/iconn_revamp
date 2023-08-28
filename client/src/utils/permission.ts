import _ from "lodash";

export type Permission = {
  userPermissions: string[]
  appPermissions: string[]
};

export function getPermissions(): Permission {
  try {
    const permissionString = localStorage.getItem('permissions');
    const permissions = permissionString ? JSON.parse(permissionString) : { userPermissions: [], appPermissions: [] };
    const { userPermissions, appPermissions } = permissions;
    return {
      userPermissions: userPermissions === undefined ? [] : userPermissions,
      appPermissions: appPermissions === undefined ? [] : appPermissions,
    };
  } catch (e) {
    console.log(e);
    return { userPermissions: [], appPermissions : []};
  }
}

export function setPermissions(permissions: Permission): boolean {
  try {
    localStorage.setItem('permissions', JSON.stringify(permissions));
    return true;
  } catch (e) {
    console.log(e);
    return false;
  }
}

export function unSetPermissions(): boolean {
  try {
    localStorage.removeItem('permissions');
    return true;
  } catch (e) {
    console.log(e);
    return false;
  }
}
export function getPrivileges() {
  try {
    const privilegeString = localStorage.getItem('permissions');
    const privileges = privilegeString ? JSON.parse(privilegeString) :
      {
        hasAnyAdminPrivileges: false,
        hasEmployeePrivileges: false,
        hasGlobalAdminPrivileges: false,
        hasManagerPrivileges: false,
        hasSystemAdminPrivileges: false
      }

    if (privileges.hasAnyAdminPrivileges) {
      return "admin"
    }
    if (privileges.hasManagerPrivileges) {
      return "manager"
    }
    if (privileges.hasEmployeePrivileges) {
      return "employee"
    }
    return false
  }
  catch (e) {
    console.log(e)
    return false;

  }
}

export function hasGlobalAdminPrivileges(): boolean {
  const permissionString = localStorage.getItem('permissions') || '{"hasGlobalAdminPrivileges":false}';
  const { hasGlobalAdminPrivileges } = JSON.parse(permissionString);
  return hasGlobalAdminPrivileges;
}
