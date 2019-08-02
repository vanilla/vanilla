/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import { iconClasses } from "@library/icons/iconClasses";

const currentColorFill = {
    fill: "currentColor",
};

export function FileTypeGenericIcon(props: { className?: string; fileType?: string }) {
    const title = props.fileType ? props.fileType : t("File");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-fileGeneric", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <rect width="16" height="16" rx="1" style={{ fill: "#777b81" }} />
            <path
                d="M11.5,6v4a3.418,3.418,0,0,1-3.334,3.5H8a3.418,3.418,0,0,1-3.5-3.334c0-.055,0-.111,0-.166V5A2.362,2.362,0,0,1,6.715,2.5,2.259,2.259,0,0,1,7,2.5,2.362,2.362,0,0,1,9.5,4.715,2.258,2.258,0,0,1,9.5,5V9.5c0,1-.5,2-1.5,2s-1.5-1-1.5-2V6"
                style={{ fill: "none", stroke: "#f1f2f2", strokeLinecap: "round" }}
            />
        </svg>
    );
}

export function FileTypeWordIcon(props: { className?: string }) {
    const title = t("Word");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-fileWord", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={t(AttachmentType.WORD)}
        >
            <title>{title}</title>
            <rect width="16" height="16" rx="1" style={{ fill: "#0175fc" }} />
            <rect x="3" y="3" width="10" height="1" style={{ fill: "#cce3fe" }} />
            <rect x="3" y="5" width="10" height="1" style={{ fill: "#cce3fe" }} />
            <rect x="3" y="12" width="10" height="1" style={{ fill: "#cce3fe" }} />
            <rect x="3" y="10" width="10" height="1" style={{ fill: "#cce3fe" }} />
            <rect x="3" y="7" width="6" height="1" style={{ fill: "#cce3fe" }} />
        </svg>
    );
}

export function FileTypeExcelIcon(props: { className?: string }) {
    const title = t("Excel");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-fileExcel", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={t(AttachmentType.EXCEL)}
        >
            <title>{title}</title>
            <rect width="16" height="16" fill="#2f7d32" />
            <polygon
                style={{ fill: "#fff" }}
                points="9.334 10.361 7.459 13.543 6 13.543 8.613 9.166 6.164 5 7.629 5 9.334 7.965 11.039 5 12.498 5 10.055 9.166 12.668 13.543 11.203 13.543 9.334 10.361"
            />
        </svg>
    );
}

export function FileTypePDFIcon(props: { className?: string }) {
    const unabbreviatedType = t(AttachmentType.PDF);
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-filePDF", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={t(AttachmentType.PDF)}
        >
            <title>
                <abbr title={unabbreviatedType || undefined}>{AttachmentType.PDF}</abbr>
            </title>
            <rect width="16" height="16" rx="1" style={{ fill: "#c80000" }} />
            <path
                d="M2.5,11V8.5m4.5,2v-5H8q1.5,0,1.5,2.143v.714Q9.5,10.5,8,10.5Zm-4.5-2v-3h1Q5,5.5,5,6.786v.428Q5,8.5,3.5,8.5Z"
                style={{ stroke: "#fff", fill: "none" }}
            />
            <polygon
                points="11 11 11 5 14 5 14 6 12.007 6 12 7.5 13.493 7.5 13.493 8.5 12 8.5 12 11 11 11"
                style={{ fill: "#fff" }}
            />
        </svg>
    );
}

export function FileTypeImageIcon(props: { className?: string }) {
    const title = t("Image");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-fileImage", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={t(AttachmentType.IMAGE)}
        >
            <title>{title}</title>

            <rect width="16" height="16" rx="1" style={{ fill: "#c80090" }} />
            <path
                d="M3,12.5a1,1,0,0,1-1-1V6A1,1,0,0,1,3,5h.5V4.75a.25.25,0,0,1,.25-.25h.5a.25.25,0,0,1,.25.25V5a.81.81,0,0,0,.724-.447l.5-1A1,1,0,0,1,6.618,3H9.382a1,1,0,0,1,.894.553l.448.894A1,1,0,0,0,11.618,5H13a1,1,0,0,1,1,1v5.5a1,1,0,0,1-1,1Zm5-2a2,2,0,1,1,2-2A2,2,0,0,1,8,10.5Zm0,1a3,3,0,1,0-3-3A3,3,0,0,0,8,11.5ZM11.5,6V7H13V6Z"
                style={{ fill: "#fff", fillOpacity: 0.97 }}
            />
        </svg>
    );
}

