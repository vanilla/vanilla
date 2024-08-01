/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MenuBar } from "@library/MenuBar/MenuBar";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { BoldIcon, CodeIcon, ItalicIcon, StrikeIcon } from "@library/icons/editorIcons";
import { t } from "@library/utility/appUtils";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import Floating, { defaultFloatingOptions } from "@library/vanilla-editor/toolbars/Floating";
import { useFloatingMarkToolbar } from "@library/vanilla-editor/toolbars/useFloatingMarkToolbar";
import { useMyEditorState } from "@library/vanilla-editor/typescript";
import { MARK_BOLD, MARK_CODE, MARK_ITALIC, MARK_STRIKETHROUGH } from "@udecode/plate-basic-marks";
import { focusEditor, getPluginType, isMarkActive, someNode, toggleMark } from "@udecode/plate-common";
import { arrow, shift } from "@udecode/plate-floating";
import { ELEMENT_LINK, triggerFloatingLink, unwrapLink, useFloatingLinkSelectors } from "@udecode/plate-link";
import { Icon } from "@vanilla/icons";
import React, { useRef } from "react";

/**
 * Toolbar for applying "marks" to text. These are inline formats like bold, italic, etc.
 *
 * - Multiple can be applied at the same time.
 * - Toolbar appears when there is a text selection spanning multiple characters.
 * - Has some notable interactions with the link toolbar.
 */
export const MarkToolbar = () => {
    const editor = useMyEditorState();
    const arrowRef = useRef<HTMLDivElement | null>(null);
    const isLink = !!editor?.selection && someNode(editor, { match: { type: getPluginType(editor, ELEMENT_LINK) } });

    const floatingLinkMode = useFloatingLinkSelectors().mode();
    const linkToolbarIsOpen =
        useFloatingLinkSelectors().isOpen(editor.id) && ["insert", "edit"].includes(floatingLinkMode);

    const { boundsRef } = useVanillaEditorBounds();

    const floatingResult = useFloatingMarkToolbar({
        floatingOptions: {
            ...defaultFloatingOptions,
            middleware: [
                shift({
                    boundary: boundsRef.current!,
                    padding: 14,
                }),
                ...(defaultFloatingOptions.middleware ?? []),
                arrow({ element: arrowRef }),
            ],
        },
    });

    return !linkToolbarIsOpen ? (
        <Floating ref={arrowRef} {...floatingResult}>
            <MenuBar>
                <MenuBarItem
                    accessibleLabel={t("Format as Bold")}
                    icon={<BoldIcon />}
                    active={isMarkActive(editor, MARK_BOLD)}
                    onActivate={() => {
                        focusEditor(editor);
                        toggleMark(editor, {
                            key: MARK_BOLD,
                        });
                    }}
                />
                <MenuBarItem
                    accessibleLabel={t("Format as Italic")}
                    icon={<ItalicIcon />}
                    active={isMarkActive(editor, MARK_ITALIC)}
                    onActivate={() => {
                        focusEditor(editor);
                        toggleMark(editor, {
                            key: MARK_ITALIC,
                        });
                    }}
                />
                <MenuBarItem
                    accessibleLabel={t("Format as Strikethrough")}
                    icon={<StrikeIcon />}
                    active={isMarkActive(editor, MARK_STRIKETHROUGH)}
                    onActivate={() => {
                        focusEditor(editor);
                        toggleMark(editor, {
                            key: MARK_STRIKETHROUGH,
                        });
                    }}
                />
                <MenuBarItem
                    accessibleLabel={t("Format as Inline Code")}
                    icon={<CodeIcon />}
                    active={isMarkActive(editor, MARK_CODE)}
                    onActivate={() => {
                        focusEditor(editor);
                        toggleMark(editor, {
                            key: MARK_CODE,
                        });
                    }}
                />
                <MenuBarItem
                    accessibleLabel={isLink ? t("Remove link") : t("Format as link")}
                    icon={<Icon icon={"editor-link"} />}
                    active={isLink}
                    onActivate={() => {
                        if (isLink) {
                            unwrapLink(editor);
                            focusEditor(editor, editor.selection!);
                        } else {
                            setTimeout(() => {
                                triggerFloatingLink(editor, { focused: true });
                                focusEditor(editor);
                            }, 0);
                        }
                    }}
                />
            </MenuBar>
        </Floating>
    ) : null;
};
