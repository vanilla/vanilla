/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import {
    FileTypeGenericIcon,
    FileTypeWordIcon,
    FileTypeExcelIcon,
    FileTypePDFIcon,
    FileTypeImageIcon,
    FileTypePowerPointIcon,
    FileTypeZipIcon,
} from "@library/icons/fileTypes";
/**
 * Map a mimeType into an AttachmentType.
 *
 * @param mimeType
 */
export function mimeTypeToAttachmentType(mimeType?: string | null): AttachmentType {
    if (!mimeType) {
        return AttachmentType.FILE;
    }

    if (mimeType.startsWith("image/")) {
        return AttachmentType.IMAGE;
    }

    switch (mimeType) {
        case "application/pdf":
            return AttachmentType.PDF;
        case "application/vnd.ms-excel":
        case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
            return AttachmentType.EXCEL;
        case "application/msword":
        case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
            return AttachmentType.WORD;
        case "application/vnd.ms-powerpoint":
        case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
            return AttachmentType.PPT;
        case "application/zip":
        case "application/x-7z-compressed":
        case "application/x-bzip":
        case "application/x-bzip2":
        case "application/x-rar-compressed":
            return AttachmentType.ARCHIVE;
        default:
            return AttachmentType.FILE;
    }
}

export function GetAttachmentIcon(props: { type: AttachmentType; className?: string }) {
    switch (props.type) {
        case AttachmentType.PDF:
            return <FileTypePDFIcon className={props.className} />;
        case AttachmentType.EXCEL:
            return <FileTypeExcelIcon className={props.className} />;
        case AttachmentType.WORD:
            return <FileTypeWordIcon className={props.className} />;
        case AttachmentType.PPT:
            return <FileTypePowerPointIcon className={props.className} />;
        case AttachmentType.ARCHIVE:
            return <FileTypeZipIcon className={props.className} />;
        case AttachmentType.IMAGE:
            return <FileTypeImageIcon className={props.className} />;
        default:
            return <FileTypeGenericIcon className={props.className} />;
    }
}
