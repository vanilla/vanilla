/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { AttachmentType, getUnabbreviatedAttachmentType } from "@library/components/attachments";

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

export function clear(className?: string, noPadding: boolean = false) {
    const title = t("Clear");
    return (
        <svg
            className={classNames("icon", "icon-close", className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M12,5a7,7,0,1,0,7,7A7,7,0,0,0,12,5Zm2.873,5.244L13.118,12l1.755,1.756a.337.337,0,0,1,0,.479l-.638.638a.337.337,0,0,1-.479,0L12,13.118l-1.756,1.755a.337.337,0,0,1-.479,0l-.638-.638a.337.337,0,0,1,0-.479L10.882,12,9.127,10.244a.337.337,0,0,1,0-.479l.638-.638a.337.337,0,0,1,.479,0L12,10.882l1.756-1.755a.337.337,0,0,1,.479,0l.638.638A.337.337,0,0,1,14.873,10.244Z"
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

export function checkCompact(className?: string) {
    const title = `✓`;
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames("icon", "icon-selectedCategory", className)}
            viewBox="0 0 16.8 13"
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <polygon
                points="12.136 3.139 13.25 4.253 6.211 11.292 2.75 7.83 3.863 6.717 6.211 9.064 12.136 3.139"
                fill="currentColor"
            />
        </svg>
    );
}

export function fileGeneric(className?: string, fileType?: string) {
    const title = fileType | t("File");
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
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 18 18"
            className={classNames("icon", "icon-filePDF", "attachmentIcon", className)}
            role="img"
            aria-label={getUnabbreviatedAttachmentType(AttachmentType.PDF)}
        >
            <title>
                <abbr className="sr-only" title={getUnabbreviatedAttachmentType(AttachmentType.PDF)}>
                    {AttachmentType.PDF}
                </abbr>
            </title>
            <rect width="18" height="18" style={{ fill: "#ff3934" }} />
            <path
                style={{ fill: "#fff" }}
                d="M2,13.767V5H3.884a2.815,2.815,0,0,1,.911.135,1.75,1.75,0,0,1,.714.481,1.889,1.889,0,0,1,.444.806,5.053,5.053,0,0,1,.123,1.25,6.2,6.2,0,0,1-.068,1,2.1,2.1,0,0,1-.289.764,1.851,1.851,0,0,1-.69.671,2.325,2.325,0,0,1-1.133.24h-.64V13.77ZM3.256,6.182v2.98h.6a1.29,1.29,0,0,0,.591-.111.7.7,0,0,0,.308-.308,1.112,1.112,0,0,0,.117-.455c.012-.181.019-.382.019-.6s0-.4-.013-.585a1.254,1.254,0,0,0-.111-.486.7.7,0,0,0-.295-.32,1.163,1.163,0,0,0-.566-.111Zm3.755,7.585V5h1.86a2.159,2.159,0,0,1,1.644.591,2.343,2.343,0,0,1,.56,1.675v4.1a2.446,2.446,0,0,1-.6,1.816,2.356,2.356,0,0,1-1.718.585ZM8.267,6.182v6.4h.579a.931.931,0,0,0,.751-.265,1.279,1.279,0,0,0,.222-.831V7.266a1.323,1.323,0,0,0-.21-.8.891.891,0,0,0-.763-.283Zm3.99,7.585V5H16V6.182H13.513v2.66H15.68v1.182H13.513v3.743Z"
            />
        </svg>
    );
}

export function downTriangle(className?: string, title: string = "▾", deg?: number) {
    let transform;
    if (deg) {
        transform = { transform: `rotate(${deg}deg` };
    }
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 8 8"
            className={classNames("icon", "icon-triangleRight", className)}
            aria-hidden="true"
            style={transform}
        >
            <title>{title}</title>
            <polygon points="0 2.594 8 2.594 4 6.594 0 2.594" fill="currentColor" />
        </svg>
    );
}

export function rightTriangle(title: string = `▶`, className?: string) {
    return downTriangle(className, title, -90);
}

export function revisionStatus_revision(className?: string) {
    const title = t("Revision");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "revisionIcon", "revisionIcon-revision", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <rect width="24" height="24" fill="transparent" />
        </svg>
    );
}

