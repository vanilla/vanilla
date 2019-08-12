/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { CheckCompactIcon } from "./common";
import { iconClasses } from "@library/icons/iconClasses";

const currentColorFill = {
    fill: "currentColor",
};

// Placeholder
export function RevisionStatusRevisionIcon(props: { className?: string }) {
    const title = t("Revision");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "revisionIcon", "revisionIcon-revision", props.className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <rect width="24" height="24" fill="transparent" />
        </svg>
    );
}

export function RevisionStatusDraftIcon(props: { className?: string }) {
    const title = t("Draft");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "revisionIcon", "revisionIcon-draft", props.className)}
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

export function RevisionStatusPendingIcon(props: { className?: string }) {
    const title = t("Pending");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "revisionIcon", "revisionIcon-pending", props.className)}
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

export function RevisionStatusPublishedIcon(props: { className?: string }) {
    return <CheckCompactIcon {...props} />;
}

export function RevisionStatusDeletedIcon(props: { className?: string }) {
    const title = t("Deleted");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "revisionIcon", "revisionIcon-deleted", props.className)}
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
