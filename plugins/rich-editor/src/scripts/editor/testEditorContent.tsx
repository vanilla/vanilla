// /**
//  * @copyright 2009-2019 Vanilla Forums Inc.
//  * @license GPL-2.0-only
//  */

// import React, { useRef, useEffect } from "react";
// import { useEditor } from "@rich-editor/editor/context";
// import registerQuill from "@rich-editor/quill/registerQuill";
// import Quill, { QuillOptionsStatic, DeltaOperation, RangeStatic, Sources } from "quill/core";
// import hljs from "highlight.js";
// import { log } from "@library/utility/utils";
// import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
// import { delegateEvent, removeDelegatedEvent } from "@library/dom/domUtils";

// interface IProps {
//     className?: string;
// }

// export function useMountQuill(element: HTMLElement | null) {
//     const { initialValue, isLoading, quill, setQuillInstance } = useEditor();
//     useEffect(() => {
//         if (!quill && element) {
//             registerQuill();
//             const options: QuillOptionsStatic = {
//                 theme: "vanilla",
//                 modules: {
//                     syntax: {
//                         highlight: text => hljs.highlightAuto(text).value,
//                     },
//                 },
//                 // scrollingContainer: this.scrollContainerRef.current || document.documentElement!,
//             };
//             const newQuill = new Quill(element, options);
//             newQuill.root.classList.value = this.contentClasses;
//             if (initialValue) {
//                 newQuill.setContents(initialValue);
//             }
//             if (isLoading) {
//                 newQuill.disable();
//             }
//             window.quill = newQuill;
//             setQuillInstance(newQuill);
//         }
//     }, [element, initialValue, isLoading, quill, setQuillInstance]);
// }

// export function useEditorContents(): {
//     setContents: (contents: DeltaOperation[]) => void;
//     getContents: () => DeltaOperation[];
//     setSelection: (range: RangeStatic | null, source: Sources) => void;
// } {
//     const { quill } = useEditor();
//     if (!quill) {
//         const noop = () => {
//             return;
//         };
//         return {
//             setContents: noop,
//             getContents: () => [],
//             setSelection: noop,
//         };
//     } else {
//         return {
//             setContents: (content: DeltaOperation[]) => {
//                 log("Setting existing content as contents of editor");
//                 quill.setContents(content);
//                 // Clear the history so that you can't "undo" your initial content.
//                 quill.getModule("history").clear();
//             },
//             getContents: () => quill.getContents().ops || [],
//             setSelection: (range, source) => quill.setSelection(range!, source),
//         };
//     }
// }

// function useLegacyTextAreaSync() {
//     const { legacyMode, legacyTextArea, quill } = useEditor();
//     const { setContents, setSelection } = useEditorContents();
//     useEffect(() => {
//         if (!legacyMode || !legacyTextArea || !quill) {
//             return;
//         }

//         const initialValue = legacyTextArea.value;
//         if (initialValue) {
//             setContents(JSON.parse(initialValue));
//         }

//         quill.on("text-change", () => {
//             legacyTextArea.value = JSON.stringify(this.quill.getContents().ops);
//         });

//         // Listen for the legacy form event if applicable and clear the form.
//         const form = quill.container.closest("form");
//         const handleFormClear = () => {
//             setContents([]);
//             setSelection(null, Quill.sources.USER);
//         };
//         if (form) {
//             form.addEventListener("X-ClearCommentForm", handleFormClear);
//         }

//         return () => {
//             form && form.removeEventListener("X-ClearCommentForm", handleFormClear);
//         };
//     }, [legacyMode, legacyTextArea, setContents, setSelection, quill]);
// }

// function useQuoteButtonHandler() {
//     const { quill } = useEditor();
//     useEffect(() => {
//         const handleQuoteClick = (event: MouseEvent, triggeringElement: HTMLElement) => {
//             event.preventDefault();
//             const embedInserter: EmbedInsertionModule = quill && quill.getModule("embed/insertion");
//             const url = triggeringElement.getAttribute("data-scrape-url") || "";
//             void embedInserter.scrapeMedia(url);
//         };

//         const handler = delegateEvent("click", ".js-quoteButton", handleQuoteClick);
//         return () => {
//             handler && removeDelegatedEvent(handler);
//         };
//     }, [quill]);
// }

// function useContentUpdateNotifications() {
//     const { quill } = useEditor();
//     const contentHandler = throttle((type: string, newValue, oldValue, source: Sources) => {
//         if (this.skipCallback) {
//             this.skipCallback = false;
//             return;
//         }
//         if (this.props.onChange && type === Quill.events.TEXT_CHANGE && source !== Quill.sources.SILENT) {
//             this.props.onChange(this.getEditorOperations()!);
//         }

//         let shouldDispatch = false;
//         if (type === Quill.events.SELECTION_CHANGE) {
//             shouldDispatch = true;
//         } else if (source !== Quill.sources.SILENT) {
//             shouldDispatch = true;
//         }

//         if (shouldDispatch) {
//             this.store.dispatch(actions.setSelection(this.quillID, this.quill.getSelection(), this.quill));
//         }
//     }, 1000 / 60); // Throttle to 60 FPS.
// }

// export function EditorContent(props: IProps) {
//     const quillMountRef = useRef<HTMLDivElement>(null);
//     useMountQuill(quillMountRef.current);
//     useLegacyTextAreaSync();
//     useQuoteButtonHandler();

//     return <div className="richEditor-textWrap" ref={quillMountRef} />;
// }
