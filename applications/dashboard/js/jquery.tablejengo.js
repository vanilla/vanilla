/**
 * Makes tables collapsible.
 *
 * Set 'data-tj-ignore="true"' on a thead td or thead th element to never collapse that column
 * Set 'data-tj-main="true"' on a thead td or thead th element to make that the main column instead of the first column.
 */
(function($) {
    $.fn.tablejengo = function(options) {

        var start = function(table, vars) {
            reset();
            var maxIterations = 10; //safety first
            var i = 0;
            while(isTooWide(table, vars) && i < maxIterations) {
                moveCell(table, vars);
                i++;
            }
        };

        var reset = function() {
            $('.tj-hidden').show();
            $('.tj-hidden').removeClass('tj-hidden');
            $('.tj-meta').remove();
        };

        var addDataLabels = function(table, vars) {
            $('tbody tr', table).each(function() {
                $('td', this).each(function() {
                    var index = $(this).index();
                    $(this).data('label', vars.labels[index]);
                    if ((index == 0 && vars.mainCell === 'firstcell')
                        || ($(this).data('label') === vars.mainLabel)
                    ) {
                        $(this).addClass('tj-main-cell');
                    }
                });
            });
        };

        var getDataLabels = function(table, vars) {
            vars['labels'] = [];
            vars['ignoreLabels'] = [];
            $('thead tr th, thead tr td', table).each(function() {
                if ($(this).data('tj-label')) {
                    label = $(this).data('tj-label');
                } else {
                    label = $(this).html();
                    $(this).data('label', label);
                }
                if ($(this).data('tjMain')) {
                    vars['mainLabel'] = label;
                    vars['mainCell'] = '[data-label="' + label + '"]';
                }
                if ($(this).data('tjIgnore')) {
                    vars.ignoreLabels.push(label);
                }
                vars.labels.push(label);
            });
        };

        var isTooWide = function(table, vars) {
            var tableWidth = table.width();
            var windowWidth = $(vars.container).outerWidth();

            if (tableWidth > windowWidth) {
                return true;
            }

            return false;
        };

        var moveCell = function(table, vars) {
            var label = '';

            $('tbody tr', table).each(function() {
                var $cell = getNext($(this), vars);
                if ($cell) {
                    var html = $cell.html();
                    if (!(html === '' && !vars.showEmptyCells)) {
                        label = $cell.data('label');

                        html = vars.metaTemplate.replace('{data}', html);
                        html = html.replace('{label}', label);
                        if (!$('.tj-main-cell .tj-meta', this).length) {
                            $('.tj-main-cell', this).append('<div class="tj-meta">' + html + '</div>');
                        } else {
                            $('.tj-main-cell .tj-meta', this).append(html);
                        }
                    }
                    $cell.addClass('tj-hidden');
                }
            });

            $('thead tr td, thead tr th', table).each(function() {
                if ($(this).data('label') === label) {
                    $(this).addClass('tj-hidden');
                }
            })

            $('.tj-hidden').hide();
            $('.tj-meta').show();
        };

        var getNext = function($row, vars) {
            var found = false;
            var $next = false;
            $('td', $row).each(function(index) {
                var ignore = vars.ignoreLabels.indexOf($(this).data('label')) >= 0;

                if (!found
                    && !$(this).hasClass('tj-hidden')
                    && !$(this).hasClass('tj-main-cell')
                    && !ignore
                ) {
                    $next = $(this);
                    found = true;
                }
            });
            return $next;
        };

        // Get table.
        this.each(function() {
            var table = $(this);

            // Extend Settings.
            var settings = $.extend({}, $.fn.tablejengo.defaults, options);

            var vars = {
                container: settings.container,
                mainCell: settings.mainCell,
                metaTemplate: settings.metaTemplate,
                showEmptyCells: settings.showEmptyCells
            };

            getDataLabels(table, vars);
            addDataLabels(table, vars);
            start(table, vars);
            $(window).resize(function() {
                start(table, vars);
            });
        });
    };

    $.fn.tablejengo.defaults = {
        container: 'body',
        mainCell: 'firstcell',
        metaTemplate: '<div class="table-meta-item">' +
        '<span class="table-meta-item-label">{label}: </span>' +
        '<span class="table-meta-item-data">{data}</span>' +
        '</div>',
        showEmptyCells: false
    };

})(jQuery);
