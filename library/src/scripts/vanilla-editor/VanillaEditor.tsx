/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import { ensureBuiltinEmbeds } from "@library/embeddedContent/embedService";
import { inputVariables } from "@library/forms/inputStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cx } from "@library/styles/styleShim";
import { ConversionNotice } from "@library/vanilla-editor/ConversionNotice";
import MentionToolbar from "@library/vanilla-editor/plugins/mentionPlugin/MentionToolbar";
import { ElementToolbar } from "@library/vanilla-editor/toolbars/ElementToolbar";
import { MarkToolbar } from "@library/vanilla-editor/toolbars/MarkToolbar";
import QuoteEmbedToolbar from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/QuoteEmbedToolbar";
import { PersistentToolbar } from "@library/vanilla-editor/toolbars/PersistentToolbar";
import { SynchronizationProvider, useSynchronizationContext } from "@library/vanilla-editor/SynchronizationContext";
import { createMyPlateEditor, MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { vanillaEditorClasses } from "@library/vanilla-editor/VanillaEditor.classes";
import { VanillaEditorPlugins } from "@library/vanilla-editor/VanillaEditor.plugins";
import { useVanillaEditorFocus, VanillaEditorFocusContext } from "@library/vanilla-editor/VanillaEditorFocusContext";
import {
    ELEMENT_PARAGRAPH,
    focusEditor,
    getLastNodeByLevel,
    getStartPoint,
    insertEmptyElement,
    Plate,
    PlateProvider,
    select,
    useEditorState,
} from "@udecode/plate-headless";
import React, { useState, useEffect, useMemo, useRef } from "react";
import { Path } from "slate";
import { delegateEvent, removeDelegatedEvent } from "@vanilla/dom-utils";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

/**
 * @todo
 *
 * - Mentions (There's a mostly wired up builtin example. Needs to preserve the @character though and search with our API + draw search UI.)
 * - Table UI.
 */

/**
 * @note USEFUL LINKS
 *
 * SlateJS docs https://docs.slatejs.org/
 * SlateJS examples https://www.slatejs.org/examples/richtext
 * SlateJS slack instance https://slate-slack.herokuapp.com/
 * Platejs docs https://plate.udecode.io/docs/
 * Platejs source (Monorepo packages) https://github.com/udecode/plate/tree/main/packages
 * Full platejs sandbox https://codesandbox.io/s/github/udecode/plate-playground
 */

interface IProps {
    uploadEnabled?: boolean;
    /** Should the content be converted before attempting to render */
    needsHtmlConversion?: boolean;
    /** The content to be edited */
    legacyTextArea?: HTMLInputElement;
    /** Use a particular editor instance. */
    editor?: MyEditor;
    /** HTML that will be loaded into the editor on initial load. */
    initialHtml?: string;
    /** The format of the initial textarea content */
    initialFormat?: string;
}

export function createVanillaEditor(initialValue?: MyEditor) {
    return createMyPlateEditor({
        plugins: VanillaEditorPlugins,
        editor: initialValue,
    });
}

export function VanillaEditor(props: IProps) {
    const { legacyTextArea, initialFormat, needsHtmlConversion, ...rest } = props;
    return (
        <SynchronizationProvider
            initialFormat={initialFormat}
            needsHtmlConversion={needsHtmlConversion}
            textArea={legacyTextArea}
        >
            <VanillaEditorImpl {...rest} />
        </SynchronizationProvider>
    );
}

function VanillaEditorImpl(props: IProps) {
    const { uploadEnabled = true } = props;
    const { syncTextArea, initialValue, initialHTML } = useSynchronizationContext();

    const [isFormatConverted, setFormatConverted] = useState(false);

    const store = getStore();
    const scrollRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        ensureBuiltinEmbeds();
    });

    const editor = useMemo(() => {
        return props.editor ?? createVanillaEditor();
    }, [props.editor]);

    useEffect(() => {
        const point = getStartPoint(editor, [0]);
        select(editor, point);
    }, [editor]);

    /**
     * Handler for clicking on quote button.
     */
    useEffect(() => {
        const handleQuoteButtonClick = (event: MouseEvent, triggeringElement: Element) => {
            event.preventDefault();
            if (!editor) {
                return;
            }

            const url = triggeringElement.getAttribute("data-scrape-url") || "";
            insertRichEmbed(editor, url, RichLinkAppearance.CARD);
            if (scrollRef.current) {
                window.scrollTo({
                    top: scrollRef.current.getBoundingClientRect().y + window.scrollY - 130,
                    behavior: "smooth",
                });
            }

            const lastNode = getLastNodeByLevel(editor, 1);
            if (!lastNode) {
                return;
            }
            const newSelection = Path.next(lastNode[1]);
            insertEmptyElement(editor, ELEMENT_PARAGRAPH, { at: newSelection, select: true });
            focusEditor(editor);
        };

        const delegatedHandler = delegateEvent("click", ".js-quoteButton", handleQuoteButtonClick);
        return () => {
            removeDelegatedEvent(delegatedHandler);
        };
    }, [editor]);

    useEffect(() => {
        if (!initialHTML) {
            return;
        }
        setFormatConverted(true);
        const dataTransfer = new DataTransfer();
        dataTransfer.setData("text/html", initialHTML);
        editor.insertData(dataTransfer);
    }, [editor, initialHTML]);

    return (
        <Provider store={store}>
            <div id="vanilla-editor-root" ref={scrollRef}>
                <PlateProvider<MyValue> editor={editor} id="editor" onChange={syncTextArea} initialValue={initialValue}>
                    <ConversionNotice showConversionNotice={isFormatConverted} />
                    <VanillaEditorFocusContext>
                        <EditorContainer>
                            <Plate<MyValue>
                                id={"editor"}
                                editor={editor}
                                editableProps={{
                                    spellCheck: false,
                                    autoFocus: false,
                                    placeholder: "Type…",
                                    scrollSelectionIntoView: () => undefined,
                                    className: cx(userContentClasses().root, vanillaEditorClasses().root),
                                }}
                            >
                                <MarkToolbar />
                                <MentionToolbar pluginKey="@" />
                                <EditorSpacer />
                                <QuoteEmbedToolbar />
                            </Plate>
                            <ElementToolbar />
                            <PersistentToolbar uploadEnabled={uploadEnabled} />
                        </EditorContainer>
                    </VanillaEditorFocusContext>
                </PlateProvider>
            </div>
        </Provider>
    );
}

