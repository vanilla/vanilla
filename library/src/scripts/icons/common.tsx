/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { iconClasses } from "@library/icons/iconStyles";
import { areaHiddenType } from "@library/styles/styleHelpersVisibility";
import { cx } from "@emotion/css";
import { Icon } from "@vanilla/icons";

const currentColorFill = {
    fill: "currentColor",
};

const leftChevronPath =
    "M3.621,10.5l7.94-7.939A1.5,1.5,0,0,0,9.439.439h0l-9,9a1.5,1.5,0,0,0,0,2.121h0l9,9a1.5,1.5,0,0,0,2.122-2.122Z";

const horizontalChevronViewBox = (centred?: boolean) => {
    return centred ? "-8 -4 30 30" : "0 0 24 24";
};

// non breaking space not just a regular space.
export const NBSP = " ";

export function RightChevronIcon(props: {
    className?: string;
    "aria-hidden"?: areaHiddenType;
    centred?: boolean;
    title?: string;
}) {
    const title = props.title ? props.title : `>`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronRight", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={horizontalChevronViewBox(props.centred)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            style={{ transform: "scaleX(-1)", transformOrigin: "50% 50%" }}
        >
            <title>{title}</title>
            <path
                transform="translate(0 1.75)"
                className="icon-chevronRightPath"
                d={leftChevronPath}
                style={currentColorFill}
            />
        </svg>
    );
}

export function LeftChevronIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; centred?: boolean }) {
    const title = `<`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronLeft", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={horizontalChevronViewBox(props.centred)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                transform="translate(0 1.75)"
                className="icon-chevronLeftPath"
                d={leftChevronPath}
                style={currentColorFill}
            />
        </svg>
    );
}

export function LeftChevronCompactIcon(props: {
    className?: string;
    "aria-hidden"?: areaHiddenType;
    isSmall?: boolean;
}) {
    const title = `<`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.chevronLeftCompact, "icon-chevronLeftCompact", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 12 21"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path d={leftChevronPath} style={currentColorFill} />
        </svg>
    );
}

export function TopChevronIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = `↑`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronUp", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M5.467,14.858l.668.618a.442.442,0,0,0,.614,0L12,10.614l5.252,4.862a.442.442,0,0,0,.614,0l.668-.618a.369.369,0,0,0,0-.569L12.308,8.524a.443.443,0,0,0-.615,0L5.467,14.289a.376.376,0,0,0-.134.284A.381.381,0,0,0,5.467,14.858Z"
            />
        </svg>
    );
}

export function BottomChevronIcon(props: {
    className?: string;
    "aria-hidden"?: areaHiddenType;
    rotate?: number;
    title?: string;
}) {
    const title = props.title ? props.title : `↓`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronDown", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            style={{
                transform: props.rotate ? `rotate(${props.rotate}deg)` : undefined,
            }}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M18.534,9.142l-.668-.618a.442.442,0,0,0-.614,0L12,13.386,6.749,8.524a.442.442,0,0,0-.614,0l-.668.618a.369.369,0,0,0,0,.569l6.226,5.765a.443.443,0,0,0,.615,0l6.226-5.765a.376.376,0,0,0,.134-.284A.381.381,0,0,0,18.534,9.142Z"
            />
        </svg>
    );
}

export function RightChevronSmallIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = `↑`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronUp", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                transform="translate(0 50%)"
                fill="currentColor"
                d="M13.8,12l-6,6c-0.4,0.4-0.4,1.2,0,1.6c0.4,0.4,1.2,0.4,1.6,0l6.8-6.9c0.4-0.4,0.4-1.2,0-1.6L9.4,4.3
				C9,3.9,8.3,3.9,7.8,4.3C7.4,4.8,7.4,5.5,7.8,6L13.8,12z"
            />
        </svg>
    );
}

export function LeftChevronSmallIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = `↑`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronUp", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                transform="translate(0 50%)"
                fill="currentColor"
                d="M9.2,12l6-6c0.4-0.4,0.4-1.2,0-1.6c-0.4-0.4-1.2-0.4-1.6,0l-6.8,6.9c-0.4,0.4-0.4,1.2,0,1.6l6.8,6.9
				c0.4,0.4,1.2,0.4,1.6,0c0.4-0.4,0.4-1.2,0-1.6L9.2,12z"
            />
        </svg>
    );
}

