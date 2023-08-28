import { ModelType } from '@/services/model';
import { useIntl } from 'react-intl';
import validator from 'validator';
import moment from 'moment';
import _ from "lodash";

export type AuthenticationParamsType = {
  error: boolean;
  message: string;
  data: any;
};

export const filterAndValidateFormDataByModel = (
  model: ModelType,
  data: any,
): AuthenticationParamsType => {
  const fields = model.modelDataDefinition.fields;

  for (let key in data) {
    if (!fields.hasOwnProperty(key)) {
      delete data[key];
    }
  }

  return {
    error: false,
    message: 'Suceesfully validate.',
    data,
  };
};

export const generateProFormFieldValidation = (
  fieldDefinition: any,
  modelName: string,
  fieldName: string,
  values: any
): Array<any> => {
  const intl = useIntl();
  let validations: Array<any> = [];
  const type = fieldDefinition.type;
  const modelFieldValidation = fieldDefinition.validations;
  
  if (type) {
    switch (type) {
      case 'email':
        validations.push({
          type: 'email',
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.email`,
            defaultMessage: 'Invalid email address',
          }),
        });
        break;
      default:
        break;
    }
  }

  if (modelFieldValidation) {
    // required
    if (modelFieldValidation.isRequired) {
      validations.push({
        required: true,
        message: intl.formatMessage({
          id: `model.${modelName}.${fieldName}.rules.required`,
          defaultMessage: `Required`,
        }),
      });
    }

    if (modelFieldValidation.isWhitespace && type === 'string') {
      validations.push({
        pattern:  new RegExp(/^\w+((?!\s{2}).)*$/),
        message: intl.formatMessage({
          id: `model.${modelName}.${fieldName}.rules.whitespace`,
          defaultMessage: 'Cannot contain more than one space',
        }),
      });
    }

    if ((modelFieldValidation.isOneWord && type === 'string') ) {
      validations.push({
        pattern:  new RegExp(/^\w+((?!\s{1}).)*$/),
        message: intl.formatMessage({
          id: `model.${modelName}.${fieldName}.rules.whitespace`,
          defaultMessage: 'Words cannot have any spaces in between',
        }),
      });
    }

    // maximum date
    if (type == 'timestamp') {
      if (modelFieldValidation.maxDate == 'today') {
        validations.push({
          validator: (rule: any, value: any) => {
            if (!_.isNull(value)) { 
              if (moment(value).format('YYYY-MM-DD') > moment(new Date()).format('YYYY-MM-DD')) {
                return Promise.reject(new Error('The date should be less than current Date.'));
              }
            }
            return Promise.resolve();
          },
        });
      }
    }

    //min and max
    if (type == 'string' || type == 'url' || type == 'textArea' || type == 'longString') {
      if (modelFieldValidation.regex) { 
         if ( modelFieldValidation.isAlphaNumeric) {
           const validationStr = modelFieldValidation.regex.replace(/\//g, '');
           validations.push({
             pattern: new RegExp(validationStr),
             message: intl.formatMessage({
               id: `model.${modelName}.${fieldName}.rules.regex`,
               defaultMessage: 'Cannot contain any spaces or special characters',
             }),
           });
         } else {
           const validationStr = modelFieldValidation.regex.replace(/\//g, '');
           validations.push({
             pattern: new RegExp(validationStr),
             message: intl.formatMessage({
               id: `model.${modelName}.${fieldName}.rules.regex`,
               defaultMessage: 'The entered format is incorrect',
             }),
           });
         }
      }
      if (modelFieldValidation.min && modelFieldValidation.max) {
        validations.push({
          min: modelFieldValidation.min,
          max: modelFieldValidation.max,
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.between`,
            defaultMessage: `Characters length error`,
          }),
        });
      } else if (modelFieldValidation.min) {
        validations.push({
          min: modelFieldValidation.min,
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.min`,
            defaultMessage: `Please enter at least ${modelFieldValidation.min} characters`,
          }),
        });
      } else if (modelFieldValidation.max) {
        validations.push({
          max: modelFieldValidation.max,
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.max`,
            defaultMessage: `Maximum length is ${modelFieldValidation.max} characters.`,
          }),
        });
      }
    } else if (type == 'number') {
      if (modelFieldValidation.min && modelFieldValidation.max &&  modelFieldValidation.precision != undefined) {
        let lengthRangeRegex = `^(?=(?:\\d\\.?){${modelFieldValidation.min},${modelFieldValidation.max}}$)\\d+(?:\\.\\d{1,${modelFieldValidation.precision}})?$`;

        console.log(lengthRangeRegex);
        validations.push({
          pattern: new RegExp(lengthRangeRegex),
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.between`,
            defaultMessage: `Numbers length error`,
          }),
        });
      } else if (modelFieldValidation.min && modelFieldValidation.max) {
        let lengthRangeRegex = `^[\\d]{${modelFieldValidation.min},${modelFieldValidation.max}}$`;
        validations.push({
          pattern: new RegExp(lengthRangeRegex),
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.between`,
            defaultMessage: `Numbers length error`,
          }),
        });
      } else if (modelFieldValidation.min) {
        let minimumRangeRegex = `^\\d{${modelFieldValidation.min},}$`;
        validations.push({
          pattern: new RegExp(minimumRangeRegex),
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.min`,
            defaultMessage: `${fieldDefinition.defaultLabel} has a minimum value of ${modelFieldValidation.min}`,
          }),
        });
      } else if (modelFieldValidation.max) {
        let maximumRangeRegex = `^\\d{${modelFieldValidation.max}}$`;
        validations.push({
          pattern: new RegExp(maximumRangeRegex),
          message: intl.formatMessage({
            id: `model.${modelName}.${fieldName}.rules.max`,
            defaultMessage: `${fieldDefinition.defaultLabel} should have ${modelFieldValidation.max} characters`,
          }),
        });
      }
    }

    // Handle minimum validation depending on another field value
    if (modelFieldValidation.minDependentOn && type === 'number') {
      validations.push({
        validator: (rule: any, value: any) => {
          if ( !_.isNull(value)
            && _.isNumber(value)
            && !_.isNull(values[modelFieldValidation.minDependentOn])
            && _.isNumber(values[modelFieldValidation.minDependentOn])
            && value <= values[modelFieldValidation.minDependentOn]  ) {
              return Promise.reject(new Error(
                intl.formatMessage({
                  id: 'invalidNumber',
                  defaultMessage: 'Should be greater than Minimum Salary.',
                })
              ));
          }

          return Promise.resolve();
        },
      });
    }

    // Handle maximum validation depending on another field value
    if (modelFieldValidation.maxDependentOn && type === 'number') {
      validations.push({
        validator: (rule: any, value: any) => {
          if ( !_.isNull(value)
            && _.isNumber(value)
            && !_.isNull(values[modelFieldValidation.maxDependentOn])
            && _.isNumber(values[modelFieldValidation.maxDependentOn])
            && values[modelFieldValidation.maxDependentOn] < value ) {
              return Promise.reject(new Error(
                intl.formatMessage({
                  id: 'invalidNumber',
                  defaultMessage: 'Should be less than Maximum Salary',
                })
              ));
          }
          return Promise.resolve();
        },
      });
    }


    // Handle minimum validation depending on another field value
    if (modelFieldValidation.maxDependentOn && type === 'timestamp') {
      validations.push({
        validator: (rule: any, value: any) => {
          if ( !_.isNull(value)
            && !_.isNull(values[modelFieldValidation.maxDependentOn])) {
              const lessThanMoment = moment(values[modelFieldValidation.maxDependentOn], 'YYYY-MM-DD');
             
              if (value > lessThanMoment) {
                return Promise.reject(new Error(
                  intl.formatMessage({
                    id: 'invalidNumber',
                    defaultMessage: 'Should be less than the '+modelFieldValidation.maxDependentOn,
                  })
                ));
              }
          }

          return Promise.resolve();
        },
      });
    }

    // Handle maximum validation depending on another field value
    if (modelFieldValidation.minDependentOn && type === 'timestamp') {

      validations.push({
        validator: (rule: any, value: any) => {
          if ( !_.isNull(value)
            && !_.isNull(values[modelFieldValidation.minDependentOn])) {

              const greaterThanMoment = moment(values[modelFieldValidation.minDependentOn], 'YYYY-MM-DD');
              
              if (value < greaterThanMoment) {
                return Promise.reject(new Error(
                  intl.formatMessage({
                    id: 'invalidNumber',
                    defaultMessage: 'Should be greater than the '+modelFieldValidation.minDependentOn,
                  })
                ));
              }
          }
          return Promise.resolve();
        },
      });
    }

    // handle isRequired depended on another
    if (modelFieldValidation.isRequiredIf && !_.isEmpty(modelFieldValidation.isRequiredIf) && _.isObject(modelFieldValidation.isRequiredIf)) {
      validations.push({
        validator: (rule: any, value: any) => {
          const logic = (value1, value2) => {
            switch (modelFieldValidation.isRequiredIf.operator) {
              case '!=':
                return value1 != value2;
              case '>':
                  return value1 > value2;
              case '>=':
                return value1 >= value2;
              case '<':
                  return value1 < value2;
              case '<=':
                return value1 <= value2;
              default:
                return value1 == value2;
            }
          }
          if (logic(values[modelFieldValidation.isRequiredIf.dependentFieldName], modelFieldValidation.isRequiredIf.value)
            && !value) {
              return Promise.reject(new Error(
                intl.formatMessage({
                  id: `model.${modelName}.${fieldName}.rules.required`,
                  defaultMessage: `Required`,
                })
              ));
          }
          return Promise.resolve();
        },
      });
    }

  }
  return validations;
};

export const isValidEmail = (value: any): boolean => {
  return validator.isEmail(value);
};

export const isValidBoolean = (value: any): boolean => {
  return validator.isBoolean(value);
};

export const isValidJSON = (value: any): boolean => {
  return validator.isJSON(value);
};

export const isValidLength = (min: number, max: number, value: any): boolean => {
  return validator.isLength(value, { min, max });
};

export const isValidPassword = (value: any): boolean => {
  return validator.isStrongPassword(value, {
    minLength: 8,
    minLowercase: 1,
    minUppercase: 1,
    minNumbers: 1,
    minSymbols: 1,
    returnScore: false,
    pointsPerUnique: 1,
    pointsPerRepeat: 0.5,
    pointsForContainingLower: 10,
    pointsForContainingUpper: 10,
    pointsForContainingNumber: 10,
    pointsForContainingSymbol: 10,
  });
};

export const isValidEnum = (enumSet: any, value: any): boolean => {
  return validator.isIn(value, enumSet);
};
