var crop = {

    element: null,

    start: function(element) {
        crop.element = element;
        $('#save-crop', crop.element).hide();
        if ($.Jcrop) {
            // Set jcrop settings on source image.
            $('#cropbox', crop.element).Jcrop({
                onChange: crop.setPreviewAndCoords,
                onSelect: crop.setPreviewAndCoords,
                onRelease: crop.removePreviewAndCoords,
                aspectRatio: 1
            });
        }
    },

    /**
     * Changes the preview cropped image based on the crop parameters and updates the form values.
     *
     * @param c The source image crop.element to crop.
     */
    setPreviewAndCoords: function (c) {
        $(document).trigger('cropStart');

        // Show the preview image and the 'save' button and hide the current cropped image.
        $('#preview', crop.element).show();
        $('#current-crop', crop.element).hide();
        $('#save-crop', crop.element).show();

        // Get constants from the form's hidden inputs.
        var cropSizeWidth = $('#Form_CropSizeWidth', crop.element).val();
        var cropSizeHeight = $('#Form_CropSizeHeight', crop.element).val();
        var sourceHeight = $('#Form_HeightSource', crop.element).val();
        var sourceWidth = $('#Form_WidthSource', crop.element).val();

        // Set the ratios for scaling.
        var rx = cropSizeWidth / c.w;
        var ry = cropSizeHeight / c.h;

        // Set the form's hidden inputs based on the crop's position and size.
        $('#Form_x', crop.element).val(c.x);
        $('#Form_y', crop.element).val(c.y);
        $('#Form_w', crop.element).val(c.w);
        $('#Form_h', crop.element).val(c.h);

        // Calculate the preview image's css rules.
        $('#preview').css({
            width: Math.round(rx * sourceWidth) + 'px',
            height: Math.round(ry * sourceHeight) + 'px',
            marginLeft: '-' + Math.round(rx * c.x) + 'px',
            marginTop: '-' + Math.round(ry * c.y) + 'px'
        });
    },

    /**
     * Hides the cropping preview and save button and shows the current cropped image.
     */
    removePreviewAndCoords: function() {
        $(document).trigger('cropEnd');
        if ($('#current-crop', crop.element)) {
            $('#current-crop', crop.element).show();
            $('#preview', crop.element).hide();
            $('#save-crop', crop.element).hide();
        }
    }

}


$(document).on('contentLoad', function(e) {
    if ($('#cropbox', e.target).length === 0) {
        return;
    }
    crop.start(e.target);
});
