/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { injectGlobal } from "@emotion/css";
import { twoColumnClasses } from "@library/layout/types/layout.twoColumns";
import { EMPTY_WIDGET_SECTION } from "@library/layout/WidgetLayout.context";
import { widgetLayoutClasses } from "@library/layout/WidgetLayout.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export function widgetLayoutCompactCSS() {
    const classes = widgetLayoutClasses();
    const panelLayoutClasses = twoColumnClasses();

    const mainBodySelectors = `
        .Content .${EMPTY_WIDGET_SECTION.widgetClass},
        .Content .DataList,
        .Content .Empty,
        .Content .DataTable,
        .Content .DataListWrap,
        .Trace,
        .Content .MessageList`;

    injectGlobal({
        [`.Frame-body .${EMPTY_WIDGET_SECTION.widgetClass}`]: classes.widgetMixin,
        [`.Frame-body .${EMPTY_WIDGET_SECTION.widgetWithContainerClass}`]: classes.widgetWithContainerMixin,

        [mainBodySelectors]: panelLayoutClasses.mainPanelWidgetMixin,

        [`.pageHeadingBox + .DataList,
          .pageHeadingBox + .Empty,
          .pageHeadingBox + .DataTable,
          .Content h2 + .${EMPTY_WIDGET_SECTION.widgetClass},
          .Content h2 + .DataList,
          .Content h2 + .DataListWrap,
          .Content h2 + .Empty,
          .Content h2 + .DataTable`]: {
            marginTop: 0,
        },

        [`.Panel .${EMPTY_WIDGET_SECTION.widgetClass}, .Panel .Box`]: panelLayoutClasses.secondaryPanelWidgetMixin,
        [`.Panel .BoxButtons`]: {
            ...Mixins.margin({
                bottom: globalVariables().spacer.panelComponent * 1.5,
            }),
        },
        [`.Frame-row .${EMPTY_WIDGET_SECTION.widgetClass}:first-child`]: {
            marginTop: 0,
        },
    });
}
