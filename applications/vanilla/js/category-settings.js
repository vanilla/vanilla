(function(window, $, document) {
    $(document).on('contentLoad', function (e) {
        $('.dd', e.target).nestable({
            expandBtnHTML: '<button data-action="expand"><svg class="icon icon-16 icon-chevron-closed" viewBox="0 0 16 16"><use xlink:href="#chevron-closed" /></svg></button>',
            collapseBtnHTML: '<button data-action="collapse"><svg class="icon icon-16 icon-chevron-open" viewBox="0 0 16 16"><use xlink:href="#chevron-open" /></svg></button>'
        });

        console.log($('.dd', e.target).nestable('serialize'));
    });
})(window, jQuery, document);