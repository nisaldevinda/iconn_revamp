const downloadBase64StringAsFile=(name:any, type:any, data:any)=>
    {
        try {
            var byteString = atob(data);
            var arrayBuffer = new ArrayBuffer(byteString.length);
            var uint8Array = new Uint8Array(arrayBuffer);
            for (var i = 0; i < byteString.length; i++) {
              uint8Array[i] = byteString.charCodeAt(i);
            }
            var blob = new Blob([uint8Array], {type: type});
            let objectURL = window.URL.createObjectURL(blob);
            let anchor = document.createElement('a');
            anchor.href = objectURL;
            anchor.download = name;
            anchor.click();
            URL.revokeObjectURL(objectURL);
            return {
                'error': false,
                'data': {
                    'name': name
                }
            };
        } catch(ex) {
            return {
                'error': true,
                'msg': ex,
                'data': {
                    'name': name
                }
            }
        }
    }

export default downloadBase64StringAsFile;