export function revisionStatus_draft(className?: string) {
    const title = t("Draft");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "revisionIcon", "revisionIcon-draft", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M3.745,21a.483.483,0,0,0,.2-.025L9.109,19.47a1.539,1.539,0,0,0,.742-.444L20.506,8.387A1.733,1.733,0,0,0,21,7.153a1.676,1.676,0,0,0-.494-1.209L18.058,3.5a1.748,1.748,0,0,0-2.447,0L4.981,14.138a2.047,2.047,0,0,0-.445.74L3.028,20.037a.762.762,0,0,0,.2.74A.754.754,0,0,0,3.745,21ZM13.856,7.375l2.793,2.789L9.307,17.5,6.514,14.706ZM16.7,4.537a.267.267,0,0,1,.173-.074.225.225,0,0,1,.173.074L19.492,6.98a.429.429,0,0,1,.074.173.2.2,0,0,1-.074.173L17.712,9.1,14.919,6.314ZM5.747,16.014l2.225,2.221-3.14.913Z"
            />
            <path
                fill="currentColor"
                d="M3.745,21a.483.483,0,0,0,.2-.025L9.109,19.47a1.539,1.539,0,0,0,.742-.444L20.506,8.387A1.733,1.733,0,0,0,21,7.153a1.676,1.676,0,0,0-.494-1.209L18.058,3.5a1.748,1.748,0,0,0-2.447,0L4.981,14.138a2.047,2.047,0,0,0-.445.74L3.028,20.037a.762.762,0,0,0,.2.74A.754.754,0,0,0,3.745,21ZM13.856,7.375l2.793,2.789L9.307,17.5,6.514,14.706ZM16.7,4.537a.267.267,0,0,1,.173-.074.225.225,0,0,1,.173.074L19.492,6.98a.429.429,0,0,1,.074.173.2.2,0,0,1-.074.173L17.712,9.1,14.919,6.314ZM5.747,16.014l2.225,2.221-3.14.913Z"
            />
        </svg>
    );
}

export function revisionStatus_pending(className?: string) {
    const title = t("Pending");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "revisionIcon", "revisionIcon-pending", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M12,3.875a8.194,8.194,0,1,0,8.194,8.194A8.193,8.193,0,0,0,12,3.875Zm0,14.8a6.608,6.608,0,1,1,6.608-6.608A6.606,6.606,0,0,1,12,18.677Zm2.042-3.449-2.8-2.039a.4.4,0,0,1-.162-.32V7.443a.4.4,0,0,1,.4-.4h1.058a.4.4,0,0,1,.4.4v4.682l2.207,1.606a.4.4,0,0,1,.086.555l-.621.856a.4.4,0,0,1-.555.086Z"
            />
        </svg>
    );
}

export function revisionStatus_published(className?: string) {
    return checkCompact(className);
}

export function revisionStatus_deleted(className?: string) {
    const title = t("Deleted");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "revisionIcon", "revisionIcon-deleted", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                style={currentColorFill}
                d="M19.444,6.651a.334.334,0,0,0-.247-.1H15.888l-.75-1.788a1.484,1.484,0,0,0-.578-.675,1.512,1.512,0,0,0-.846-.279H10.286a1.512,1.512,0,0,0-.846.279,1.484,1.484,0,0,0-.578.675l-.75,1.788H4.8A.33.33,0,0,0,4.46,6.9v.685a.329.329,0,0,0,.343.343H5.831v10.2a2.348,2.348,0,0,0,.5,1.516,1.507,1.507,0,0,0,1.21.626h8.912a1.5,1.5,0,0,0,1.21-.647,2.438,2.438,0,0,0,.5-1.537V7.925H19.2a.329.329,0,0,0,.343-.343V6.9a.333.333,0,0,0-.1-.246ZM10.126,5.3a.3.3,0,0,1,.182-.118H13.7a.308.308,0,0,1,.182.118L14.4,6.554H9.6L10.126,5.3ZM16.8,18.079a1.212,1.212,0,0,1-.075.433.965.965,0,0,1-.155.289c-.054.061-.091.091-.112.091H7.544c-.021,0-.058-.03-.112-.091a.943.943,0,0,1-.155-.289,1.212,1.212,0,0,1-.075-.433V7.925h9.6V18.079ZM8.915,16.836H9.6a.33.33,0,0,0,.343-.343V10.324A.33.33,0,0,0,9.6,9.982H8.915a.329.329,0,0,0-.342.342v6.169a.329.329,0,0,0,.342.343Zm2.742,0h.686a.329.329,0,0,0,.342-.343V10.324a.329.329,0,0,0-.342-.342h-.686a.329.329,0,0,0-.342.342v6.169a.329.329,0,0,0,.342.343Zm2.742,0h.686a.329.329,0,0,0,.342-.343V10.324a.329.329,0,0,0-.342-.342H14.4a.33.33,0,0,0-.343.342v6.169a.33.33,0,0,0,.343.343Z"
            />
        </svg>
    );
}

