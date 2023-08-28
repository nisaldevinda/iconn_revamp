export enum RelationshipType {
    /**
    * Ex - one employee has one gender
    *
    * Employee table          Gender table
    * |-genderId              |-id
    *
    * Employee Model          Gender Model
    * |-gender: HAS_ONE       |-employee: BELONGS_TO
    */
    HAS_ONE = 'HAS_ONE', // ONE_TO_ONE, on foreign key side
    BELONGS_TO = 'BELONGS_TO', // ONE_TO_ONE, on primary key side
 
     /**
     * Ex - one employee has many qualifications
     *
     * Employee table          Qualification table
     * |-id                     |-employeeId
     *
     * Employee Model          Qualification Model
     * |-gender: HAS_MANY       |-employee: BELONGS_TO_MANY
     */
    HAS_MANY = 'HAS_MANY', // ONE_TO_MANY, on primary key side
    BELONGS_TO_MANY = 'BELONGS_TO_MANY', // ONE_TO_MANY, on foreign key side
 
     /**
     * Ex - one employee has many competencies, and competency also having more than one employee
     *
     * Employee table          Competency table
     * |-competencyIds         |-id
     *
     * Employee Model                    Qualification Model
     * |-competency: HAS_MANY_TO_MANY    |-employee: 
     */
    HAS_MANY_TO_MANY = 'HAS_MANY_TO_MANY' // MANY_TO_MANY, on foreign key side
}

export enum AttributeType {
    string = 'string',
    model = 'model',
    timestamp = 'timestamp',
    email = 'email',
    url = 'url',
    enum = 'enum',
    rate = 'rate',
    textArea = 'textArea',
    timeZone = 'timeZone',
    switch = 'switch',
    WYSIWYG = 'WYSIWYG',
    radio = 'radio',
    checkbox = 'checkbox',
    currency = 'currency'
}

export const getAttributes = (model: any): Array<any> => {
    return model.fields ?? {};
}

export const getAttribute = (model: any, attribute: string): any => {
    return getAttributes(model)[attribute] ?? null;
}

export const getAttributeType = (model: any, attribute: string): AttributeType => {
    return getAttribute(model, attribute)['type'] ?? null;
}

export const getRelations = (model: any): Map<String, RelationshipType> => {
    return model.relations ?? {};
}

export const getRelationType = (model: any, attribute: string): RelationshipType => {
    return getRelations(model)[attribute] ?? null;
}

export function getModels() {
  try {
    const modelsString = localStorage.getItem('models');
    const models = modelsString ? JSON.parse(modelsString) : {};
    return models;
  } catch (e) {
    console.log(e);
    return { userPermissions: [], appPermissions : []};
  }
}

export function setModels(models: any): boolean {
  try {
    localStorage.setItem('models', JSON.stringify(models));
    return true;
  } catch (e) {
    console.log(e);
    return false;
  }
}

export function unSetModels(): boolean {
  try {
    localStorage.removeItem('models');
    return true;
  } catch (e) {
    console.log(e);
    return false;
  }
}