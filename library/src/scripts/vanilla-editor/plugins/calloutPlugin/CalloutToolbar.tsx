/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MenuBar } from "@library/MenuBar/MenuBar";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { t } from "@library/utility/appUtils";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { useMyEditorState } from "@library/vanilla-editor/getMyEditor";
import { focusEditor, getBlockAbove, setNodes, toDOMNode, withoutNormalizing } from "@udecode/plate-common";
import { Icon } from "@vanilla/icons";
import { MenuBarItemSeparator } from "@library/MenuBar/MenuBarItemSeparator";
import { CalloutAppearance, ELEMENT_CALLOUT } from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";

/**
 * Toolbar for applying different appearances/types - info, warning, alert, neutral, for callouts.
 */
export const CalloutToolbar = () => {
    const editor = useMyEditorState();

    const { boundsRef: editorBounds } = useVanillaEditorBounds();

    const calloutEntry = getBlockAbove(editor, {
        match: {
            type: ELEMENT_CALLOUT,
        },
    });

    const calloutNode = calloutEntry?.[0];

    const calloutAsDomNode = calloutNode && toDOMNode(editor, calloutEntry?.[0]);
    const calloutRect = calloutAsDomNode?.getBoundingClientRect();

    const updateCalloutAppearance = (appearance: CalloutAppearance) => {
        withoutNormalizing(editor, () => {
            setNodes(editor, { appearance }, { at: calloutEntry?.[1] });
            focusEditor(editor);
        });
    };

    if (!calloutRect) {
        return null;
    }

    return (
        <div
            style={{
                position: "absolute",
                zIndex: 2,
                // below the callout
                ...{
                    top:
                        calloutRect.top -
                        (editorBounds?.current?.getBoundingClientRect().top || 0) +
                        calloutRect.height -
                        3,
                    left: calloutRect?.width / 2 - 72,
                },
            }}
        >
            <MenuBar>
                <MenuBarItem
                    active={calloutNode?.appearance === "neutral"}
                    accessibleLabel={t("Neutral")}
                    icon={<Icon icon="callout-neutral" />}
                    onActivate={() => {
                        updateCalloutAppearance("neutral");
                    }}
                />
                <MenuBarItemSeparator />
                <MenuBarItem
                    active={calloutNode?.appearance === "info"}
                    accessibleLabel={t("Info")}
                    icon={<Icon icon="callout-info" />}
                    onActivate={() => {
                        updateCalloutAppearance("info");
                    }}
                />
                <MenuBarItemSeparator />
                <MenuBarItem
                    active={calloutNode?.appearance === "warning"}
                    accessibleLabel={t("Warning")}
                    icon={<Icon icon="callout-warning" />}
                    onActivate={() => {
                        updateCalloutAppearance("warning");
                    }}
                />
                <MenuBarItemSeparator />
                <MenuBarItem
                    active={calloutNode?.appearance === "alert"}
                    accessibleLabel={t("Alert")}
                    icon={<Icon icon="callout-alert" />}
                    onActivate={() => {
                        updateCalloutAppearance("alert");
                    }}
                />
            </MenuBar>
        </div>
    );
};
