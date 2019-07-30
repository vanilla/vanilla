/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { userContentClasses } from "@library/content/userContentStyles";
import { delegateEvent, removeDelegatedEvent } from "@vanilla/dom-utils";
import { debug } from "@vanilla/utils";
import { useEditorContents } from "@rich-editor/editor/contentContext";
import { useEditor } from "@rich-editor/editor/context";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import registerQuill from "@rich-editor/quill/registerQuill";
import { resetQuillContent, SELECTION_UPDATE } from "@rich-editor/quill/utility";
import classNames from "classnames";
import throttle from "lodash/throttle";
import Quill, { DeltaOperation, QuillOptionsStatic, Sources } from "quill/core";
import React, { useCallback, useEffect, useRef, useMemo } from "react";
import { useLastValue } from "@vanilla/react-utils";
import { IAutoHighlightResult } from "highlight.js";

const DEFAULT_CONTENT = [{ insert: "\n" }];

interface IProps {
    legacyTextArea?: HTMLInputElement | HTMLTextAreaElement;
    placeholder?: string;
}

/**
 * The content area for Rich Editor.
 *
 * This is essentially a React wrapper around quill.
 */
export default function EditorContent(props: IProps) {
    const quillMountRef = React.createRef<HTMLDivElement>();
    useQuillInstance(quillMountRef);
    useLegacyTextAreaSync(props.legacyTextArea);
    useDebugPasteListener(props.legacyTextArea);
    useQuillAttributeSync(props.placeholder);
    useLoadStatus();
    useInitialValue();
    useOperationsQueue();
    useQuoteButtonHandler();
    useGlobalSelectionHandler();
    useSynchronization();

    return <div className="richEditor-textWrap" ref={quillMountRef} />;
}

let  hightLightJs : any;

/**
 * Use a dynamically imported highlight.js to highlight text synchronously.
 *
 * Ideally with a rewrite of the SyntaxModule we would have this working async all the time
 * but until then we need this hack.60FPS
 *
 * - If hightLightJs is loaded, run it.
 * - Otherwise return the text back and start loading hightLightJs.
 */
function highLightText(text: string): IAutoHighlightResult | string {
    if (!hightLightJs) {
        void import("highlight.js" /* webpackChunkName: "highlightJs" */).then(imported => {
            hightLightJs = imported.default;
            hightLightJs.highlightAuto(text).value;
        });
        return text;
    } else {
        return hightLightJs.highlightAuto(text).value;
    }
}

/**
 * Manage and construct a quill instance ot some ref.
 *
 * @param mountRef The ref to mount quill onto.
 */