export function CloseIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; compact?: boolean }) {
    const title = t("Close");
    const viewBox = props.compact ? "0 0 16 16" : "0 0 24 24";
    const transform = props.compact ? "translate(-4 -4)" : "";
    const classes = iconClasses();
    return (
        <svg
            className={classNames(props.compact ? classes.compact : classes.close, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={viewBox}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
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

export function CloseCompactIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    return (
        <CloseIcon
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            compact={true}
            className={props.className}
        />
    );
}

export function CloseTinyIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = t("Close");
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.closeTiny, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 9.5 9.5"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M10.836,11.75,7.793,8.707A1,1,0,0,1,9.207,7.293l3.043,3.043,3.043-3.043a1,1,0,0,1,1.414,1.414L13.664,11.75l3.043,3.043a1,1,0,0,1-1.414,1.414L12.25,13.164,9.207,16.207a1,1,0,1,1-1.439-1.389l.025-.025Z"
                transform="translate(-7.488 -7.012)"
            />
        </svg>
    );
}

export function ClearIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = t("Clear");
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-clear", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M12,5a7,7,0,1,0,7,7A7,7,0,0,0,12,5Zm2.873,5.244L13.118,12l1.755,1.756a.337.337,0,0,1,0,.479l-.638.638a.337.337,0,0,1-.479,0L12,13.118l-1.756,1.755a.337.337,0,0,1-.479,0l-.638-.638a.337.337,0,0,1,0-.479L10.882,12,9.127,10.244a.337.337,0,0,1,0-.479l.638-.638a.337.337,0,0,1,.479,0L12,10.882l1.756-1.755a.337.337,0,0,1,.479,0l.638.638A.337.337,0,0,1,14.873,10.244Z"
            />
        </svg>
    );
}

export function CheckIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = `✓`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-check", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <polygon fill="currentColor" points="5,12.7 3.6,14.1 9,19.5 20.4,7.9 19,6.5 9,16.8" />
        </svg>
    );
}

export function NewFolderIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.newFolder, props.className)}
            viewBox="0 0 22 19"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{props.title ? props.title : t("New Folder")}</title>
            <path
                d="M1,15.066V3.77Q1,1,3.548,1H9.12L11.3,3.769h7.286q2.372-.083,2.372,2.6v8.7q0,3.205-2.372,3.206H3.548Q1,18.272,1,15.066Z"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "1.2px" }}
            />
            <path
                d="M10.5,8v6m3-3h-6"
                style={{ fill: "none", stroke: "currentColor", strokeLinecap: "round", strokeWidth: "1.2px" }}
            />
        </svg>
    );
}

export function ErrorIcon(props: { className?: string; errorMessage?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    const { className, errorMessage = t("Error") } = props;

    return (
        <svg
            className={classNames(classes.warning, className)}
            aria-label={errorMessage}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 17 17"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{errorMessage}</title>
            <path
                d="M11.753,4.247a.843.843,0,0,1,0,1.19L9.191,8l2.562,2.562a.842.842,0,0,1,.076,1.105l-.076.086a.843.843,0,0,1-1.19,0L8,9.191,5.438,11.753a.842.842,0,0,1-1.191-1.19L6.809,8,4.247,5.438a.842.842,0,0,1-.076-1.1l.076-.086a.843.843,0,0,1,1.19,0L8,6.809l2.562-2.562a.842.842,0,0,1,1.191,0Z"
                transform="translate(0.5 0.5)"
                style={{ fill: "currentColor", fillRule: "evenodd" }}
            />
            <circle cx="8.5" cy="8.5" r="8" style={{ fill: "none", stroke: "currentColor" }} />
        </svg>
    );
}

export function PendingIcon(props: { className?: string }) {
    const classes = iconClasses();
    const { className } = props;
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 17 17"
            className={classNames(classes.standard, className)}
            role="img"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <path
                fill="currentColor"
                d="M5.5 12.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"
                transform="translate(1,1) scale(0.6)"
            />
            <circle cx="8.5" cy="8.5" r="8" style={{ fill: "none", stroke: "currentColor" }} />
        </svg>
    );
}

export function ApproveIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    const { className } = props;

    return (
        <svg
            className={classNames(classes.warning, className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 17 17"
            id="checkmark"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <polygon
                points="12.136 3.139 13.25 4.253 6.211 11.292 2.75 7.83 3.863 6.717 6.211 9.064 12.136 3.139"
                fill="currentColor"
                transform="translate(0,1)"
            />
            <circle cx="8.5" cy="8.5" r="8" style={{ fill: "none", stroke: "currentColor" }} />
        </svg>
    );
}

export function InformationIcon(props: {
    className?: string;
    informationMessage?: string;
    "aria-hidden"?: areaHiddenType;
}) {
    const classes = iconClasses();
    const { className, informationMessage = t("Information") } = props;

    return (
        <svg
            className={classNames(classes.warning, className)}
            aria-label={informationMessage}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{informationMessage}</title>
            <circle cx="8" cy="8" r="7.5" style={{ fill: "none", stroke: "currentColor" }} />
            <path
                d="M9,12H7V7H9ZM7,5.006a1.063,1.063,0,0,1,.236-.757A1.006,1.006,0,0,1,8,4a1.012,1.012,0,0,1,.764.254A1.058,1.058,0,0,1,9,5.006.883.883,0,0,1,8,6,.879.879,0,0,1,7,5.006Z"
                style={{ fill: "currentColor", fillRule: "evenodd" }}
            />
        </svg>
    );
}

export function CategoryIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = t("Folder");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.categoryIcon, "icon-categoryIcon", props.className)}
            viewBox="0 0 24 24"
            role="img"
            aria-label={title}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                d="M5.5,19 L18.5,19 C19.3284271,19 20,18.3284271 20,17.5 L20,8.5 C20,7.67157288 19.3284271,7 18.5,7 L12,7 L10.2222222,5 L5.5,5 C4.67157288,5 4,5.67157288 4,6.5 L4,17.5 C4,18.3284271 4.67157288,19 5.5,19 Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function CheckCompactIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = `✓`;
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.selectedCategory, "icon-selectedCategory", props.className)}
            viewBox="0 0 16.8 13"
            role="img"
            aria-label={title}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <polygon
                points="12.136 3.139 13.25 4.253 6.211 11.292 2.75 7.83 3.863 6.717 6.211 9.064 12.136 3.139"
                fill="currentColor"
            />
        </svg>
    );
}

