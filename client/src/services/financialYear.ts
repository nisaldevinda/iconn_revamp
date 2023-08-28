import request from '@/utils/request';


export async function financialYears(params: any) {
    return request('/api/financialYears', { params });

}


export async function addFinancialYear(params: any) {
    return request('api/financialYears', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function updateFinancialYear(params: any) {
    return request(`api/financialYears/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
        
    }, true);
}
export async function removeFinancialYear(record: any) {
    return request(`api/financialYears/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
