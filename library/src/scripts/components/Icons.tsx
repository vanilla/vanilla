/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";

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

export function leftChevron(className?: string) {
    const title = `<`;
    return (
        <svg
            className={classNames("icon", "icon-chevronLeft", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M14.9,7.7l-4.4,4.4,4.4,4.4a.5.5,0,0,1,0,.6l-.6.6a.5.5,0,0,1-.6,0L8.5,12.5a.5.5,0,0,1,0-.6l5.2-5.2a.5.5,0,0,1,.6,0s.676.543.7.7A.325.325,0,0,1,14.9,7.7Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function close(className?: string, noPadding: boolean = false) {
    const title = t("Close");
    const viewBox = noPadding ? "0 0 16 16" : "0 0 24 24";
    const transform = noPadding ? "translate(-4 -4)" : "";
    return (
        <svg
            className={classNames("icon", "icon-close", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={viewBox}
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                transform={transform}
                fill="currentColor"
                d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"
            />
        </svg>
    );
}

export function check(className?: string) {
    const title = `✓`;
    return (
        <svg
            className={classNames("icon", "icon-check", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <polygon fill="currentColor" points="5,12.7 3.6,14.1 9,19.5 20.4,7.9 19,6.5 9,16.8" />
        </svg>
    );
}

export function dropDownMenu(className?: string) {
    const title = `…`;
    return (
        <svg
            className={classNames("icon", "icon-dropDownMenu", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <circle cx="5.7" cy="12" r="2" fill="currentColor" />
            <circle cx="18.3" cy="12" r="2" fill="currentColor" />
            <circle cx="12" cy="12" r="2" fill="currentColor" />
        </svg>
    );
}

export function newFolder(className?: string, title: string = t("New Folder")) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames("icon", "icon-dropDownMenu", className)}
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M12.25,11.438a.5.5,0,0,0-1,0v2.75H8.5a.5.5,0,0,0,0,1h2.75v2.75a.5.5,0,0,0,1,0v-2.75H15a.5.5,0,0,0,0-1H12.25Z"
                fill="currentColor"
            />
            <path
                d="M21,7.823H13.825L12.457,4.735a.5.5,0,0,0-.457-.3H3a.5.5,0,0,0-.5.5v16a.5.5,0,0,0,.5.5H21a.5.5,0,0,0,.5-.5V8.323A.5.5,0,0,0,21,7.823Zm-.5,12.615H3.5v-15h8.175l1.368,3.087a.5.5,0,0,0,.457.3h7Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function categoryIcon(className?: string) {
    const title = t("Folder");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames("icon", "icon-categoryIcon", className)}
            viewBox="0 0 14.25 12.75"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M9.369,3.164H14.75a.375.375,0,0,1,.375.375V13a.375.375,0,0,1-.375.375H1.25A.375.375,0,0,1,.875,13V1A.375.375,0,0,1,1.25.625H8a.375.375,0,0,1,.343.223ZM1.625,1.375v11.25h12.75V3.914H9.125a.375.375,0,0,1-.343-.223L7.756,1.375Z"
                transform="translate(-0.875 -0.625)"
                fill="currentColor"
            />
        </svg>
    );
}

export function selectedCategory(className?: string) {
    const title = `✓`;
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames("icon", "icon-selectedCategory", className)}
            viewBox="0 0 16.8 13"
            aria-hidden="true"
        >
            <title>{title}</title>
            <polygon
                points="12.136 3.139 13.25 4.253 6.211 11.292 2.75 7.83 3.863 6.717 6.211 9.064 12.136 3.139"
                fill="currentColor"
            />
        </svg>
    );
}