export function UpTriangleIcon(props: {
    className?: string;
    "aria-hidden"?: areaHiddenType;
    title?: string;
    deg?: number;
}) {
    return (
        <DownTriangleIcon
            className={props.className}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            deg={180}
            translateY={-2}
        />
    );
}

export function DownTriangleIcon(props: {
    className?: string;
    "aria-hidden"?: areaHiddenType;
    title?: string;
    deg?: number;
    translateY?: number;
}) {
    let rotation: string | undefined = undefined;
    if (props.deg !== undefined) {
        rotation = `rotate(${props.deg}, 4, 4)`;
    }

    let translate: string | undefined = undefined;
    if (props.translateY) {
        translate = `translate(0, ${props.translateY})`;
    }

    const transform = [rotation, translate].filter((item) => item !== undefined).join(", ");
    const classes = iconClasses();

    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 10 6"
            className={classNames(props.className, classes.triangeTiny)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{props.title ? props.title : `▾`}</title>
            <polygon points="0 0 10 0 5 6 0 0" fill="currentColor" transform={transform} />
        </svg>
    );
}

export function RightTriangleIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    return (
        <DownTriangleIcon
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={props.className}
            title={props.title ? props.title : `▶`}
            deg={-90}
        />
    );
}

export function HelpIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = t("Help");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames(classes.compact, "icon-help", props.className)}
            role="img"
            aria-label={title}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
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

export function ComposeIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-compose", props.className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("Compose")}</title>
            <path
                fill="currentColor"
                d="M23.591,1.27l-.9-.9a1.289,1.289,0,0,0-1.807,0l-.762.863,2.6,2.587.868-.751a1.24,1.24,0,0,0,.248-.373,1.255,1.255,0,0,0,0-1.052A1.232,1.232,0,0,0,23.591,1.27ZM19.5,20.5H3.5V4.5H15.4l1.4-1.431H2.751A1,1,0,0,0,2,4.07V20.939a1,1,0,0,0,1,1H20.011a1,1,0,0,0,1-1V7L19.5,8.445ZM21.364,3.449l-9.875,9.8-.867-.861,9.874-9.8-.867-.863-4.938,4.9-4.938,4.9L8.74,15.167l3.617-1.055,9.875-9.8Z"
            />
        </svg>
    );
}

export function AnalyticsIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.standard, "icon-analytics", props.className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("Analytics")}</title>
            <path
                d="M1 1.5V18.5C1 19.0523 1.44772 19.5 2 19.5H21"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
            />
            <path d="M1 15L5.59459 9.76191L9.27027 15L14.7838 4L18 10.8095" stroke="currentColor" />
            <circle cx="18" cy="10.5" r="1.5" fill="currentColor" />
            <circle cx="14.7" cy="3.5" r="1.5" fill="currentColor" />
            <circle cx="9.30005" cy="15.5" r="1.5" fill="currentColor" />
            <circle cx="5.59998" cy="9.5" r="1.5" fill="currentColor" />
        </svg>
    );
}

export function PlusCircleIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const title = `+`;
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            viewBox="0 0 16 16"
            className={classNames(classes.plusCircle, "icon-plusCircle", props.className)}
            role="img"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <g fill="none" fillRule="evenodd">
                <g>
                    <g transform="translate(-969 -413) translate(970 414)">
                        <circle cx="7" cy="7" r="7" stroke="currentColor" strokeWidth=".778" />
                        <path fill="currentColor" d="M6.3 3.5H7.699999999999999V10.5H6.3z" />
                        <path fill="currentColor" d="M6.3 3.5L7.7 3.5 7.7 10.5 6.3 10.5z" transform="rotate(90 7 7)" />
                    </g>
                </g>
            </g>
        </svg>
    );
}

