/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { userContentClasses } from "@library/content/UserContent.styles";
import { ensureBuiltinEmbeds } from "@library/embeddedContent/embedService";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import getStore from "@library/redux/getStore";
import { cx } from "@library/styles/styleShim";
import { useUniqueID } from "@library/utility/idUtils";
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
import { VanillaEditorContainer } from "./VanillaEditorContainer";

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
    /** Should we include upload image/file buttons in editor menu bar */
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
    /** Optionally set onchange to easily emit the value */
    onChange?: (value: MyValue) => void;
    /** Optionally set initial content */
    initialContent?: MyValue;
}

export function createVanillaEditor(options?: { initialValue?: MyEditor; id?: string }) {
    return createMyPlateEditor({
        id: options?.id,
        plugins: VanillaEditorPlugins,
        editor: options?.initialValue,
    });
}

export function LegacyVanillaEditor(props: IProps) {
    const { legacyTextArea, initialFormat, needsHtmlConversion, ...rest } = props;
    const store = getStore();

    return (
        <Provider store={store}>
            <SynchronizationProvider
                initialFormat={initialFormat}
                needsHtmlConversion={needsHtmlConversion}
                textArea={legacyTextArea}
            >
                <VanillaEditorLoadable legacyTextArea={legacyTextArea} {...rest} />
            </SynchronizationProvider>
        </Provider>
    );
}

export function VanillaEditorLoadable(props: IProps) {
    const { uploadEnabled = true, legacyTextArea } = props;
    const { syncTextArea, initialValue, showConversionNotice } = useSynchronizationContext();

    const scrollRef = useRef<HTMLDivElement>(null);

    ensureBuiltinEmbeds();

    const editorID = useUniqueID("editor");

    const editor = useMemo(() => {
        return props.editor ?? createVanillaEditor({ id: editorID });
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
            <PlateProvider<MyValue>
                id={editorID}
                editor={editor}
                onChange={(value: MyValue) => {
                    syncTextArea(value);
                    if (props.onChange) {
                        props.onChange(value);
                    }
                }}
                initialValue={props.initialContent ? props.initialContent : initialValue}
            >
                <ConversionNotice showConversionNotice={showConversionNotice} />
                <VanillaEditorBoundsContext>
                    <VanillaEditorContainer boxShadow>
                        <VanillaEditorFocusContext>
                            <Plate<MyValue>
                                id={editorID}
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
                    </VanillaEditorContainer>
                </VanillaEditorBoundsContext>
            </PlateProvider>
        </div>
    );
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
