import { getPermissions } from '@/utils/permission';
import routes from '../config/routes';
import _ from 'lodash'
export default () => {
  // console.log('initialState > ', initialState);

  //TODO:: should change this to state
  const { appPermissions, userPermissions } = getPermissions();

  // console.log('permissionList > ', appPermissions);
  // console.log('userPermissions > ', userPermissions);

  const permissionJson = appPermissions.reduce(
    (jsonObj, permission) => ({
      ...jsonObj,
      [permission]: userPermissions.includes(permission) ? true : false,
    }),
    {},
  );

  console.log('permissionJson >>>>', permissionJson);

  const hasPermitted = (permission: string | undefined) => {
    if (!permission) {
      return false;
    }
    return permissionJson[permission] ? true : false;
  };

  const canShowMenuItem = (route: any) => {
    let canShow = false;
    route.permissions.forEach((permission: string) => {
      if (permissionJson[permission] === true) {
        canShow = true;
      }
    });
    return canShow;
  };
  const hasAnyPermission = (permissions: string[] | undefined) => {
    let canShow = false
    if (!permissions || permissions?.length === 0) {
      return false
    }
    canShow = permissions.some(element => permissionJson[element])
    return canShow
  }
  const canShowShortcuts = (routeName: any) => {
    let path=_.find(routes[0].routes[1].routes[0].routes,o=>o.path===routeName)
    let canShow = false;
    if(!path){
      return false
    }
    path.permissions.forEach((permission: string) => {
      if (permissionJson[permission] === true) {
        canShow = true;
      }
    });
    return canShow;
  };
  return {
    ...permissionJson,
    hasPermitted,
    canShowMenuItem,
    hasAnyPermission,
    canShowShortcuts
  };
}
