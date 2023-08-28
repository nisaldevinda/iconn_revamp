import { reloadAuthorized } from './Authorized';
import jwt_decode from "jwt-decode";

// use localStorage to store the authority info, which might be sent from server in actual project.
export function getAuthority(str?: string): string | string[] {
  const authorityString =
    typeof str === 'undefined' && localStorage ? localStorage.getItem('user_session') : str;
  // authorityString could be admin, "admin", ["admin"]
  let authority;
  try {
    if (authorityString) {
      authority = JSON.parse(authorityString);
    }
  } catch (e) {
    authority = authorityString;
  }
  if (typeof authority === 'string') {
    return [authority];
  }
  // preview.pro.ant.design only do not use in your production.
  // preview.pro.ant.design Dedicated environment variable, please do not use it in your project.
  if (!authority && ANT_DESIGN_PRO_ONLY_DO_NOT_USE_IN_YOUR_PRODUCTION === 'site') {
    return ['admin'];
  }
  return authority;
}

export function setAuthority(authority: {
  token_id: string,
  access_token: string,
  refresh_token: string,
  access_token_expire_at: number,
  refresh_token_expire_at: number
}): void {
  const { token_id, access_token_expire_at, refresh_token_expire_at } = authority;
  const { userId } = jwt_decode(authority.access_token);
  localStorage.setItem(
    'user_session',
    JSON.stringify({ access_token_expire_at, refresh_token_expire_at, userId }),
  );

  // auto reload
  reloadAuthorized();
}

export function unsetAuthority(): void {
  localStorage.removeItem('user_session');

  // auto reload
  reloadAuthorized();
}
