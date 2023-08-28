import React, { useEffect, useState } from 'react';
import {
  Col,
  Row,
  Form,
  Button
} from 'antd';
import { useIntl, useParams, history, useAccess, Access } from 'umi';
import TextArea from 'antd/lib/input/TextArea';
import request, { APIResponse } from '@/utils/request';
import { getModel, ModelType } from '@/services/model';
import { getEmployeeCurrentDetails } from '@/services/employee';

import { DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';
import moment from 'moment';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import ApprovalLevelDetails from './approvalLevelDetails';
import ReactHtmlParser from 'react-html-parser';
import { getFormTemplateInstanceInitialValues } from '@/utils/utils';
import TemplateFormInput from '@/components/TemplateFormInput';
import { ModalForm } from '@ant-design/pro-form';
import FormContent from './formContent';

type TemplatePreviewerProps = {
  layout: any;
  addFormVisible: any;
  setAddFormVisible: any;
  formDetail: any;
  onlyPreview: any;
};

const TemplatePreviewer: React.FC<TemplatePreviewerProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();

  const [addFormReference] = Form.useForm();
  const [questionsSet, setQuestionSet] = useState<any>([]);
  const [currentRecord, setCurrentRecord] = useState<any>();

  useEffect(() => {
    if (props.addFormVisible) {
      const intialValues = getFormTemplateInstanceInitialValues(props.layout);
      setCurrentRecord(intialValues);
    }
  }, [props.addFormVisible]);

  const closeModalForm = () => {
    props.setAddFormVisible(false);
  };

  const addViewProps = {
    title: (
      <>
        <Row>
          <Col span={23} style={{ fontSize: 32 }}>
            <Row style={{ color: '#ffffff', fontWeight: 'bold' }}>
              <span>
                {props.formDetail.formTitle ? props.formDetail.formTitle : 'Untitled Form'}
              </span>
            </Row>
            <Row>
              <span style={{ fontSize: 16, color: '#ffffff' }}>
                {props.formDetail.formDiscription
                  ? props.formDetail.formDiscription
                  : 'Form Description'}
              </span>
            </Row>
          </Col>
        </Row>
      </>
    ),
    // key: `add_${props.titleKey}`,
    visible: props.addFormVisible,
    onVisibleChange: props.setAddFormVisible,
    form: addFormReference,
    width: '60%',
    className: 'templatePreviewModal',
    // onValuesChange: setAddFormChangedValue,
    submitter: props.onlyPreview
      ? {
          render: (props, doms) => {
            return [
              <Button
                key="cancel"
                size="middle"
                onClick={() => {
                  closeModalForm();
                }}
              >
                Cancel
              </Button>,
            ];
          },
        }
      : {
          searchConfig: {
            submitText: intl.formatMessage({
              id: 'add',
              defaultMessage: 'Save',
            }),
            resetText: intl.formatMessage({
              id: 'cancel',
              defaultMessage: 'Cancel',
            }),
          },
        },
    onFinish: async () => {
      //   const key = 'saving';
      //   message.loading({
      //     content: intl.formatMessage({
      //       id: 'saving',
      //       defaultMessage: 'Saving...',
      //     }),
      //     key,
      //   });
      // return;

      console.log(currentRecord);

      //   await props
      //     .addFunction(convertTagString(currentRecord))
      //     .then((response: APIResponse) => {
      //       if (response.error) {
      //         message.error({
      //           content:
      //             response.message ??
      //             intl.formatMessage({
      //               id: 'failedToSave',
      //               defaultMessage: 'Cannot Save',
      //             }),
      //           key,
      //         });
      //         if (response.data && Object.keys(response.data).length !== 0) {
      //           for (const feildName in response.data) {
      //             const errors = response.data[feildName];
      //             addFormReference.setFields([
      //               {
      //                 name: feildName,
      //                 errors: errors,
      //               },
      //             ]);
      //           }
      //         }
      //         return;
      //       }

      //       message.success({
      //         content:
      //           response.message ??
      //           intl.formatMessage({
      //             id: 'successfullySaved',
      //             defaultMessage: 'Successfully Saved',
      //           }),
      //         key,
      //       });

      //       const fields =
      //         props.model && props.model.modelDataDefinition
      //           ? props.model.modelDataDefinition.fields
      //           : {};

      //       if (!_.isEmpty(fields)) {
      //         setupTableColumns(fields);
      //       }

      //       actionRef?.current?.reload();
      //       setAddFormVisible(false);
      //     })

      //     .catch((error: APIResponse) => {
      //       let errorMessage;
      //       let errorMessageInfo;
      //       if (error.message.includes('.')) {
      //         let errorMessageData = error.message.split('.');
      //         errorMessage = errorMessageData.slice(0, 1);
      //         errorMessageInfo = errorMessageData.slice(1).join('.');
      //       }
      //       console.log('sdsd');
      //       message.error({
      //         content: error.message ? (
      //           <>
      //             {errorMessage ?? error.message}
      //             <br />
      //             <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
      //               {errorMessageInfo ?? ''}
      //             </span>
      //           </>
      //         ) : (
      //           intl.formatMessage({
      //             id: 'failedToSave',
      //             defaultMessage: 'Cannot Save',
      //           })
      //         ),
      //         key,
      //       });
      //       if (error && Object.keys(error.data).length !== 0) {
      //         for (const feildName in error.data) {
      //           const errors = error.data[feildName];
      //           addFormReference.setFields([
      //             {
      //               name: feildName,
      //               errors: errors,
      //             },
      //           ]);
      //         }
      //       }
      //     });
    },
  };

  return (
    <>
      <Row>
        <ModalForm
          modalProps={{
            destroyOnClose: true,
          }}
          {...addViewProps}
        >
          <Row
            className="templateBuilderRender"
            style={{ width: '100%', marginLeft: 20, overflowY: 'auto', height: 650 }}
          >
            <FormContent
              content={props.layout}
              formReference={addFormReference}
              currentRecord={currentRecord}
              setCurrentRecord={setCurrentRecord}
            />
          </Row>
        </ModalForm>
      </Row>
    </>
  );
};

export default TemplatePreviewer;
