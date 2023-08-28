import React, { useState, useEffect, useRef } from 'react';
import ProForm, {
  ProFormRadio,
  ProFormSelect,
  ProFormText,
  ProFormUploadButton,
  DrawerForm,
  ModalForm,
  ProFormGroup,
  ProFormList,
  ProFormSwitch,
  ProFormDigit,
} from '@ant-design/pro-form';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Card,
  Form,
  Typography,
  Transfer,
  Spin,
  message,
  Divider,
  Space,
  Button,
  Input,
  Upload,
  Row,
  Col,
  Modal,
  Tooltip,
  Radio,
  Image,
  Skeleton,
} from 'antd';
import { useIntl, useParams, history, useAccess, Access } from 'umi';
import { Editor } from '@tinymce/tinymce-react';
import { apiKey, tokenizeEditorObj } from './editorHelper';
import {
  updateFormTemplates,
  getAllFormTemplates,
  getFormTemplate,
  addFormTemplates,
  updateFormTemplateStatus,
} from '@/services/template';
import { getModel, Models } from '@/services/model';
import { genarateEmptyValuesObject } from '@/utils/utils';
import { getEmployeeList, getManagerList } from '@/services/dropdown';
import { getAllLocations } from '@/services/location';
import { queryCurrent } from '@/services/user';
import _, { forEach, min } from 'lodash';
import PermissionDeniedPage from './../403';
import { getAllNoticeCategory, addNoticeCategory } from '@/services/noticeCategory';
import { EyeOutlined, PlusOutlined } from '@ant-design/icons';
import { UploadOutlined, SettingOutlined } from '@ant-design/icons';
import { getBase64 } from '@/utils/fileStore';
import {
  CloseOutlined,
  ExclamationCircleOutlined,
  EditOutlined,
  DeleteOutlined,
} from '@ant-design/icons';
import SectionAddIcon from '../../assets/templateBuilder/sectionAdd.svg';
import QuestionAddIcon from '../../assets/templateBuilder/questionAdd.svg';
import { FormattedMessage } from 'react-intl';
import AddEditFormDetailIcon from '../../assets/templateBuilder/add-edit-form-detail.svg';
import EditIcon from '../../assets/templateBuilder/Icon-edit.svg';
import ReactHtmlParser from 'react-html-parser';
import listTableList from 'mock/listTableList';
import TemplatePreviewer from './templatePreviewModal';

const { confirm } = Modal;

export type FormTemplateRouteParams = {
  id: string;
};

