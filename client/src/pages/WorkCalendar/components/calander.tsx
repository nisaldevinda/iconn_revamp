import React from 'react';
import _ from 'lodash';
import { Calendar, Badge, ConfigProvider, Menu, Dropdown } from 'antd';
import { Moment } from 'moment';
import moment from 'moment';
import styles from '../styles.less';
import en_US from 'antd/lib/locale-provider/en_US';
import 'moment/locale/en-gb';
import { HeaderRender } from 'antd/lib/calendar/generateCalendar';

moment.locale('en-gb');

type CalendarMode = 'year' | 'month';

type CalendarItems = {
  date: number | string | Moment;
  dayType: string;
  dayTypeId: string | number;
  dayTypeColor: 'warning' | 'error' | 'success';
};

type CalendarDateType = {
  id: number;
  name: string;
  typeColor: string;
};

type SpeicalDayDataParams = {
  calendarId: string | number;
  date: string;
  dateTypeId: string;
};

interface SelectableCalendarProps {
  getDateCellData: CalendarItems[];
  calendarOnSelect?: (date: Moment) => void;
  calendarOnPanelChange: (date: Moment, mode: CalendarMode) => void;
  calendarValue?: any;
  calendarDateTypes: CalendarDateType[];
  getSelectedDateType: (dateTypeKey: number, selectedDate: any) => void;
  menuOnClick: (dateTypeKey: number) => void;
  calendarCustomHeader?: HeaderRender<Moment>;
}

const SelectableCalendar: React.FC<SelectableCalendarProps> = (props) => {
  
  const DateCellRender = (date: Moment) => {
    if (!_.isNull(props.getDateCellData) || _.isArray(props.getDateCellData)) {
      if (!_.isNull(props.calendarDateTypes) || _.isArray(props.calendarDateTypes)) {
        const dateTypeMenu = (selectedDate: string) => (
          <Menu>
            {props.calendarDateTypes?.map(
              (dropDownItem: CalendarDateType, dropDownIndex: number) => {
                return (
                  <Menu.Item
                    key={dropDownIndex}
                    onClick={(data: any) => {
                      props.getSelectedDateType(data.key, selectedDate);
                    }}
                  >
                    <p>{dropDownItem.name}</p>
                  </Menu.Item>
                );
              },
            )}
          </Menu>
        );

        return props.getDateCellData.map((dateCellItem: CalendarItems, dateCellIndex: number) => {
          if (dateCellItem.date == date.format('YYYY-MM-DD')) {
            return (
              <Dropdown
                overlay={() => {
                  return dateTypeMenu(dateCellItem.date);
                }}
                placement="topCenter"
                key={dateCellIndex}
              >
                <Badge
                  className={styles.calendarBadge}
                  color={dateCellItem.dayTypeColor}
                  text={dateCellItem.dayType}
                />
              </Dropdown>
            );
          }
        });
      }
    }
  };

  return (
    <div>
      <ConfigProvider locale={en_US}>
        <Calendar
          style={{ padding: '3.7vh', borderRadius: 10 }}
          dateCellRender={DateCellRender}
          onSelect={props.calendarOnSelect}
          onPanelChange={props.calendarOnPanelChange}
          headerRender={props.calendarCustomHeader}
        />
      </ConfigProvider>
    </div>
  );
};

export { SelectableCalendar, CalendarItems, CalendarDateType, SpeicalDayDataParams };
