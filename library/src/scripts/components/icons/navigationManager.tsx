/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { checkCompact } from "./common";

const currentColorFill = {
    fill: "currentColor",
};

export function expandAll(className?: string) {
    const title = t("Expand All");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "revisionIcon", "revisionIcon-revision", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M8,5H22V7H8ZM8,9H22v1.5H8ZM2,5H7L4.5,7.5Zm6,9H22v2H8ZM2,14H7L4.5,16.5Zm6,4H22v1.5H8Z"
                fill="currentColor"
                transform="translate(0 -1)"
            />
        </svg>
    );
}

export function collapseAll(className?: string) {
    const title = t("Collapse All");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "revisionIcon", "revisionIcon-revision", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M3,12V6L6,9Zm0,7V13l3,3ZM8,8H22v2H8Zm0,7H22v2H8Z"
                fill="currentColor"
                transform="translate(0 -1.2)"
            />
        </svg>
    );
}

export function folderClosed(className?: string) {
    const title = t("Closed Folder");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 14"
            className={classNames("icon", "icon-folderClosed", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M1.5,14h13A1.5,1.5,0,0,0,16,12.5v-9A1.5,1.5,0,0,0,14.5,2H8L6.222,0H1.5A1.5,1.5,0,0,0,0,1.5v11A1.5,1.5,0,0,0,1.5,14Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function folderOpen(className?: string) {
    const title = t("Open Folder");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18.612 14"
            className={classNames("icon", "icon-folderOpen", className)}
            role="img"
            aria-label={title}
        >
            <path
                d="M0,10.926V1.5A1.5,1.5,0,0,1,1.5,0H6.222L8,2h6.5A1.5,1.5,0,0,1,16,3.5V4H4.081A2.5,2.5,0,0,0,1.709,5.709Zm.329,2.087L2.658,6.026A1.5,1.5,0,0,1,4.081,5H17.613a1,1,0,0,1,.948,1.316l-2.333,7a1,1,0,0,1-.949.684H1.041a.75.75,0,0,1-.75-.75A.759.759,0,0,1,.329,13.013Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function article(className?: string, fillClass?: string) {
    const title = t("Article");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("icon", "icon-article", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            {!!fillClass && <rect className={fillClass} x="0.75" y="0.75" width="14.5" height="14.5" />}
            <path
                d="M1,1V15H15V1ZM1,0H15a1,1,0,0,1,1,1V15a1,1,0,0,1-1,1H1a1,1,0,0,1-1-1V1A1,1,0,0,1,1,0ZM4,4V7H7V4ZM3,3H8V8H3Zm0,8h7v1H3ZM3,9H13v1H3ZM9,5h4V6H9ZM9,7h4V8H9ZM9,3h4V4H9Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function organize(className?: string) {
    const rectStyle: React.CSSProperties = {
        fill: "none",
        stroke: "#777a80",
    };
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18 16"
            className={classNames("icon", "icon-organize", className)}
        >
            <title>{t("Organize")}</title>
            <rect x="0.5" y="0.5" width="14" height="3" rx="0.5" style={rectStyle} />
            <rect x="3.5" y="6.5" width="14" height="3" rx="0.5" style={rectStyle} />
            <rect x="0.5" y="12.5" width="14" height="3" rx="0.5" style={rectStyle} />
        </svg>
    );
}
