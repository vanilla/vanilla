/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { fileExcel, fileGeneric, filePDF, fileWord } from "@library/components/icons/fileTypes";
import { t } from "@library/application";

export enum AttachmentType {
    FILE = "file",
    PDF = "PDF",
    EXCEL = "excel",
    WORD = "word",
}

export function getAttachmentIcon(type: AttachmentType, className?: string) {
    switch (type) {
        case AttachmentType.EXCEL:
            return fileExcel(className);
        case AttachmentType.PDF:
            return filePDF(className);
        case AttachmentType.WORD:
            return fileWord(className);
        default:
            return fileGeneric(className);
    }
}

export function getUnabbreviatedAttachmentType(type: AttachmentType): string | null {
    switch (type) {
        case AttachmentType.EXCEL:
            return t("Microsoft Excel Document");
        case AttachmentType.PDF:
            return t("Adobe Portable Document Format");
        case AttachmentType.WORD:
            return t("Microsoft Word Document");
        default:
            return null;
    }
}