export function help(className?: string) {
    const title = t("Help");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames("icon", "icon-help", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M12,19a7,7,0,1,0-7-7A7,7,0,0,0,12,19Zm0,1a8,8,0,1,1,8-8A8,8,0,0,1,12,20Zm-.866-6.5v-.338a2,2,0,0,1,.211-.969,2.757,2.757,0,0,1,.741-.8,4.09,4.09,0,0,0,.812-.773,1.156,1.156,0,0,0,.183-.656.826.826,0,0,0-.3-.683,1.333,1.333,0,0,0-.851-.238A2.941,2.941,0,0,0,11,9.185a6.65,6.65,0,0,0-.836.344L9.721,8.6a4.653,4.653,0,0,1,2.3-.6,2.485,2.485,0,0,1,1.645.508,1.727,1.727,0,0,1,.609,1.4,1.983,1.983,0,0,1-.117.706,2.006,2.006,0,0,1-.352.59,5.653,5.653,0,0,1-.812.731,3.088,3.088,0,0,0-.659.64,1.229,1.229,0,0,0-.166.682V13.5Zm-.217,1.688a.7.7,0,0,1,.778-.8.775.775,0,0,1,.582.209.818.818,0,0,1,.2.59.838.838,0,0,1-.2.595.878.878,0,0,1-1.156.006A.844.844,0,0,1,10.917,15.185Z"
                transform="translate(-4 -4)"
                fill="currentColor"
            />
        </svg>
    );
}

export function compose(className?: string) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "icon-compose", className)}
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M23.591,1.27l-.9-.9a1.289,1.289,0,0,0-1.807,0l-.762.863,2.6,2.587.868-.751a1.24,1.24,0,0,0,.248-.373,1.255,1.255,0,0,0,0-1.052A1.232,1.232,0,0,0,23.591,1.27ZM19.5,20.5H3.5V4.5H15.4l1.4-1.431H2.751A1,1,0,0,0,2,4.07V20.939a1,1,0,0,0,1,1H20.011a1,1,0,0,0,1-1V7L19.5,8.445ZM21.364,3.449l-9.875,9.8-.867-.861,9.874-9.8-.867-.863-4.938,4.9-4.938,4.9L8.74,15.167l3.617-1.055,9.875-9.8Z"
            />
        </svg>
    );
}

export function download(className?: string) {
    const title = t("Download");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames("icon", "icon-compose", className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M6.483,10.462h.005a.5.5,0,0,1,.707.01l3.954,4.066V3.887a.5.5,0,0,1,.5-.5h.621a.5.5,0,0,1,.5.5V14.533l4.035-4.073h0a.5.5,0,0,1,.707,0l.437.437a.5.5,0,0,1,0,.707h0l-5.6,5.6a.5.5,0,0,1-.707,0h0l-5.6-5.6a.5.5,0,0,1,0-.707h0ZM20.25,19.5V17.25a.75.75,0,0,1,1.5,0v3A.75.75,0,0,1,21,21H3a.75.75,0,0,1-.75-.75v-3a.75.75,0,0,1,1.5,0V19.5Z"
                fill="currentColor"
            />
        </svg>
    );
}
