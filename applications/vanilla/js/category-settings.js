(function(window, $, document) {
    $(document)
        .on('contentLoad', function(e) {
            $('.dd', e.target).nestable({
                expandBtnHTML: '<button data-action="expand"><svg class="icon icon-16 icon-chevron-closed" viewBox="0 0 16 16"><use xlink:href="#chevron-closed" /></svg></button>',
                collapseBtnHTML: '<button data-action="collapse"><svg class="icon icon-16 icon-chevron-open" viewBox="0 0 16 16"><use xlink:href="#chevron-open" /></svg></button>'
            });

            console.log($('.dd', e.target).nestable('serialize'));
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
                    gdn.informError(xhr, draft);
                },
                success: function (data) {
                    setUI($content, data.DisplayAs);
                }
            });
        })
    ;
})(window, jQuery, document);