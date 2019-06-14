/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Attachment from "@library/content/attachments/Attachment";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { IEmbedProps, IFileUploadData, registerEmbedComponent } from "@library/content/embeds/embedUtils";
import { onContent } from "@library/utility/appUtils";
import React from "react";
import ReactDOM from "react-dom";
import { mimeTypeToAttachmentType } from "@library/content/attachments/attachmentUtils";
import { sanitizeUrl } from "@vanilla/utils";

export function initFileEmbeds() {
    registerEmbedComponent("file", FileEmbed);
    mountFileEmbeds();
    onContent(mountFileEmbeds);
}

/**
 * Mount all of the existing file embeds embeds in the page.
 *
 * Data (including server rendered HTML content should be coming down in JSON encoded attribute data-json).
 */
export function mountFileEmbeds() {
    const embeds = document.querySelectorAll(".js-fileEmbed");
    for (const embed of embeds) {
        const data = embed.getAttribute("data-json");
        if (data) {
            const fileData = JSON.parse(data) as IFileUploadData;
            const onRenderComplete = () => {
                embed.removeAttribute("data-json");
                embed.classList.remove("embedResponsive-initialLink");
            };
            ReactDOM.render(<FileEmbed data={fileData} inEditor={false} onRenderComplete={onRenderComplete} />, embed);
        }
    }
}

/**
 * An embed class for quoted user content on the same site.
 *
 * This is not an editable quote. Instead it an expandable/collapsable snapshot of the quoted/embedded comment/discussion.
 *
 * This can either recieve the post format and body (when created directly in the editor) or be given the fully rendered content (when mounting on top of existing server rendered DOM stuff).
 */
export class FileEmbed extends BaseEmbed<IEmbedProps<IFileUploadData>> {
    public render() {
        const { url, attributes } = this.props.data;
        const { type, size, dateInserted, name } = attributes;
        const attachmentType = mimeTypeToAttachmentType(type);
        return (
            <Attachment
                type={attachmentType}
                size={size}
                name={name}
                url={sanitizeUrl(url)}
                dateUploaded={dateInserted}
                mimeType={type}
            />
        );
    }
}
