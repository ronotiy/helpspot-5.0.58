tinymce.PluginManager.add('hsstaffautocomplete', function (editor, url) {
    var url = "admin?pg=ajax_gateway&action=staff_search_json";
    //prefetch all staff so that we can search them when needed.
    var staffAuto = [];
    var call = new Ajax.Request(url, {
        method: "post",
        onComplete: function () {
            staff_obj = JSON.parse(arguments[0].responseText);
            for (let i = 0; i < staff_obj.length; i++) {
                let result = staff_obj[i];
                staffAuto.push({
                    value: result.id.toString(),
                    text: result.text
                });
            }
        }
    });
    editor.ui.registry.addAutocompleter('hsstaffautocomplete', {
        ch: '@',
        minChars: 1,
        columns: 1,
        fetch: function (pattern) {
            var matchedChars = staffAuto.filter(function (char) {
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
            var valueSplit = value.split('|');
            var id = valueSplit[0];
            var name = valueSplit[1];
            editor.selection.setRng(rng);
            editor.insertContent("@"+name);
            autocompleteApi.hide();
            var selectID = "ccstaff_public-select-multiple-"+id;
            add_notification();
            $jq('#ccstaff_button').hide();
            $jq('#ccstaff_public').show();
            $jq('#request-drawer').show();
            ms_select(selectID,id,'ccstaff');
        }
    });
});
