var whatwedo_select2 = {
    empty: '-- leer --',
    init: function() {
        $('select').not('[data-disable-interactive]').not('[data-ajax-select]').each(function(i, elem) {
            whatwedo_select2.select2(elem);
        });

        $('select[data-ajax-select]').not('[data-disable-interactive]').each(function(i, elem) {
            whatwedo_select2.ajaxSelect2(elem);
        });
    },

    select2: function(elem) {
        elem = $(elem);
        var ph = elem.find('option[value=""]').text();
        ph = ph !== '' ? ph : whatwedo_select2.empty;
        elem.select2({
            language: 'de',
            width: '100%',
            placeholder: ph,
            allowClear: true
        });
    },

    ajaxSelect2: function(elem) {
        elem = $(elem);
        var entity = elem.data('ajax-entity');
        var url = elem.closest('form').data('ajax-url');
        elem.select2({
            language: 'de',
            width: '100%',
            allowClear: false,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        entity: entity
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.items
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
    }
};

$(document).ready(function() {
    whatwedo_select2.init();
});
