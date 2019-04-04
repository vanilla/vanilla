/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { userContentClasses } from "@library/content/userContentStyles";
import { delegateEvent, removeDelegatedEvent } from "@library/dom/domUtils";
import getStore from "@library/redux/getStore";
import { debug, log } from "@library/utility/utils";
import { IStoreState } from "@rich-editor/@types/store";
import { IWithEditorProps, withEditor } from "@rich-editor/editor/context";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import registerQuill from "@rich-editor/quill/registerQuill";
import { getIDForQuill, SELECTION_UPDATE } from "@rich-editor/quill/utility";
import { actions } from "@rich-editor/state/instance/instanceActions";
import classNames from "classnames";
import hljs from "highlight.js";
import throttle from "lodash/throttle";
import Quill, { DeltaOperation, QuillOptionsStatic, Sources } from "quill/core";
import React from "react";
import { hot } from "react-hot-loader";

interface IProps extends IWithEditorProps {
    legacyTextArea?: HTMLInputElement;
}

/**
 * React component for instantiating a rich editor.
 */
export class EditorContent extends React.Component<IProps> {
    /** Ref for a dom node for quill to mount into. */
    private quillMountRef = React.createRef<HTMLDivElement>();

    /** The redux store. */
    private store = getStore<IStoreState>();

    private skipCallback = false;

    /** The hash of our delegated quote event handler. Used to unset the handler on unmount. */
    private quoteHandler: string;

    /** The quill instance */
    private quill: Quill;

    /** The ID for accessing quill in redux */
    private quillID: string;

    /**
     * Render either the legacy or modern view for the editor.
     */
    public render() {
        return <div className="richEditor-textWrap" ref={this.quillMountRef} />;
    }

