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
            $('.dd', e.target).nestable({
                expandBtnHTML: '<button data-action="expand"><svg class="icon icon-16 icon-chevron-closed" viewBox="0 0 16 16"><use xlink:href="#chevron-closed" /></svg></button>',
                collapseBtnHTML: '<button data-action="collapse"><svg class="icon icon-16 icon-chevron-open" viewBox="0 0 16 16"><use xlink:href="#chevron-open" /></svg></button>'
            })
            .on('dragEnd', function(event, item, source, destination, position) {
                var tree = $(source).nestable('serialize');
                var postTree = massageTree(tree);

                $.ajax({
                    type: "POST",
                    url: gdn.url('/vanilla/settings/categoriestree.json'),
                    data: {
                        TransientKey: gdn.getMeta('TransientKey'),
                        Subtree: JSON.stringify(postTree)
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
            var $content = $(this).closest('.dd-content');
            var $item = $(this).closest('.dd-item');
            var categoryID = $item.data('id');
            var currentDisplayAs = $('.js-displayas.selected', $item).data('displayas');

            var setUI = function ($content, displayAs) {
                displayAs = displayAs.toLowerCase();

                // Deselect the wrong menu items and select the right one.
                $('.js-displayas', $content).removeClass('selected');
                $('.js-displayas[data-displayas=' + displayAs + ']', $content).addClass('selected');

                // Change the options SVG.
                var svgMap = {categories: "nested"};
                var svg = svgMap[displayAs] || displayAs;
                svg = '<svg class="icon icon-16 icon-$name" viewBox="0 0 16 16"><use xlink:href="#' + svg + '" /></svg>';
                $('.OptionsTitle', $content).html(svg);
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
                    setUI($content, data.DisplayAs);
                }
            });
        })
    ;
})(window, jQuery, document);