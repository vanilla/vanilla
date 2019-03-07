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
import { attachmentClasses } from "@library/styles/attachmentStyles";
import { attachmentIconClasses } from "@library/styles/attachmentIconsStyles";

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
        const classes = attachmentClasses();
        const iconClasses = attachmentIconClasses();

        return (
            <div className={classNames("attachment", className, classes.root)}>
                <a
                    href={url}
                    className={classNames("attachment-link", "attachment-box", classes.link, classes.box)}
                    type={mimeType}
                    download={name}
                    tabIndex={1}
                >
                    {type && (
                        <div className={classNames("attachment-format", classes.format)}>
                            {getAttachmentIcon(type, iconClasses.root)}
                        </div>
                    )}
                    <div className={classNames("attachment-main", classes.main)}>
                        <div className={classNames("attachment-title", classes.title)}>{label}</div>
                        <div className={classNames("attachment-metas", "metas", classes.metas)}>
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
