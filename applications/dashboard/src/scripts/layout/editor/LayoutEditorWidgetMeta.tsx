/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import type { ILayoutEditorPath, ILayoutEditorWidgetPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx, css } from "@emotion/css";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/styleUtils";

export function LayoutEditorWidgetMeta(props: { widgetPath: ILayoutEditorPath | null }) {
    const { widgetPath } = props;
    const { editorContents, layoutViewType } = useLayoutEditor();
    const catalog = useLayoutCatalog(layoutViewType);
    const widgetSpec = widgetPath
        ? editorContents.getWidget(widgetPath) ?? editorContents.getSection(widgetPath)
        : null;
    const { $hydrate, ...rest } = widgetSpec ?? {};
    const widget = $hydrate
        ? catalog?.assets[$hydrate] ?? catalog?.widgets[$hydrate] ?? catalog?.sections[$hydrate]
        : undefined;
    const classes = widgetMetaClasses.useAsHook();
    // Don't render icons if we don't have an SVG for it
    const iconUrl = widget?.iconUrl && widget?.iconUrl.includes(".svg") ? widget.iconUrl : undefined;

    return (
        <div className={classes.root}>
            {iconUrl && <img src={iconUrl} className={classes.image} />}
            <div className={classes.widgetType}>{widget?.name}</div>
        </div>
    );
}

const widgetMetaClasses = useThemeCache(() => ({
    root: css({
        display: "flex",
        alignItems: "center",
        gap: 12,
    }),
    image: css({
        maxHeight: 48,
        width: "auto",
        height: "100%",
        border: singleBorder(),
        borderRadius: 4,
    }),
    widgetType: css({
        whiteSpace: "nowrap",
    }),
}));
