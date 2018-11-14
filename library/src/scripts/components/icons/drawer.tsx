/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import classNames from "classnames";

const currentColorFill = {
    fill: "currentColor",
};

export function rightChevron(className?: string) {
    const title = `>`;
    return (
        <svg
            className={classNames("icon", "icon-chevronRight", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M15.4955435,6.92634039 C15.4955435,6.82589384 15.4509005,6.73660802 15.3839362,6.66964366 L14.8258998,6.11160728 C14.7589354,6.04464291 14.6584889,6 14.5692031,6 C14.4799172,6 14.3794707,6.04464291 14.3125063,6.11160728 L9.11160728,11.3125063 C9.04464291,11.3794707 9,11.4799172 9,11.5692031 C9,11.6584889 9.04464291,11.7589354 9.11160728,11.8258998 L14.3125063,17.0267989 C14.3794707,17.0937632 14.4799172,17.1384061 14.5692031,17.1384061 C14.6584889,17.1384061 14.7589354,17.0937632 14.8258998,17.0267989 L15.3839362,16.4687625 C15.4509005,16.4017981 15.4955435,16.3013516 15.4955435,16.2120657 C15.4955435,16.1227799 15.4509005,16.0223334 15.3839362,15.955369 L10.9977702,11.5692031 L15.3839362,7.18303712 C15.4509005,7.11607276 15.4955435,7.01562621 15.4955435,6.92634039 Z"
                transform="matrix(-1 0 0 1 25 .5)"
            />
        </svg>
    );
}

export function bottomChevron(className?: string) {
    const title = `<`;
    return (
        <svg
            className={classNames("icon", "icon-chevronLeft", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path d={leftChefronPath} style={currentColorFill} />
        </svg>
    );
}
