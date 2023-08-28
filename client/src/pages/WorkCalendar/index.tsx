import React, { useState, useEffect, useRef } from 'react';
import { Layout, Button, Row, Col, message, Form } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { WorkCalanderMenu, MenuItemData } from './components/menu';
import { SummeryCard, SummeryCardParams, SummeryData } from './components/summeryCard';
import AddCalendarModel, { calanderDayOptions } from './components/addCalendarModel';
import { PlusOutlined } from '@ant-design/icons';
import { Moment } from 'moment-timezone';
import styles from './styles.less';
import _ from 'lodash';
import { useIntl, FormattedMessage } from 'react-intl';
import { APIResponse } from '@/utils/request';
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import CalendarHeader, { CalendarNameParams } from './components/calendarHeader';
import {
  getCalendarList,
  addCalendar,
  getCalendarMetaData,
  CalendarMetaParams,
  getCalendarSummery,
  getCalendarDateTypes,
  addSpecialDay,
  editCalendarName,
} from '@/services/workCalendarService';
import {
  CalendarItems,
  SelectableCalendar,
  CalendarDateType,
  SpeicalDayDataParams,
} from './components/calander';
import './styles.css';
import { ActionType } from '@ant-design/pro-table';

const WorkCalander: React.FC = () => {
  const [calendarMetaData, setCalendarMetaData] = useState<CalendarItems[]>([]);
  const [calendarMenuItemList, setCalendarMenuItemList] = useState<MenuItemData[]>();
  const [calendarSummeryData, setCalendarSummeryData] = useState<SummeryData>();
  const [calendarDateTypes, setCalendarDateTypes] = useState<CalendarDateType[]>();
  const [calendarName, setCalendarName] = useState<string>('');
  const [calendarMetaParams, setCalendarMetaParams] = useState<CalendarMetaParams>();
  const [calendarSummeryParams, setCalendarSummeryParams] = useState<SummeryCardParams>();
  const [specialDayAddedState, setSpecialDayAddedState] = useState<boolean>(false);
  const [addModelVisiblity, setAddModelVisibility] = useState<boolean>(false);
  const [calendarAddedState, setCalendarAddedState] = useState<boolean>(false);

  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  let initialCalendar = {};
  const [addFormReference] = Form.useForm();

  // created to fetch the inital menu data
  useEffect(() => {
    getCalendarList().then((res: any) => {
      if (_.isArray(res.data) && !_.isNull(res.data)) {
        setCalendarMenuItemList(res.data);
      }
    });

    getCalendarDateTypes().then((res) => {
      if (_.isArray(res.data) && !_.isNull(res.data)) {
        setCalendarDateTypes(res.data);
      }
    });
  }, []);

  // hook to intial calendar metadata and heading data and summery card data
  useEffect(() => {
    if (!_.isUndefined(calendarMenuItemList) || !_.isEmpty(calendarMenuItemList)) {
      if (!_.isEmpty(calendarMenuItemList[0].menuItemName)) {
        setCalendarName(calendarMenuItemList[0].menuItemName);
      }

      const initalMetaParams: CalendarMetaParams = {
        calendarId: calendarMenuItemList[0].calendarId,
        month: initialCalendar.month,
        year: initialCalendar.year,
      };
      setCalendarMetaParams(initalMetaParams);
      const initalSummeryCardParams: SummeryCardParams = {
        calendarId: calendarMenuItemList[0].calendarId,
        year: calendarMenuItemList[0].year.trim(),
      };
      setCalendarSummeryParams(initalSummeryCardParams);
    }
  }, [calendarMenuItemList]);

  // hook to fetch the calendar meta data
  useEffect(() => {
    if (calendarMetaParams != undefined) {
      getCalendarMetaData(calendarMetaParams).then((res: any) => {
        if (_.isArray(res.data) || !_.isEmpty(res.data)) {
          setCalendarMetaData(res.data);
        }
      });
    }
  }, [calendarMetaParams, specialDayAddedState]);

  // hook to fetch the calendar summery data
  useEffect(() => {
    if (calendarMetaParams != undefined) {
      getCalendarSummery(calendarSummeryParams).then((res: any) => {
        if (_.isArray(res.data) || !_.isEmpty(res.data)) {
          setCalendarSummeryData(res.data);
        }
      });
    }
  }, [calendarSummeryParams]);

  // function to add a speical daytype for each day
  const addSpeicalDayType = (dateTypeKey: number, selectedDate: any) => {
    const key = 'saving';

    const structuredSpeicalDayParams: SpeicalDayDataParams = {
      calendarId: calendarMetaParams.calendarId,
      date: selectedDate,
      dateTypeId: calendarDateTypes[dateTypeKey].id,
    };

    addSpecialDay(structuredSpeicalDayParams)
      .then((response: APIResponse) => {
        if (response.error) {
          setSpecialDayAddedState(false);
          message.error({
            content:
              response.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Failed to Save',
              }),
            key,
          });
          return;
        }
        
        setCalendarSummeryParams({
          year:calendarMetaParams.year,
          calendarId:calendarMetaParams.calendarId ,
        });
        setSpecialDayAddedState(specialDayAddedState ? false : true);
        message.success({
          content:
            response.message ??
            intl.formatMessage({
              id: 'successfullySaved',
              defaultMessage: 'Successfully Saved',
            }),
          key,
        });
      })
      .catch((error: APIResponse) => {
        setSpecialDayAddedState(false);
        let errorMessage;
        let errorMessageInfo;
        if (error.message.includes('.')) {
          let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        message.error({
          content: error.message ? (
            <>
              {errorMessage ?? error.message}
              <br />
              <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                {errorMessageInfo ?? ''}
              </span>
            </>
          ) : (
            intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Failed to Save',
            })
          ),

          key,
        });
      })
      .catch((error: APIResponse) => {
        setSpecialDayAddedState(false);
        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Failed to Save',
            }),
          key,
        });
      });
  };

  const handleEditCalendarNameOnChange = (editedName: string) => {
    const key = 'updating';
    const structuredCalendarNameParams: CalendarNameParams = {
      id: calendarMetaParams.calendarId,
      name: editedName,
    };

    if (calendarName.localeCompare(editedName)) {
      message.loading({
        content: intl.formatMessage({
          id: 'updating',
          defaultMessage: 'Updating...',
        }),
        key,
      });

      editCalendarName(structuredCalendarNameParams)
        .then((response: APIResponse) => {
          if (response.error) {
            message.error({
              content:
                response.message ??
                intl.formatMessage({
                  id: 'failedToUpdate',
                  defaultMessage: 'Failed to Update',
                }),
              key,
            });
            return;
          }

          setCalendarName(editedName);
          calendarMenuItemList?.map((menuData: any) => {
            if (menuData.calendarId === structuredCalendarNameParams.id) {
              return (menuData.menuItemName = editedName);
            }
          });
          setCalendarMenuItemList(calendarMenuItemList);
          setCalendarMetaParams({ ...calendarMetaParams, id: calendarMetaParams?.calendarId });

          message.success({
            content:
              response.message ??
              intl.formatMessage({
                id: 'successfullyUpdated',
                defaultMessage: 'Successfully Updated',
              }),
            key,
          });
        })
        .catch((error: APIResponse) => {
          message.error({
            content:
              error.message ??
              intl.formatMessage({
                id: 'failedToUpdate',
                defaultMessage: 'Failed to Update',
              }),
            key,
          });
        });
    }
  };

  const handleAddCalendarOnFinish = async (params: any, dayOptions: calanderDayOptions) => {
    const key = 'saving';
    const restrcutredCheckList = dayOptions.map((data: any, index: any) => {
      const matchingKeys = params.check.find((element: any) => element == data.label);
      if (matchingKeys == data.label) {
        data.isChecked = true;
      }
      return {
        date: data.label,
        isChecked: data.isChecked,
      };
    });
    const formData = {
      name: params.name,
      check: restrcutredCheckList,
    };

    message.loading({
      content: intl.formatMessage({
        id: 'saving',
        defaultMessage: 'Saving...',
      }),
      key,
    });
    setCalendarAddedState(false);
    addCalendar(formData)
      .then((response: APIResponse) => {
        if (response.error) {
          message.error({
            content:
              response.message ??
              intl.formatMessage({
                id: 'failedToSave',

                defaultMessage: 'Failed to Save',
              }),

            key,
          });
          return;
        }
        setAddModelVisibility(false);
        addFormReference.resetFields();
        setCalendarAddedState(true);
        setCalendarMetaParams({ ...calendarMetaParams, id: calendarMetaParams?.calendarId });

        message.success({
          content:
            response.message ??
            intl.formatMessage({
              id: 'successfullySaved',

              defaultMessage: 'Successfully Saved',
            }),

          key,
        });
        if (!_.isEmpty(response.data) || !_.isUndefined(response.data)) {
          const calendarItem = {
            key: calendarMenuItemList?.length,
            menuItemName: response.data[0].name,
            calendarId: response.data[0].id,
          };
          calendarMenuItemList?.push(calendarItem);
          setCalendarMenuItemList(calendarMenuItemList);
        }
      })
      .catch((error: APIResponse) => {
        if (!_.isUndefined(error)) {
          addFormReference.setFields([
            {
              name: 'name',
              errors: [error.message],
            },
          ]);
        }
        if (!error.data.isUnique) {
          message.error({
            content:
              error.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Failed to Save',
              }),

            key,
          });
        }
      });
  };

  return (
    <PageContainer>
      <Layout>
        <Access
          accessible={hasPermitted('work-calendar-read-write')}
          fallback={<PermissionDeniedPage />}
        >
          <div>
            <Row>
              <Col span={7}>
                <WorkCalanderMenu
                  title={
                    <div className={styles.calendarListContainer}>
                      <h3 className={styles.calendarListTitle}>
                        <FormattedMessage
                          id="work-calendar-calendar-list"
                          defaultMessage="Calendar List"
                        />
                      </h3>
                      <AddCalendarModel
                        trigger={
                          <Button
                            className={styles.addCalendarButton}
                            type="primary"
                            icon={<PlusOutlined />}
                          />
                        }
                        cardTitle={intl.formatMessage({
                          id: 'work-calendar-add-calendar',
                          defaultMessage: 'Add New Calendar',
                        })}
                        createCalanderFunction={handleAddCalendarOnFinish}
                        setModelVisiblity={setAddModelVisibility}
                        modelVisiblity={addModelVisiblity}
                        formRef={addFormReference}
                      />
                    </div>
                  }
                  menuWidth={470}
                  titleIcon={<></>}
                  titleKey={'sub1'}
                  deafultOpenKey={['sub1']}
                  defaultSelectedKeys={['0']}
                  menuOnClick={(data: any) => {
                    const menuItemsObject: MenuItemData = calendarMenuItemList[data.key];
                    if (_.isObject(menuItemsObject) && !_.isNull(menuItemsObject)) {
                      const metaPramasObject: CalendarMetaParams = {
                        calendarId: menuItemsObject.calendarId,
                        month: initialCalendar.month,
                        year: initialCalendar.year,
                      };
                      setCalendarName(menuItemsObject.menuItemName);
                      setCalendarMetaParams(metaPramasObject);
                      setCalendarSummeryParams({
                        year: metaPramasObject.year,
                        calendarId: metaPramasObject.calendarId,
                      });
                    }
                  }}
                  menuItemData={calendarMenuItemList}
                  selectedKeys={calendarMenuItemList ? '2' : '1'}
                />

                <SummeryCard
                  cardTitle={intl.formatMessage({
                    id: 'work-calendar-calendar-summary',
                    defaultMessage: 'Calendar Summary',
                  })}
                  summeryData={calendarSummeryData}
                />
              </Col>
              <Col span={17}>
                <SelectableCalendar
                  // calendarValue={setCalendarValue}
                  getSelectedDateType={addSpeicalDayType}
                  calendarDateTypes={calendarDateTypes}
                  calendarOnPanelChange={(dateObject: Moment, mode: any) => {
                    let currentMonth = dateObject.format('MMMM');
                    let currentYear = dateObject.format('YYYY');

                    setCalendarMetaParams({
                      ...calendarMetaParams,
                      calendarId: calendarMetaParams?.calendarId,
                      month: currentMonth,
                      year: currentYear,
                    });
                    setCalendarSummeryParams({ ...calendarSummeryParams, year: currentYear });
                  }}
                  getDateCellData={calendarMetaData}
                  calendarCustomHeader={(headingProps: any) => {
                    return (
                      <CalendarHeader
                        componentHeaderProps={headingProps}
                        commonProps={{
                          typographOnChange: handleEditCalendarNameOnChange,
                          calendarName: calendarName,
                          getMomentObject: (data: Moment) => {
                            const localeData = data.localeData();
                            const year = data.year();
                            const month = localeData.monthsShort(data);
                            initialCalendar = {
                              year,
                              month,
                            };
                          },
                        }}
                      />
                    );
                  }}
                />
              </Col>
            </Row>
          </div>
        </Access>
      </Layout>
    </PageContainer>
  );
};

export default WorkCalander;
