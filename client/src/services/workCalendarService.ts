import request from '@/utils/request';

export type CalendarMetaData = {
  date: string;
  dayType: string;
  dayTypeColor: string;
};

export type CalendarMetaParams = {
  calendarId: string;
  month: string;
  year: string;
};


export async function addCalendar(params: any) {
  return request(
    'api/work-calender/',
    {
      method: 'POST',
      data: { ...params },
    },
    true,
  );
}

export async function addSpecialDay(params: any) {
  return request(
    'api/work-calender/special-day',
    {
      method: 'POST',
      data: { ...params },
    },
    true,
  );
}

export async function getCalendarList() {
  return request('api/work-calender/calendar-list', {}, true);
}

export async function getCalendarMetaData(params: any) {
  return request('api/work-calender/calendar-meta-data/', { params }, true);
}

export async function getCalendarSummery(params: any) {
  return request('api/work-calender/calendar-summery/', { params }, true);
}

export async function getCalendarDateTypes() {
  return request('api/work-calender/calendar-date-types/', {}, true);
}

export async function editCalendarName(params: any) {
  return request(
    `api/work-calender/${params.id}/edit-calendar-name`,
    {
      method: 'PUT',
      data: { ...params },
    },
    true,
  );
}