    private get contentClasses() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        const classesUserContent = userContentClasses();
        return classNames("ql-editor", "richEditor-text", "userContent", classesRichEditor.text, {
            [classesUserContent.root]: !this.props.legacyMode,
            // These classes shouln't be applied until the forum is converted to the new styles.
        });
    }
    /**
     * Initial editor setup.
     */
    public componentDidMount() {
        document.body.classList.add("hasFullHeight");

        // Setup quill
        registerQuill();
        const options: QuillOptionsStatic = {
            theme: "vanilla",
            modules: {
                syntax: {
                    highlight: text => hljs.highlightAuto(text).value,
                },
            },
            // scrollingContainer: this.scrollContainerRef.current || document.documentElement!,
        };
        this.quill = new Quill(this.quillMountRef.current!, options);
        this.quill.root.classList.value = this.contentClasses;
        if (this.props.initialValue) {
            this.quill.setContents(this.props.initialValue);
        }
        if (this.props.isLoading) {
            this.quill.disable();
        }
        window.quill = this.quill;
        this.quillID = getIDForQuill(this.quill);

        // Setup syncing
        this.setupLegacyTextAreaSync();
        this.setupDebugPasteListener();
        this.store.dispatch(actions.createInstance(this.quillID));
        this.quill.on(Quill.events.EDITOR_CHANGE, this.onQuillUpdate);

        this.addGlobalSelectionHandler();
        this.addQuoteHandler();

        // Once we've created our quill instance we need to force an update to allow all of the quill dependent
        // Modules to render.
        this.props.setQuillInstance(this.quill);
    }

    /**
     * Cleanup from componentDidMount.
     */
    public componentWillUnmount() {
        this.removeGlobalSelectionHandler();
        this.removeQuoteHandler();
        this.quill.off(Quill.events.EDITOR_CHANGE, this.onQuillUpdate);
        this.store.dispatch(actions.deleteInstance(this.quillID));
    }

    public componentDidUpdate(oldProps: IProps) {
        if (oldProps.isLoading && !this.props.isLoading) {
            this.quill.enable();
        } else if (!oldProps.isLoading && this.props.isLoading) {
            this.quill.disable();
        }

        if (!oldProps.reinitialize && this.props.reinitialize) {
            if (this.props.initialValue) {
                this.skipCallback = true;
                this.setEditorContent(this.props.initialValue);
            }
        }

        if (this.props.operationsQueue && this.props.operationsQueue.length > 0) {
            this.props.operationsQueue.forEach(operation => {
                const scrollLength = this.quill.scroll.length();

                if (typeof operation === "string") {
                    this.quill.clipboard.dangerouslyPasteHTML(scrollLength, operation);
                } else {
                    const offsetOperations = scrollLength > 1 ? { retain: scrollLength } : { delete: 1 };
                    this.quill.updateContents([offsetOperations, ...operation]);
                }
            });
            if (this.props.clearOperationsQueue) {
                this.props.clearOperationsQueue();
            }
        }
    }

    /**
     * Get the content out of the quill editor.
     */
    public getEditorOperations(): DeltaOperation[] | undefined {
        this.ensureUniqueHeaderIDs();
        return this.quill.getContents().ops;
    }

    /**
     * Loop through the editor document and ensure every header has a unique data-id.
     */
    private ensureUniqueHeaderIDs() {
        HeaderBlot.resetCounters();
        const headers = (this.quill.scroll.descendants(
            blot => blot instanceof HeaderBlot,
            0,
            this.quill.scroll.length(),
        ) as any) as HeaderBlot[]; // Explicit mapping of types because the parchments types suck.

        headers.forEach(header => header.setGeneratedID());
        this.quill.update(Quill.sources.API);
    }

    /**
     * Get the content out of the quill editor.
     */
    public getEditorText(): string {
        return this.quill.getText();
    }

    /**
     * Set the quill editor contents.
     *
     * @param content The delta to set.
     */
    public setEditorContent(content: DeltaOperation[]) {
        log("Setting existing content as contents of editor");
        this.quill.setContents(content);
        // Clear the history so that you can't "undo" your initial content.
        this.quill.getModule("history").clear();
    }

    /**
     * Quill dispatches a lot of unnecessary updates. We need to filter out only the ones we want.
     *
     * We need
     * - Every non-silent event.
     * - Every selection change event (even the "silent" ones).
     */
    private onQuillUpdate = throttle((type: string, newValue, oldValue, source: Sources) => {
        if (this.skipCallback) {
            this.skipCallback = false;
            return;
        }
        if (this.props.onChange && type === Quill.events.TEXT_CHANGE && source !== Quill.sources.SILENT) {
            this.props.onChange(this.getEditorOperations()!);
        }

        let shouldDispatch = false;
        if (type === Quill.events.SELECTION_CHANGE) {
            shouldDispatch = true;
        } else if (source !== Quill.sources.SILENT) {
            shouldDispatch = true;
        }

        if (shouldDispatch) {
            this.store.dispatch(actions.setSelection(this.quillID, this.quill.getSelection(), this.quill));
        }
    }, 1000 / 60); // Throttle to 60 FPS.

    /**
     * Synchronization from quill's contents to the bodybox for legacy contexts.
     *
     * Once we rewrite the post page, this should no longer be necessary.
     */
    private setupLegacyTextAreaSync() {
        if (!this.props.legacyMode) {
            return;
        }

        const { legacyTextArea } = this.props;
        if (!legacyTextArea) {
            return;
        }

        const initialValue = legacyTextArea.value;

        if (initialValue) {
            this.setEditorContent(JSON.parse(initialValue));
        }

        this.quill.on("text-change", () => {
            legacyTextArea.value = JSON.stringify(this.quill.getContents().ops);
        });

        // Listen for the legacy form event if applicable and clear the form.
        const form = this.quill.container.closest("form");
        if (form) {
            form.addEventListener("X-ClearCommentForm", () => {
                this.quill.setContents([]);
                this.quill.setSelection(null as any, Quill.sources.USER);
            });
        }
    }

    /**
     * Handler for clicking on quote button.
     *
     * Triggers a media scraping.
     */
    private quoteButtonClickHandler = (event: MouseEvent, triggeringElement: Element) => {
        event.preventDefault();
        const embedInserter: EmbedInsertionModule = this.quill.getModule("embed/insertion");
        const url = triggeringElement.getAttribute("data-scrape-url") || "";
        void embedInserter.scrapeMedia(url);
    };

    /**
     * Add the handler for when a quote button is clicked.
     */
    private addQuoteHandler() {
        this.quoteHandler = delegateEvent("click", ".js-quoteButton", this.quoteButtonClickHandler)!;
    }

    /**
     * Removes the event listener from addQuoteHandler.
     *
     * In some rare instances the component will unmount before quoteHandler exists.
     * Because of this we need to check that it has been set.
     */
    private removeQuoteHandler() {
        if (this.quoteHandler) {
            removeDelegatedEvent(this.quoteHandler);
        }
    }

    /**
     * Handle forced selection updates.
     */
    private handleGlobalSelectionUpdate = () => {
        window.requestAnimationFrame(() => {
            this.onQuillUpdate(Quill.events.SELECTION_CHANGE, null, null, Quill.sources.USER);
        });
    };

    /**
     * Add a handler for forced selection updates.
     */
    private addGlobalSelectionHandler() {
        document.addEventListener(SELECTION_UPDATE, this.handleGlobalSelectionUpdate);
    }

    /**
     * Remove the handler for forced selection updates.
     */
    private removeGlobalSelectionHandler() {
        document.removeEventListener(SELECTION_UPDATE, this.handleGlobalSelectionUpdate);
    }

    /**
     * Adds a paste listener on the old bodybox for debugging purposes.
     *
     * Pasting a valid quill JSON delta into the box will reset the contents of the editor to that delta.
     * This only works for PASTE. Not editing the contents.
     */
    private setupDebugPasteListener() {
        if (!this.props.legacyMode) {
            return;
        }
        const { legacyTextArea } = this.props;

        if (debug() && legacyTextArea) {
            legacyTextArea.addEventListener("paste", event => {
                event.stopPropagation();
                event.preventDefault();

                // Get pasted data via clipboard API
                const clipboardData = event.clipboardData || window.clipboardData;
                const pastedData = clipboardData.getData("Text");
                const delta = JSON.parse(pastedData);
                this.quill.setContents(delta);
            });
        }
    }

    private get descriptionID(): string {
        return this.domID + "-description";
    }
}

export default hot(module)(withEditor(EditorContent));
