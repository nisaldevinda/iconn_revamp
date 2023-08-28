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
  selector: 'textarea#export',
  plugins:
    'pagebreak code image table paste lists advlist link hr charmap directionality print autoresize',
  menubar: false,
  toolbar:
    'undo redo | formatselect fontselect fontsizeselect bold italic underline strikethrough forecolor backcolor subscript superscript | alignleft aligncenter alignright alignjustify indent outdent rtl ltr | bullist numlist checklist | emoticons image table link hr charmap',
  ...a4Size,
  toolbar_mode: 'wrap',
  fontsize_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 15pt 16pt 18pt 24pt 36pt 48pt',
  content_style:
    'body { font-family:Arial; font-size:10pt; margin-left: 10mm; margin-right: 10mm; margin-top: 10mm; margin-bottom: 10mm; }',
  /* enable title field in the Image dialog */
  image_title: true,
  paste_data_images: true,
  /* enable automatic uploads of images represented by blob or data URIs */
  automatic_uploads: true,
  /*
      URL of our upload handler (for more details check: https://www.tiny.cloud/docs/configure/file-image-upload/#images_upload_url)
      images_upload_url: 'postAcceptor.php',
      here we add custom filepicker only to Image dialog
    */
  file_picker_types: 'image',
  /* and here's our custom image picker */
  file_picker_callback: function (cb, value, meta) {
    var input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    /*
        Note: In modern browsers input[type="file"] is functional without
        even adding it to the DOM, but that might not be the case in some older
        or quirky browsers like IE, so you might want to add it to the DOM
        just in case, and visually hide it. And do not forget do remove it
        once you do not need it anymore.
      */
    input.onchange = function () {
      var file = this.files[0];
      var reader = new FileReader();
      reader.onload = function () {
        /*
            Note: Now we need to register the blob in TinyMCEs image blob
            registry. In the next release this part hopefully won't be
            necessary, as we are looking to handle it internally.
          */
        var id = 'blobid' + new Date().getTime();
        var blobCache = tinymce.activeEditor.editorUpload.blobCache;
        var base64 = reader.result.split(',')[1];
        var blobInfo = blobCache.create(id, file, base64);
        blobCache.add(blobInfo);
        /* call the callback and populate the Title field with the file name */
        cb(blobInfo.blobUri(), { title: file.name });
      };
      reader.readAsDataURL(file);
    };

    input.click();
  },
};

export const tokenizeEditorObj = { ...defaultEditorObj, toolbar: 'newdocument print pagebreak undo redo | formatselect fontselect fontsizeselect bold italic underline strikethrough forecolor backcolor subscript superscript | alignleft aligncenter alignright alignjustify indent outdent rtl ltr | bullist numlist checklist | image table link hr charmap | tokens' };
