jQuery(document).ready(function($) {
    $('#save-crop').hide();

    if ($.Jcrop)
        $('#cropbox').Jcrop({
            onChange: setPreviewAndCoords,
            onSelect: setPreviewAndCoords,
            onRelease: removePreviewAndCoords,
            aspectRatio: 1
        });

    function setPreviewAndCoords(c) {
        $('#preview').show();
        $('#current-crop').hide();
        $('#save-crop').show();
        var cropSizeWidth = $('#Form_CropSizeWidth').val();
        var cropSizeHeight = $('#Form_CropSizeHeight').val();
        var sourceHeight = $('#Form_HeightSource').val();
        var sourceWidth = $('#Form_WidthSource').val();
        var rx = cropSizeWidth / c.w;
        var ry = cropSizeHeight / c.h;
        $('#Form_x').val(c.x);
        $('#Form_y').val(c.y);
        $('#Form_w').val(c.w);
        $('#Form_h').val(c.h);
        $('#preview').css({
            width: Math.round(rx * sourceWidth) + 'px',
            height: Math.round(ry * sourceHeight) + 'px',
            marginLeft: '-' + Math.round(rx * c.x) + 'px',
            marginTop: '-' + Math.round(ry * c.y) + 'px'
        });
    }

    function removePreviewAndCoords(c) {
        if ($('#current-crop')) {
            $('#current-crop').show();
            $('#preview').hide();
            $('#save-crop').hide();
        }
    }
});
