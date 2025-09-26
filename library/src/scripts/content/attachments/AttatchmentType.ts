/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const AttachmentType = {
    FILE: "A file",
    PDF: "Adobe Portable Document Format (PDF)",
    EXCEL: "Microsoft Excel Spreadsheet",
    WORD: "Microsoft Word Document",
    PPT: "Microsoft PowerPoint Presentation",
    ARCHIVE: "An archived file or files",
    IMAGE: "An image file",
} as const;
export type AttachmentType = (typeof AttachmentType)[keyof typeof AttachmentType];
