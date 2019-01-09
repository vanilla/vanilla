/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "../../application";
import { AttachmentType, getUnabbreviatedAttachmentType } from "../attachments";

const currentColorFill = {
    fill: "currentColor",
};

export function fileGeneric(className?: string, fileType?: string) {
    const title = !!fileType ? fileType : t("File");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18 18"
            className={classNames("icon", "icon-fileGeneric", "attachmentIcon", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <rect width="18" height="18" style={{ fill: "#4c4c4c" }} />
            <path
                d="M9.616,6.558,13.1,10.045a1.869,1.869,0,0,1,.093,2.688,1.849,1.849,0,0,1-2.688-.094L4.764,6.9c-.1-.1-.99-1.05-.186-1.854s1.749.083,1.854.186l5.189,5.189a.483.483,0,0,1,.01.732.5.5,0,0,1-.754.007L7.948,8.226l-.556.556,2.931,2.931A1.311,1.311,0,1,0,12.177,9.86L6.987,4.67a2.054,2.054,0,0,0-2.965-.185A2.054,2.054,0,0,0,4.207,7.45L9.953,13.2a2.624,2.624,0,1,0,3.706-3.707L10.172,6Z"
                style={{ fill: "#fff" }}
            />
        </svg>
    );
}

export function fileWord(className?: string) {
    const textFill = "#fff";
    const title = t("Word");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18 18"
            className={classNames("icon", "icon-fileWord", "attachmentIcon", className)}
            role="img"
            aria-label={getUnabbreviatedAttachmentType(AttachmentType.WORD)}
        >
            <title>{title}</title>
            <rect width="18" height="18" fill="#2b5599" />
            <polygon
                style={{ fill: "#fff" }}
                points="6.133 13.543 4 5 5.365 5 6.707 11.07 6.73 11.07 8.389 5 9.326 5 10.979 11.07 11.002 11.07 12.35 5 13.715 5 11.582 13.543 10.498 13.543 8.869 7.385 8.846 7.385 7.211 13.543 6.133 13.543"
            />
        </svg>
    );
}

export function fileExcel(className?: string) {
    const title = t("Excel");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18 18"
            className={classNames("icon", "icon-fileExcel", "attachmentIcon", className)}
            role="img"
            aria-label={getUnabbreviatedAttachmentType(AttachmentType.EXCEL)}
        >
            <title>{title}</title>
            <title>{t("Excel Document")}</title>
            <rect width="18" height="18" fill="#2f7d32" />
            <polygon
                style={{ fill: "#fff" }}
                points="9.334 10.361 7.459 13.543 6 13.543 8.613 9.166 6.164 5 7.629 5 9.334 7.965 11.039 5 12.498 5 10.055 9.166 12.668 13.543 11.203 13.543 9.334 10.361"
            />
        </svg>
    );
}

export function filePDF(className?: string) {
    const textFill = "#fff";
    const title = t("PDF");
    const unabbreviatedType = getUnabbreviatedAttachmentType(AttachmentType.PDF);
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18 18"
            className={classNames("icon", "icon-filePDF", "attachmentIcon", className)}
            role="img"
            aria-label={getUnabbreviatedAttachmentType(AttachmentType.PDF)}
        >
            <title>
                <abbr title={unabbreviatedType || undefined}>{AttachmentType.PDF}</abbr>
            </title>
            <rect width="18" height="18" style={{ fill: "#ff3934" }} />
            <path
                style={{ fill: "#fff" }}
                d="M2,13.767V5H3.884a2.815,2.815,0,0,1,.911.135,1.75,1.75,0,0,1,.714.481,1.889,1.889,0,0,1,.444.806,5.053,5.053,0,0,1,.123,1.25,6.2,6.2,0,0,1-.068,1,2.1,2.1,0,0,1-.289.764,1.851,1.851,0,0,1-.69.671,2.325,2.325,0,0,1-1.133.24h-.64V13.77ZM3.256,6.182v2.98h.6a1.29,1.29,0,0,0,.591-.111.7.7,0,0,0,.308-.308,1.112,1.112,0,0,0,.117-.455c.012-.181.019-.382.019-.6s0-.4-.013-.585a1.254,1.254,0,0,0-.111-.486.7.7,0,0,0-.295-.32,1.163,1.163,0,0,0-.566-.111Zm3.755,7.585V5h1.86a2.159,2.159,0,0,1,1.644.591,2.343,2.343,0,0,1,.56,1.675v4.1a2.446,2.446,0,0,1-.6,1.816,2.356,2.356,0,0,1-1.718.585ZM8.267,6.182v6.4h.579a.931.931,0,0,0,.751-.265,1.279,1.279,0,0,0,.222-.831V7.266a1.323,1.323,0,0,0-.21-.8.891.891,0,0,0-.763-.283Zm3.99,7.585V5H16V6.182H13.513v2.66H15.68v1.182H13.513v3.743Z"
            />
        </svg>
    );
}