/**
 * Spacer element to fill up to the min-height.
 *
 * When clicked, it indicates the document isn't tall enough, so we insert an empty break into the editor
 * and move the focus to it.
 */
export function EditorSpacer() {
    const editor = useEditorState();
    return (
        <span
            onClick={(e) => {
                e.preventDefault();

                const lastNode = getLastNodeByLevel(editor, 0);
                if (!lastNode) {
                    return;
                }
                const newSelection = Path.next(lastNode[1]);
                insertEmptyElement(editor, ELEMENT_PARAGRAPH, { at: newSelection, select: true });
                focusEditor(editor);
            }}
            style={{ flex: "1 0 auto" }}
            id="editor-spacer"
        ></span>
    );
}

/**
 * Container for the editor and it's static toolbar.
 */
export function EditorContainer(props: { children: React.ReactNode; className?: string }) {
    const focusContext = useVanillaEditorFocus();
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const baseStyles = css({
        // Border can get clipped by inner contents so use a box shadow instead.
        display: "flex",
        // alignItems: "/",
        flexDirection: "column",
        minHeight: 200,
        borderRadius: inputVars.border.radius,
        boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(
            focusContext.isFocusWithinEditor ? globalVars.mainColors.primary : inputVars.border.color,
        )}`,
    });
    return <div className={cx(baseStyles, props.className)}>{props.children}</div>;
}
