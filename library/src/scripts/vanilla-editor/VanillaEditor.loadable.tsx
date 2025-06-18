/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { userContentClasses } from "@library/content/UserContent.styles";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import getStore from "@library/redux/getStore";
import { cx } from "@library/styles/styleShim";
import { useUniqueID } from "@library/utility/idUtils";
import { ConversionNotice } from "@library/vanilla-editor/ConversionNotice";
import { SynchronizationProvider, useSynchronizationContext } from "@library/vanilla-editor/SynchronizationContext";
import { vanillaEditorClasses } from "@library/vanilla-editor/VanillaEditor.classes";
import { VanillaEditorPlugins } from "@library/vanilla-editor/VanillaEditor.plugins";
import { VanillaEditorBoundsContext } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { VanillaEditorFocusContext } from "@library/vanilla-editor/VanillaEditorFocusContext";
import VanillaEditorPlaceholder from "@library/vanilla-editor/VanillaEditorPlaceholder";
import MentionToolbar from "@library/vanilla-editor/plugins/mentionPlugin/MentionToolbar";
import QuoteEmbedToolbar from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/QuoteEmbedToolbar";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { FloatingElementToolbar } from "@library/vanilla-editor/toolbars/ElementToolbar";
import { MarkToolbar } from "@library/vanilla-editor/toolbars/MarkToolbar";
import { PersistentToolbar } from "@library/vanilla-editor/toolbars/PersistentToolbar";
import { MyEditor, MyValue, type IVanillaEditorRef } from "@library/vanilla-editor/typescript";
import { createMyPlateEditor } from "./getMyEditor";
import {
    Plate,
    PlateProvider,
    focusEditor,
    getLastNodeByLevel,
    insertEmptyElement,
    deserializeHtml as plateDeserializeHtml,
    resetEditorChildren,
    selectEditor,
} from "@udecode/plate-common";
import { ELEMENT_LINK } from "@udecode/plate-link";
import { ELEMENT_PARAGRAPH } from "@udecode/plate-paragraph";
import { delegateEvent, removeDelegatedEvent } from "@vanilla/dom-utils";
import { logError } from "@vanilla/utils";
import React, { forwardRef, useEffect, useImperativeHandle, useMemo, useRef } from "react";
import { Provider } from "react-redux";
import { Path } from "slate";
import { VanillaEditorContainer } from "@library/vanilla-editor/VanillaEditorContainer";
import { isMyValue } from "@library/vanilla-editor/utils/isMyValue";
import { useIsInModal } from "@library/modal/Modal.context";
import { TableToolbar } from "@library/vanilla-editor/plugins/tablePlugin/toolbar/TableToolbar";
import { VanillaEditorTableProvider } from "@library/vanilla-editor/VanillaEditorTableContext";
import { getMeta, t } from "@library/utility/appUtils";
import classNames from "classnames";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import {
    cleanTableRowspanColspan,
    needsInitialNormalizationForTables,
    needsRowspanColspanCleaning,
} from "@library/vanilla-editor/plugins/tablePlugin/tableUtils";
import { ensureBuiltinEmbedsSync } from "@library/embeddedContent/embedService.loadable";

const userMentionsEnabled: boolean = getMeta("ui.userMentionsEnabled", true);

const isRichTableEnabled = getMeta("featureFlags.RichTable.Enabled", false);

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

export interface IVanillaEditorProps {
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
    initialContent?: MyValue | string;
    /** Optionally force mobile so toolbar is not on left side */
    isMobile?: boolean;
    /** Show a static message for the layout editor instead of an editable field */
    isPreview?: boolean;
    /** Any react node which need to appear within the editor bounds */
    inEditorContent?: React.ReactNode;
    autoFocus?: boolean;

    editorRef?: React.RefObject<MyEditor>;
    showConversionNotice?: boolean;
    onBlur?: React.TextareaHTMLAttributes<HTMLDivElement>["onBlur"];
    containerClasses?: string;
    editorClasses?: string;
}

export function createVanillaEditor(options?: { initialValue?: MyEditor; id?: string }) {
    return createMyPlateEditor({
        id: options?.id,
        plugins: VanillaEditorPlugins,
        editor: options?.initialValue,
    });
}

export function LegacyFormVanillaEditorLoadable(props: IVanillaEditorProps) {
    const { legacyTextArea, initialFormat, needsHtmlConversion, containerClasses, ...rest } = props;
    const store = getStore();

    return (
        <Provider store={store}>
            <SynchronizationProvider
                initialFormat={initialFormat}
                needsHtmlConversion={needsHtmlConversion}
                textArea={legacyTextArea}
            >
                <VanillaEditorLoadable
                    legacyTextArea={legacyTextArea}
                    containerClasses={classNames(containerClasses, "is-legacy")}
                    {...rest}
                />
            </SynchronizationProvider>
        </Provider>
    );
}

