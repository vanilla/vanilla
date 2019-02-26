/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import getStore from "@library/state/getStore";
import { debug, log } from "@library/utility";
import { delegateEvent, removeDelegatedEvent } from "@library/dom";
import MentionToolbar from "@rich-editor/components/toolbars/MentionToolbar";
import InlineToolbar from "@rich-editor/components/toolbars/InlineToolbar";
import { EditorProvider } from "@rich-editor/components/context";
import EditorDescriptions from "@rich-editor/components/editor/pieces/EditorDescriptions";
import { Provider as ReduxProvider } from "react-redux";
import { actions } from "@rich-editor/state/instance/instanceActions";
import { getIDForQuill, SELECTION_UPDATE } from "@rich-editor/quill/utility";
import { IStoreState } from "@rich-editor/@types/store";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import Quill, { DeltaOperation, Sources, QuillOptionsStatic } from "quill/core";
import { hot } from "react-hot-loader";
import registerQuill from "@rich-editor/quill/registerQuill";
import { uniqueId } from "lodash";
import classNames from "classnames";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import { Devices } from "@library/components/DeviceChecker";
import ParagraphToolbar from "@rich-editor/components/toolbars/ParagraphToolbar";
import throttle from "lodash/throttle";
import EmbedBar from "@rich-editor/components/editor/pieces/EmbedBar";
import hljs from "highlight.js";

interface ICommonProps {
    isPrimaryEditor: boolean;
    isLoading?: boolean;
    onChange?: (newContent: DeltaOperation[]) => void;
    allowUpload: boolean;
    device?: Devices;
    initialValue?: DeltaOperation[];
    reinitialize?: boolean;
}

interface ILegacyProps extends ICommonProps {
    legacyMode: true;
    legacyTextArea?: HTMLInputElement;
}

interface INewProps extends ICommonProps {
    className?: string;
    legacyMode: boolean;
}

type IProps = ILegacyProps | INewProps;

/**
 * React component for instantiating a rich editor.
 */
export class Editor extends React.Component<IProps> {
    /** Ref for a dom node for quill to mount into. */
    private quillMountRef = React.createRef<HTMLDivElement>();

    /** Ref for the embedBar height */
    private embedBarRef = React.createRef<HTMLDivElement>();

    /** Ref for the scroll container */
    private scrollContainerRef = React.createRef<HTMLDivElement>();

    /** The ID of the root rich editor node. */
    private domID: string = uniqueId("editor-");

    /** The redux store. */
    private store = getStore<IStoreState>();

    private skipCallback = false;

    /**
     * The ID of our quill instance.
     * This is needed to work our quill instance's chunk of the redux store
     */
    private quillID: string;

    /** The quill instance. */
    private quill: Quill;

    /** The hash of our delegated quote event handler. Used to unset the handler on unmount. */
    private quoteHandler: string;

    /**
     * Render either the legacy or modern view for the editor.
     */
    public render() {
        return this.props.legacyMode ? this.renderLegacy() : this.renderModern();
    }

    /**
     * The modern view is characterized by a contextual menu at the top and it's own scroll container.
     */
    private renderModern(): React.ReactNode {
        const { className } = this.props as INewProps;
        return (
            <div
                className={classNames("richEditor", className, { isDisabled: this.props.isLoading })}
                aria-label={t("Type your message.")}
                aria-describedby={this.descriptionID}
                role="textbox"
                aria-multiline={true}
                id={this.domID}
            >
                {this.renderContexts(
                    <>
                        {this.renderEmbedBar()}
                        <div className="richEditor-scrollFrame">
                            <div className="richEditor-scrollContainer" ref={this.scrollContainerRef}>
                                {/*<div className="richEditor-scrollable">*/}
                                <div className={classNames("richEditor-frame InputBox isMenuInset")} id="testScroll">
                                    {this.renderMountPoint()}
                                    {this.renderInlineToolbars()}
                                </div>
                                {this.renderParagraphToolbar()}
                                {/*</div>*/}
                            </div>
                        </div>
                    </>,
                )}
            </div>
        );
    }

    /**
     * The legacy rendering mode has everything at the bottom, and uses the document as it's scroll container.
     */
    private renderLegacy(): React.ReactNode {
        return this.renderContexts(
            <div className={classNames("richEditor-frame", "InputBox")} id="testScroll">
                {this.renderMountPoint()}
                {this.renderParagraphToolbar()}
                {this.renderInlineToolbars()}
                {this.renderEmbedBar()}
            </div>,
        );
    }

    /**
     * Render the elements that Quill will mount into.
     */
    private renderMountPoint(): React.ReactNode {
        return (
            <div className="richEditor-textWrap" ref={this.quillMountRef}>
                <div
                    className="ql-editor richEditor-text userContent"
                    data-gramm="false"
                    contentEditable={this.props.isLoading}
                    data-placeholder="Create a new post..."
                    tabIndex={0}
                />
            </div>
        );
    }

    /**
     * Conditionally render the embed bar.
     */
    private renderEmbedBar(): React.ReactNode {
        return (
            this.quill && (
                <EmbedBar
                    isLoading={!!this.props.isLoading}
                    isMobile={this.isMobile}
                    legacyMode={this.props.legacyMode}
                    barRef={this.embedBarRef}
                />
            )
        );
    }

    /**
     * Conditionally render the paragraph toolbar.
     */
    private renderParagraphToolbar(): React.ReactNode {
        return this.quill && !this.props.isLoading && !this.isMobile && <ParagraphToolbar />;
    }

    /**
     * Conditionally render the inline toolbars.
     */
    private renderInlineToolbars(): React.ReactNode {
        return (
            this.quill &&
            !this.props.isLoading && (
                <>
                    <InlineToolbar />
                    <MentionToolbar />
                </>
            )
        );
    }

    /**
     * Render the top level contexts for the editor.
     * @param content The content to nest inside of the contexts.
     */
    private renderContexts(content: React.ReactNode): React.ReactNode {
        const { isLoading, legacyMode } = this.props;

        return (
            <ReduxProvider store={this.store}>
                <EditorProvider
                    value={{
                        quill: this.quill,
                        editorID: this.quillID,
                        legacyMode,
                        isLoading: !!isLoading,
                    }}
                >
                    <EditorDescriptions id={this.descriptionID} />
                    {content}
                </EditorProvider>
            </ReduxProvider>
        );
    }

    /**
     * Determine if we are in mobile view or not. Always false for legacy mode.
     */
    private get isMobile(): boolean {
        let isMobile = false; // fallback for legacy: isMobile is always false
        if (!this.props.legacyMode && this.props.device) {
            isMobile = this.props.device === Devices.MOBILE;
        }
        return isMobile;
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
            scrollingContainer: this.scrollContainerRef.current || document.documentElement!,
        };
        this.quill = new Quill(this.quillMountRef.current!, options);
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
        this.forceUpdate();
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

        const { legacyTextArea } = this.props as ILegacyProps;
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
        const { legacyTextArea } = this.props as ILegacyProps;

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

export default hot(module)(Editor);
