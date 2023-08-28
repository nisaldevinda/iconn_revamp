import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getProRateFormulaList(params?:any) {
  return request('/api/pro-rate-formula-list', {
    params,
  });
}



