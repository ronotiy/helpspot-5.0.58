tinymce.PluginManager.add('hsresponseautocomplete', function (editor, url) {
    var url = "admin?pg=ajax_gateway&action=response_search_json";
    //prefetch all responses so that we can search them when needed.
    var responseAuto = [];
    var call = new Ajax.Request(url, {
        method: "post",
        onComplete: function () {
            response_obj = JSON.parse(arguments[0].responseText);
            for (let i = 0; i < response_obj.length; i++) {
                let result = response_obj[i];
                responseAuto.push({
                    value: result.id.toString(),
                    text: result.text
                });
            }
        }
    });
    editor.ui.registry.addAutocompleter('responseAuto', {
        ch: '#',
        minChars: 1,
        columns: 1,
        fetch: function (pattern) {
            var matchedChars = responseAuto.filter(function (char) {
                return char.text.toLowerCase().split(" ").join("_").indexOf(pattern.toLowerCase().split(" ").join("_")) !== -1;
            });
            return new tinymce.util.Promise(function (resolve) {
                var results = matchedChars.map(function (char) {
                    return {
                        value: char.value,
                        text: char.text,
                    }
                });
                resolve(results);
            });
        },
        onAction: function (autocompleteApi, rng, value) {
            editor.selection.setRng(rng);
            getResponse(value);
            autocompleteApi.hide();
        }
    });
});
