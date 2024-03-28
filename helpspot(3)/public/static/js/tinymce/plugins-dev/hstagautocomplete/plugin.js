tinymce.PluginManager.add('hstagautocomplete', function (editor, url) {
    var url = "admin?pg=ajax_gateway&action=placeholder_tags_json";
    //prefetch all tags so that we can search them when needed.
    var tagAuto = [];
    var call = new Ajax.Request(url, {
        method: "post",
        onComplete: function () {
            tags_obj = JSON.parse(arguments[0].responseText);
            for (let i = 0; i < tags_obj.length; i++) {
                let result = tags_obj[i];
                tagAuto.push({
                    value: result.id.toString(),
                    text: result.text
                });
            }
        }
    });
    editor.ui.registry.addAutocompleter('hstagautocomplete', {
        ch: '$',
        minChars: 1,
        columns: 1,
        fetch: function (pattern) {
            var matchedChars = tagAuto.filter(function (char) {
                return char.text.toLowerCase().indexOf(pattern.toLowerCase()) !== -1;
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
            editor.insertContent(value);
            autocompleteApi.hide();
        }
    });
});
