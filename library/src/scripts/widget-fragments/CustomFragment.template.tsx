// These custom props are configured by administrators when configuring the widget.
// See the "Fragment Form" tab to configure the props & form.
import Custom from "@vanilla/injectables/CustomFragment";
import Utils from "@vanilla/injectables/Utils";
import Components from "@vanilla/injectables/Components";
import React from "react";
import Api from "@vanilla/injectables/Api";

/**
 * Make your own custom widget.
 *
 * The props passed in will be based on the custom form you create for the fragment.
 * Your community managers can then place your widget anywhere they like and configure it using Layout Editor.
 */
export default function CustomFragment(props: Custom.Props) {
    return (
        <Components.LayoutWidget
            // If 2 widgets are next to each other with interWidgetSpacing set to "none",
            // The layout will not add additional space will be put between widgets.
            // This is useful when you want to have widgets side by side without any space in between.
            // Ideal for banners and full width elements with background colors or images and their own spacing.
            interWidgetSpacing="standard"
        >
            Custom Fragment
        </Components.LayoutWidget>
    );
}
