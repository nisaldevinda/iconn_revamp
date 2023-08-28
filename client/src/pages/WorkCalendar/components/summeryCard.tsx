import React from 'react';
import { Card, Col, Row, Typography, Spin, Tooltip } from 'antd';
import styles from '../styles.less';
import { FormattedMessage } from 'react-intl';
import _ from 'lodash';

type SummeryCardParams = {
  calendarId: string;
  year: string;
};

type SummeryCardRequestData = {
  workingDays: string;
  nonWorkingDays: string;
  calendarCreatedOn: string;
};

export type SummeryData = {
  calendarCreatedOn: string;
  dateTypes: any;
};
interface SummeryCardProps {
  cardTitle: string;
  summeryData: SummeryData;
}
interface CalendarWidgetProps {
  dateTypeName: string;
  dateTypes: any;
}

const { Text } = Typography;

const CalendarWidget: React.FC<CalendarWidgetProps> = (props) => {
  return (
    <>
      <div
        className={styles.calendarWidgetContainer}
        style={{
          border: `2px solid  ${props.dateTypes[props.dateTypeName].color}`,
        }}
      >
        <div
          className={styles.calendarWidgetHeading}
          style={{ backgroundColor: props.dateTypes[props.dateTypeName].color }}
        >
          &nbsp;
        </div>
        <div className={styles.calendarWidgetContent}>
          {props.dateTypes[props.dateTypeName].dayCount}
        </div>
      </div>
    </>
  );
};

const SummeryCard: React.FC<SummeryCardProps> = (props) => {
  return (
    <Card
      style={{ width: '90%', borderRadius: 10 }}
      className={styles.summeryCard}
      title={props.cardTitle}
    >
      <div style={{ padding: '0 1vh 1vh 1vh' }}>
        <Row>
          {_.isUndefined(props.summeryData) ? (
            <Spin />
          ) : (
            <Col style={{ display: 'flex', flexDirection: 'column' }}>
              <Text style={{ fontSize: 14, fontWeight: 300 }}>
                <FormattedMessage
                  id="work-calendar-summery-last-updated-on"
                  defaultMessage="Last Updated On"
                />
              </Text>
              <Text style={{ fontSize: 18, fontWeight: 400 }}>
                {props.summeryData.calendarCreatedOn}
              </Text>
            </Col>
          )}
        </Row>
        <Row style={{ marginTop: '2vh' }}>
          <Col span={24}>
            <Row gutter={[20, 20]} style={{ display: 'flex', flexDirection: 'row' }}>
              {_.isUndefined(props.summeryData) || _.isUndefined(props.summeryData.dateTypes) ? (
                <Spin />
              ) : (
                Object.keys(props.summeryData.dateTypes)
                  .reverse()
                  .map((dayTypeName: string) => {
                    return (
                      <Col
                        span={24}
                        style={{ display: 'flex', flexDirection: 'row' }}
                      >
                        <CalendarWidget
                          dateTypeName={dayTypeName}
                          dateTypes={props.summeryData.dateTypes}
                        />
                          <Tooltip title={`Total ${dayTypeName}`}>
                          <Text
                            style={{
                              marginLeft: 5,
                              fontSize: 14,
                              fontWeight: 300,
                              display: 'inline-block',
                              width: '70%',
                              textOverflow: 'ellipsis',
                              whiteSpace: 'nowrap',
                              overflow: 'hidden'
                            }}
                          >
                            <FormattedMessage
                              id="work-calendar-summery-total-days"
                              defaultMessage={`Total ${dayTypeName}`}
                            />

                          </Text>
                        </Tooltip>



                      </Col>
                    );
                  })
              )}
            </Row>
          </Col>
        </Row>
      </div>
    </Card>
  );
};

export { SummeryCard, SummeryCardParams, SummeryCardRequestData };
