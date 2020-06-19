/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { iconClasses } from "@library/icons/iconStyles";
import { areaHiddenType } from "@library/styles/styleHelpersVisibility";
import { SearchIcon } from "./titleBar";

export function SearchFilterAll(props: { className?: string; "aria-hidden"?: areaHiddenType; centred?: boolean }) {
    return <SearchIcon {...props} />;
}

export function TypeDiscussions(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Discussions")}</title>
            <path
                fill="currentColor"
                d="M9 3C5.17 3 2 5.464 2 8.612c0 1.274.527 2.474 1.463 3.44l-.487 1.984c-.158.641.507 1.174 1.1.882l2.256-1.114c.54.178 1.107.3 1.69.365.024-.261-.29-.72-.222-.969-.665-.07-.937-.28-1.518-.5l-2.551 1.521.769-2.419c-.963-.85-1.5-1.968-1.5-3.19C3 5.942 5.564 4 9 4s5.4 2 5.4 3c0 .131 1.313.871 1.3 1 0-3-3.7-5-6.7-5z"
            />
            <path
                stroke="currentColor"
                d="M14 7.5c1.804 0 3.454.578 4.647 1.535 1.134.908 1.853 2.162 1.853 3.577 0 1.191-.519 2.307-1.42 3.193l-.51 2.38-1.868-.922c-.842.303-1.759.46-2.702.46-1.804 0-3.454-.578-4.647-1.534C8.219 15.28 7.5 14.026 7.5 12.612c0-1.415.72-2.669 1.853-3.577C10.546 8.078 12.196 7.5 14 7.5z"
            />
        </svg>
    );
}

export function TypeArticles(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Articles")}</title>
        </svg>
    );
}

export function TypeCategoriesAndGroups(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Categories & Groups")}</title>
        </svg>
    );
}

export function TypeMember(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Members")}</title>
        </svg>
    );
}

export function TypeCategories(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Categories")}</title>
        </svg>
    );
}

export function TypeIdeas(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Ideas")}</title>
        </svg>
    );
}

export function TypePolls(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Polls")}</title>
        </svg>
    );
}

export function TypeQuestion(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
        >
            <title>{t("Questions")}</title>
        </svg>
    );
}
