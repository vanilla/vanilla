/**
 * Makes tables collapsible by setting meta data on a main column. The meta label is based on the column heading and
 * the meta body is the cell content.
 *
 * Set 'data-tj-ignore="true"' on a thead td or thead th element to never collapse that column
 * Set 'data-tj-main="true"' on a thead td or thead th element to make that the main column instead of the first column.
 * Set 'data-tj-label="New label"' to override the column heading as the meta label.
 */
(function($) {
    $.fn.tablejenga = function(options) {

        var lastWindowWidth;

        /**
         * Initiates table jengo.
         *
         * @param table
         * @param vars
         */
        var start = function(table, vars) {
            var maxIterations = 10; //safety first
            var i = 0;
            while(isTooWide(table, vars) && i < maxIterations) {
                moveCell(table, vars);
                i++;
            }
        };

        /**
         * If we're making the window less wide, we don't have to reassess any already hidden columns.
         * If we're getting wider, recalculate everything.
         *
         * @param table
         * @param vars
         */
        var windowResize = function(table, vars) {
            if ($(window).width() < lastWindowWidth) {
                start(table, vars);
            } else {
                reset(table);
                start(table, vars);
            }

            lastWindowWidth = $(window).width();
        };

        /**
         * Resets a table to its state before it collapsed.
         *
         * @param table
         */
        var reset = function(table) {
            $('.tj-hidden', table).show();
            $('.tj-hidden', table).removeClass('tj-hidden');
            $('.tj-main-heading', table).css('width', '');
            $('.tj-meta', table).remove();

            $('td[colspan]', table).each(function() {
               $(this).attr('colspan', $(this).data('originalColspan'));
            });
        };

        /**
         * Adds each cell's column heading as a data attribute.
         *
         * @param table
         * @param vars
         */
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

        /**
         * Saves the column headings into an array and determines which cell is the main cell.
         *
         * @param table
         * @param vars
         */
        var getDataLabels = function(table, vars) {
            vars['labels'] = [];
            vars['ignoreLabels'] = [];
            var mainFound = false;
            $('thead tr th, thead tr td', table).each(function() {
                if ($(this).data('tjLabel')) {
                    label = $(this).data('tjLabel');
                } else {
                    label = $(this).html();
                    $(this).data('label', label);
                }
                if ($(this).data('tjMain')) {
                    $(this).addClass('tj-main-heading');
                    mainFound = true;
                    vars['mainLabel'] = label;
                    vars['mainCell'] = '[data-label="' + label + '"]';
                }
                if ($(this).data('tjIgnore')) {
                    vars.ignoreLabels.push(label);
                }
                vars.labels.push(label);
            });
            if (!mainFound) {
                $('thead tr th, thead tr td', table).first().addClass('tj-main-heading');
            }
        };

        /**
         * Checks if a table is too wide for its container.
         *
         * @param table
         * @param vars
         * @returns {boolean} Whether a table is wider than its container.
         */
        var isTooWide = function(table, vars) {
            var tableWidth = table.width();
            var containerWidth = $(vars.container).outerWidth();

            if (tableWidth > containerWidth) {
                return true;
            }

            return false;
        };

        /**
         * Does what we came here to do. Moves data from a cell to a meta item under the data in the main cell.
         *
         * @param table
         * @param vars
         */
        var moveCell = function(table, vars) {
            var label = '';

            $('tbody tr', table).each(function() {
                var $cell = getNext($(this), vars);
                if ($cell) {
                    var html = $cell.html();
                    if (!(html === '' && !vars.showEmptyCells)) {
                        label = $cell.data('label');

                        html = vars.metaTemplate.replace('{data}', html);
                        if (label) {
                            var labelHtml = vars.metaLabel.replace('{label}', label);
                            html = html.replace('{label}', labelHtml);
                        } else {
                            html = html.replace('{label}', '');
                        }

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
                if (!$(this).hasClass('tj-hidden') && $(this).data('label') === label) {
                    // check the width of the column and main-heading assumes its width if it's wider than main
                    var minWidth = $(this).width();
                    if ($('.tj-main-heading', table).width() < minWidth) {
                        $('.tj-main-heading', table).css('width', minWidth);
                    }

                    decrementColspan(table);
                    $(this).addClass('tj-hidden');
                }
            });

            $('.tj-hidden', table).hide();
            $('.tj-meta', table).show();
        };


        /**
         * Decrements the colspan of a cell.
         *
         * @param table
         */
        var decrementColspan = function(table) {
            $('td[colspan]', table).each(function() {
                $(this).attr('colspan', ($(this).attr('colspan') - 1));
            });
        };

        /**
         * Returns the next cell to collapse.
         *
         * @param $row
         * @param vars
         * @returns {boolean}
         */
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

        this.each(function() {
            var $table = $(this);

            $('td[colspan]', $table).each(function() {
                $(this).data('originalColspan', $(this).attr('colspan'));
            });

            $table.css('table-layout', 'fixed');

            // Extend Settings.
            var settings = $.extend({}, $.fn.tablejenga.defaults, options);

            var vars = {
                container: settings.container,
                mainCell: settings.mainCell,
                metaTemplate: settings.metaTemplate,
                metaLabel: settings.metaLabel,
                showEmptyCells: settings.showEmptyCells
            };

            getDataLabels($table, vars);
            addDataLabels($table, vars);
            start($table, vars);

            lastWindowWidth = $(window).width();
            $(window).resize(function() {
                windowResize($table, vars);
            });
        });
    };

    $.fn.tablejenga.defaults = {
        container: 'body',
        mainCell: 'firstcell',
        metaTemplate: '<div class="table-meta-item">' +
        '{label}' +
        '<span class="table-meta-item-data">{data}</span>' +
        '</div>',
        metaLabel: '<span class="table-meta-item-label">{label}: </span>',
        showEmptyCells: false
    };

})(jQuery);