export const VanillaEditorLoadable = forwardRef(function VanillaEditorLoadable(
    props: IVanillaEditorProps,
    ref: React.RefObject<IVanillaEditorRef>,
) {
    const { uploadEnabled = true, legacyTextArea, inEditorContent } = props;
    const syncContext = useSynchronizationContext();
    const { syncTextArea, initialValue } = syncContext;
    const showConversionNotice = props.showConversionNotice ?? syncContext.showConversionNotice;

    const scrollRef = useRef<HTMLDivElement>(null);

    const editorID = useUniqueID("editor");

    const editor = useMemo(() => {
        return props.editor ?? createVanillaEditor({ id: editorID });
    }, [props.editor]);
    useImperativeHandle(props.editorRef, () => editor, [editor]);

    useImperativeHandle(ref, () => ({
        focusEditor: () => {
            focusEditor(editor);
        },
    }));

    const isInModal = useIsInModal();
    const device = useDevice();
    const isMobile = props.isMobile ?? [Devices.MOBILE, Devices.XS].includes(device);

    const followMobileRenderingRules = isInModal || isMobile;

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

    const ensureMyValue = (value: unknown): MyValue | undefined => {
        if (typeof value === "string") {
            try {
                // If the string can be parsed and is myValue, return it.
                const transformedValue = JSON.parse(value);
                if (isMyValue(transformedValue)) {
                    return transformedValue;
                }
            } catch (error) {
                // If the string can't be parsed, assume its already HTML or plain text
                return deserializeHtml(value);
            }
        } else {
            if (isMyValue(value as any)) {
                return value as MyValue;
            }
        }
    };

    const initValue = useMemo(() => {
        const value = props.initialContent ? ensureMyValue(props.initialContent) : initialValue;

        // we need to run normalizers for initial tables manually for backwords compatibility
        if (needsInitialNormalizationForTables(editor, value) && needsRowspanColspanCleaning(value as MyValue)) {
            return cleanTableRowspanColspan(editor, value as MyValue) as MyValue;
        }
        return value;
    }, [props.initialContent, initialValue]);

    const classesUserContent = userContentClasses.useAsHook();
    const classesEditor = vanillaEditorClasses.useAsHook();

    return (
        <div id="vanilla-editor-root" ref={scrollRef} data-testid={"vanilla-editor"}>
            <PlateProvider<MyValue>
                id={editorID}
                editor={editor}
                onChange={(value: MyValue) => {
                    // temporary fix to email links until Plate version is updated
                    const newValue = emailLinkCheck(value);
                    syncTextArea(newValue);
                    if (props.onChange) {
                        props.onChange(newValue);
                    }
                }}
                initialValue={initValue}
                normalizeInitialValue={needsInitialNormalizationForTables(editor, initValue)}
            >
                <ConversionNotice showConversionNotice={showConversionNotice} />
                <VanillaEditorBoundsContext>
                    <VanillaEditorContainer boxShadow className={props.containerClasses}>
                        {props?.isPreview ? (
                            <div
                                className={cx(classesUserContent.root, classesEditor.root({ horizontalPadding: true }))}
                            >
                                {t("This is a preview and cannot be edited.")}
                            </div>
                        ) : (
                            <VanillaEditorFocusContext>
                                <ConditionalWrap component={VanillaEditorTableProvider} condition={isRichTableEnabled}>
                                    <Plate<MyValue>
                                        id={editorID}
                                        editor={editor}
                                        editableProps={{
                                            onBlur: props.onBlur,
                                            autoFocus: props.autoFocus,
                                            className: cx(
                                                classesUserContent.root,
                                                classesEditor.root({ horizontalPadding: true }),
                                                props.editorClasses,
                                            ),
                                            "aria-label": t(
                                                "To access the paragraph format menu, press control, shift, and P. To access the text format menu, press control, shift, and I. Use the arrow keys to navigate in each menu.",
                                            ),
                                        }}
                                    >
                                        <VanillaEditorPlaceholder />
                                        <MarkToolbar />
                                        {userMentionsEnabled && <MentionToolbar pluginKey="@" />}
                                        <QuoteEmbedToolbar />
                                        {isRichTableEnabled && <TableToolbar />}
                                    </Plate>
                                </ConditionalWrap>
                            </VanillaEditorFocusContext>
                        )}
                        <div className={classesEditor.footer}>
                            {!followMobileRenderingRules && <FloatingElementToolbar />}
                            <PersistentToolbar
                                uploadEnabled={uploadEnabled}
                                flyoutsDirection={"above"}
                                isMobile={followMobileRenderingRules}
                            />
                            {inEditorContent}
                        </div>
                    </VanillaEditorContainer>
                </VanillaEditorBoundsContext>
            </PlateProvider>
        </div>
    );
});

