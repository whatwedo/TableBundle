var whatwedoTable = {
    clickableRows: function() {
        $(document).on('click', '.whatwedo_table tr[data-href], .dataTable tr[data-href]', function(e) {
            var $this = $(this);

            if (!$this.closest('.whatwedo_table').hasClass('whatwedo_table__editable')) {
                if($(e.target).is('td')) {
                    window.document.location = $this.data("href");
                }
            }
        });

    },

    currentRequest: null,

    loadContent: function(url, data, failCallback, method, animate, replace, showLoad, callback) {
        if (typeof data === 'undefined') {
            data = null;
        }

        if (typeof method === 'undefined') {
            method = 'GET';
        }

        if (typeof animate === 'undefined') {
            animate = true;
        }

        if (typeof replace === 'undefined') {
            replace = true;
        }

        if (typeof showLoad === 'undefined') {
            showLoad = true;
        }

        if (typeof callback === 'undefined') {
            callback = jQuery.noop;
        }

        var $whatwedoTable = $('#whatwedo_table');

        if (showLoad) {
            $whatwedoTable.addClass('loading');
        }

        if (whatwedoTable.currentRequest !== null) {
            whatwedoTable.currentRequest.abort();
        }

        this.currentRequest = $.ajax({
            url: url,
            data: data,
            method: method
        })
            .done(function(html) {
                callback(html);

                if (!replace) {
                    return;
                }

                history.pushState({
                    'type': 'table',
                    'url': url,
                    'data': data
                }, null, url);

                $whatwedoTable.replaceWith(html);
                whatwedoTable.tableHeader();

                if (animate) {
                    $("html, body").animate({ scrollTop: $('#whatwedo_table').offset().top - 100 }, 250);
                }
            })
            .fail(failCallback)
            .always(function() {
                whatwedoTable.currentRequest = null;
            });
    },

    handleDataLoadEnabled: true,

    handleDataLoad: function(event) {
        if (!whatwedoTable.handleDataLoadEnabled) {
            return;
        }

        var href = this.getAttribute('href');
        var $this = $(this);

        event.preventDefault();

        // Bugfix for Firefox
        var additionalName = null;
        var additionalValue = null;
        var action = null;

        if ($this.prop("tagName") !== 'FORM') {
            if ($this.prop("tagName") === 'BUTTON') {
                additionalName = $this.attr('name');
                additionalValue = $this.attr('value');
            }
            if ($this.prop("tagName") === 'A') {
                action = $this.attr('href');
            } else {
                $this = $($this.parents('form'));
                action = $this.attr('action');
            }
        } else {
            if (typeof $this.attr('action') !== 'undefined') {
                action = $this.attr('action');
            }
        }

        $this.find('input[data-handle-data-load]').remove();

        if (action) {
            var formParams = {};
            $this
                .serializeArray()
                .forEach(function(item) {
                    if (formParams[item.name]) {
                        formParams[item.name] = [formParams[item.name]];
                        formParams[item.name].push(item.value)
                    } else {
                        formParams[item.name] = item.value
                    }
                });

            if (typeof additionalName !== 'undefined'
                && typeof additionalValue !== 'undefined'
                && additionalName
                && additionalValue) {
                $('<input/>')
                    .attr('type', 'hidden')
                    .attr('data-handle-data-load', 1)
                    .attr('name', additionalName)
                    .attr('value', additionalValue)
                    .appendTo($this);
                formParams[additionalName] = additionalValue;
            }

            if (typeof formParams['filter_name'] !== 'undefined') {
                $this.submit();
                return;
            }

            whatwedoTable.loadContent(action, $this.serialize(), function() {
                $this.submit();
            });
        }
        else if (typeof $this.attr('href') !== 'undefined') {
            whatwedoTable.loadContent($this.attr('href'), null, function() {
                window.location.href = $this.attr('href');
            });
        }
        else if (typeof href !== 'undefined') {
            whatwedoTable.loadContent(href, null, function() {
                window.location.href = href;
            });
        }
    },

    filters: function($table) {
        var filterTemplate = $('#whatwedo_table__' + $table.data('identifier') + '__filters__template__block').text();

        var optionNameMatcher = /filter_([\w\d]+)\[(\d)\]\[(\d)\]/i;

        var findCurrentBlocksBlockIteratorNumber = function($blocksContainer) {
            var optionName = $blocksContainer.find('.whatwedo_table__filters__block:last select:first').attr('name');
            if (!optionNameMatcher.test(optionName)) {
                return 0;
            }
            var result = optionNameMatcher.exec(optionName);

            result = parseInt(result[3]);
            if (isNaN(result)) {
                return 0;
            }

            return result;
        };

        var findCurrentBlockIteratorNumber = function($blocksContainer) {
            var optionName = $blocksContainer.find('.whatwedo_table__filters__block:last select:first').attr('name');
            if (!optionNameMatcher.test(optionName)) {
                return 0;
            }
            var result = optionNameMatcher.exec(optionName);

            result = parseInt(result[2]);
            if (isNaN(result)) {
                return 0;
            }

            return result;
        };

        $(document).on('click', '#whatwedo_table_' + $table.data('identifier') + ' [data-filter-action="add-and"]', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $blocksContainer = $this.closest('.whatwedo_table__filters__blocks');
            var currentBlockIteratorNumber = findCurrentBlockIteratorNumber($blocksContainer);
            var currentBlocksBlockIteratorNumber = findCurrentBlocksBlockIteratorNumber($blocksContainer);
            var block = filterTemplate
                .replace(/{iBlock}/g, currentBlockIteratorNumber.toString())
                .replace(/{i}/g, (currentBlocksBlockIteratorNumber + 1).toString());
            $blocksContainer.append(block);
        });

        // TODO: fix for empty filters (i.e. $lastBlocksContainer undefined)
        $(document).on('click', '#whatwedo_table_' + $table.data('identifier') + ' [data-filter-action="add-or"]', function(e) {
            e.preventDefault();
            var $lastBlocksContainer = $('#whatwedo_table_' + $table.data('identifier')).find('.whatwedo_table__filters__blocks:last');
            var currentBlockIteratorNumber = findCurrentBlockIteratorNumber($lastBlocksContainer);
            var block = filterTemplate
                .replace(/{iBlock}/g, (currentBlockIteratorNumber + 1).toString())
                .replace(/{i}/g, '1');
            block = '<div class="whatwedo_table__filters__blocks"><p><strong>oder</strong></p>' + block + '</div>';

            $lastBlocksContainer.after(block);
        });

        $(document).on('click', '#whatwedo_table_' + $table.data('identifier') + ' [data-filter-action="remove"]', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $block = $this.closest('.whatwedo_table__filters__block');

            var $blocksContainer = $block.closest('.whatwedo_table__filters__blocks');

            $block.remove();

            if ($blocksContainer.find('.whatwedo_table__filters__block').length === 0) {
                $blocksContainer.closest('.whatwedo_table__filters').submit();
                $blocksContainer.remove();
            }
        });

        $(document).on('click', '#whatwedo_table_' + $table.data('identifier') + ' [data-toggle="filter"]', function() {
            var $whatwedoTableFilters = $('#whatwedo_table_' + $table.data('identifier') + ' .whatwedo_table__filters');

            if ($whatwedoTableFilters.hasClass('active')) {
                $whatwedoTableFilters.slideUp();
                $whatwedoTableFilters.removeClass('active')
            } else {
                $whatwedoTableFilters.slideDown();
                $whatwedoTableFilters.addClass('active');
            }
        });

        $(document).on('change', '#whatwedo_table_' + $table.data('identifier') + ' select[name^="' + $table.data('identifier') + '_filter_column"]', function() {
            var $this = $(this);
            var $parentBlock = $this.parents('.whatwedo_table__filters_filter');
            var $choosenOption = $this.find(":selected");

            var $operator = $parentBlock.find('select[name^="' + $table.data('identifier') + '_filter_operator"]');
            $operator.empty();
            $.each($choosenOption.data('operator-options'), function(key, name) {
                $operator.append('<option value=' + key + '>' + name + '</option>')
            });

            var $field = $parentBlock.find('[name^="' + $table.data('identifier') + '_filter_value"]');

            if (typeof $field.data('select2') !== 'undefined') {
                $field.select2('destroy');
            }
            var fieldName = $field.attr('name');
            var template = $choosenOption.data('value-template');
            $field.replaceWith(template.replace(/{name}/g, fieldName));
            $field = $parentBlock.find('[name^="' + $table.data('identifier') + '_filter_value"]');

            if ($field.prop('tagName') === 'SELECT'
                && typeof $field.attr('data-disable-interactive') === 'undefined') {
                if (typeof $field.attr('data-ajax-select') === 'undefined') {
                    whatwedo_select2.select2($field[0]);
                } else {
                    whatwedo_select2.ajaxSelect2($field[0]);
                }
            }
        });

        $(document).on('submit', '.whatwedo_table__save', function() {
            return whatwedoTable.updateFormFilterValues($table);
        });

        $('.whatwedo_table__filters_filter').keypress(function(e){
            if (e.which === 13) { // enter key pressed
                e.preventDefault();
                $('#whatwedo_table_' + $table.data('identifier') + ' .whatwedo_table__show_results').trigger('click');
            }
        });
    },

    tableHeader: function() {
        $('.whatwedo_table_inner[data-fixed-header]').stickyTableHeaders({
            fixedOffset: 50
        });
    },

    setLimit: function($table) {
      var buildUrl = function(base, key, value) {
        var sep = (base.indexOf('?') > -1) ? '&' : '?';
        return base + sep + key + '=' + value;
      };

      $('#whatwedo_table_' + $table.data('identifier') +' select[name="limit"]').change(function(e) {
            window.location.href = buildUrl(buildUrl(window.location.href, $table.data('identifier') + '_page', 1), $table.data('identifier') + '_limit', $(this).val());
            e.preventDefault();
        });
    },

    updateFormFilterValues: function($table) {
        if ($('input[name$=filter_name]').val() === '') {
            alert('Filter Name darf nicht leer sein');
            return false;
        }
        var data = $table.find('.whatwedo_table__filters').serializeArray();
        var identifier = $table.data('identifier');
        var retArray = {};
        retArray[identifier + '_filter_operator'] = [[], []];
        retArray[identifier + '_filter_value'] = [[], []];
        retArray[identifier + '_filter_column'] = [[], []];
        for (var i = 0; i < data.length; i++) {
            var name = data[i]['name'];
            if (name.startsWith(identifier+'_filter_operator') || name.startsWith(identifier+'_filter_value') || name.startsWith(identifier+'_filter_column')) {
                var matches = name.match(/(.+)\[(\d+)\]\[(\d+)\]$/);
                if (typeof retArray[matches[1]][matches[2]] === 'undefined') {
                    retArray[matches[1]][matches[2]] = [];
                }
                retArray[matches[1]][matches[2]][matches[3]] = data[i]['value'];
            }
        }
        $('input[name$=filter_conditions]').val(JSON.stringify(retArray))
        return true;
    },

    initExport: function() {
        $(document).ready(function(){
            $('[data-whatwedo-table-export-csv-current]').on('click', function(event) {
                event.preventDefault();
                var trs = $(this).parents('.whatwedo_table').find('tr[data-href]');
                var q = '';
                for (var i = 0; i < trs.length; i++) {
                    q += i == 0 ? '?' : '&';
                    q += 'ids[]=' + $(trs[i]).data('href').match(/[0-9]+$/g);
                }
                window.location.href = $(this).data('export') + q;
            });
            $('[data-whatwedo-table-export-csv-all]').on('click', function(event) {
                event.preventDefault();
                window.location.href = $(this).data('export') + '?ids[]=-1';
            })
        });
    },

    /**
     * initialize class
     */
    init: function() {
        var _ = this;

        _.clickableRows();
        _.tableHeader();
        _.initExport();
        $('.whatwedo_table').each(function() {
            var $table = $(this);

            _.filters($table);
            _.setLimit($table);
        });
    }
};

$(document).ready(function() {
    whatwedoTable.init();
});
