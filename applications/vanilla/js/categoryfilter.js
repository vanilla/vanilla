(function(window, $, document) {

    var categoryFilter = function($input) {
        var containerSelector = $input.data('container');
        if (!containerSelector) {
            containerSelector = '.category-filter-container';
        }

        var hideContainerSelector = $input.data('hideContainer');
        var categoryID = $input.data('categoryId');
        var limit = $input.data('limit');

        var hideContainers = function() {
            if (hideContainerSelector !== undefined) {
                if ($input.val() === '') {
                    $(containerSelector).hide();
                    $(hideContainerSelector).show();
                } else {
                    $(containerSelector).show();
                    $(hideContainerSelector).hide();
                }
            }
        };

        hideContainers();

        $input.on('keyup', function(filterEvent) {
            var fetchData = hideContainerSelector === undefined || $input.val() !== '';
            if (fetchData) {
                jQuery.get(
                    gdn.url("module/categoryfiltermodule/vanilla"),
                    {
                        categoryID: categoryID,
                        filter: filterEvent.target.value,
                        limit: limit,
                        showHeadings: true,
                        view: 'categoryfilter-dashboard'
                    },
                    function(data, textStatus, jqXHR) {
                        $(containerSelector).replaceWith($(containerSelector), data);
                    }
                );
            }

            hideContainers();
        });
    };

    $(document).on('contentLoad', function(e) {
        $(".js-category-filter-input", e.target).each(function() {
            categoryFilter($(this));
        });
    });

})(window, jQuery, document);
