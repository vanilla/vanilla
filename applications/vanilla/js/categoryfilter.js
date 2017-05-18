(function(window, $, document) {

    /**
     * Given a template string and an object, replaces any property name contained in curly braces `{}` with the
     * corresponding value in the json object. If the  property doesn't exist, replaces the property name with an
     * empty string. Will only replace the values passed as strings in the replacements array.
     *
     * @param {Object. <string, *>} json The object that contains the values we're replacing.
     * @param {string} template The template string with the variables to replace.
     * @param {string[]} replacements The replacement properties to look for.
     * @returns {string} A string with the replacement values replaced.
     */
    var renderTemplate = function(json, template, replacements) {
        var result = template;
        for (var i = 0; i < replacements.length; ++i) {
            if (json[replacements[i]] === undefined) {
                json[replacements[i]] = "";
            }
            result = result.replace('{' + replacements[i] + '}', json[replacements[i]]);
        }
        return result;
    };

    /**
     * Takes an object of `key: value` attributes and outputs an attributes string to add to an HTML tag.
     *
     * @param {Object. <string, string>} attributes A `key: value` object of attributes to transform into a string.
     * @returns {string} An string representation of the attributes.
     */
    var attrToString = function(attributes) {
        if (attributes === undefined) {
            return '';
        }
        var attrStr = '';
        Object.keys(attributes).forEach(function(key) {
            attrStr += key + '="' + attributes[key] + '" ';
        });

        return attrStr;
    };

    /**
     * Takes an array of options (retrieved using the CategoriesController's getOptions function) and outputs
     * an HTML string representing the options dropdown.
     *
     * @param {Object. <string, *>} options The options to render.
     * @returns {string}
     */
    var categoryOptionsToString = function(options) {

        var itemTemplate = ' \
        <a role="menuitem" class="dropdown-item {cssClass}" href="{url}" {attributes}> \
            {icon}{text} \
        </a>';

        var headerTemplate = ' \
        <div class="dropdown-header"> \
        {text} \
        </div>';

        var dropdownTemplate = ' \
        <div class="dropdown dropdown-category-options"> \
            <button class="dropdown-toggle btn" id="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-id="{categoryID}"> \
            {text} \
            </button> \
            <div class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby=""> \
            {items} \
            </div> \
        </div>';

        var menuItems = '';
        var items = options.items;

        for (var key in items) {
            if (items.hasOwnProperty(key)) {
                var group = items[key];
                if (group.text !== "") {
                    menuItems += renderTemplate(group, headerTemplate, ['text']);
                }
                var groupItems = items[key].items;
                for (var key in groupItems) {
                    if (groupItems.hasOwnProperty(key)) {
                        var item = groupItems[key];
                        if (typeof item === 'object') {
                            item.attributes = attrToString(item.attributes);
                            var properties = ['cssClass', 'icon', 'text', 'url', 'attributes'];
                            menuItems += renderTemplate(item, itemTemplate, properties);
                        }
                    }
                }

                // If this isn't the last item in the list...
                if (key !== Object.keys(items)[Object.keys(items).length-1]) {
                    menuItems += '<div class="dropdown-divider"></div>';
                }
            }
        }

        options.items = menuItems;
        options.text = options.trigger.text;
        options.categoryID = options.trigger.attributes['data-id'];
        return renderTemplate(options, dropdownTemplate, ['text', 'items', 'categoryID']);
    };

    /**
     * Transforms an input box into a category filter box. The input should set the following data attributes:
     *
     * data-category-id: Which parent category to filter children for.
     * data-container: The selector for the container that we display the results in.
     * data-hide-container: OPTIONAL If we're toggling showing and hiding categories based on the existance of a
     *    filter input, set this to be the selector for the container we should hide when the filter exists.
     *
     * @param {jQuery} $input The form input wrapped in a jQuery object.
     */
    var categoryFilter = function($input) {
        var categories;
        var filteredCategories;
        var hideContainerSelector = $input.data('hideContainer');
        var categoryID = $input.data('categoryId');
        var containerSelector = $input.data('container');

        if (!containerSelector) {
            containerSelector = '.category-filter-container';
        }

        var categoryOptions = ' \
        <div class="plank-options"> \
            {Options} \
        </div>';

        var categoryTemplate = ' \
        <li class="plank js-category-item" data-id="{CategoryID}"> \
            <div class="plank-title"> \
                {NameHTML} \
            </div> \
            '+ categoryOptions + '\
        </li>';

        /**
         * Renders the HTML for any category in our filtered list and displays in the container.
         */
        var renderCategories = function() {
            var replacements = ['NameHTML', 'CategoryID', 'Options'];
            $(containerSelector).html('');
            var html = '';
            filteredCategories.forEach(function(category) {
                if (category['DisplayAs'] === 'Categories' || category['DisplayAs'] === 'Flat') {
                    // Wrap in an anchor
                    category['NameHTML'] = ' \
                        <a href="' + gdn.url('vanilla/settings/categories?parent=' + category['UrlCode']) + '"> \
                        ' + category["Name"] + ' \
                        </a>';
                } else {
                    category['NameHTML'] = category['Name'];
                }
                html += renderTemplate(category, categoryTemplate, replacements);
            });
            $(containerSelector).html(html)
        };

        /**
         * Filters the categories list for categories whose names contain the filter string.
         * Stores these categories in the filteredCategories array.
         *
         * @param {string} filter The filter to filter category names by.
         */
        var filterCategories = function(filter) {
            if (filter === undefined) {
                filteredCategories = categories;
                return;
            }
            filteredCategories = [];
            if (categories !== undefined) {
                for (var i = 0; i < categories.length; ++i) {
                    var name = categories[i]['Name'].toLowerCase();
                    filter = filter.toLowerCase();
                    if (name.indexOf(filter) !== -1) {
                        filteredCategories.push(categories[i]);
                    }
                }
            }
        };

        /**
         * Toggles the display of the container and hide container, if one exists.
         */
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

        /**
         * Updates the category options with new HTML.
         *
         * @param {Object. <string, string>} data An object containing the category ID and the new HTML for the options.
         */
        var updateCategoryOptionsText = function(data) {
            categories.forEach(function(category) {
                if (category.CategoryID === data.categoryID) {
                    category.Options = data.options;
                }
            });
        };

        /**
         * Do the things we do on page load or when we get a new filter.
         *
         * @param filter
         */
        var go = function(filter) {
            filterCategories(filter);
            renderCategories();
        };

        hideContainers();

        /**
         * Fetch the categories. Just once.
         */
        (function() {
            // fetch our categories
            jQuery.get(
                gdn.url("categories/getflattenedchildren/" + categoryID),
                function(json, textStatus, jqXHR) {
                    categories = json.Categories;
                    for (var i = 0; i < categories.length; ++i) {
                        categories[i].Options = categoryOptionsToString(categories[i].Options);
                    }
                    go();
                },
                'json'
            );
        })();

        $input.on('keyup', function(filterEvent) {
            go(filterEvent.target.value);
            hideContainers();
        });

        $(document).on('updateDisplayAs', function(e, data) {
            updateCategoryOptionsText(data);
        });
    };

    $(document).on('contentLoad', function(e) {
        $(".js-category-filter-input", e.target).each(function() {
            categoryFilter($(this));
        });
    });

})(window, jQuery, document);
