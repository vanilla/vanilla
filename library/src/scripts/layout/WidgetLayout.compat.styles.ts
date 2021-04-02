/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { injectGlobal } from "@emotion/css";
import { twoColumnLayoutClasses } from "@library/layout/types/layout.twoColumns";
import { EMPTY_WIDGET_LAYOUT } from "@library/layout/WidgetLayout.context";
import { widgetLayoutClasses } from "@library/layout/WidgetLayout.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export function widgetLayoutCompactCSS() {
    const classes = widgetLayoutClasses();
    const panelLayoutClasses = twoColumnLayoutClasses();

    const mainBodySelectors = `
        .Content .${EMPTY_WIDGET_LAYOUT.widgetClass},
        .Content .DataList,
        .Content .Empty,
        .Content .DataTable,
        .Trace,
        .Content .MessageList`;

    injectGlobal({
        [`.Frame-body .${EMPTY_WIDGET_LAYOUT.widgetClass}`]: classes.widgetMixin,
        [`.Frame-body .${EMPTY_WIDGET_LAYOUT.widgetWithContainerClass}`]: classes.widgetWithContainerMixin,

        [`.Content .${EMPTY_WIDGET_LAYOUT.widgetClass},
          .Content .DataList,
          .Content .Empty,
          .Content .DataTable,
          .Trace,
          .Content .MessageList
        `]: panelLayoutClasses.mainPanelWidgetMixin,

        [`.pageHeadingBox + .DataList,
          .pageHeadingBox + .Empty,
          .pageHeadingBox + .DataTable,
          .Content h2 + .${EMPTY_WIDGET_LAYOUT.widgetClass},
          .Content h2 + .DataList,
          .Content h2 + .Empty,
          .Content h2 + .DataTable`]: {
            marginTop: 0,
        },

        [`.Panel .${EMPTY_WIDGET_LAYOUT.widgetClass}, .Panel .Box`]: panelLayoutClasses.secondaryPanelWidgetMixin,
        [`.Panel .BoxButtons`]: {
            ...Mixins.margin({
                bottom: globalVariables().spacer.panelComponent * 1.5,
            }),
        },
        [`.Frame-row .${EMPTY_WIDGET_LAYOUT.widgetClass}:first-child`]: {
            marginTop: 0,
        },
    });
}
