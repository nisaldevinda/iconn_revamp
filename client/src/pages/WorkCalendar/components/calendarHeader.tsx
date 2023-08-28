import React from 'react';
import { HeaderRender } from 'antd/lib/calendar/generateCalendar';
import { Moment } from 'moment';
import { Row, Col, Select, Typography } from 'antd';
import styles from '../styles.less';

interface CommonProps {
  calendarName: string;
  setCalendarName?: (calendar: string) => void;
  typographOnChange: (calendarName: string) => void;
  getMomentObject?: (data: Moment) => void;
}
interface CalanderHeaderProps {
  commonProps?: CommonProps;
  componentHeaderProps: HeaderRender<Moment>;
}

export type CalendarNameParams = {
  id: string | number;
  name: string;
};

const CalanderHeader: React.FC<CalanderHeaderProps> = (props) => {
  const { onChange, onTypeChange, type, value } = props.componentHeaderProps; // header props
  const start = 0;
  const end = 12;
  const monthOptions = [];

  const localeData = value.localeData();
  const current = value.clone();

  props.commonProps?.getMomentObject(value);

  const months = [];
  for (let i = 0; i < 12; i++) {
    current.month(i);
    months.push(localeData.monthsShort(current));
  }

  for (let index = start; index < end; index++) {
    monthOptions.push(
      <Select.Option className="month-item" key={`${index}`}>
        {months[index]}
      </Select.Option>,
    );
  }
  const month = value.month();

  const year = value.year();
  const options = [];
  for (let i = year - 10; i < year + 10; i += 1) {
    options.push(
      <Select.Option key={i} value={i} className="year-item">
        {i}
      </Select.Option>,
    );
  }

  return (
    <div style={{ paddingBottom: 20 }}>
      <Row>
        <Col span={12}>
          <Typography.Title
            level={4}
            className={styles.calendarHeading}
            editable={{ onChange: props.commonProps?.typographOnChange, maxLength:100 }}
          >
            {props.commonProps?.calendarName}
          </Typography.Title>
        </Col>
        <Col span={12} style={{ justifyContent: 'right', display: 'flex' }}>
          <Select
            size="middle"
            dropdownMatchSelectWidth={false}
            onChange={(newYear) => {
              const now = value.clone().year(newYear);

              onChange(now);
            }}
            value={String(year)}
          >
            {options}
          </Select>

          <Select
            style={{ marginLeft: 10 }}
            size="middle"
            dropdownMatchSelectWidth={false}
            value={String(month)}
            onChange={(selectedMonth) => {
              const newValue = value.clone();

              newValue.month(parseInt(selectedMonth, 10));

              onChange(newValue);
            }}
          >
            {monthOptions}
          </Select>
        </Col>
      </Row>
    </div>
  );
};

export default CalanderHeader;
