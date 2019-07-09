/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    filePDF,
    fileExcel,
    fileWord,
    filePowerPoint,
    fileZip,
    fileImage,
    fileGeneric,
} from "@library/icons/fileTypes";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";

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

export function getAttachmentIcon(type: AttachmentType, className?: string) {
    switch (type) {
        case AttachmentType.PDF:
            return filePDF(className);
        case AttachmentType.EXCEL:
            return fileExcel(className);
        case AttachmentType.WORD:
            return fileWord(className);
        case AttachmentType.PPT:
            return filePowerPoint(className);
        case AttachmentType.ARCHIVE:
            return fileZip(className);
        case AttachmentType.IMAGE:
            return fileImage(className);
        default:
            return fileGeneric(className);
    }
}
