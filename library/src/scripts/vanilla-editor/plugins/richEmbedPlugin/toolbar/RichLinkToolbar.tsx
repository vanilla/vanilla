/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Floating, { defaultFloatingOptions } from "@library/vanilla-editor/toolbars/Floating";
import React, { useRef } from "react";
import { arrow, shift } from "@udecode/plate-floating";
import { css, cx } from "@emotion/css";
import { focusEditor, useHotkeys } from "@udecode/plate-common";

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Icon } from "@vanilla/icons";
import LinkForm from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkForm";
import { MenuBar } from "@library/MenuBar/MenuBar";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { TabHandler } from "@vanilla/dom-utils";
import TruncatedText from "@library/content/TruncatedText";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { getMeta } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { linkToolbarClasses } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkToolbar.classes";
import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import { t } from "@vanilla/i18n";
import { triggerFloatingLinkEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/triggerFloatingLinkEdit";
import { unlinkRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/unlinkRichLink";
import { useFloatingLinkEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingLinkEdit";
import { useFloatingLinkInsert } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingLinkInsert";
import { useFloatingLinkSelectors } from "@udecode/plate-link";
import { useMyEditorState } from "@library/vanilla-editor/getMyEditor";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { useVanillaEditorFocus } from "@library/vanilla-editor/VanillaEditorFocusContext";

const FloatingLinkInsertRoot = function (props: React.PropsWithChildren<{}>) {
    const arrowRef = useRef<HTMLDivElement | null>(null);
    const { boundsRef } = useVanillaEditorBounds();
    const { isFocusWithinEditor } = useVanillaEditorFocus();

    const floatingResult = useFloatingLinkInsert({
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

    if (!floatingResult.open || !isFocusWithinEditor) {
        return null;
    }

    return (
        <Floating ref={arrowRef} {...floatingResult}>
            {props.children}
        </Floating>
    );
};

const FloatingLinkEditRoot = function (props: React.PropsWithChildren<{}>) {
    const arrowRef = useRef<HTMLDivElement | null>(null);
    const { boundsRef } = useVanillaEditorBounds();
    const { isFocusWithinEditor } = useVanillaEditorFocus();

    const floatingResult = useFloatingLinkEdit({
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

    if (!floatingResult.open || !isFocusWithinEditor) {
        return null;
    }

    return (
        <Floating ref={arrowRef} {...floatingResult}>
            {props.children}
        </Floating>
    );
};

export default function RichLinkToolbar() {
    const isEditing = useFloatingLinkSelectors().isEditing();
    const { boundsRef } = useVanillaEditorBounds();

    const editor = useMyEditorState();

    const entry = queryRichLink(editor);

    useHotkeys(
        "ctrl+shift+k",
        (e) => {
            const linkToolbar = document.querySelector("#floatingToolbar_richLink");
            if (linkToolbar) {
                const tabHandler = new TabHandler(linkToolbar);
                tabHandler.getInitial()?.focus();
            }
        },
        {
            enabled: !isEditing,
            enableOnContentEditable: true,
        },
        [],
    );

    if (entry?.element?.embedData?.embedType === "quote") {
        return null;
    }

    const url = entry?.element?.url;

    const classes = linkToolbarClasses();
    const classesDropDown = dropDownClasses();

    const isUrlEmbedsDisabled = getMeta("disableUrlEmbeds");

    const form = (
        <div
            className={cx(
                classesDropDown.likeDropDownContent,
                classes.linkFormContainer,
                css({ maxWidth: boundsRef.current?.clientWidth }),
            )}
        >
            <LinkForm />
        </div>
    );

    const editContent = !isEditing ? (
        <MenuBar className={classes.menuBar} data-testid="rich-link-menu" id="floatingToolbar_richLink">
            <MenuBarItem
                active={entry?.appearance === RichLinkAppearance.LINK}
                accessibleLabel={"Display as Text"}
                icon={<Icon icon="editor-link-text" />}
                onActivate={() => {
                    setRichLinkAppearance(editor, RichLinkAppearance.LINK);
                    focusEditor(editor);
                }}
            ></MenuBarItem>
            <MenuBarItem
                active={entry?.appearance === RichLinkAppearance.BUTTON}
                accessibleLabel={"Display as Button"}
                icon={<Icon icon="buttons" />}
                onActivate={() => {
                    setRichLinkAppearance(editor, RichLinkAppearance.BUTTON);
                    focusEditor(editor);
                }}
            ></MenuBarItem>
            {!isUrlEmbedsDisabled && (
                <>
                    <MenuBarItem
                        active={entry?.appearance === RichLinkAppearance.INLINE}
                        accessibleLabel={"Display as Rich Link"}
                        icon={<Icon icon="editor-link-rich" />}
                        onActivate={() => {
                            setRichLinkAppearance(editor, RichLinkAppearance.INLINE);
                            focusEditor(editor);
                        }}
                    ></MenuBarItem>
                    <MenuBarItem
                        active={entry?.appearance === RichLinkAppearance.CARD}
                        accessibleLabel={"Display as Card"}
                        icon={<Icon icon="editor-link-card" />}
                        onActivate={() => {
                            setRichLinkAppearance(editor, RichLinkAppearance.CARD);
                            focusEditor(editor);
                        }}
                    ></MenuBarItem>
                </>
            )}
            <span
                role="separator"
                style={{
                    display: "block",
                    height: 16,
                    width: 1,
                    background: ColorsUtils.colorOut(globalVariables().border.color),
                    marginLeft: 4,
                    marginRight: 8,
                }}
            ></span>

            {/* Some rich links may be files or images uploading and don't have a url yet. */}
            {url != null && (
                <MenuBarItem
                    className={classes.linkPreviewMenuBarItem}
                    accessibleLabel={url ?? ""}
                    onActivate={() => window.open(url, "_blank")}
                    textContent={
                        <a
                            className={classes.linkPreview}
                            href={url}
                            target="_blank"
                            title={url}
                            aria-label={url}
                            tabIndex={-1}
                        >
                            <TruncatedText lines={1}>
                                {
                                    url.replace(/^(?:https?:\/\/)?(?:www\.)?/i, "") //removes protocol and www
                                }
                            </TruncatedText>
                            <Icon icon="meta-external-compact" className={classes.linkPreviewIcon} />
                        </a>
                    }
                />
            )}

            <MenuBarItem
                disabled={!url} // Some rich links may be files or images uploading and don't have a url yet.
                accessibleLabel={t("Edit Link")}
                icon={<Icon icon="edit" />}
                onActivate={() => triggerFloatingLinkEdit(editor)}
            />

            <MenuBarItem
                accessibleLabel={t("Remove Link")}
                icon={<Icon icon="editor-unlink" />}
                onActivate={() => {
                    unlinkRichLink(editor);
                    focusEditor(editor, editor.selection!);
                }}
            />
        </MenuBar>
    ) : (
        form
    );

    return (
        <>
            <FloatingLinkInsertRoot>{form}</FloatingLinkInsertRoot>
            <FloatingLinkEditRoot>{editContent}</FloatingLinkEditRoot>
        </>
    );
}
