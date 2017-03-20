(function(window, $, document) {
    var massageTree = function (subtree) {
        var result = [];

        subtree.forEach(function (row) {
            var resultRow = { CategoryID: row.id };

            if (row.children) {
                resultRow.Children = massageTree(row.children);
            }
            result.push(resultRow);
        });
        return result;
    };

    $(document)
        .on('contentLoad', function(e) {
            if (gdn.getMeta("AllowSorting", true) === false) {
                return;
            }

            $('.js-nestable', e.target).nestable({
                listNodeName    : 'ol',
                itemNodeName    : 'li',
                rootClass       : 'js-nestable', // selector
                listClass       : 'js-nestable-list', // selector
                itemClass       : 'js-nestable-item', // selector
                dragClass       : 'nestable-dragel', // applied to item being dragged
                handleClass     : 'js-nestable-handle', // selector
                collapsedClass  : 'nestable-collapsed', // applied to collapsed lists
                placeClass      : 'nestable-placeholder', // applied to position we're moving item from
                noDragClass     : 'js-nestable-nodrag',
                emptyClass      : 'nestable-empty', // applied to empty list elements
                expandBtnHTML   : '<button class="nestable-collapse" data-action="expand"><svg class="icon icon-16 icon-chevron-closed" viewBox="0 0 16 16"><use xlink:href="#chevron-closed" /></svg></button>',
                collapseBtnHTML : '<button class="nestable-collapse" data-action="collapse"><svg class="icon icon-16 icon-chevron-open" viewBox="0 0 16 16"><use xlink:href="#chevron-open" /></svg></button>'
            })
            .on('dragEnd', function(event, item, source, destination, position) {
                var tree = $(source).nestable('serialize');
                var postTree = massageTree(tree);

                $.ajax({
                    type: "POST",
                    url: gdn.url('/vanilla/settings/categoriestree.json'),
                    data: {
                        TransientKey: gdn.getMeta('TransientKey'),
                        Subtree: JSON.stringify(postTree),
                        ParentID: $(source).data('parentId')
                    },
                    dataType: 'json',
                    error: function (xhr) {
                        gdn.informError(xhr);
                    }
                });
            });

            // console.log($('.dd', e.target).nestable('serialize'));
        })
        .on('click', '.js-displayas', function(e) {
            e.preventDefault();

            var displayAs = $(this).data('displayas');
            var $item = $(this).closest('.js-category-item');
            var categoryID = $item.data('id');

            var setUI = function ($item, displayAs) {
                displayAs = displayAs.toLowerCase();

                // Deselect the wrong menu items and select the right one.
                $('.js-displayas', $item).removeClass('selected');
                $('.js-displayas[data-displayas=' + displayAs + ']', $item).addClass('selected');

                // Change the options SVG.
                var svgMap = {categories: "nested"};
                var svg = svgMap[displayAs] || displayAs;
                var options = '';
                svg = ' <svg class="icon icon-16 icon-' + svg + '" viewBox="0 0 16 16"><use xlink:href="#' + svg + '" /></svg> ';
                $('.dropdown-toggle[data-id="' + categoryID + '"]').each(function() {
                    $(this).html(svg);
                    options = $('.js-nestable-item[data-id="' + categoryID + '"] .options').html();
                });

                $(document).trigger('updateDisplayAs', {categoryID: categoryID, options: options});
            };

            // Ajax the value.
            $.ajax({
                type: "POST",
                url: gdn.url('/vanilla/settings/categorydisplayas.json'),
                data: {
                    TransientKey: gdn.getMeta('TransientKey'),
                    CategoryID: categoryID,
                    DisplayAs: displayAs
                },
                dataType: 'json',
                error: function (xhr) {
                    gdn.informError(xhr);
                },
                success: function (data) {
                    setUI($item, data.DisplayAs);
                }
            });
        })
    ;
})(window, jQuery, document);
