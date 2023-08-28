import request from '@/utils/request';

export async function getCountryByCountryKey(countryKey: string) {
  return request(`api/countries/${countryKey}`);
}

export async function getStateByStateKey(countryKey: string, stateKey: number) {
  return request(`api/countries/${countryKey}/states/${stateKey}`);
}

export async function getCountries() {
  return request('/api/countries');
}

export async function getStateByCountryKey(key: string) {
  return request(`/api/countries/${key}/states`);
}

export async function getCountriesList() {
  return request('/api/countries-list-for-work-patterns');
}
