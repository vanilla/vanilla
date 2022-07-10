/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import uniqueId from "lodash/uniqueId";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import StandardEmbedError from "@rich-editor/quill/blots/embeds/StandardEmbedError";
import AttachmentError from "@library/content/attachments/AttachmentError";
import { SelectableEmbedBlot } from "@rich-editor/quill/blots/abstract/SelectableEmbedBlot";
import { IError } from "@library/errorPages/CoreErrorMessages";

export enum ErrorBlotType {
    FILE = "file",
    STANDARD = "standard",
}

export interface IErrorData {
    error: IError;
    type: ErrorBlotType;
    file?: File;
}

/**
 * A full error. Non-recoverable. A form should not be submitted while one of these is present.
 */
export default class ErrorBlot extends SelectableEmbedBlot {
    public static blotName = "embed-error";
    public static className = "embed-error";
    public static tagName = "div";

    public static create(data: IErrorData) {
        const node = super.create(data) as HTMLElement;
        node.classList.add("js-embed");
        return node;
    }

    /**
     * Extend the base attach to remove ourselves if we are missing some particular data.
     */
    public attach() {
        super.attach();
        if (!this.data.error) {
            this.remove();
            this.quill && this.quill.update();
        }
    }

    private data: IErrorData;

    constructor(domNode: HTMLElement, data: IErrorData) {
        super(domNode);
        this.data = data;
        const id = uniqueId("embedLoader");

        if (!data.error) {
            return;
        }
        domNode.classList.remove(EMBED_FOCUS_CLASS);
        domNode.removeAttribute("tabindex");

        if (data.type === ErrorBlotType.FILE) {
            const now = new Date();
            const file = data.file!;
            ReactDOM.render(
                <AttachmentError
                    message={data.error.message}
                    name={file.name}
                    dateUploaded={now.toISOString()}
                    deleteAttachment={this.handleRemoveClick}
                />,
                domNode,
            );
        } else {
            ReactDOM.render(
                <StandardEmbedError onDismissClick={this.handleRemoveClick} id={id} error={this.data.error} />,
                domNode,
            );
        }
    }

    private handleRemoveClick = () => this.remove();
}
