/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import TruncatedText from "@library/content/TruncatedText";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { MenuBar } from "@library/MenuBar/MenuBar";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import LinkForm from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkForm";
import { linkToolbarClasses } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkToolbar.classes";
import { triggerFloatingLinkEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/triggerFloatingLinkEdit";
import { useFloatingLinkEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingLinkEdit";
import { useFloatingLinkInsert } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingLinkInsert";
import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import { unlinkRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/unlinkRichLink";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import Floating, { defaultFloatingOptions } from "@library/vanilla-editor/toolbars/Floating";
import { useMyEditorState } from "@library/vanilla-editor/typescript";
import { useVanillaEditorFocus } from "@library/vanilla-editor/VanillaEditorFocusContext";
import { arrow, focusEditor, shift } from "@udecode/plate-headless";
import { useFloatingLinkSelectors } from "@udecode/plate-link";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useRef } from "react";

const FloatingLinkInsertRoot = function (props: React.PropsWithChildren<{}>) {
    const arrowRef = useRef<HTMLDivElement | null>(null);
    const { editorRef } = useVanillaEditorFocus();

    const floatingResult = useFloatingLinkInsert({
        floatingOptions: {
            ...defaultFloatingOptions,
            middleware: [
                shift({
                    boundary: editorRef.current!,
                    padding: 14,
                }),
                ...(defaultFloatingOptions.middleware ?? []),
                arrow({ element: arrowRef }),
            ],
        },
    });

    if (!floatingResult.open) {
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
    const { editorRef } = useVanillaEditorFocus();

    const floatingResult = useFloatingLinkEdit({
        floatingOptions: {
            ...defaultFloatingOptions,
            middleware: [
                shift({
                    boundary: editorRef.current!,
                    padding: 14,
                }),
                ...(defaultFloatingOptions.middleware ?? []),
                arrow({ element: arrowRef }),
            ],
        },
    });

    if (!floatingResult.open) {
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
    const { editorRef } = useVanillaEditorFocus();

    const editor = useMyEditorState();

    const entry = queryRichLink(editor);

    if (entry?.element?.embedData?.embedType === "quote") {
        return null;
    }

    const url = entry?.element?.url;

    const classes = linkToolbarClasses();
    const classesDropDown = dropDownClasses();

    const form = (
        <div
            className={cx(
                classesDropDown.likeDropDownContent,
                classes.linkFormContainer,
                css({ maxWidth: editorRef.current?.clientWidth }),
            )}
        >
            <LinkForm />
        </div>
    );

    const editContent = !isEditing ? (
        <MenuBar className={classes.menuBar} data-testid="rich-link-menu">
            <MenuBarItem
                active={entry?.appearance === RichLinkAppearance.LINK}
                accessibleLabel={"Display as URL"}
                icon={<Icon icon="editor-link-text" />}
                onActivate={() => {
                    setRichLinkAppearance(editor, RichLinkAppearance.LINK);
                    focusEditor(editor);
                }}
            ></MenuBarItem>
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
                            <Icon icon="meta-external" className={classes.linkPreviewIcon} />
                        </a>
                    }
                />
            )}

            <MenuBarItem
                disabled={!url} // Some rich links may be files or images uploading and don't have a url yet.
                accessibleLabel={t("Edit Link")}
                icon={<Icon icon="data-pencil" />}
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
