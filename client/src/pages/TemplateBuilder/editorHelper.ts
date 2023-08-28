export const apiKey = "r8malagy0ssa4sxfdiuc313sxq6kzhr3fa8iepg0iae1lklx"

export const a4Size = { width: 793.7007874, height: 1122.519685 };

export const a5Size = { width: 559.37007874, height: 1122.519685 };

export const letterSize = { width: 816, height: 1056 };

export const getPageSize = (size: string): any => {
    switch (size) {
      case 'a4':
        return { ...a4Size };
      case 'a5':
        return { ...a5Size };
      case 'letter':
        return { ...letterSize };
    }
}

export const defaultEditorObj = {
  selector: 'textarea',  // change this value according to your html
  menubar: false,
  toolbar1: 'bold italic underline | link image ',
  toolbar_mode: 'floating',
  // toolbar2: 'alignleft aligncenter alignright'
}


export const tokenizeEditorObj = { ...defaultEditorObj, toolbar: 'newdocument print pagebreak undo redo | formatselect fontselect fontsizeselect bold italic underline strikethrough forecolor backcolor subscript superscript | alignleft aligncenter alignright alignjustify indent outdent rtl ltr | bullist numlist checklist | image table link hr charmap | tokens' };