export function ChevronUpIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; rotate?: number }) {
    const title = t("Chevron Up");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 51 17"
            style={{
                transform: props.rotate ? `rotate(${props.rotate}deg)` : undefined,
            }}
            className={classNames(classes.chevronUp, "icon-chevronUp", props.className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M4.9,16.752A3.552,3.552,0,0,1,.329,14.668c-.039-.106-.074-.214-.1-.323A4.185,4.185,0,0,1,2.39,9.152L24.246.252a3.31,3.31,0,0,1,2.508,0l21.856,8.9a4.184,4.184,0,0,1,2.166,5.193,3.552,3.552,0,0,1-4.351,2.511,3.41,3.41,0,0,1-.325-.1L25.5,8.358Z"
            />
        </svg>
    );
}

export function SearchErrorIcon(props: { message?: string; className?: string }) {
    const title = props.message ? props.message : t("Page Not Found");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-notFound", props.className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                d="M16.178,14.358l4.628,4.616c.633.631,1.2,1.417.354,2.261s-1.647.286-2.285-.336q-.627-.612-4.7-4.688a7.7,7.7,0,1,1,2.005-1.853ZM9.984,9.214,11.563,7.64a.418.418,0,0,1,.591.59L10.576,9.8l1.578,1.574a.418.418,0,0,1-.591.59L9.984,10.394,8.4,11.968a.418.418,0,0,1-.591-.59L9.392,9.8,7.814,8.23A.418.418,0,0,1,8.4,7.64Zm.063,7.545a7.044,7.044,0,1,0-7.03-7.043A7.037,7.037,0,0,0,10.047,16.759Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function AccessibleImageMenuIcon(props: { message?: string; className?: string }) {
    const { message, className } = props;
    const title = message ? message : t("Accessibility");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-accessibleImageMenuIcon", className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M12 22.7C6.09 22.7 1.3 17.91 1.3 12 1.3 6.09 6.09 1.3 12 1.3c5.91 0 10.7 4.79 10.7 10.7 0 5.91-4.79 10.7-10.7 10.7zm0-1.4a9.3 9.3 0 1 0 0-18.6 9.3 9.3 0 0 0 0 18.6z"
            />
            <path fill="currentColor" d="M12 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4" />
            <path
                fill="currentColor"
                d="M16.06 9.004h-.005l-3.06.336a2.823 2.823 0 0 1-.313.018H11.32c-.104 0-.208-.006-.312-.017l-3.065-.337c-.482-.047-.902.394-.94.984-.038.59.321 1.106.803 1.153l2.473.275c.15.017.265.17.265.355v.822c0 .179-.027.356-.08.522L9.06 17.494c-.195.541.005 1.174.446 1.414.442.24.958-.005 1.154-.546l1.336-4.007 1.349 4.017c.201.528.71.762 1.144.528.435-.235.637-.853.456-1.391l-1.408-4.395a1.717 1.717 0 0 1-.08-.521v-.822c0-.185.115-.339.265-.355l2.47-.275c.48-.045.841-.56.804-1.15-.036-.59-.456-1.033-.938-.988z"
            />
        </svg>
    );
}

export function EditIcon(props: {
    className?: string;
    "aria-hidden"?: areaHiddenType;
    title?: string;
    small?: boolean;
}) {
    const classes = iconClasses();
    return (
        <svg
            viewBox="0 0 24 24"
            className={classNames(classes.editIcon, props.className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{props.title ? props.title : t("Edit")}</title>
            <g className={classNames({ [classes.isSmall]: props.small })}>
                <g transform="translate(4, 3)">
                    <polygon fill="currentColor" points="1.05405405 14 3 15.9736842 0 17" />
                    <path
                        d="M1.53965611,12.8579964 L14.2200643,0.146669161 C14.4151476,-0.0488897203 14.6102308,-0.0488897203 14.805314,0.146669161 L16.8536876,2.20003741 C17.0487708,2.39559629 17.0487708,2.59115517 16.8536876,2.78671406 L4.17327936,15.4980413 L0.466698493,16.9647329 C0.076532086,17.0625124 -0.118551118,16.9647329 0.076532086,16.5736152 L1.53965611,12.8579964 Z"
                        stroke="currentColor"
                        fill="none"
                    />
                </g>
            </g>
        </svg>
    );
}

export function DeleteIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            viewBox="0 0 17 17"
            className={classNames(classes.deleteIcon, props.className)}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("Delete")}</title>
            <g clipRule="evenodd" fillRule="evenodd" fill="currentColor">
                <path
                    d="M14,4v9c0,1.1-0.9,2-2,2H5c-1.1,0-2-0.9-2-2V4H2.3C2.1,4,2,3.9,2,3.7V3.3C2,3.1,2.1,3,2.3,3h3.2l0.3-1
		C5.9,1.4,6.4,1,7,1h3c0.6,0,1.1,0.4,1.2,1l0.3,1h3.2C14.9,3,15,3.1,15,3.3v0.4C15,3.9,14.9,4,14.7,4H14z M7,2.2C7,2.1,7.1,2,7.2,2
		h2.6C9.9,2,10,2.1,10,2.2L10.2,3H6.8L7,2.2z M4,4h9v9c0,0.5-0.4,1-1,1H5c-0.6,0-1-0.5-1-1V4z"
                />
                <path
                    d="M8.5,5.5L8.5,5.5C8.8,5.5,9,5.7,9,6v6c0,0.3-0.2,0.5-0.5,0.5l0,0C8.2,12.5,8,12.3,8,12V6
		C8,5.7,8.2,5.5,8.5,5.5z"
                />
                <path
                    d="M10.5,5.5L10.5,5.5C10.8,5.5,11,5.7,11,6v6c0,0.3-0.2,0.5-0.5,0.5l0,0c-0.3,0-0.5-0.2-0.5-0.5V6
		C10,5.7,10.2,5.5,10.5,5.5z"
                />
                <path
                    d="M6.5,5.5L6.5,5.5C6.8,5.5,7,5.7,7,6v6c0,0.3-0.2,0.5-0.5,0.5l0,0C6.2,12.5,6,12.3,6,12V6
		C6,5.7,6.2,5.5,6.5,5.5z"
                />
            </g>
        </svg>
    );
}