const TemplateBuilderForm: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const editorRef = useRef(null);
  const [question, setQuestion] = useState<string>('');
  const { id } = useParams<FormTemplateRouteParams>();
  const { hasPermitted, hasAnyPermission } = access;
  const { Text } = Typography;
  const [form] = Form.useForm();
  const [tinyKey, setTinyKey] = useState<number>(1);
  const [isEditForm, setIsEditForm] = useState(false);
  const [isQuestionRequired, setIsQuestionRequired] = useState(false);
  const [loading, setLoading] = useState(false);
  const [formLoading, setFormLoading] = useState(false);
  const [initializing, setInitializing] = useState(false);
  const [selectedAnswerType, setSelectedAnswerType] = useState(null);
  const [selectedWeightedValue, setSelectedWeightedValue] = useState(null);
  const [refreshing, setRefresing] = useState(false);
  const [layout, setLayout] = useState([]);
  const [editableSection, setEditableSection] = useState({});
  const [editableQuestion, setEditableQuestion] = useState(null);
  const [formDetail, setFormDetail] = useState({});
  const [dataList, setDataList] = useState([]);
  const [sectionSideBarState, setSectionSideBarState] = useState({});
  const [questionSideBarState, setQuestionSideBarState] = useState({});
  const [isAddSectionModalVisible, setIsAddSectionModalVisible] = useState(false);
  const [isAddFormDetailsModalVisible, setIsAddFormDetailsModalVisible] = useState(false);
  const [isFormPreviewModalVisible, setIsFormPreviewModalVisible] = useState(false);
  const [isEditSectionTitleModalVisible, setIsEditSectionTitleModalVisible] = useState(false);
  const [isQuestionAddEditModalVisible, setIsQuestionAddEditModalVisible] = useState(false);
  const [linearLowerLimit, setLinearLowerLimit] = useState(1);
  const [linearUpperLimit, setLinearUpperLimit] = useState(5);
  const [linearLowerLimitLabel, setLinearLowerLimitLabel] = useState(null);
  const [linearUpperLimitLabel, setLinearUpperLimitLabel] = useState(null);
  const [formType, setFormType] = useState('FEEDBACK');
  const [formStatus, setFormStatus] = useState('Unpublished');
  const [addedRows, setAddedRows] = useState([{ label: 'Row 1' }]);
  const [addedColumns, setAddedColumns] = useState([{ label: 'Column 1' }]);
  const [addedOptions, setAddedOptions] = useState([{ label: 'Option 1' }]);

  const [notice, setNotice] = useState({});

  useEffect(() => {
    setFormDetail({
      formTilte: null,
      formDiscription: null,
      formContent: [],
    });

    const data = [];
    for (let i = 1, len = 7; i < len; i++) {
      data.push({
        title: `rows${i}`,
      });
    }

    setDataList(data);
  }, []);

  const [editorInit, setEditorInit] = useState<EditorProps>({
    ...tokenizeEditorObj,
    setup: function (editor) {
      editor.ui.registry.addButton('tokens', {
        text: 'Tokens',
        onAction: function () {
          //   setIsTokenModalVisible(false);
        },
      });
    },
  });

  useEffect(() => {
    if (id) {
      setIsEditForm(true);
      fetchTemplateDetails();
    } else {
      setIsEditForm(false);
    }
  }, [id]);

  const fetchTemplateDetails = async () => {
    const response = await getFormTemplate(id);

    let name = response.data?.name;
    let status = response.data?.status;
    let type = response.data?.type;
    let content = response.data.formContent ? JSON.parse(response.data.formContent) : {};

    form.setFieldsValue({
      name: name,
      type: type,
      status: status,
    });

    //set formDetails
    let formDataSet = {
      formTitle: content.formTitle ? content.formTitle : null,
      formDiscription: content.formDiscription ? content.formDiscription : null,
      formContent: [],
    };

    setFormDetail(formDataSet);
    setFormStatus(status);
    setFormType(type);

    await processSectionQuestionSideBarState(content.formContent);
    await setLayout(content.formContent);

    // console.log(content.formContent);
  };

  const answerTypes = [
    {
      value: 'text',
      label: 'Short Answer',
    },
    {
      value: 'textArea',
      label: 'Paragraph',
    },
    {
      value: 'radioGroup',
      label: 'Multiple Choice',
    },
    {
      value: 'checkBoxesGroup',
      label: 'Checkboxes',
    },
    {
      value: 'enum',
      label: 'DropDown',
    },
    {
      value: 'linearScale',
      label: 'Linear Scale',
    },
    {
      value: 'multipleChoiceGrid',
      label: 'Multiple Choice Grid',
    },
    {
      value: 'checkBoxGrid',
      label: 'CheckBox Grid',
    },
    {
      value: 'date',
      label: 'Date',
    },
    {
      value: 'time',
      label: 'Time',
    },
  ];

  const onFinish = async (formData: any) => {
    try {
      let content = [...layout];
      let totoalMultiChoiceWeightedValue = 0;
      let hasMultipleChoiceQuestions = false;

      content.forEach((sec) => {
        sec.questions.forEach((ques) => {
            if (ques.answerType == 'radioGroup') {
                hasMultipleChoiceQuestions = true;
                totoalMultiChoiceWeightedValue += ques.questionWeightedValue
            }
        });
      });

      if (formData.type == 'EVALUATION' && hasMultipleChoiceQuestions) {
          if (totoalMultiChoiceWeightedValue != 100) {
            message.error(intl.formatMessage({
                id: 'totalWeightedValueNotMatch',
                defaultMessage: 'Total Weighted value of multiple choice answer type questions should be equal to 100 in evaluation type form template',
              }));
            return;
          }
      }

      let formDataSet = {
        formTitle: formDetail.formTitle ? formDetail.formTitle : null,
        formDiscription: formDetail.formDiscription ? formDetail.formDiscription : null,
        formContent: content,
      };

      formData.formContent = JSON.stringify(formDataSet);

      if (!isEditForm) {
        formData.status = 'Unpublished';
      }

      const response = isEditForm
        ? await updateFormTemplates(id, formData)
        : await addFormTemplates(formData);

      message.success(response.message);
      history.push(`/settings/template-builder`);
      
    } catch (error) {
      message.error(error.message);
      if (error && Object.keys(error.data).length !== 0) {
        for (const feildName in error.data) {
          const errors = error.data[feildName];
          form.setFields([
            {
              name: feildName,
              errors: errors,
            },
          ]);
        }
      }
    }
  };

  const changeLoadingState = () => {
    setLoading(false);
  };

  const changeFormLoadingState = () => {
    setFormLoading(false);
  };

  const addSection = async (data: { key: string; title: string; addAfter?: string }) => {
    try {
      let newTabs = [...layout];

      let randNum = Math.floor(Math.random() * 1000 + 1);
      let secKey = 'section-' + randNum;
      const newSection = {
        key: secKey,
        defaultLabel: data.title,
        labelKey: _.camelCase(data.title),
        questions: [],
        closable: true,
        relateQuestionsSideBarStates: {},
      };

      if (data.addAfter) {
        const sectionIndex = newTabs.findIndex(
          (section) =>
            typeof section === 'object' && section != null && section.key === data.addAfter,
        );
        newTabs.splice(sectionIndex + 1, 0, newSection);
      } else {
        newTabs = [];
        newTabs.push(newSection);
      }

      let processedTabs = {};
      newTabs.forEach((tab) => {
        if (tab.key == secKey) {
          processedTabs[tab.key] = true;
        } else {
          processedTabs[tab.key] = false;
        }
      });

      setSectionSideBarState(processedTabs);
      setLayout(newTabs);
      setIsAddSectionModalVisible(false);
    } catch (error) {
      console.log(error);
    }
  };

  const changeSectionSideBarState = (secKey) => {
    let processedTabs = {};
    let newTabs = [...layout];
    newTabs.forEach((tab) => {
      if (tab.key == secKey) {
        processedTabs[tab.key] = true;
      } else {
        processedTabs[tab.key] = false;
      }

      setSectionSideBarState(processedTabs);
    });
  };

  const changeSectionQuestionSideBarState = (section, questionKey) => {
    let processedTabs = {};
    let secKey = section.key;
    let newTabs = [...layout];
    let sectionWiseQuestionSideBarStates = { ...questionSideBarState };
    let qusSideBarState = {};

    section.questions.forEach((changeQuest) => {
      qusSideBarState[changeQuest.questionKey] =
        changeQuest.questionKey == questionKey ? true : false;
    });

    sectionWiseQuestionSideBarStates[secKey] = qusSideBarState;

    setQuestionSideBarState(sectionWiseQuestionSideBarStates);
  };

  const processSectionQuestionSideBarState = (layouts) => {
    let sectionWiseQuestionSideBarStates = {};

    layouts.forEach((section) => {
      let secKey = section.key;
      let qusSideBarState = {};

      section.questions.forEach((changeQuest, quesIndex) => {
        qusSideBarState[changeQuest.questionKey] = quesIndex == 0 ? true : false;
      });

      sectionWiseQuestionSideBarStates[secKey] = qusSideBarState;
    });

    setQuestionSideBarState(sectionWiseQuestionSideBarStates);
  };

  const addFormDetails = async (data: { formTitle: string; formDiscription: string }) => {
    try {
      let exsistFormDetail = { ...formDetail };
      exsistFormDetail.formTitle = data.formTitle;
      exsistFormDetail.formDiscription = data.formDiscription;
      setFormDetail(exsistFormDetail);
      setIsAddFormDetailsModalVisible(false);
    } catch (error) {
      console.log(error);
    }
  };

  const updateSectionTitle = async (data: { sectionTitle: string }) => {
    try {
      let newTabs = [...layout];
      let processedTabs = [];

      newTabs.forEach((tab) => {
        if (tab.key == editableSection.key) {
          (tab.defaultLabel = data.sectionTitle), (tab.labelKey = _.camelCase(data.sectionTitle));
        }
        processedTabs.push(tab);
      });

      setLayout(processedTabs);
      setIsEditSectionTitleModalVisible(false);
    } catch (error) {
      console.log(error);
    }
  };

  const resetSectionWiseQuestionOrder = async (sectionKey, newTabs) => {
    try {
      const sectionIndex = newTabs.findIndex(
        (section) => typeof section === 'object' && section != null && section.key === sectionKey,
      );

      if (sectionIndex < 0) return;

      let newQuestionList = [];

      newTabs[sectionIndex]['questions'].forEach((question, index) => {
        let qKey = 'Q' + (index + 1);
        question.questionKey = qKey;
        (question.name = sectionKey + ':' + qKey), newQuestionList.push(question);
      });

      setLayout(newTabs);
    } catch (error) {
      console.log(error);
    }
  };

  const addUpdateQuestion = async (data: {
    answerType: string;
    addQuestionAfter?: string;
    options?: any;
    linearLowerLimit?: any;
    linearUpperLimit?: any;
    linearLowerLimitLabel?: any;
    linearUpperLimitLabel?: any;
    rowsList?: any;
    columnsList?: any;
    isRequired?: any;
    questionWeightedValue?: any;
  }) => {
    try {
      const answerTypeIndex = answerTypes.findIndex((item) => data['answerType'] == item.value);
    
      if (editableQuestion == null) {
        let questionKey = 'Q' + (editableSection.questions.length + 1);

        let newQuestion = {
          questionKey: questionKey,
          name: editableSection.key + ':' + questionKey,
          questionString: editorRef.current?.getContent(),
          answerType: data['answerType'] ? data['answerType'] : null,
          answerTypeLabel: data['answerType'] ? answerTypes[answerTypeIndex].label : null,
          answerDetails: {},
          questionWeightedValue : data['questionWeightedValue'] ? data['questionWeightedValue'] : null
        };

        let answerDetailObj = {};
        answerDetailObj.isRequired = data.isRequired;
        switch (data['answerType']) {
          case 'checkBoxesGroup':
          case 'radioGroup':
          case 'enum':
            let processedOptions = [];

            if (formType == 'EVALUATION' && data['answerType'] == 'radioGroup') {
              processedOptions = data.options;
            } else {
              data.options.forEach((opt) => {
                opt.value = opt.label;
                processedOptions.push(opt);
              });
            }

            console.log(processedOptions);

            answerDetailObj.options = processedOptions;
            break;
          case 'linearScale':
            answerDetailObj.linearLowerLimit = data.linearLowerLimit;
            answerDetailObj.linearUpperLimit = data.linearUpperLimit;
            answerDetailObj.linearLowerLimitLabel = data.linearLowerLimitLabel
              ? data.linearLowerLimitLabel
              : null;
            answerDetailObj.linearUpperLimitLabel = data.linearUpperLimitLabel
              ? data.linearUpperLimitLabel
              : null;

            let optionArr = [];
            for (let i = data.linearLowerLimit; i <= data.linearUpperLimit; i++) {
              let tempObj = {
                label: i.toString(),
                value: i.toString(),
              };
              optionArr.push(tempObj);
            }

            answerDetailObj.linearScaleOptionArr = optionArr;
            break;
          case 'multipleChoiceGrid':
          case 'checkBoxGrid':
            answerDetailObj.columnsList = data.columnsList;
            answerDetailObj.rowsList = data.rowsList;

            let commonOptionArray = [];
            let headerList = [''];
            let subRadioGroupData = [];

            data.columnsList.forEach((column) => {
              let tempOptObj = {
                label: column.label,
                value: column.label,
              };

              commonOptionArray.push(tempOptObj);
              headerList.push(column.label);
            });

            //create data rows
            data.rowsList.forEach((row, rowIndex) => {
              let tempDataObj = {
                options: commonOptionArray,
                label: row.label,
                key: editableSection.key + ':' + questionKey + ':sub_q:' + (rowIndex + 1),
              };

              subRadioGroupData.push(tempDataObj);
            });

            answerDetailObj.headerList = headerList;
            answerDetailObj.subRadioGroupData = subRadioGroupData;

            break;

          default:
            break;
        }

        newQuestion.answerDetails = answerDetailObj;

        let newTabs = [...layout];
        let sectionWiseQuestionSideBarStates = { ...questionSideBarState };
        let processedTabs = [];

        newTabs.forEach((tab) => {
          if (tab.key == editableSection.key) {
            tab.questions.push(newQuestion);
            tab.relateQuestionsSideBarStates = [];

            let qusSideBarState = {};
            tab.questions.forEach((changeQuest) => {
              _;
              qusSideBarState[changeQuest.questionKey] =
                changeQuest.questionKey == questionKey ? true : false;
            });

            sectionWiseQuestionSideBarStates[tab.key] = qusSideBarState;
          }
          processedTabs.push(tab);
        });

        const sectionIndex = processedTabs.findIndex(
          (section) =>
            typeof section === 'object' && section != null && section.key === editableSection.key,
        );

        if (sectionIndex < 0) return;

        const questionIndex = processedTabs[sectionIndex]['questions'].findIndex(
          (question) =>
            typeof question === 'object' &&
            question != null &&
            question.questionKey === questionKey,
        );

        if (questionIndex < 0) return;

        if (data.addQuestionAfter) {
          processedTabs[sectionIndex]['questions'].splice(questionIndex, 1);
          const selectedQuestionIndex = processedTabs[sectionIndex]['questions'].findIndex(
            (question) =>
              typeof question === 'object' &&
              question != null &&
              question.questionKey === data.addQuestionAfter,
          );
          processedTabs[sectionIndex]['questions'].splice(
            selectedQuestionIndex + 1,
            0,
            newQuestion,
          );
        }

        setQuestionSideBarState(sectionWiseQuestionSideBarStates);
        resetSectionWiseQuestionOrder(editableSection.key, processedTabs);
        setIsQuestionAddEditModalVisible(false);
      } else {
        let questionKey = editableQuestion.questionKey;
        let newTabs = [...layout];
        let exsistQuestion = { ...editableQuestion };
        let sectionKey = editableSection.key;

        exsistQuestion.questionString = editorRef.current?.getContent();
        exsistQuestion.answerType = data['answerType'] ? data['answerType'] : null;
        exsistQuestion.answerTypeLabel = data['answerType']
          ? answerTypes[answerTypeIndex].label
          : null;
        exsistQuestion.questionWeightedValue = data['questionWeightedValue'] ? data['questionWeightedValue'] : null;

        let answerDetailObj = {};
        answerDetailObj.isRequired = data.isRequired;
        switch (data['answerType']) {
          case 'checkBoxesGroup':
          case 'radioGroup':
          case 'enum':
            let processedOptions = [];
            if (formType == 'EVALUATION' && data['answerType'] == 'radioGroup') {
              processedOptions = data.options;
            } else {
              data.options.forEach((opt) => {
                opt.value = opt.label;
                processedOptions.push(opt);
              });
            }
            answerDetailObj.options = processedOptions;
            break;
          case 'linearScale':
            answerDetailObj.linearLowerLimit = data.linearLowerLimit;
            answerDetailObj.linearUpperLimit = data.linearUpperLimit;
            answerDetailObj.linearLowerLimitLabel = data.linearLowerLimitLabel
              ? data.linearLowerLimitLabel
              : null;
            answerDetailObj.linearUpperLimitLabel = data.linearUpperLimitLabel
              ? data.linearUpperLimitLabel
              : null;

            let optionArr = [];
            for (let i = data.linearLowerLimit; i <= data.linearUpperLimit; i++) {
              let tempObj = {
                label: i.toString(),
                value: i.toString(),
              };
              optionArr.push(tempObj);
            }

            answerDetailObj.linearScaleOptionArr = optionArr;
            break;
          case 'multipleChoiceGrid':
          case 'checkBoxGrid':
            answerDetailObj.columnsList = data.columnsList;
            answerDetailObj.rowsList = data.rowsList;

            let commonOptionArray = [];
            let headerList = [''];
            let subRadioGroupData = [];

            data.columnsList.forEach((column) => {
              let tempOptObj = {
                label: column.label,
                value: column.label,
              };

              commonOptionArray.push(tempOptObj);
              headerList.push(column.label);
            });

            //create data rows
            data.rowsList.forEach((row, rowIndex) => {
              let tempDataObj = {
                options: commonOptionArray,
                label: row.label,
                key: editableSection.key + ':' + questionKey + ':sub_q:' + (rowIndex + 1),
              };

              subRadioGroupData.push(tempDataObj);
            });

            answerDetailObj.headerList = headerList;
            answerDetailObj.subRadioGroupData = subRadioGroupData;
            break;

          default:
            break;
        }

        exsistQuestion.answerDetails = answerDetailObj;

        const sectionIndex = newTabs.findIndex(
          (section) => typeof section === 'object' && section != null && section.key === sectionKey,
        );

        if (sectionIndex < 0) return;

        const questionIndex = newTabs[sectionIndex]['questions'].findIndex(
          (question) =>
            typeof question === 'object' &&
            question != null &&
            question.questionKey === questionKey,
        );

        if (questionIndex < 0) return;

        if (data.addQuestionAfter) {
          newTabs[sectionIndex]['questions'].splice(questionIndex, 1);
          const selectedQuestionIndex = newTabs[sectionIndex]['questions'].findIndex(
            (question) =>
              typeof question === 'object' &&
              question != null &&
              question.questionKey === data.addQuestionAfter,
          );
          newTabs[sectionIndex]['questions'].splice(selectedQuestionIndex + 1, 0, exsistQuestion);
        } else {
          newTabs[sectionIndex]['questions'][questionIndex] = exsistQuestion;
        }

        resetSectionWiseQuestionOrder(sectionKey, newTabs);
        setIsQuestionAddEditModalVisible(false);
      }
    } catch (error) {
      console.log(error);
    }
  };

  const removeSection = (sectionKey: string) => {
    let newTabs = [...layout];

    const sectionIndex = newTabs.findIndex(
      (section) => typeof section === 'object' && section != null && section.key === sectionKey,
    );

    if (sectionIndex < 0) return;

    newTabs.splice(sectionIndex, 1);

    setLayout(newTabs);
  };

  const getQuestionListForDropDown = async () => {
    let questionList = [];
    editableSection.questions.map((question) => {
      if (editableQuestion != null) {
        if (question.questionKey != editableQuestion.questionKey) {
          let option = {
            value: question.questionKey,
            label: question.questionKey,
          };
          questionList.push(option);
        }
      } else {
        let option = {
          value: question.questionKey,
          label: question.questionKey,
        };
        questionList.push(option);
      }
    });

    return questionList;
  };

  const removeQuestion = (sectionKey: string, questionKey) => {
    let newTabs = [...layout];

    const sectionIndex = newTabs.findIndex(
      (section) => typeof section === 'object' && section != null && section.key === sectionKey,
    );

    if (sectionIndex < 0) return;

    const questionIndex = newTabs[sectionIndex]['questions'].findIndex(
      (question) =>
        typeof question === 'object' && question != null && question.questionKey === questionKey,
    );

    if (questionIndex < 0) return;

    let questionsList = newTabs[sectionIndex].questions;

    questionsList.splice(questionIndex, 1);

    newTabs[sectionIndex].questions = questionsList;

    resetSectionWiseQuestionOrder(sectionKey, newTabs);
    // console.log(processedSecions);
    // setLayout(newTabs);
  };

  return (
    <Access
      accessible={
        hasAnyPermission(['template-builder'])
      }
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer>
        <Card style={{ backgroundColor: '#f0f2f5' }}>
          <Spin spinning={formLoading}>
            <ProForm
              initialValues={notice}
              form={form}
              submitter={false}
              //   onValuesChange={() => setNotice(form.getFieldsValue())}
              onFinish={onFinish}
            >
              <Row>
                <Col span={6} style={{ marginRight: 15 }}>
                  <ProFormText
                    name="name"
                    label={intl.formatMessage({
                      id: 'template.name',
                      defaultMessage: 'Name',
                    })}
                    rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'template.name.required',
                          defaultMessage: 'Required.',
                        }),
                      },
                      {
                        max: 100,
                        message: intl.formatMessage({
                          id: 'name',
                          defaultMessage: 'Maximum length is 100 characters.',
                        }),
                      },
                    ]}
                  />
                </Col>
                <Col span={6} style={{marginTop: 28, marginLeft: 20}}>
                  {isEditForm ? (
                    <Form.Item
                      name="status"
                      label= ""
                    >
                      <Radio.Group
                        onChange={async (value) => {
                          setFormLoading(true);
                          setFormStatus(value.target.value);
                          let data = {
                            id: id,
                            status: value.target.value,
                          };
                          await updateFormTemplateStatus(data);
                          await fetchTemplateDetails(id);

                          setTimeout(changeFormLoadingState, 500);
                        }}
                        buttonStyle="solid"
                      >
                        <Radio.Button value="Published">Published</Radio.Button>
                        <Radio.Button value="Unpublished">Unpublished</Radio.Button>
                      </Radio.Group>
                    </Form.Item>
                  ) : (
                    <></>
                  )}
                </Col>
              </Row>
              <Row className="templateBuilderRadio">
                <Col span={24}>
                  <ProFormRadio.Group
                    name="type"
                    label={intl.formatMessage({
                      id: 'TEMPLATE_TYPE',
                      defaultMessage: 'Template Type',
                    })}
                    fieldProps={{
                      onChange: async (value) => {
                        setFormType(value.target.value);
                      },
                    }}
                    initialValue={formType}
                    options={[
                      {
                        label: `${intl.formatMessage({
                          id: 'FEEDBACK_TEMPLATES',
                          defaultMessage: 'Feedback',
                        })}`,
                        value: 'FEEDBACK',
                      },
                      {
                        label: `${intl.formatMessage({
                          id: 'EVALUATION_TEMPLATES',
                          defaultMessage: 'Evaluation',
                        })}`,
                        value: 'EVALUATION',
                      },
                    ]}
                    rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'topic',
                          defaultMessage: 'Required.',
                        }),
                      },
                    ]}
                  />
                </Col>
              </Row>
              <Row style={{ marginBottom: 20 }}>
                <Col span={16}>
                  <Card
                    title={
                      <>
                        <Row>
                          <Col span={23} style={{ fontSize: 32 }}>
                            <Row>
                              <span>
                                {formDetail.formTitle ? formDetail.formTitle : 'Untitled Form'}
                              </span>
                            </Row>
                            <Row>
                              <span style={{ fontSize: 16, color: '#626D6C' }}>
                                {formDetail.formDiscription
                                  ? formDetail.formDiscription
                                  : 'Form Description'}
                              </span>
                            </Row>
                          </Col>
                        </Row>
                      </>
                    }
                  >
                    <>
                      {layout.map((section) => (
                        <Row>
                          <Col span={23}>
                            <Card
                              key={section.key}
                              title={intl.formatMessage({
                                id: section.labelKey,
                                defaultMessage: section.defaultLabel,
                              })}
                              onClick={() => {
                                changeSectionSideBarState(section.key);
                              }}
                              style={{ marginBottom: 40 }}
                            >
                              {
                                //    section.questions.length
                                section.questions.length > 0 ? (
                                  <>
                                    {section.questions.map((question) => (
                                      <Row style={{ marginBottom: 15 }}>
                                        <Col span={22}>
                                          <div
                                            style={{
                                              border: '1px solid #e1dddd',
                                              height: 100,
                                              width: '100%',
                                              borderRadius: 8,
                                            }}
                                            onClick={() => {
                                              changeSectionQuestionSideBarState(
                                                section,
                                                question.questionKey,
                                              );
                                            }}
                                          >
                                            <Row
                                              style={{
                                                width: '100%',
                                                marginTop: 15,
                                                marginLeft: 15,
                                              }}
                                            >
                                              <Col span={24}>
                                                <Row>
                                                  <span style={{ marginRight: 15 }}>
                                                    {question.questionKey + ':'}
                                                  </span>
                                                  {ReactHtmlParser(question.questionString)}
                                                </Row>
                                              </Col>
                                            </Row>
                                            <Row
                                              style={{
                                                width: '100%',
                                                marginTop: 15,
                                                marginLeft: 15,
                                              }}
                                            >
                                              <Col span={24}>
                                                <Row>
                                                  <span style={{ marginRight: 10 }}>
                                                    {'Answer Type' + ':'}
                                                  </span>{' '}
                                                  {question.answerType
                                                    ? question.answerTypeLabel
                                                    : '-'}
                                                </Row>
                                              </Col>
                                            </Row>
                                          </div>
                                        </Col>

                                        {questionSideBarState[section.key][question.questionKey] && formStatus == 'Unpublished' ? (
                                          <Col span={1}>
                                            <div
                                              style={{
                                                marginLeft: 10,
                                                height: 'auto',
                                                width: 50,
                                                border: '1px solid #e1dddd',
                                                backgroundColor: '#ffffff',
                                                borderRadius: 16,
                                              }}
                                            >
                                              <Row style={{ marginTop: 15, marginBottom: 5 }}>
                                                <Tooltip
                                                  key="question-edit-tool-tip"
                                                  placement="right"
                                                  title="Edit Question"
                                                >
                                                  <a
                                                    key="question-edit-btn"
                                                    onClick={() => {
                                                      setEditableQuestion(question);
                                                      setEditableSection(section);
                                                      setQuestion(question.questionString);
                                                      setSelectedAnswerType(question.answerType);
                                                      setIsQuestionRequired(
                                                        question.answerDetails.isRequired,
                                                      );
                                                      switch (question.answerType) {
                                                        case 'checkBoxesGroup':
                                                        case 'radioGroup':
                                                        case 'enum':
                                                          setAddedOptions(
                                                            question.answerDetails.options,
                                                          );
                                                          if (question.answerType == 'radioGroup' && formType == 'EVALUATION') {
                                                            setSelectedWeightedValue(question.questionWeightedValue);
                                                          } 

                                                          break;
                                                        case 'linearScale':
                                                          setLinearLowerLimit(
                                                            question.answerDetails.linearLowerLimit,
                                                          );
                                                          setLinearUpperLimit(
                                                            question.answerDetails.linearUpperLimit,
                                                          );
                                                          setLinearLowerLimitLabel(
                                                            question.answerDetails
                                                              .linearLowerLimitLabel,
                                                          );
                                                          setLinearUpperLimitLabel(
                                                            question.answerDetails
                                                              .linearUpperLimitLabel,
                                                          );
                                                          break;
                                                        case 'multipleChoiceGrid':
                                                        case 'checkBoxGrid':
                                                          setAddedColumns(
                                                            question.answerDetails.columnsList,
                                                          );
                                                          setAddedRows(
                                                            question.answerDetails.rowsList,
                                                          );
                                                          break;

                                                        default:
                                                          break;
                                                      }

                                                      setIsQuestionAddEditModalVisible(true);
                                                    }}
                                                    style={{ marginLeft: 10, marginRight: 10 }}
                                                  >
                                                    <Image
                                                      style={{ width: 28, paddingLeft: 2 }}
                                                      src={EditIcon}
                                                      preview={false}
                                                    />
                                                  </a>
                                                </Tooltip>
                                              </Row>
                                              <Row style={{ marginBottom: 5 }}>
                                                <Tooltip
                                                  key="question-remove-tip"
                                                  placement="right"
                                                  title="Remove Question"
                                                >
                                                  <a
                                                    key="question-remove-btn"
                                                    onClick={() => {
                                                      confirm({
                                                        title: 'Are you sure remove this question?',
                                                        icon: <ExclamationCircleOutlined />,
                                                        okText: 'Remove',
                                                        okType: 'danger',
                                                        cancelText: 'No',
                                                        onOk() {
                                                          removeQuestion(
                                                            section.key,
                                                            question.questionKey,
                                                          );
                                                        },
                                                      });
                                                    }}
                                                    style={{ marginLeft: 9 }}
                                                  >
                                                    <DeleteOutlined
                                                      style={{ fontSize: 31, color: '#656c6b' }}
                                                    />
                                                  </a>
                                                </Tooltip>
                                              </Row>
                                            </div>
                                          </Col>
                                        ) : (
                                          <></>
                                        )}
                                      </Row>
                                    ))}
                                  </>
                                ) : (
                                  <></>
                                )
                              }
                            </Card>
                          </Col>
                          <Col span={1}>
                            {sectionSideBarState[section.key] && formStatus == 'Unpublished' ? (
                              <div style={{ height: 50, width: 50, marginRight: 10 }}>
                                <div
                                  style={{
                                    marginLeft: 10,
                                    height: 'auto',
                                    width: 50,
                                    border: '1px solid #e1dddd',
                                    backgroundColor: '#ffffff',
                                    borderRadius: 16,
                                  }}
                                >
                                  <Row style={{ marginTop: 15, marginBottom: 5 }}>
                                    <Tooltip
                                      key="add-question-tool-tip"
                                      placement="right"
                                      title="Add Question"
                                    >
                                      <a
                                        key="add-question-btn"
                                        onClick={() => {
                                          setEditableSection(section);
                                          setEditableQuestion(null);
                                          setIsQuestionRequired(false);
                                          setAddedOptions([{ label: 'Option 1' }]);
                                          setSelectedWeightedValue(null);
                                          setIsQuestionAddEditModalVisible(true);
                                          setLoading(true);
                                          setQuestion('');
                                          setSelectedAnswerType(null);
                                          setTimeout(changeLoadingState, 500);
                                        }}
                                        style={{ marginLeft: 10, marginRight: 10 }}
                                      >
                                        <Image src={QuestionAddIcon} preview={false} />
                                      </a>
                                    </Tooltip>
                                  </Row>
                                  <Row style={{ marginBottom: 5 }}>
                                    <Tooltip
                                      key="add-edit-form-detail-tip"
                                      placement="right"
                                      title="Edit Section Title"
                                    >
                                      <a
                                        key="add-section-btn"
                                        onClick={() => {
                                          setEditableSection(section);
                                          setIsEditSectionTitleModalVisible(true);
                                        }}
                                        style={{ marginLeft: 10, marginRight: 10 }}
                                      >
                                        <Image src={AddEditFormDetailIcon} preview={false} />
                                      </a>
                                    </Tooltip>
                                  </Row>
                                  <Row style={{ marginBottom: 5 }}>
                                    <Tooltip
                                      key="section-remove-tip"
                                      placement="right"
                                      title="Remove Section"
                                    >
                                      <a
                                        key="add-section-btn"
                                        onClick={() => {
                                          confirm({
                                            title: 'Are you sure remove this section?',
                                            icon: <ExclamationCircleOutlined />,
                                            okText: 'Remove',
                                            okType: 'danger',
                                            cancelText: 'No',
                                            onOk() {
                                              removeSection(section.key);
                                            },
                                          });
                                        }}
                                        style={{ marginLeft: 9 }}
                                      >
                                        <DeleteOutlined
                                          style={{ fontSize: 31, color: '#656c6b' }}
                                        />
                                      </a>
                                    </Tooltip>
                                  </Row>
                                </div>
                              </div>
                            ) : (
                              <></>
                            )}
                          </Col>
                        </Row>
                      ))}
                    </>
                  </Card>
                </Col>
                <Col span={1}>
                  <div
                    style={{
                      marginLeft: 10,
                      height: 'auto',
                      width: 50,
                      border: '1px solid #e1dddd',
                      backgroundColor: '#ffffff',
                      borderRadius: 16,
                    }}
                  >
                    {
                        formStatus == 'Unpublished' ? (
                            <>
                                <Row style={{ marginTop: 15, marginBottom: 5 }}>
                        
                                    <Tooltip key="add-section-tool-tip" placement="right" title="Add Section">
                                        <a
                                        key="add-section-btn"
                                        onClick={() => {
                                            setIsAddSectionModalVisible(true);
                                        }}
                                        style={{ marginLeft: 10, marginRight: 10 }}
                                        >
                                        <Image src={SectionAddIcon} preview={false} />
                                        </a>
                                    </Tooltip>
                                </Row>
                                <Row style={{ marginBottom: 5 }}>
                                <Tooltip
                                    key="add-edit-form-detail-tip"
                                    placement="right"
                                    title="Add Form Title And Description"
                                >
                                    <a
                                    key="add-section-btn"
                                    onClick={() => {
                                        setIsAddFormDetailsModalVisible(true);
                                    }}
                                    style={{ marginLeft: 10, marginRight: 10 }}
                                    >
                                    <Image src={AddEditFormDetailIcon} preview={false} />
                                    </a>
                                </Tooltip>
                                </Row>
                            </>
                        ) : (
                            <></>
                        )
                    }
                    <Row style={ formStatus == 'Published' ? {marginBottom: 5, marginTop: 10} : { marginBottom: 5 }}>
                      <Tooltip key="preview-form-tip" placement="right" title="Preview">
                        <a
                          key="preview-form-btn"
                          onClick={() => {
                            setIsFormPreviewModalVisible(true);
                          }}
                          style={{ marginLeft: 10, marginRight: 10 }}
                        >
                          <EyeOutlined style={{ fontSize: 28, color: '#656c6b' }} />
                        </a>
                      </Tooltip>
                    </Row>
                  </div>
                </Col>
              </Row>
              <Row>
                <Col span={16} style={{ textAlign: 'right' }}>
                  <Form.Item>
                    <Space>
                      <Button
                        htmlType="button"
                        onClick={() => {
                          history.push(`/settings/template-builder`);
                        }}
                      >
                        Back
                      </Button>
                      <Button type="primary" htmlType="submit">
                        Save
                      </Button>
                    </Space>
                  </Form.Item>
                </Col>
              </Row>
            </ProForm>
          </Spin>
        </Card>

        {/* Add Section Modal Form */}
        <ModalForm
          title="Add Section"
          visible={isAddSectionModalVisible}
          onFinish={addSection}
          onVisibleChange={setIsAddSectionModalVisible}
          modalProps={{
            destroyOnClose: true,
          }}
        >
          <ProFormText
            name="title"
            label="Section title"
            rules={[
              { required: true, message: 'Please enter new section title!' },
              () => ({
                validator(rule, value) {
                  if (value && layout.some((tab) => tab.title === value)) {
                    return Promise.reject(new Error('Section title must be unique.'));
                  }
                  return Promise.resolve();
                },
              }),
            ]}
          />
          {layout.length > 0 ? (
            <ProFormSelect
              name="addAfter"
              label="Add new section after"
              placeholder="Please select a section"
              rules={[{ required: true, message: 'Please select a section!' }]}
              request={async () => {
                return layout.map((section) => {
                  return {
                    value: section.key,
                    label: section.defaultLabel,
                  };
                });
              }}
            />
          ) : (
            <></>
          )}
        </ModalForm>

        {/* Add Or Edit Form Details Modal Form */}
        <ModalForm
          title="Form Details"
          visible={isAddFormDetailsModalVisible}
          onFinish={addFormDetails}
          onVisibleChange={setIsAddFormDetailsModalVisible}
          modalProps={{
            destroyOnClose: true,
          }}
        >
          <ProFormText
            name="formTitle"
            label="Form Title"
            initialValue={formDetail.formTitle ? formDetail.formTitle : null}
          />
          <ProFormText
            name="formDiscription"
            label="Form Discription"
            initialValue={formDetail.formDiscription ? formDetail.formDiscription : null}
          />
        </ModalForm>

        {/* Edit Section Title Modal Form */}
        <ModalForm
          title="Section Title"
          visible={isEditSectionTitleModalVisible}
          onFinish={updateSectionTitle}
          onVisibleChange={setIsEditSectionTitleModalVisible}
          modalProps={{
            destroyOnClose: true,
          }}
        >
          <ProFormText
            name="sectionTitle"
            label="Section Title"
            initialValue={editableSection.defaultLabel}
            rules={[{ required: true, message: 'Required' }]}
          />
        </ModalForm>

        {/* Add Edit Question For Section */}
        <ModalForm
          title="Add Question"
          visible={isQuestionAddEditModalVisible}
          onFinish={addUpdateQuestion}
          width={'50%'}
          onVisibleChange={setIsQuestionAddEditModalVisible}
          className={'questionModal'}
          modalProps={{
            destroyOnClose: true,
          }}
        >
          {!loading ? (
            <>
              <Row style={{ width: '100%', marginBottom: 15 }}>
                <Col span={24}>
                  <Row>
                    <Col style={{ paddingBottom: 8, color: '#626D6C' }}>
                      <FormattedMessage id="question" defaultMessage="Question" />
                      <span style={{ color: '#ff4d4f', paddingLeft: 5 }}>{'*'}</span>
                    </Col>
                  </Row>
                  <Row>
                    <Editor
                      apiKey={TINY_API_KEY}
                      key={tinyKey}
                      onInit={(evt, editor) => (editorRef.current = editor)}
                      initialValue={question}
                      init={editorInit}
                    />
                  </Row>
                </Col>
              </Row>
              <Row style={{ width: '100%' }}>
                <Col span={8} style={{ marginRight: 10 }}>
                  <ProFormSelect
                    name="answerType"
                    label="Answer Type"
                    placeholder="Please select an answer type"
                    rules={[{ required: true, message: 'Please select an answer type!' }]}
                    options={answerTypes}
                    initialValue={selectedAnswerType}
                    fieldProps={{
                      onChange: (value) => {
                        setSelectedAnswerType(value);
                      },
                    }}
                  />
                </Col>
                {selectedAnswerType == 'radioGroup' && formType == 'EVALUATION' ? (
                  <Col span={6} style={{ marginRight: 10 }}>
                    <ProFormDigit
                      rules={[{ required: true, message: 'Required' }]}
                      label="Question Weighted Value"
                      max={100}
                      min={0}
                      name="questionWeightedValue"
                      initialValue={selectedWeightedValue}
                      fieldProps={{
                        onChange: (value) => {
                        setSelectedWeightedValue(value)
                        },
                      }}
                      placeholder={'Option Value %'}
                    />
                  </Col>
                ) : (
                  <></>
                )}

                <Col span={9}>
                  {layout.length > 0 ? (
                    <ProFormSelect
                      name="addQuestionAfter"
                      label="Add Question after"
                      placeholder="Please select a question"
                      // rules={[{ required: true, message: 'Please select a section!' }]}
                      request={async () => {
                        return getQuestionListForDropDown();
                      }}
                    />
                  ) : (
                    <></>
                  )}
                </Col>
              </Row>
              {selectedAnswerType == 'radioGroup' ||
              selectedAnswerType == 'enum' ||
              selectedAnswerType == 'checkBoxesGroup' ? (
                <Row style={{ width: '100%' }}>
                  <Col span={12}>
                    <ProFormList
                      name="options"
                      label="Options"
                      creatorButtonProps={{
                        creatorButtonText: 'Add new option',
                      }}
                      deleteIconProps={{
                        tooltipText: 'Delete',
                      }}
                      copyIconProps={false}
                      initialValue={addedOptions}
                    >
                      <ProFormGroup key="group">
                        <Row style={{ width: '100%' }}>
                          <Col
                            span={
                              selectedAnswerType == 'radioGroup' && formType == 'EVALUATION'
                                ? 12
                                : 24
                            }
                            style={{ marginRight: 10 }}
                          >
                            <ProFormText
                              label="Label"
                              rules={[{ required: true, message: 'Required' }]}
                              name="label"
                              placeholder={'Option Label'}
                              label=""
                            />
                          </Col>
                          {selectedAnswerType == 'radioGroup' && formType == 'EVALUATION' ? (
                            <Col span={10}>
                              <ProFormDigit
                                rules={[{ required: true, message: 'Required' }]}
                                max={100}
                                min={0}
                                name="value"
                                label=""
                                placeholder={'Option Value %'}
                              />
                            </Col>
                          ) : (
                            <></>
                          )}
                        </Row>
                      </ProFormGroup>
                    </ProFormList>
                  </Col>
                </Row>
              ) : (
                <></>
              )}

              {selectedAnswerType == 'linearScale' ? (
                <Row style={{ width: '100%' }}>
                  <Col span={12}>
                    <Row style={{ width: '100%' }}>
                      <Col span={11}>
                        <ProFormSelect
                          name="linearLowerLimit"
                          rules={[{ required: true, message: 'Required' }]}
                          initialValue={linearLowerLimit}
                          fieldProps={{
                            onChange: (value) => {
                              setLinearLowerLimit(value);
                            },
                          }}
                          request={async () => {
                            return [
                              {
                                value: 0,
                                label: '0',
                              },
                              {
                                value: 1,
                                label: '1',
                              },
                            ];
                          }}
                        />
                      </Col>
                      <Col span={2} style={{ paddingLeft: 10, paddingTop: 5 }}>
                        {'to'}
                      </Col>
                      <Col span={11}>
                        <ProFormSelect
                          name="linearUpperLimit"
                          rules={[{ required: true, message: 'Required' }]}
                          initialValue={linearUpperLimit}
                          fieldProps={{
                            onChange: (value) => {
                              console.log(value);
                              setLinearUpperLimit(value);
                            },
                          }}
                          request={async () => {
                            return [
                              {
                                value: 2,
                                label: '2',
                              },
                              {
                                value: 3,
                                label: '3',
                              },
                              {
                                value: 4,
                                label: '4',
                              },
                              {
                                value: 5,
                                label: '5',
                              },
                              {
                                value: 6,
                                label: '6',
                              },
                              {
                                value: 7,
                                label: '1',
                              },
                              {
                                value: 8,
                                label: '8',
                              },
                              {
                                value: 9,
                                label: '9',
                              },
                              {
                                value: 10,
                                label: '10',
                              },
                            ];
                          }}
                        />
                      </Col>
                    </Row>
                    <Row style={{ width: '100%' }}>
                      <Col span={2} style={{ paddingLeft: 10, paddingTop: 5 }}>
                        {linearLowerLimit}
                      </Col>
                      <Col span={22}>
                        <ProFormText
                          initialValue={linearLowerLimitLabel}
                          name="linearLowerLimitLabel"
                          placeholder={'Label (optioal)'}
                          label=""
                        />
                      </Col>
                    </Row>
                    <Row style={{ width: '100%' }}>
                      <Col span={2} style={{ paddingLeft: 10, paddingTop: 5 }}>
                        {linearUpperLimit}
                      </Col>
                      <Col span={22}>
                        <ProFormText
                          initialValue={linearUpperLimitLabel}
                          name="linearUpperLimitLabel"
                          placeholder={'Label (optioal)'}
                          label=""
                        />
                      </Col>
                    </Row>
                  </Col>
                </Row>
              ) : (
                <></>
              )}

              {selectedAnswerType == 'multipleChoiceGrid' ||
              selectedAnswerType == 'checkBoxGrid' ? (
                <Row style={{ width: '100%' }}>
                  <Col span={6} style={{ marginRight: 20 }}>
                    <ProFormList
                      name="rowsList"
                      label="Rows"
                      creatorButtonProps={{
                        creatorButtonText: 'Add row',
                      }}
                      deleteIconProps={{
                        tooltipText: 'Delete',
                      }}
                      initialValue={addedRows}
                      rules={[
                        {
                          validator: async (_, value) => {
                            if (value && value.length > 0) {
                              return;
                            }
                            throw new Error('Required');
                          },
                        },
                      ]}
                      copyIconProps={false}
                    >
                      <ProFormGroup key="rowGroup">
                        <Row style={{ width: '100%' }}>
                          <Col span={24}>
                            <ProFormText
                              label="Value"
                              rules={[{ required: true, message: 'Required' }]}
                              name="label"
                              placeholder={'Option Value'}
                              label=""
                            />
                          </Col>
                        </Row>
                      </ProFormGroup>
                    </ProFormList>
                  </Col>
                  <Col span={6}>
                    <ProFormList
                      name="columnsList"
                      label="Columns"
                      deleteIconProps={{
                        tooltipText: 'Delete',
                      }}
                      creatorButtonProps={{
                        creatorButtonText: 'Add column',
                      }}
                      initialValue={addedColumns}
                      rules={[
                        {
                          validator: async (_, value) => {
                            if (value && value.length > 0) {
                              return;
                            }
                            throw new Error('Required');
                          },
                        },
                      ]}
                      copyIconProps={false}
                    >
                      <ProFormGroup key="columnGroup">
                        <Row style={{ width: '100%' }}>
                          <Col span={24}>
                            <ProFormText
                              label="Value"
                              rules={[{ required: true, message: 'Required' }]}
                              name="label"
                              placeholder={'Option Value'}
                              label=""
                            />
                          </Col>
                        </Row>
                      </ProFormGroup>
                    </ProFormList>
                  </Col>
                </Row>
              ) : (
                <></>
              )}
              <Row style={{ width: '100%' }}>
                <ProFormSwitch
                  fieldProps={{
                    onChange: (value) => {
                      setIsQuestionRequired(value);
                    },
                  }}
                  initialValue={isQuestionRequired}
                  label={
                    selectedAnswerType == 'multipleChoiceGrid' ||
                    selectedAnswerType == 'checkBoxGrid'
                      ? 'Require a response in each row'
                      : 'Required'
                  }
                  name="isRequired"
                />
              </Row>
            </>
          ) : (
            <>
              <Skeleton loading={loading} active avatar></Skeleton>
            </>
          )}
        </ModalForm>

        {/* Preview Created Template */}
        <TemplatePreviewer
          onlyPreview={true}
          addFormVisible={isFormPreviewModalVisible}
          setAddFormVisible={setIsFormPreviewModalVisible}
          formDetail={formDetail}
          layout={layout}
        ></TemplatePreviewer>
      </PageContainer>
    </Access>
  );
};

export default TemplateBuilderForm;
