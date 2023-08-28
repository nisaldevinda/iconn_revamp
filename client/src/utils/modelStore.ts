import localforage from 'localforage';

export const init = () => {
    localforage.config({
      driver: localforage.INDEXEDDB,
      name: 'iconn-hrm',
      storeName: 'models',
    });
}

export const setModel = (modelName: string, modelDefinition: any) => {
    return localforage.setItem(modelName, modelDefinition);
}

export const getModel = (modelName: string) => {
    return localforage.getItem(modelName);
}

export const getAllModels = () => {
  return localforage.keys();
};

export const setManyModels = async (models: Array<any>) => {
  return Promise.all(
    models.map(async ({ key, value }) => {
      return await setModel(key, value);
    }),
  );
};

export const clear = () => {
  return localforage.clear();
};
