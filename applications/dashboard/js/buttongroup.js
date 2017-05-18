/**
 * If a btn-group gets too long for the window width, this will transform it into a dropdown-filter.
 *
 * Selector: `.btn-group`
 *
 * @param element - The scope of the function.
 */
var buttonGroup = function(element) {

    /**
     * Transforms a button group into a dropdown-filter.
     *
     * @param $buttonGroup
     */
    var transformButtonGroup = function(buttonGroup) {
        var elem = document.createElement('div');
        $(elem).addClass('dropdown');
        $(elem).addClass('dropdown-filter');

        var items = $(buttonGroup).html();
        var title = gdn.definition('Filter');
        var list = document.createElement('div');
        var id = Math.random().toString(36).substr(2, 9);


        $(list).addClass('dropdown-menu');
        $(list).attr('aria-labelledby', id);
        $(list).html(items);

        $('.btn', list).each(function() {
            $(this).removeClass('btn');
            $(this).removeClass('btn-secondary');
            $(this).addClass('dropdown-item');

            if ($(this).hasClass('active')) {
                title = $(this).html();
            }
        });

        $(elem).prepend(
            '<button ' +
            'id="' + id + '" ' +
            'type="button" ' +
            'class="btn btn-secondary dropdown-toggle" ' +
            'data-toggle="dropdown" ' +
            'aria-haspopup="true" ' +
            'aria-expanded="false"' +
            '>' +
            title +
            '</button>'
        );

        $(elem).append($(list));

        return elem;
    };

    var showButtonGroup = function(buttonGroup, dropdown) {
        $(buttonGroup).show();
        $(dropdown).hide();
    };

    var showDropdown = function(buttonGroup, dropdown) {
        $(buttonGroup).hide();
        $(dropdown).show();
    };

    /**
     * Generates an equivalent dropdown to the btn-group. Calculates widths to see whether we show the dropdown
     * or btn-group, and then shows/hides the appropriate one.
     *
     * @param element The scope of the function.
     */
    var checkWidth = function(element) {
        $('.btn-group', element).each(function() {
            var self = this;
            var maxWidth = $(self).data('maxWidth');
            var container = $(self).data('containerSelector');

            if (!container && !maxWidth) {
                maxWidth = $(window).width();
            }

            if (container) {
                maxWidth = $(container).width();
            }

            if (!self.width) {
                self.width = $(self).width();
            }

            if (!self.dropdown) {
                self.dropdown = transformButtonGroup(self);
                $(self).after(self.dropdown);
            }

            if (self.width <= maxWidth) {
                showButtonGroup(self, self.dropdown);
            } else {
                showDropdown(self, self.dropdown);
            }
        });
    };

    checkWidth(element);

    $(window).resize(function() {
        checkWidth(document);
    });
};
