import request from '@/utils/request';

import type { ViewReportTableParams, ReportDataDropdownParams, TableListItem } from '@/pages/ReportEngine/sub_pages/ViewReport/data.d';
import { modifyFilterParams } from '@/utils/utils';
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import moment from 'moment';
/**
 * To get all Reports
 */
 

export async function queryReport(params?: any) {
  params = modifyFilterParams(params);
  return request('/api/report-data/', { params });
}
export async function getReportDataById(reportId?: string) {
  return request(`/api/report-data/${reportId}`);
}
export async function getAllReports(params: any, privilege: string | boolean) {

  return request(`/api/report-data/${privilege}/get-all`, { params });
}

export async function queryReportNamesAndIds(params?: ReportDataDropdownParams) {
  return request('/api/report-data-names/', { params });
}

export async function queryReportWithDynamicFilters(reportId: string, params?: any) {
  return request(`/api/report-data/${reportId}/query-report-dynamically`, { params });
}

export async function queryReportById(route: any ,reportId: string) {
  return request(`/api/report-data/${route}/${reportId}/get-report`);
}

export async function addReport(params: any) {
  return request('api/report-data/', {
    method: 'POST',
    data: {
      ...params,
    },
  });
}

export async function updateReport(id,data) {
  return request(`api/report-data/${id}`, {
    method: 'PUT',
    data: {
      ...data,
    },
  });
}

export async function removeReport(params) {
  return request(`api/report-data/${params}`, {
    method: 'DELETE',
  });
}

export async function printToPdf(dynamicColumnData:[], data: any ,reportName:any) {
  const unit = "pt";
  const size = "A4"; // Use A1, A2, A3 or A4 - if using a select menu give its value insted 
  const orientation = "portrait"; // portrait or landscape - same as page size 
  const pdf = new jsPDF(orientation, unit, size)
  autoTable(pdf, {
      horizontalPageBreak:true ,
      head: [dynamicColumnData],
      body: data.map((dynamicData: any) => {
          const restructureData = []
          for (const key in dynamicData) {
              restructureData.push(dynamicData[key])
          }
          return restructureData
      })
  })
  const date = moment().format('YYYY-MM-DD_HH_mm_ss');
  pdf.save(`${reportName}_${date}.pdf`)
}

export async function queryFilterDefinitions() {
  return request('/api/report-filter-definitions')
}

export async function queryChartById(route: any, reportId: string) {
  return request(`/api/report-data/${route}/${reportId}/get-chart`);
}

export async function downloadReportByFormat(route:any, reportId:any, params?:any ) {
  return request(`/api/report/${route}/${reportId}/download-report-by-format`,{ params });
}
  