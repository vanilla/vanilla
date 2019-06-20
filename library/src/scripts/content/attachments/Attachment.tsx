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
import { EmbedContainer, EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";

export interface IFileAttachment {
    name: string; // File name
    title?: string; // Optional other label for file
    dateUploaded: string;
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
        const { title, name, url, dateUploaded, type, mimeType, size } = this.props;
        const label = title || name;
        const classes = attachmentClasses();
        const iconClasses = attachmentIconClasses();
        const classesMetas = metasClasses();

        return (
            <EmbedContainer size={EmbedContainerSize.SMALL} withPadding>
                <div className={classes.box}>
                    {type && (
                        <div className={classNames(classes.format)}>{getAttachmentIcon(type, iconClasses.root)}</div>
                    )}
                    <div className={classNames(classes.main)}>
                        <SmartLink to={url} className={classes.link} type={mimeType} download={name} tabIndex={1}>
                            <div className={classNames(classes.title)}>{label}</div>
                        </SmartLink>
                        <div className={classNames(classes.metas, classesMetas.root)}>
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
                </div>
            </EmbedContainer>
        );
    }
}
