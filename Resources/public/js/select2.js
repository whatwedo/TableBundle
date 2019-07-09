var whatwedo_select2 = {
    empty: '-- leer --',
    init: function() {
        $('select').each(function(i, elem) {
          whatwedo_select2.initElement(elem)
        });
    },

    initElement: function(elem) {
        if($(elem).is('[data-disable-interactive]')) return;

        if($(elem).is('[data-ajax-select]')) whatwedo_select2.ajaxSelect2(elem);
        else whatwedo_select2.select2(elem);
    },

    select2: function(elem) {
        $(elem).select2({
            language: 'de',
            width: '100%',
            placeholder: $(elem).find('option[value=""]').text() || whatwedo_select2.empty,
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
            allowClear: typeof elem.attr('required') === 'undefined',
            placeholder: {
              id: '',
              placeholder: 'Leer'
            },
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
                }
            },
            minimumInputLength: 2
        });
    }
};

$(document).ready(function() {
    whatwedo_select2.init();
});