export function FileTypePowerPointIcon(props: { className?: string }) {
    const textFill = "#fff";
    const title = t("PPT");
    const unabbreviatedType = t(AttachmentType.PPT);
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-filePowerPoint", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={t(AttachmentType.IMAGE)}
        >
            <title>
                {unabbreviatedType ? <abbr title={unabbreviatedType}>{AttachmentType.PDF}</abbr> : AttachmentType.PDF}
            </title>
            <rect width="16" height="16" rx="1" style={{ fill: "#ee6a01" }} />
            <path d="M8,4V7.5h3.55A3.5,3.5,0,1,1,8,4Z" style={{ fill: "#fbe1cc" }} />
            <path d="M9,3h.05a3.5,3.5,0,0,1,3.5,3.5H9Z" style={{ fill: "#fbe1cc" }} />
            <rect x="3" y="12" width="10" height="1" style={{ fill: "#fbe1cc" }} />
        </svg>
    );
}

export function FileTypeZipIcon(props: { className?: string }) {
    const title = t("Zip");
    const unabbreviatedType = t(AttachmentType.ARCHIVE);
    const barStyle = { fill: "#fff", fillOpacity: 0.9 };
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("attachmentIcon-fileZip", "attachmentIcon", classes.fileType, props.className)}
            role="img"
            aria-label={t(AttachmentType.IMAGE)}
        >
            <title>
                {unabbreviatedType ? <abbr title={unabbreviatedType}>{AttachmentType.PDF}</abbr> : AttachmentType.PDF}
            </title>
            <rect width="16" height="16" rx="1" style={{ fill: "#eeb601" }} />
            <path
                d="M6,7.5h4l.45,4.955A.5.5,0,0,1,10,13H6.054a.5.5,0,0,1-.5-.5.338.338,0,0,1,0-.045Zm.99,3a.149.149,0,0,0-.149.14l-.08,1.2v.01a.15.15,0,0,0,.15.15H9.1a.149.149,0,0,0,.139-.16l-.08-1.2a.149.149,0,0,0-.149-.14Z"
                style={{ fill: "#fdf7e6" }}
            />
            <rect x="6" y="6" width="2" height="1" style={barStyle} />
            <rect x="8" y="5" width="2" height="1" style={barStyle} />
            <rect x="6" y="4" width="2" height="1" style={barStyle} />
            <rect x="8" y="3" width="2" height="1" style={barStyle} />
            <rect x="6" y="2" width="2" height="1" style={barStyle} />
            <rect x="8" y="1" width="2" height="1" style={barStyle} />
            <rect x="6" width="2" height="1" style={barStyle} />
        </svg>
    );
}

export function AttachmentErrorIcon(props: { className?: string }) {
    const title = t("Error");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 18"
            className={classNames("attachmentIcon-error", classes.attachmentError, props.className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M.9,18a.917.917,0,0,1-.9-.86.9.9,0,0,1,.107-.449L9.17.474A.976.976,0,0,1,10.445.107a.906.906,0,0,1,.381.36V.474l9.061,16.215a.889.889,0,0,1-.378,1.2l-.036.018a.951.951,0,0,1-.416.093Zm.021-1ZM9.985,1.012,1.081,17H18.919Z"
                fill="currentColor"
            />
            <path d="M9,8.4V6h2V8.4L10.8,12H9.3ZM9,13h2v2H9Z" fill="currentColor" />
        </svg>
    );
}
