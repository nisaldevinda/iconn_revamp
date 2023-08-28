import { ModelType } from '@/services/model';
import { parse } from 'querystring';
import { RelationshipType } from './model';

/* eslint no-useless-escape:0 import/prefer-default-export:0 */
const reg = /(((^https?:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+(?::\d+)?|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)$/;

export const isUrl = (path: string): boolean => reg.test(path);

export const isAntDesignPro = (): boolean => {
  if (ANT_DESIGN_PRO_ONLY_DO_NOT_USE_IN_YOUR_PRODUCTION === 'site') {
    return true;
  }
  return window.location.hostname === 'preview.pro.ant.design';
};

// For the official demo site, it is used to turn off features that are not needed in the real development environment
export const isAntDesignProOrDev = (): boolean => {
  const { NODE_ENV } = process.env;
  if (NODE_ENV === 'development') {
    return true;
  }
  return isAntDesignPro();
};

export const getPageQuery = () => parse(window.location.href.split('?')[1]);

export const modifyFilterParams = (params: any): object => {
  const keyNotRelatedToFilter = ['current', 'pageSize', 'sorter', 'filter'];

  let filters = { ...params };
  keyNotRelatedToFilter.forEach((key) => {
    delete filters[key];
  });

  for (const [key, value] of Object.entries(filters)) {
    filters[key] = 'eq:'.concat(value);
  }

  params = keyNotRelatedToFilter.reduce((result, key) => { result[key] = params[key]; return result; }, {});

  return { ...params, filters };
};

export const genarateEmptyValuesObject = (model: ModelType, fieldNamePrefix?: string): {} => {
  let responseObject = {};
  const fields = model && model.modelDataDefinition ? model.modelDataDefinition.fields : {};
  const relations = model && model.modelDataDefinition && model.modelDataDefinition.relations ? model.modelDataDefinition.relations : {};

  for (let fieldName in fields) {
    const field = fields[fieldName];
    const fieldType = field['type'];
    const relation = relations[fieldName];

    // skip if the field is computed
    // if (field.isComputedProperty)
    //   continue;

    // concat fieldname prefix if exist
    if (fieldNamePrefix)
      fieldName = fieldNamePrefix.concat(fieldName)

    switch (fieldType) {
      case 'model':
        switch (relation) {
          case RelationshipType.HAS_ONE:
            responseObject[fieldName.concat('Id')] = field.defaultValue ?? null;
            break;
          case RelationshipType.HAS_MANY_TO_MANY:
            responseObject[fieldName.concat('Ids')] = field.defaultValue ?? [];
            break;
          case RelationshipType.HAS_MANY:
            responseObject[fieldName] = [];

            if (field.isEffectiveDateConsiderable) {
              const currentFieldName = 'current' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Id';
              responseObject[currentFieldName] = null;
            }

            break;
          default:
            responseObject[fieldName] = field.defaultValue ?? null;
            break;
        }
        break;
      default:
        responseObject[fieldName] = field.defaultValue ?? null;
        break;
    }
  }

  return responseObject;
}

export const genarateEmptyValuesObjectsForTemplate = (fieldsList): {} => {
  let responseObject = {};
  
  const fields = fieldsList ? fieldsList : {};
  // const relations = model && model.modelDataDefinition && model.modelDataDefinition.relations ? model.modelDataDefinition.relations : {};

  for (let fieldIndex in fields) {
    const field = fields[fieldIndex].name;
    const fieldType = fields[fieldIndex].answerType;


    switch (fieldType) {
      case 'checkBoxesGroup':
        responseObject[field]  = [];
        break;
      case 'radioGroup':
      case 'enum':
        responseObject[field]  = null;
        break;
      case 'linearScale':
        responseObject[field]  = null;
        break;
      case 'multipleChoiceGrid':
      case 'checkBoxGrid':
        let dataArr = {};

        fields[fieldIndex].answerDetails.subRadioGroupData.forEach((data) => {
          dataArr[data.key] = (fieldType == 'checkBoxGrid') ? [] : null;
        })

        responseObject[field]  = dataArr;
        break;
      default:
        responseObject[field]  = null;
        break;
    }
  }

  return responseObject;
}

export const getFormTemplateInstanceInitialValues = (content: any) => {
  let questionList: any = [];
  content.forEach((sections: any) => {
    if (sections.questions.length > 0) {
      sections.questions.forEach((question: any) => {
        let qesObj = {
          name: question.name,
          answerDetails: question.answerDetails,
          answerType: question.answerType,
        };
        questionList.push(qesObj);
      });
    }
  });

  return genarateEmptyValuesObjectsForTemplate(questionList);
}

export const downloadBase64File = (contentType: string, base64Data: string, fileName: string) => {
  const a = document.createElement('a'); //Create <a>
  a.href = `data:${contentType};base64,${base64Data}`; //Image Base64 Goes here
  a.download = fileName;
  a.click();
}

export const humanReadableFileSize = (bytes: number, standard = false, depth = 1) => {
  const thresh = standard ? 1000 : 1024;

  if (Math.abs(bytes) < thresh) {
    return bytes + ' B';
  }

  const units = standard
    ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
    : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
  let unit = -1;
  const rounded = 10 ** depth;

  do {
    bytes /= thresh;
    ++unit;
  } while (Math.round(Math.abs(bytes) * rounded) / rounded >= thresh && unit < units.length - 1);

  return bytes.toFixed(depth) + ' ' + units[unit];
}

export const parseToFormValuesFromDbRecord = (model: ModelType, values: object): object => {
  let _values = { ...values };

  // handle date type field set null value issue 
  Object.values(model.modelDataDefinition.fields)
    .filter(field => field.type == 'timestamp')
    .forEach(field => {
      _values[field.name] = values[field.name] && values[field.name] != '0000-00-00'
        ? values[field.name]
        : undefined;
    });

  return _values;
}

export const parseHumanReadableDurationToDayCount = (humanReadableDuration: string): Number => {
  const durationData = humanReadableDuration.split(" ");

  if (durationData.length != 2) {
    return -1;
  }

  switch (durationData[1].toUpperCase()) {
    case 'YEAR':
    case 'YEARS':
      return parseInt(durationData[0]) * 365;
    case 'MONTH':
    case 'MONTHS':
      return parseInt(durationData[0]) * 30;
    case 'DAY':
    case 'DAYS':
      return parseInt(durationData[0]);
    default:
      return -1;
  }
}

export const genarateOrgHierarchy = async (data: any, id: number): Promise<any> => {
  let response: Array<any> = [];
  const {entities, orgHierarchyConfig} = data;

  for (const key in orgHierarchyConfig) {
    if (!id) return response.reverse();

    const entity = entities.find((entity: any) => {
      return entity.id === id;
    });

    if (!entity) return response.reverse();

    response[orgHierarchyConfig[entity.entityLevel]] = entity;
    id = entity.parentEntityId;
  }
}
