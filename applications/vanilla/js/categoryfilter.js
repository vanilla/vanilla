(function(window, $, document) {

    var categoryFilter = function($input) {

        var categoryOptions = ' \
        <div class="options"> \
            {Options} \
        </div>';

        var categoryTemplate = ' \
        <li class="dd-item tree-item" data-id="{CategoryID}"> \
            <div class="dd-content tree-content"> \
                {Name}' + categoryOptions + ' \
            </div> \
        </li>';


        var categories;
        var filteredCategories;
        var displayAs = gdn.definition('CategoryModuleDisplayAs');
        // var hideContainerSelector = $input.data('hideContainer');
        var hideContainerSelector;
        var categoryID = $input.data('categoryId');
        var limit = $input.data('limit');

        var containerSelector = $input.data('container');
        if (!containerSelector) {
            containerSelector = '.category-filter-container';
        }

        var renderCategory = function(category) {
            var replacements = ['Name', 'CategoryID', 'Url', 'Options'];
            var catString = categoryTemplate;
            for (var i = 0; i < replacements.length; ++i) {
                catString = catString.replace('{' + replacements[i] + '}', category[replacements[i]]);
            }
            return catString;
        };

        var renderCategories = function() {
            $(containerSelector).html('');
            var html = '';
            for (var i = 0; i < filteredCategories.length; ++i) {
                html += renderCategory(filteredCategories[i]);
            }
            $(containerSelector).html(html)
        };

        var filterCategories = function(str) {
            if (str === undefined) {
                filteredCategories = categories;
                return;
            }
            filteredCategories = [];
            if (categories !== undefined) {
                for (var i = 0; i < categories.length; ++i) {
                    var name = categories[i]['Name'].toLowerCase();
                    str = str.toLowerCase();
                    if (name.indexOf(str) !== -1) {
                        filteredCategories.push(categories[i]);
                    }
                }
            }
        };

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

        var go = function(str) {
            filterCategories(str);
            renderCategories();
        };

        hideContainers();

        (function() {
            if (displayAs !== 'Flat') {
                // fetch our categories
                jQuery.get(
                    gdn.url("categories/getflattenedchildren/" + categoryID),
                    {
                        // categoryID: categoryID,
                        limit: limit,
                        showHeadings: true,
                        view: 'json'
                    },
                    function(json, textStatus, jqXHR) {
                        categories = json.Categories;
                        go();
                    },
                    'json'
                );
            }
        })();

        $input.on('keyup', function(filterEvent) {
            if (displayAs !== 'Flat') {
                go(filterEvent.target.value);
            } else {
                // var fetchData = hideContainerSelector === undefined || $input.val() !== '';
                // if (fetchData) {
                    jQuery.get(
                        gdn.url("categories/getflattenedchildren/" + categoryID),
                        {
                            // categoryID: categoryID,
                            filter: filterEvent.target.value,
                            limit: limit,
                            showHeadings: true,
                            view: 'categoryfilter-dashboard'
                        },
                        function(data, textStatus, jqXHR) {
                            $(containerSelector).replaceWith($(containerSelector), data);
                        },
                        'json'
                    );
                // }
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
