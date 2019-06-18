/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { HumanFileSize } from "@library/utility/fileUtils";
import { getAttachmentIcon } from "@library/content/attachments/attachmentUtils";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import { attachmentClasses } from "@library/content/attachments/attachmentStyles";
import { metasClasses } from "@library/styles/metasStyles";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import { attachmentIconClasses } from "@library/content/attachments/attachmentIconsStyles";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";

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
        const classesMetas = metasClasses();

        return (
            <div className={classNames("attachment", className, classes.root)}>
                <SmartLink
                    to={url}
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
                        <div className={classNames("attachment-metas", "metas", classes.metas, classesMetas.root)}>
                            {dateUploaded && (
                                <span className={classesMetas.meta}>
                                    <Translate source="Uploaded <0/>" c0={<DateTime timestamp={dateUploaded} />} />
                                </span>
                            )}
                            <span className={classesMetas.meta}>
                                <HumanFileSize numBytes={size} />
                            </span>
                        </div>
                    </div>
                </SmartLink>
            </div>
        );
    }
}