export function useQuillInstance(mountRef: React.RefObject<HTMLDivElement>, extraOptions?: QuillOptionsStatic) {
    const ref = useRef<Quill>();
    const { setQuillInstance } = useEditor();

    useEffect(() => {
        registerQuill();
        const options: QuillOptionsStatic = {
            theme: "vanilla",
            modules: {
                syntax: {
                    highlight: highLightText,
                },
            },
        };
        if (mountRef.current) {
            const quill = new Quill(mountRef.current, options);
            quill.setContents(DEFAULT_CONTENT);
            setQuillInstance(quill);
            ref.current = quill;

            // The latest quill gets synced to the window element.
            window.quill = quill;
            return () => {
                setQuillInstance(null);
                window.quill = null;
            };
        }
        // Causes an infinite loops if we specify mountRef.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [extraOptions, setQuillInstance]);
    return ref.current;
}

/**
 * Apply our CSS classes/styles and other attributes to quill's root. (Not a react component).
 */
function useQuillAttributeSync(placeholder?: string) {
    const { legacyMode, quill } = useEditor();
    const classesRichEditor = richEditorClasses(legacyMode);
    const classesUserContent = userContentClasses();
    const quillRootClasses = useMemo(
        () =>
            classNames("richEditor-text", "userContent", classesRichEditor.text, {
                // These classes shouln't be applied until the forum is converted to the new styles.
                [classesUserContent.root]: !legacyMode,
            }),
        [classesRichEditor.text, classesUserContent.root, legacyMode],
    );

    useEffect(() => {
        if (quill) {
            // Initialize some CSS classes onto the quill root.quillRootClasses
            // quill && quill.root.classList.value,
            quill.root.classList.value += " " + quillRootClasses;
        }
    }, [quill, quillRootClasses]);

    useEffect(() => {
        if (quill && placeholder) {
            quill.root.setAttribute("placeholder", placeholder);
        }
    }, [quill, placeholder]);

    return quillRootClasses;
}

/**
 * Map our isLoading context into quill being enabled or disabled.
 */
function useLoadStatus() {
    const { quill, isLoading } = useEditor();
    const prevLoading = useLastValue(isLoading);
    useEffect(() => {
        if (quill) {
            if (!prevLoading && isLoading) {
                quill.disable();
            } else if (prevLoading && !isLoading) {
                quill.enable();
            }
        }
    }, [isLoading, quill, prevLoading]);
}

/**
 * Handle the updating of the initial editor value.
 */
function useInitialValue() {
    const { quill, initialValue, reinitialize } = useEditor();
    const prevInitialValue = useLastValue(initialValue);
    const prevReinitialize = useLastValue(reinitialize);

    useEffect(() => {
        if (quill && initialValue && initialValue.length > 0) {
            if (prevInitialValue !== initialValue && prevReinitialize !== reinitialize) {
                quill.setContents(initialValue);
            }
        }
    }, [quill, initialValue, reinitialize, prevInitialValue, prevReinitialize]);
}

/**
 * Handle queued insert operations when the editor loads up.
 */
function useOperationsQueue() {
    const { operationsQueue, quill, clearOperationsQueue } = useEditor();
    useEffect(() => {
        if (!operationsQueue || !quill || operationsQueue.length === 0) {
            return;
        }
        operationsQueue.forEach(operation => {
            const scrollLength = quill.scroll.length();

            if (typeof operation === "string") {
                quill.clipboard.dangerouslyPasteHTML(scrollLength, operation);
            } else {
                const offsetOperations = scrollLength > 1 ? { retain: scrollLength } : { delete: 1 };
                quill.updateContents([offsetOperations, ...operation]);
            }
        });
        clearOperationsQueue && clearOperationsQueue();
    }, [quill, operationsQueue, clearOperationsQueue]);
}

/**
 * Synchronization from quill's contents to the bodybox for legacy contexts.
 *
 * Once we rewrite the post page, this should no longer be necessary.
 */
function useLegacyTextAreaSync(textArea?: HTMLInputElement | HTMLTextAreaElement) {
    const { legacyMode, quill } = useEditor();

    useEffect(() => {
        if (!legacyMode || !textArea || !quill) {
            return;
        }
        const initialValue = textArea.value;
        if (initialValue) {
            resetQuillContent(quill, JSON.parse(initialValue));
        }
    }, [legacyMode, textArea, quill]);

    useEffect(() => {
        if (!legacyMode || !textArea || !quill) {
            return;
        }
        // Sync the text areas together.
        // Throttled to keep performance up on slower devices.
        const handleChange = throttle(() => {
            requestAnimationFrame(() => {
                textArea.value = JSON.stringify(quill.getContents().ops);
            });
        }, 1000 / 60); // 60FPS
        quill.on(Quill.events.TEXT_CHANGE, handleChange);

        // Listen for the legacy form event if applicable and clear the form.
        const handleFormClear = () => {
            resetQuillContent(quill, []);
            quill.setSelection(null as any, Quill.sources.USER);
        };

        const form = quill.container.closest("form");
        form && form.addEventListener("X-ClearCommentForm", handleFormClear);

        // Cleanup function
        return () => {
            quill.off(Quill.events.TEXT_CHANGE, handleChange);
            form && form.removeEventListener("X-ClearCommentForm", handleFormClear);
        };
    }, [legacyMode, textArea, quill]);
}

/**
 * Page handlers for the rich quote buttons.
 */
function useQuoteButtonHandler() {
    const { quill } = useEditor();

    useEffect(() => {
        /**
         * Handler for clicking on quote button.
         *
         * Triggers a media scraping.
         */
        const handleQuoteButtonClick = (event: MouseEvent, triggeringElement: Element) => {
            if (!quill) {
                return;
            }
            event.preventDefault();
            const embedInserter: EmbedInsertionModule = quill.getModule("embed/insertion");
            const url = triggeringElement.getAttribute("data-scrape-url") || "";
            void embedInserter.scrapeMedia(url);
        };
        const delegatedHandler = delegateEvent("click", ".js-quoteButton", handleQuoteButtonClick)!;
        return () => {
            removeDelegatedEvent(delegatedHandler);
        };
    }, [quill]);
}

/**
 * Handle global forced selection updates.
 */
function useGlobalSelectionHandler() {
    const updateHandler = useUpdateHandler();

    const handleGlobalSelectionUpdate = useCallback(() => {
        updateHandler(Quill.events.SELECTION_CHANGE, null, null, Quill.sources.USER);
    }, [updateHandler]);

    useEffect(() => {
        document.addEventListener(SELECTION_UPDATE, handleGlobalSelectionUpdate);
        return () => {
            document.removeEventListener(SELECTION_UPDATE, handleGlobalSelectionUpdate);
        };
    }, [handleGlobalSelectionUpdate]);
}

/**
 * Adds a paste listener on the old bodybox for debugging purposes. Only works in legacy mode.
 *
 * Pasting a valid quill JSON delta into the box will reset the contents of the editor to that delta.
 * This only works for PASTE. Not editing the contents.
 */
function useDebugPasteListener(textArea?: HTMLInputElement | HTMLTextAreaElement) {
    const { legacyMode, quill } = useEditor();
    useEffect(() => {
        if (!legacyMode || !textArea || !debug() || !quill) {
            return;
        }
        const pasteHandler = (event: ClipboardEvent) => {
            event.stopPropagation();
            event.preventDefault();

            // Get pasted data via clipboard API
            const clipboardData = event.clipboardData || window.clipboardData;
            const pastedData = clipboardData.getData("Text");
            const delta = JSON.parse(pastedData);
            quill.setContents(delta);
        };

        textArea.addEventListener("paste", pasteHandler);
        return () => {
            textArea.addEventListener("paste", pasteHandler);
        };
    }, [legacyMode, quill, textArea]);
}

/**
 * Hook for a re-usable quill update handler.
 * Quill dispatches a lot of unnecessary updates. We need to filter out only the ones we want.
 *
 * We need
 * - Every non-silent event.
 * - Every selection change event (even the "silent" ones).
 */
function useUpdateHandler() {
    const { onChange, quill } = useEditor();
    const editorContents = useEditorContents();

    const getOperations = useCallback((): DeltaOperation[] => {
        if (!quill) {
            return [];
        }

        HeaderBlot.resetCounters();
        const headers = (quill.scroll.descendants(
            blot => blot instanceof HeaderBlot,
            0,
            quill.scroll.length(),
        ) as any) as HeaderBlot[]; // Explicit mapping of types because the parchments types suck.

        headers.forEach(header => header.setGeneratedID());
        quill.update(Quill.sources.API);
        return quill.getContents().ops!;
    }, [quill]);

    const handleUpdate = useCallback(
        throttle((type: string, newValue, oldValue, source: Sources) => {
            if (!quill) {
                return;
            }
            if (onChange && type === Quill.events.TEXT_CHANGE && source !== Quill.sources.SILENT) {
                onChange(getOperations());
            }

            let shouldDispatch = false;
            if (type === Quill.events.SELECTION_CHANGE) {
                shouldDispatch = true;
            } else if (source !== Quill.sources.SILENT) {
                shouldDispatch = true;
            }

            if (shouldDispatch) {
                editorContents.updateSelection(quill.getSelection());
            }
        }, 1000 / 60), // Throttle to 60 FPS.
        [quill, onChange, getOperations],
    );

    return handleUpdate;
}

/**
 * Hook for synchonizing quill's values to our update handler.
 */
function useSynchronization() {
    const { quill } = useEditor();
    const updateHandler = useUpdateHandler();

    useEffect(() => {
        if (!quill) {
            return;
        }

        // Call intially with the value.
        updateHandler(Quill.events.TEXT_CHANGE, null, null, Quill.sources.API);

        quill.on(Quill.events.EDITOR_CHANGE, updateHandler);
        return () => {
            quill.off(Quill.events.EDITOR_CHANGE, updateHandler);
        };
    }, [quill, updateHandler]);
}
