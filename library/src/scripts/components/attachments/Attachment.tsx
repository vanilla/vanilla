/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import { getAttachmentIcon, AttachmentType, mimeTypeToAttachmentType } from "@library/components/attachments";
import classNames from "classnames";
import { t } from "@library/application";
import { HumanFileSize, humanFileSize } from "@library/utils/fileUtils";

export interface IFileAttachment {
    name: string; // File name
    title?: string; // Optional other label for file
    dateUploaded: string;
    className?: string;
    mimeType?: string;
    deleteAttachment?: () => void;
}

interface IProps extends IFileAttachment {
    type: AttachmentType;
    size: number; // bytes
    url: string;
}

export default class Attachment extends React.Component<IProps> {
    public render() {
        const { title, name, url, dateUploaded, type, mimeType, size, className } = this.props;
        const label = title || name;

        return (
            <div className={classNames("attachment", className)}>
                <a href={url} className="attachment-link attachment-box" type={mimeType} download={name} tabIndex={1}>
                    {type && <div className="attachment-format">{getAttachmentIcon(type)}</div>}
                    <div className="attachment-main">
                        <div className="attachment-title">{label}</div>
                        <div className="attachment-metas metas">
                            {dateUploaded && (
                                <span className="meta">
                                    <Translate source="Uploaded <0/>" c0={<DateTime timestamp={dateUploaded} />} />
                                </span>
                            )}
                            <span className="meta">
                                <HumanFileSize numBytes={size} />
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        );
    }
}