export function DiscussionIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.discussionIcon, props.className)}
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{props.title ? props.title : t("Speech Bubble")}</title>
            <path
                fill="currentColor"
                d="M12 17.431c4.418 0 8-2.783 8-6.216C20 7.782 16.418 5 12 5s-8 2.783-8 6.216c0 1.572.75 3.008 1.99 4.102l-.765 3.11 3.28-1.619a9.9 9.9 0 0 0 3.495.623zm-6.332 1.892c-.762.376-1.616-.31-1.414-1.134l.627-2.55C3.678 14.396 3 12.854 3 11.215 3 7.168 7.077 4 12 4s9 3.168 9 7.215c0 4.048-4.077 7.215-9 7.215-1.192 0-2.352-.185-3.43-.54l-2.902 1.433z"
            />
        </svg>
    );
}

export function TranslateIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, props.className)}
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{props.title ? props.title : t("Translate")}</title>
            <path
                d="M9.836,13.2l-.455-1.495H7.09L6.635,13.2H5.2L7.417,6.892H9.046L11.27,13.2Zm-.773-2.612q-.359-1.149-.711-2.3-.065-.209-.114-.42-.142.549-.812,2.72Zm9.472,1.626a.5.5,0,0,0,0-1h-2.07V10a.466.466,0,1,0-.93,0v1.214h-1.9c-.256,0-.088.22,0,.5s0,.5.255.5h.216a4.936,4.936,0,0,0,1.164,2.75c-.528.34.31.329-.35.329-.256,0,0,.077,0,.353s.093.508.35.508c.614,0,.01.03.73-.508a4.216,4.216,0,0,0,2.535.854.5.5,0,0,0,0-1,3.319,3.319,0,0,1-1.8-.536,4.936,4.936,0,0,0,1.164-2.75h.641ZM16,14.34a3.893,3.893,0,0,1-.956-2.125h1.912A3.893,3.893,0,0,1,16,14.34Z"
                style={{ fill: "currentColor" }}
            />
            <path
                d="M11.271,2.709H4.017q-1.418,0-1.417,1.9V16.445q0,1.575,1.733,1.575H16.1Z"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "1.2px" }}
            />
            <path
                d="M12.348,6.116h7.7q1.313,0,1.312,1.234v12.7q0,1.47-1.312,1.47H13.635L12,18.02h4.1Zm1.287,15.4,2.817-3.287"
                style={{ fill: "none", stroke: "currentColor", strokeWidth: "1.2px" }}
            />
        </svg>
    );
}

export function HamburgerIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.hamburger, props.className)}
            viewBox="0 0 21.999 15.871"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{props.title ? props.title : t("Menu")}</title>
            <path
                d="M21.111,8.9H.89a.925.925,0,0,1,0-1.849H21.11a.925.925,0,0,1,0,1.849m0-7.012H.89A.906.906,0,0,1,0,.97.907.907,0,0,1,.889.045H21.11A.907.907,0,0,1,22,.969a.907.907,0,0,1-.889.924m0,14.023H.89a.925.925,0,0,1,0-1.849H21.11a.925.925,0,0,1,0,1.849"
                transform="translate(0 -0.045)"
                style={{ fill: "currentcolor" }}
            />
        </svg>
    );
}

export function DarkThemeIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <g fill="none" fillRule="evenodd" transform="translate(2 2)">
                <circle cx="10" cy="10" r="10" fill="#1E1E1E" stroke="#DDDEE0" transform="rotate(-180 10 10)" />
                <path fill="#DDDEE0" d="M10 19.986V.014C15.573.288 20 4.654 20 10c0 5.346-4.428 9.712-10 9.986z" />
                <circle cx="4.444" cy="4.444" r="3.951" stroke="#DDDEE0" transform="rotate(-90 10 4.444)" />
                <path
                    fill="#D8D8D8"
                    d="M6.097 5.564l-.305-1.392-1.348-.279 1.348-.318.305-1.353.239 1.353 1.462.314-1.462.283zM3.875 8.898L3.57 7.506l-1.348-.28 1.348-.318.305-1.352.238 1.352 1.462.314-1.462.284z"
                />
            </g>
        </svg>
    );
}