/**
 * Pass this method HTML and it should return it back valid Rich2
 */
export function deserializeHtml(html: string): MyValue | undefined {
    const editor = createVanillaEditor();
    if (!html) {
        logError("html not provided");
        return;
    }

    let validHTML = html;

    // Parse the html string into a DOM object to evaluate nodes
    const parser = new DOMParser();
    const parsedHTML = parser.parseFromString(html, "text/html");
    // Only evaluate the top level child nodes found within the body
    const nodes = Array.from(parsedHTML.body.childNodes);

    // Invalid HTML nodes exist in the list. Let's fix it, Felix!
    if (nodes.filter((node) => !validNode(node))) {
        // group the nodes before wrapping properly
        const nodeGroups: Array<ChildNode | ChildNode[]> = [];
        let tmpGroup: ChildNode[] = [];

        nodes.forEach((node, idx) => {
            // Node is valid. Add the temp group and the node to the list and reset the temp group
            if (validNode(node)) {
                nodeGroups.push(tmpGroup, node);
                tmpGroup = [];
                return;
            }

            // Add the node to the temp group
            tmpGroup.push(node);

            // Add the temp group and reset if the node and it's next sibling is a BR element or we have reached the end of the list
            if (
                idx === nodes.length - 1 ||
                (node instanceof HTMLBRElement && node.nextSibling instanceof HTMLBRElement)
            ) {
                nodeGroups.push(tmpGroup);
                tmpGroup = [];
            }
        });

        // Now that we have grouped invalid nodes, let's wrap them in paragraphs and build valid HTML
        const tmpBody = document.createElement("body");
        const nodesLastIdx = nodeGroups.length - 1;
        nodeGroups.forEach((item, itemIdx) => {
            // The current item is a valid node, let's just add it to the temp body
            if (!Array.isArray(item)) {
                tmpBody.append(item);
                return;
            }

            // The current item contains only an image and therefore should not be wrapped in a paragraph
            if (item.length === 1 && item[0] instanceof HTMLImageElement) {
                tmpBody.append(item[0]);
                return;
            }

            // The current item is an array of elements, let's place them inside a paragraph
            if (item.length > 0) {
                const itemLastIdx = item.length - 1;
                const tmpParagraph = document.createElement("p");
                item.forEach((child, childIdx) => {
                    // We don't want to add BR elements that created hard line breaks
                    const addToDOM = !(
                        child instanceof HTMLBRElement &&
                        ((childIdx === 0 && itemIdx > 0) || (childIdx === itemLastIdx && itemIdx < nodesLastIdx))
                    );

                    if (addToDOM) {
                        tmpParagraph.append(child);
                    }
                });

                // Add the paragraph to the temp body
                tmpBody.append(tmpParagraph);
            }
        });

        // the temp body innerHTML is now our valid HTML
        validHTML = tmpBody.innerHTML;
    }

    return plateDeserializeHtml(editor, {
        element: validHTML,
    }) as MyValue;
}

/**
 * The version of Plate currently in use (19.3.0) does not recognize `mailto` links.
 * This method is a temporary fix to ensure email links are rendered properly
 * until the version of Plate in use is upgraded.
 */
export function emailLinkCheck(value) {
    const transformLink = (child) => {
        if (child.type === ELEMENT_LINK) {
            const mailtoMatch = child.url && child.url.match(/((http(s?)):\/\/mailto:)/gi);
            if (mailtoMatch && mailtoMatch.length > 0) {
                return {
                    ...child,
                    url: child.url.replace(/((http(s?)):\/\/mailto:)/gi, "mailto:"),
                };
            }

            return child;
        }

        if (child.children) {
            return {
                ...child,
                children: child.children.map(transformLink),
            };
        }

        return child;
    };

    const newValue: MyValue = value.map((child) => {
        const newChild = { ...child };

        if (child.children) {
            newChild.children = newChild.children.map(transformLink);
        }

        return newChild;
    });

    return newValue;
}

/**
 * Determine if the node is valid as stand alone HTML
 */
export function validNode(node) {
    return !["Text", "HTMLBRElement", "HTMLElement", "HTMLImageElement"].includes(node.constructor.name);
}
