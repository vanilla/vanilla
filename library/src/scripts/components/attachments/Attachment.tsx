/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import { getAttachmentIcon, AttachmentType } from "@library/components/attachments";
import classNames from "classnames";
import { t } from "@library/application";

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

export function getUnabbreviatedFileSizeUnit(unit: string) {
    switch (unit.toLowerCase()) {
        case "byte":
        case "b":
            return t("Byte");
        case "kilobyte":
        case "kb":
            return t("Kilobyte");
        case "megabyte":
        case "mb":
            return t("Megabyte");
        case "terabyte":
        case "tb":
            return t("Terabyte");
        default:
            return null;
    }
}

export function humanFileSize(size: number) {
    const i: number = Math.floor(Math.log(size) / Math.log(1024));
    const sizes = ["B", "KB", "MB", "GB", "TB"];
    const unit = sizes[i];
    const unabbreviated = getUnabbreviatedFileSizeUnit(unit);
    return (
        <>
            {((size / Math.pow(1024, i)) as any).toFixed(2) * 1}
            {unabbreviated ? <abbr title={unabbreviated}>{` ${unit}`}</abbr> : sizes[i]}
        </>
    );
}

export default class Attachment extends React.Component<IProps> {
    public render() {
        const { title, name, type, url, dateUploaded, mimeType, size, deleteAttachment, className } = this.props;
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
                            <span className="meta">{humanFileSize(size)}</span>
                        </div>
                    </div>
                </a>
            </div>
        );
    }
}