export function LightThemeIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <g fill="none" fillRule="evenodd" transform="translate(2 2)">
                <circle cx="10" cy="10" r="10" fill="#555A62" stroke="#555A62" />
                <path fill="#DDDEE0" d="M10 .556v18.888A9.444 9.444 0 1 1 10 .556z" />
                <g transform="rotate(90 8.889 10)">
                    <path
                        fill="#555A62"
                        fillRule="nonzero"
                        d="M17.18 7.901h-1.768c-.322 0-.597.228-.597.494s.275.494.597.494h1.769c.321 0 .597-.228.597-.494s-.276-.494-.597-.494zM8.395 14.815c-.266 0-.494.275-.494.597v1.769c0 .321.228.597.494.597s.494-.276.494-.597v-1.769c0-.322-.228-.597-.494-.597zM8.395 0c-.266 0-.494.276-.494.597v1.769c0 .321.228.597.494.597s.494-.276.494-.597V.597C8.889.276 8.66 0 8.395 0zM12.71 12.779c-.184.182-.145.535.084.763l1.255 1.256c.229.228.581.267.764.084.183-.183.144-.535-.084-.763l-1.256-1.256c-.228-.229-.58-.267-.764-.084zM2.484 2.553c-.183.183-.144.536.084.764l1.256 1.256c.228.228.581.267.764.084.183-.183.144-.535-.084-.764L3.248 2.638c-.228-.229-.58-.268-.764-.085zM4.588 12.779c-.183-.183-.536-.145-.764.084l-1.256 1.256c-.228.228-.267.58-.084.763.183.183.536.144.764-.084l1.256-1.256c.228-.228.267-.58.084-.763zM14.813 2.553c-.183-.183-.535-.144-.764.085l-1.255 1.255c-.229.229-.268.581-.085.764.183.183.536.144.764-.084l1.256-1.256c.228-.228.267-.58.084-.764zM2.366 7.901H.597c-.321 0-.597.228-.597.494s.253.494.597.494h1.769c.321 0 .597-.228.597-.494s-.276-.494-.597-.494z"
                    />
                    <circle cx="8.889" cy="8.889" r="3.951" stroke="#555A62" />
                </g>
                <circle cx="10" cy="10" r="10" fill="#555A62" stroke="#555A62" />
                <path fill="#FFF" d="M10 .556v18.888A9.444 9.444 0 1 1 10 .556z" />
                <g transform="rotate(90 8.889 10)">
                    <path
                        fill="#555A62"
                        fillRule="nonzero"
                        d="M17.18 7.901h-1.768c-.322 0-.597.228-.597.494s.275.494.597.494h1.769c.321 0 .597-.228.597-.494s-.276-.494-.597-.494zM8.395 14.815c-.266 0-.494.275-.494.597v1.769c0 .321.228.597.494.597s.494-.276.494-.597v-1.769c0-.322-.228-.597-.494-.597zM8.395 0c-.266 0-.494.276-.494.597v1.769c0 .321.228.597.494.597s.494-.276.494-.597V.597C8.889.276 8.66 0 8.395 0zM12.71 12.779c-.184.182-.145.535.084.763l1.255 1.256c.229.228.581.267.764.084.183-.183.144-.535-.084-.763l-1.256-1.256c-.228-.229-.58-.267-.764-.084zM2.484 2.553c-.183.183-.144.536.084.764l1.256 1.256c.228.228.581.267.764.084.183-.183.144-.535-.084-.764L3.248 2.638c-.228-.229-.58-.268-.764-.085zM4.588 12.779c-.183-.183-.536-.145-.764.084l-1.256 1.256c-.228.228-.267.58-.084.763.183.183.536.144.764-.084l1.256-1.256c.228-.228.267-.58.084-.763zM14.813 2.553c-.183-.183-.535-.144-.764.085l-1.255 1.255c-.229.229-.268.581-.085.764.183.183.536.144.764-.084l1.256-1.256c.228-.228.267-.58.084-.764zM2.366 7.901H.597c-.321 0-.597.228-.597.494s.253.494.597.494h1.769c.321 0 .597-.228.597-.494s-.276-.494-.597-.494z"
                    />
                    <circle cx="8.889" cy="8.889" r="3.951" stroke="#555A62" />
                </g>
            </g>
        </svg>
    );
}

export function PlusIcon(props: { className?: string; "aria-hidden"?: areaHiddenType; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="80"
            height="81"
            viewBox="0 0 80 81"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <g fill="currentColor" fillRule="evenodd">
                <path d="M32 0h16v80.762H32zM80 32.381v16H48v-16zM32 32.381v16H0v-16z" />
            </g>
        </svg>
    );
}

export function LoaderIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            xmlns="http://www.w3.org/2000/svg"
            width="18px"
            height="18px"
            viewBox="0 0 18 18"
            version="1.1"
            className={props.className}
        >
            <title>{t("Loader")}</title>
            <g stroke="none" strokeWidth="1" fill="none" fillRule="evenodd">
                <g fill="currentColor">
                    <path
                        d="M9,0 C13.9705627,0 18,4.02943725 18,9 C18,13.9705627 13.9705627,18 9,18 C4.07738737,18 0,13.97 0,9 C0,8.99144674 1,8.99144674 3,9 C3,12.3137085 5.6862915,15 9,15 C12.3137085,15 15,12.3137085 15,9 C15,5.6862915 12.3137085,3 9,3 L9,0 Z"
                        id="Path"
                        opacity="0.3"
                    ></path>
                    <path
                        d="M9,5.1159077e-13 C9,5 4.97,9 1.42108547e-14,9 C1.42108547e-14,9 1.42108547e-14,6 1.42108547e-14,6 C3.31,6 5.95313475,3.31 6,5.1159077e-13 C6,5.1159077e-13 9,5.1159077e-13 9,5.1159077e-13 Z"
                        id="Path"
                        transform="translate(4.500000, 4.500000) rotate(180.000000) translate(-4.500000, -4.500000) "
                    ></path>
                </g>
            </g>
        </svg>
    );
}

