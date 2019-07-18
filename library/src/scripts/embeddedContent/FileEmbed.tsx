/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import Attachment from "@library/content/attachments/Attachment";
import { mimeTypeToAttachmentType } from "@library/content/attachments/attachmentUtils";

interface IProps extends IBaseEmbedProps {
    type: string; // Mime type.
    size: number;
    dateInserted: string;
    name: string;
}

/**
 * An embed class for quoted user content on the same site.
 */
export function FileEmbed(props: IProps) {
    const { type, size, dateInserted, name, url } = props;
    const attachmentType = mimeTypeToAttachmentType(type);
    return (
        <Attachment
            inEditor={props.inEditor}
            type={attachmentType}
            size={size}
            name={name}
            url={url}
            dateUploaded={dateInserted}
            mimeType={type}
        />
    );
}
