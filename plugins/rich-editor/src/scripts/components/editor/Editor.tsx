/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@dashboard/application";
import getStore from "@dashboard/state/getStore";
import { hasPermission } from "@dashboard/permissions";
import { log, debug } from "@dashboard/utility";
import Quill from "@rich-editor/quill";
import EmbedPopover from "@rich-editor/components/popovers/EmbedPopover";
import EmojiPopover from "@rich-editor/components/popovers/EmojiPopover";
import MentionToolbar from "@rich-editor/components/toolbars/MentionToolbar";
import ParagraphToolbar from "@rich-editor/components/toolbars/ParagraphToolbar";
import InlineToolbar from "@rich-editor/components/toolbars/InlineToolbar";
import UploadButton from "@rich-editor/components/editor/pieces/EditorUploadButton";
import { EditorProvider } from "@rich-editor/components/context";
import EditorDescriptions from "@rich-editor/components/editor/pieces/EditorDescriptions";
import { Provider as ReduxProvider } from "react-redux";
import IState from "@rich-editor/state/IState";
import { actions } from "@rich-editor/state/instance/instanceActions";
import uniqueId from "lodash/uniqueId";
import { Sources } from "quill/core";
import { getIDForQuill } from "@rich-editor/quill/utility";

interface IProps {
    editorID: string;
    editorDescriptionID: string;
    bodybox: HTMLInputElement;
}

const store = getStore<IState>();

export default class Editor extends React.Component<IProps> {
    private hasUploadPermission: boolean;
    private quillMountRef: React.RefObject<HTMLDivElement> = React.createRef();
    private allowPasteListener = true;
    private editorID: string;
    private quill: Quill;

    constructor(props) {
        super(props);
        log("Initializing Rich Editor");
        this.hasUploadPermission = hasPermission("uploads.add");
    }

    public componentDidMount() {
        // Setup quill
        const { bodybox } = this.props;
        const options = { theme: "vanilla" };
        this.quill = new Quill(this.quillMountRef!.current!, options);
        bodybox.style.display = "none";

        this.editorID = getIDForQuill(this.quill);

        // Setup syncing
        this.setupBodyBoxSync();
        this.setupDebugPasteListener();
        store.dispatch(actions.createInstance(this.editorID));
        this.quill.on(Quill.events.EDITOR_CHANGE, this.onEditorChange);

        // Once we've created our quill instance we need to force an update to allow all of the quill dependent
        // Modules to render.
        this.forceUpdate();
    }

    public render() {
        const { editorDescriptionID } = this.props;

        // These items CANNOT be rendered before quill is ready, but the main text area is required for quill to render.
        // These should all re-render after componentDidMount calls forceUpdate().
        const quillDependantItems = this.quill && (
            <React.Fragment>
                <InlineToolbar />
                <ParagraphToolbar />
                <MentionToolbar />
                <div className="richEditor-menu richEditor-embedBar">
                    <ul className="richEditor-menuItems" role="menubar" aria-label={t("Inline Level Formatting Menu")}>
                        <li className="richEditor-menuItem u-richEditorHiddenOnMobile" role="menuitem">
                            <EmojiPopover />
                        </li>
                        {this.hasUploadPermission && (
                            <li className="richEditor-menuItem" role="menuitem">
                                <UploadButton />
                            </li>
                        )}
                        <li className="richEditor-menuItem" role="menuitem">
                            <EmbedPopover />
                        </li>
                    </ul>
                </div>
            </React.Fragment>
        );

        return (
            <ReduxProvider store={getStore()}>
                <EditorProvider value={{ quill: this.quill, editorID: this.editorID }}>
                    <div
                        className="richEditor"
                        aria-label={t("Type your message")}
                        data-id={this.props.editorID}
                        aria-describedby={editorDescriptionID}
                        role="textbox"
                        aria-multiline="true"
                    >
                        <EditorDescriptions id={editorDescriptionID} />
                        <div className="richEditor-frame InputBox">
                            <div className="richEditor-textWrap" ref={this.quillMountRef}>
                                <div
                                    className="ql-editor richEditor-text userContent"
                                    data-gramm="false"
                                    contentEditable={true}
                                    data-placeholder="Create a new post..."
                                    tabIndex={0}
                                />
                            </div>
                            {quillDependantItems}
                        </div>
                    </div>
                </EditorProvider>
            </ReduxProvider>
        );
    }

    private onEditorChange = (type, newDeltaOrSelection, oldDeltaOrSelection, source: Sources) => {
        if (source !== Quill.sources.SILENT) {
            store.dispatch(actions.setSelection(this.editorID, this.quill.getSelection()));
        }
    };

    /**
     * Synchronization from quill's contents to the bodybox for legacy contexts.
     *
     * Once we rewrite the post page, this should no longer be necessary.
     */
    private setupBodyBoxSync() {
        const { bodybox } = this.props;
        const initialValue = bodybox.value;

        if (initialValue) {
            log("Setting existing content as contents of editor");
            this.quill.setContents(JSON.parse(initialValue));
        }

        this.quill.on("text-change", () => {
            bodybox.value = JSON.stringify(this.quill.getContents().ops);
        });

        // Listen for the legacy form event if applicable and clear the form.
        const form = this.quill.container.closest("form");
        if (form) {
            form.addEventListener("X-ClearCommentForm", () => {
                this.allowPasteListener = false;
                this.quill.setContents([]);
                this.quill.setSelection(null as any, Quill.sources.USER);
                this.allowPasteListener = true;
            });
        }
    }

    /**
     * Adds a paste listener on the old bodybox for debugging purposes.
     *
     * Pasting a valid quill JSON delta into the box will reset the contents of the editor to that delta.
     * This only works for PASTE. Not editing the contents.
     */
    private setupDebugPasteListener() {
        if (debug()) {
            const { bodybox } = this.props;
            bodybox.addEventListener("paste", event => {
                if (this.allowPasteListener) {
                    event.stopPropagation();
                    event.preventDefault();

                    // Get pasted data via clipboard API
                    const clipboardData = event.clipboardData || window.clipboardData;
                    const pastedData = clipboardData.getData("Text");
                    const delta = JSON.parse(pastedData);
                    this.quill.setContents(delta);
                }
            });
        }
    }
}