export function ResetIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            width="25"
            height="24"
            viewBox="0 0 25 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("Reset")}</title>
            <path
                fill="currentColor"
                fillOpacity="0.7"
                fillRule="evenodd"
                d="M12 6c3.315 0 6 2.685 6 6s-2.685 6-6 6-6-2.685-6-6 2.685-6 6-6zm-.006 3a2.988 2.988 0 00-2.066.83l-.432-.43A.29.29 0 009 9.604v1.622c0 .16.13.29.29.29h1.622a.29.29 0 00.205-.495l-.505-.506a2.032 2.032 0 11.05 3.015.145.145 0 00-.198.006l-.48.48a.144.144 0 00.006.21A3 3 0 1011.994 9z"
            />
        </svg>
    );
}

export function DocumentationIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.documentation, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 11.795 15"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("Documentation")}</title>
            <path
                fill="currentColor"
                fillRule="evenodd"
                d="M11.689,3.442,8.761.142A.429.429,0,0,0,8.443,0H.424A.424.424,0,0,0,0,.424V14.576A.424.424,0,0,0,.424,15H11.371a.424.424,0,0,0,.424-.424V3.724A.429.429,0,0,0,11.689,3.442ZM8.861,1.534,10.446,3.32H8.861Zm2.086,12.617H.848V.85H8.012v2.9a.424.424,0,0,0,.424.424h2.51v9.977ZM2.368,6.4a.424.424,0,0,0,.424.424H9a.425.425,0,0,0,0-.849H2.793a.425.425,0,0,0-.425.425ZM9,8.269H2.793a.425.425,0,1,0-.041.849H9a.425.425,0,0,0,0-.849Zm0,2.293H2.793a.424.424,0,0,0,0,.848H9a.424.424,0,0,0,0-.848Z"
                style={{ fill: "currentColor", fillRule: "evenodd" }}
            />
        </svg>
    );
}

/**
 * @deprecated Use BookmarkIcon instead.
 */
