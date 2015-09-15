jQuery(document).ready(function($) {
    $('#save-crop').hide();

    if ($.Jcrop) {
        // Set jcrop settings on source image.
        $('#cropbox').Jcrop({
            onChange: setPreviewAndCoords,
            onSelect: setPreviewAndCoords,
            onRelease: removePreviewAndCoords,
            aspectRatio: 1
        });
    }

    /**
     * Changes the preview cropped image based on the crop parameters and updates the form values.
     *
     * @param c The source image element to crop.
     */
    function setPreviewAndCoords(c) {
        // Show the preview image and the 'save' button and hide the current cropped image.
        $('#preview').show();
        $('#current-crop').hide();
        $('#save-crop').show();

        // Get constants from the form's hidden inputs.
        var cropSizeWidth = $('#Form_CropSizeWidth').val();
        var cropSizeHeight = $('#Form_CropSizeHeight').val();
        var sourceHeight = $('#Form_HeightSource').val();
        var sourceWidth = $('#Form_WidthSource').val();

        // Set the ratios for scaling.
        var rx = cropSizeWidth / c.w;
        var ry = cropSizeHeight / c.h;

        // Set the form's hidden inputs based on the crop's position and size.
        $('#Form_x').val(c.x);
        $('#Form_y').val(c.y);
        $('#Form_w').val(c.w);
        $('#Form_h').val(c.h);

        // Calculate the preview image's css rules.
        $('#preview').css({
            width: Math.round(rx * sourceWidth) + 'px',
            height: Math.round(ry * sourceHeight) + 'px',
            marginLeft: '-' + Math.round(rx * c.x) + 'px',
            marginTop: '-' + Math.round(ry * c.y) + 'px'
        });
    }

    /**
     * Hides the cropping preview and save button and shows the current cropped image.
     */
    function removePreviewAndCoords() {
        if ($('#current-crop')) {
            $('#current-crop').show();
            $('#preview').hide();
            $('#save-crop').hide();
        }
    }
});
