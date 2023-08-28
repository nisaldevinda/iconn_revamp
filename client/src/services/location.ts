import request from '@/utils/request';


export async function getAllLocations(params?: any) {
  return await request('/api/locations/', { params });
}

export async function addLocation(params: any) {
  return request('api/locations/', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateLocation(params: any) {
  return request(`api/locations/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeLocation(record: any) {
  return request(`api/locations/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}

export async function getLocationByCountryId(params?: any) {
  return await request('/api/locationByCountryId/', { params });
}