export function _BookmarkIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    return (
        <svg
            className={classNames("svgBookmark", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 12.733 16.394"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("Bookmark")}</title>
            <path
                className={"svgBookmark-mainPath"}
                strokeWidth={2}
                d="M1.05.5H11.683a.55.55,0,0,1,.55.55h0V15.341a.549.549,0,0,1-.9.426L6.714,12a.547.547,0,0,0-.7,0L1.4,15.767a.55.55,0,0,1-.9-.426V1.05A.55.55,0,0,1,1.05.5z"
            />
            <path
                d="M11.7,0.5H6.4v11.4c0.1,0,0.2,0,0.3,0.1l4.6,3.8c0.1,0.1,0.2,0.1,0.4,0.1c0.3,0,0.5-0.2,0.5-0.6V1.1C12.2,0.7,12,0.5,11.7,0.5z"
                className={"svgBookmark-loadingPath"}
            />
        </svg>
    );
}
export function NewPostMenuIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    return (
        <svg
            className={classNames(iconClasses().newPostMenuIcon, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("New Post")}</title>
            <path
                d="M8,0A1,1,0,0,1,9,1V7h6a1,1,0,0,1,.993.883L16,8a1,1,0,0,1-1,1H9v6a1,1,0,0,1-.883.993L8,16a1,1,0,0,1-1-1V9H1a1,1,0,0,1-.993-.883L0,8A1,1,0,0,1,1,7H7V1A1,1,0,0,1,7.883.006Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function NewDiscussionIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();

    return (
        <svg
            className={classNames(classes.itemFlyout)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("New Discussion Icon")}</title>
            <path
                fill="currentColor"
                fillRule="evenodd"
                d="M17.101 4l-.866 1.5H4c-.276 0-.5.224-.5.5v14c0 .276.224.5.5.5h7.324l-.002.044.059-.044H19c.276 0 .5-.224.5-.5v-8.154L21 9.248V20c0 1.105-.895 2-2 2H4c-1.105 0-2-.895-2-2V6c0-1.105.895-2 2-2h13.101zm-4.455 9.718l.243.245c.413.397.86.734 1.34 1.012.482.278.997.496 1.547.654l.334.089-3.689 2.757c-.088.066-.213.048-.28-.04-.03-.042-.044-.093-.038-.144l.543-4.573zM21.172.35l2.252 1.3c.335.193.45.621.256.956L16.358 15.29c-.677-.162-1.303-.41-1.88-.743-.482-.278-.929-.615-1.341-1.012l-.243-.245L20.216.606c.193-.335.621-.45.956-.256z"
            />
        </svg>
    );
}

export function NewIdeaIcon(props: { className?: string; "area-hidden"?: areaHiddenType }) {
    const classes = iconClasses();

    return (
        <svg
            className={classNames(classes.itemFlyout)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("New Idea Icon")}</title>
            <path
                d="M14.24,22.642a.536.536,0,0,1,0,1.042H9.76a.536.536,0,0,1,0-1.042Zm1.093-2.482a.521.521,0,1,1,0,1.042H8.667a.521.521,0,1,1,0-1.042ZM12,1.128a7.678,7.678,0,0,1,7.786,7.525,7.483,7.483,0,0,1-2.577,5.576l-.084.082c-1.232,1.19-1.092,3.735-1.092,3.762a.506.506,0,0,1-.14.352.472.472,0,0,1-.336.135H8.415a.527.527,0,0,1-.336-.135.439.439,0,0,1-.14-.352c.028-.027.14-2.571-1.092-3.762A7.483,7.483,0,0,1,4.214,8.653,7.678,7.678,0,0,1,12,1.128Zm-.028.893A6.713,6.713,0,0,0,5.139,8.626a6.5,6.5,0,0,0,2.352,4.98,6.156,6.156,0,0,1,1.4,4.007h6.19a6.179,6.179,0,0,1,1.316-3.9c0-.027.056-.081.084-.108a6.48,6.48,0,0,0,2.324-4.981A6.713,6.713,0,0,0,11.972,2.021ZM11.318,3.09a6.016,6.016,0,0,1,6.262,5.8.494.494,0,0,1-.985,0A5.094,5.094,0,0,0,11.318,4a.491.491,0,0,1-.493-.456.491.491,0,0,1,.493-.456Z"
                fill="currentColor"
                stroke="currentColor"
                strokeWidth="0.5px"
            />
            <path
                d="M21.75,1.75l-1.5,1.5m3.856,4.659L22,8.167M.068,7.734l2.11.222m.05-6.123L3.877,3.167"
                fill="none"
                stroke="currentColor"
                strokeLinecap="round"
                strokeWidth="1.5px"
            />
        </svg>
    );
}

export function NewPollIcon(props: { className?: string; "area-hidden"?: areaHiddenType }) {
    const classes = iconClasses();

    return (
        <svg
            className={classNames(classes.itemFlyout)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
        >
            <title>{t("New Poll Icon")}</title>
            <rect
                x="1"
                y="14.238"
                width="11"
                height="5"
                rx="1.6"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.2px"
            />
            <rect
                x="1"
                y="4.6"
                width="7.333"
                height="5"
                rx="1.6"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.2px"
            />
            <path
                d="M19.081,7.153H13.843"
                fill="none"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.8px"
            />
            <path
                d="M11.538,7.153l2.514-2.5v5Z"
                fill="currentColor"
                stroke="currentColor"
                strokeLinejoin="round"
                strokeWidth="0.9px"
                fillRule="evenodd"
            />
            <path
                d="M15.62,16.532h5.238"
                fill="none"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="1.8px"
            />
            <path
                d="M23.163,16.532l-2.514,2.5v-5Z"
                fill="currentColor"
                stroke="currentColor"
                strokeLinejoin="round"
                strokeWidth="0.9px"
                fillRule="evenodd"
            />
        </svg>
    );
}

export function FeatureIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            className={classNames(classes.featureIcon, props.className)}
            style={{ height: 24, width: 24 }}
            aria-hidden={props["aria-hidden"]}
        >
            <g fill="none" fillRule="evenodd" opacity="1.0">
                <g stroke="currentColor" strokeWidth="1.0">
                    <g>
                        <path
                            d="M10 14.329L5.365 16.766 6.25 11.604 2.5 7.949 7.682 7.196 10 2.5 12.318 7.196 17.5 7.949 13.75 11.604 14.635 16.766z"
                            transform="translate(-1167 -297) translate(1167 297)"
                        />
                    </g>
                </g>
            </g>
        </svg>
    );
}

export function ArrowIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();
    return (
        <svg
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            width="12"
            height="12"
            className={classNames(classes, props.className)}
            style={{ height: 12, width: 12 }}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 12 12"
        >
            <path fill="currentColor" id="arrow" d="M6,0,4.909,1.091l4.13,4.13H0V6.779H9.039l-4.13,4.13L6,12l6-6Z" />
        </svg>
    );
}

export function DownvoteIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();

    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className={cx(classes.downvote, props.className)}>
            <g fillRule="evenodd" strokeLinejoin="round">
                <path d="M19.087 8.5L12 16.768 4.913 8.5h14.174z" />
            </g>
        </svg>
    );
}

export function UpvoteIcon(props: { className?: string; "aria-hidden"?: areaHiddenType }) {
    const classes = iconClasses();

    return <DownvoteIcon aria-hidden={props["aria-hidden"]} className={cx(classes.upvote, props.className)} />;
}
