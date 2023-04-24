/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import { ensureBuiltinEmbeds } from "@library/embeddedContent/embedService";
import { inputVariables } from "@library/forms/inputStyles";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import getStore from "@library/redux/getStore";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cx } from "@library/styles/styleShim";
import { ConversionNotice } from "@library/vanilla-editor/ConversionNotice";
import MentionToolbar from "@library/vanilla-editor/plugins/mentionPlugin/MentionToolbar";
import QuoteEmbedToolbar from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/QuoteEmbedToolbar";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { SynchronizationProvider, useSynchronizationContext } from "@library/vanilla-editor/SynchronizationContext";
import { FloatingElementToolbar } from "@library/vanilla-editor/toolbars/ElementToolbar";
import { MarkToolbar } from "@library/vanilla-editor/toolbars/MarkToolbar";
import { PersistentToolbar } from "@library/vanilla-editor/toolbars/PersistentToolbar";
import { createMyPlateEditor, MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { vanillaEditorClasses } from "@library/vanilla-editor/VanillaEditor.classes";
import { VanillaEditorPlugins } from "@library/vanilla-editor/VanillaEditor.plugins";
import { VanillaEditorBoundsContext } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { VanillaEditorFocusContext } from "@library/vanilla-editor/VanillaEditorFocusContext";
import {
    deserializeHtml as plateDeserializeHtml,
    ELEMENT_PARAGRAPH,
    focusEditor,
    getLastNodeByLevel,
    insertEmptyElement,
    Plate,
    PlateProvider,
    resetEditorChildren,
    selectEditor,
} from "@udecode/plate-headless";
import { delegateEvent, removeDelegatedEvent } from "@vanilla/dom-utils";
import { logError } from "@vanilla/utils";
import React, { useEffect, useMemo, useRef } from "react";
import { Provider } from "react-redux";
import { Path } from "slate";

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
    const store = getStore();

    return (
        <Provider store={store}>
            <SynchronizationProvider
                initialFormat={initialFormat}
                needsHtmlConversion={needsHtmlConversion}
                textArea={legacyTextArea}
            >
                <VanillaEditorImpl legacyTextArea={legacyTextArea} {...rest} />
            </SynchronizationProvider>
        </Provider>
    );
}

export function VanillaEditorImpl(props: IProps) {
    const { uploadEnabled = true, legacyTextArea } = props;
    const { syncTextArea, initialValue, showConversionNotice } = useSynchronizationContext();

    const scrollRef = useRef<HTMLDivElement>(null);

    ensureBuiltinEmbeds();

    const editor = useMemo(() => {
        return props.editor ?? createVanillaEditor();
    }, [props.editor]);

    const device = useDevice();
    const isMobile = [Devices.MOBILE, Devices.XS].includes(device);

    /**
     * Event listener so when the legacy/hidden textArea clears we clear our editor content and reset the focus on it.
     */
    useEffect(() => {
        const form = legacyTextArea?.form;
        form &&
            form.addEventListener("X-ClearCommentForm", () => {
                resetEditorChildren(editor);
                focusEditor(editor);
            });
    }, []);

    useEffect(() => {
        selectEditor(editor, { edge: "start", focus: false });
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

    return (
        <div id="vanilla-editor-root" ref={scrollRef}>
            <PlateProvider<MyValue> editor={editor} id="editor" onChange={syncTextArea} initialValue={initialValue}>
                <ConversionNotice showConversionNotice={showConversionNotice} />
                <VanillaEditorBoundsContext>
                    <EditorContainer boxShadow>
                        <VanillaEditorFocusContext>
                            <Plate<MyValue>
                                id={"editor"}
                                editor={editor}
                                editableProps={{
                                    autoFocus: false,
                                    placeholder: "Type…",
                                    scrollSelectionIntoView: () => undefined,
                                    className: cx(
                                        userContentClasses().root,
                                        vanillaEditorClasses().root({ horizontalPadding: true }),
                                    ),
                                }}
                            >
                                <MarkToolbar />
                                <MentionToolbar pluginKey="@" />
                                <QuoteEmbedToolbar />
                            </Plate>
                        </VanillaEditorFocusContext>
                        <PersistentToolbar uploadEnabled={uploadEnabled} flyoutsDirection={"above"} />
                        {!isMobile && <FloatingElementToolbar />}
                    </EditorContainer>
                </VanillaEditorBoundsContext>
            </PlateProvider>
        </div>
    );
}

/**
 * Container for the editor and its static toolbar.
 */
export function EditorContainer(props: { children: React.ReactNode; className?: string; boxShadow?: boolean }) {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const { boxShadow = false } = props;
    const baseStyles = css({
        display: "flex",
        flexDirection: "column",
        justifyContent: "space-between",
        minHeight: 200,
        borderRadius: inputVars.border.radius,
        ...(boxShadow && {
            boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(inputVars.border.color)}`,
            // Border can get clipped by inner contents so use a box shadow instead.
            ":focus-within": {
                boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
            },
        }),
    });
    return <div className={cx(baseStyles, props.className)}>{props.children}</div>;
}

/**
 * Pass this method HTML and it should return it back valid Rich2
 */
export function deserializeHtml(html: string): MyValue | undefined {
    const editor = createVanillaEditor();
    if (!html) {
        logError("html not provided");
        return;
    }
    //check if our string contains html tags, as we need to provide valid Rich2, with element type etc
    //in case this is just a text, e.g. bbcode/text/wysiwig etc formats coming as just a text from BE
    //we wrap it in paragraph so plate deserialization gives back valid structure
    const isHTML = !!html.match(/<\/?[a-z][\s\S]*>/i);
    return plateDeserializeHtml(editor, {
        element: isHTML ? html : `<p>${html}</p>`,
    }) as MyValue;
}
