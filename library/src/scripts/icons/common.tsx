/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { iconClasses } from "@library/icons/iconClasses";

const currentColorFill = {
    fill: "currentColor",
};

const leftChevronPath =
    "M3.621,10.5l7.94-7.939A1.5,1.5,0,0,0,9.439.439h0l-9,9a1.5,1.5,0,0,0,0,2.121h0l9,9a1.5,1.5,0,0,0,2.122-2.122Z";

const horizontalChevronViewBox = (centred?: boolean) => {
    return centred ? "-8 -4 30 30" : "0 0 24 24";
};

export function RightChevronIcon(props: { className?: string; centred?: boolean; title?: string }) {
    const title = props.title ? props.title : `>`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronRight", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={horizontalChevronViewBox(props.centred)}
            aria-hidden="true"
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

export function LeftChevronIcon(props: { className?: string; centred?: boolean }) {
    const title = `<`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronLeft", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={horizontalChevronViewBox(props.centred)}
            aria-hidden="true"
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

export function LeftChevronCompactIcon(props: { className?: string }) {
    const title = `<`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.chevronLeftCompact, "icon-chevronLeftCompact", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 12 21"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path d={leftChevronPath} style={currentColorFill} />
        </svg>
    );
}

export function TopChevronIcon(props: { className?: string }) {
    const title = `↑`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronUp", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M5.467,14.858l.668.618a.442.442,0,0,0,.614,0L12,10.614l5.252,4.862a.442.442,0,0,0,.614,0l.668-.618a.369.369,0,0,0,0-.569L12.308,8.524a.443.443,0,0,0-.615,0L5.467,14.289a.376.376,0,0,0-.134.284A.381.381,0,0,0,5.467,14.858Z"
            />
        </svg>
    );
}

export function BottomChevronIcon(props: { className?: string; rotate?: number; title?: string }) {
    const title = props.title ? props.title : `↓`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-chevronDown", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
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

export function CloseIcon(props: { className?: string; compact?: boolean }) {
    const title = t("Close");
    const viewBox = props.compact ? "0 0 16 16" : "0 0 24 24";
    const transform = props.compact ? "translate(-4 -4)" : "";
    const classes = iconClasses();
    return (
        <svg
            className={classNames(props.compact ? classes.compact : classes.close, props.className)}
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

export function CloseCompactIcon(props: { className?: string }) {
    return <CloseIcon compact={true} className={props.className} />;
}

export function CloseTinyIcon(props: { className?: string }) {
    const title = t("Close");
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.closeTiny, props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 9.5 9.5"
            aria-hidden="true"
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

export function ClearIcon(props: { className?: string }) {
    const title = t("Clear");
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-clear", props.className)}
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

export function CheckIcon(props: { className?: string }) {
    const title = `✓`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-check", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <polygon fill="currentColor" points="5,12.7 3.6,14.1 9,19.5 20.4,7.9 19,6.5 9,16.8" />
        </svg>
    );
}

export function DropDownMenuIcon(props: { className?: string }) {
    const title = `…`;
    const classes = iconClasses();
    return (
        <svg
            className={classNames(classes.standard, "icon-dropDownMenu", props.className)}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M5.5 12.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm8 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"
            />
        </svg>
    );
}

export function NewFolderIcon(props: { className?: string; title?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.newFolder, props.className)}
            viewBox="0 0 22 19"
            aria-hidden="true"
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

export function WarningIcon(props: { className?: string; warningMessage: string }) {
    const classes = iconClasses();

    return (
        <svg
            className={classNames(classes.warning, props.className)}
            aria-label={props.warningMessage}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
        >
            <title>{props.warningMessage}</title>
            <circle cx="8" cy="8" r="8" style={currentColorFill} />
            <circle cx="8" cy="8" r="7.5" style={{ fill: "none", stroke: "#000", strokeOpacity: 0.122 }} />
            <path
                d="M11,10.4V8h2v2.4L12.8,13H11.3Zm0,4h2v2H11Z"
                transform="translate(-4 -4)"
                style={{ fill: "#fff" }}
            />
        </svg>
    );
}

export function CategoryIcon(props: { className?: string }) {
    const title = t("Folder");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.categoryIcon, "icon-categoryIcon", props.className)}
            viewBox="0 0 24 24"
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M5.5,19 L18.5,19 C19.3284271,19 20,18.3284271 20,17.5 L20,8.5 C20,7.67157288 19.3284271,7 18.5,7 L12,7 L10.2222222,5 L5.5,5 C4.67157288,5 4,5.67157288 4,6.5 L4,17.5 C4,18.3284271 4.67157288,19 5.5,19 Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function CheckCompactIcon(props: { className?: string }) {
    const title = `✓`;
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            className={classNames(classes.selectedCategory, "icon-selectedCategory", props.className)}
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

export function UpTriangleIcon(props: { className?: string; title?: string; deg?: number }) {
    return <DownTriangleIcon deg={180} translateY={-2} />;
}

export function DownTriangleIcon(props: { className?: string; title?: string; deg?: number; translateY?: number }) {
    let rotation: string | undefined = undefined;
    if (props.deg !== undefined) {
        rotation = `rotate(${props.deg}, 4, 4)`;
    }

    let translate: string | undefined = undefined;
    if (props.translateY) {
        translate = `translate(0, ${props.translateY})`;
    }

    const transform = [rotation, translate].filter(item => item !== undefined).join(", ");

    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 8 8"
            className={classNames("icon-triangleRight", props.className)}
            aria-hidden="true"
        >
            <title>{props.title ? props.title : `▾`}</title>
            <polygon points="0 2.594 8 2.594 4 6.594 0 2.594" fill="currentColor" transform={transform} />
        </svg>
    );
}

export function RightTriangleIcon(props: { className?: string; title?: string }) {
    return <DownTriangleIcon className={props.className} title={props.title ? props.title : `▶`} deg={-90} />;
}

export function HelpIcon(props: { className?: string }) {
    const title = t("Help");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames(classes.compact, "icon-help", props.className)}
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

export function ComposeIcon(props: { className?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-compose", props.className)}
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M23.591,1.27l-.9-.9a1.289,1.289,0,0,0-1.807,0l-.762.863,2.6,2.587.868-.751a1.24,1.24,0,0,0,.248-.373,1.255,1.255,0,0,0,0-1.052A1.232,1.232,0,0,0,23.591,1.27ZM19.5,20.5H3.5V4.5H15.4l1.4-1.431H2.751A1,1,0,0,0,2,4.07V20.939a1,1,0,0,0,1,1H20.011a1,1,0,0,0,1-1V7L19.5,8.445ZM21.364,3.449l-9.875,9.8-.867-.861,9.874-9.8-.867-.863-4.938,4.9-4.938,4.9L8.74,15.167l3.617-1.055,9.875-9.8Z"
            />
        </svg>
    );
}

export function PlusCircleIcon(props: { className?: string }) {
    const title = `+`;
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 14 14"
            className={classNames(classes.plusCircle, "icon-plusCircle", props.className)}
            role="img"
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M14,7A7,7,0,1,1,7,0,7,7,0,0,1,14,7Zm-3.727.79a.339.339,0,0,0,.34-.338h0v-.9a.339.339,0,0,0-.339-.339H7.79V3.727a.339.339,0,0,0-.338-.34h-.9a.339.339,0,0,0-.339.339h0V6.21H3.727a.339.339,0,0,0-.34.338h0v.9a.339.339,0,0,0,.339.339H6.21v2.483a.339.339,0,0,0,.338.34h.9a.339.339,0,0,0,.339-.339h0V7.79Z"
            />
        </svg>
    );
}

export function SignInIcon(props: { className?: string }) {
    const title = t("Sign In");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="-4 0 24 18"
            className={classNames(classes.signIn, "icon-signIn", props.className)}
            role="img"
        >
            <title>{title}</title>
            <g fill="none" fillRule="evenodd" stroke="currentColor" strokeLinecap="round" strokeWidth="1.5">
                <path strokeLinejoin="round" d="M7.243 12.417L10.92 8.74 7.243 5.063" />
                <path d="M10.617 8.74H.843" />
                <path
                    strokeLinejoin="round"
                    d="M2 5.07V1.235C2.051.411 2.451 0 3.2 0h11.507C15.57.033 16 .593 16 1.681v15.136c-.058.789-.346 1.183-.865 1.183H3.2c-.8-.01-1.2-.404-1.2-1.183v-4.12"
                />
            </g>
        </svg>
    );
}

export function ChevronUpIcon(props: { className?: string; rotate?: number }) {
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
            aria-hidden="true"
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
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M16.178,14.358l4.628,4.616c.633.631,1.2,1.417.354,2.261s-1.647.286-2.285-.336q-.627-.612-4.7-4.688a7.7,7.7,0,1,1,2.005-1.853ZM9.984,9.214,11.563,7.64a.418.418,0,0,1,.591.59L10.576,9.8l1.578,1.574a.418.418,0,0,1-.591.59L9.984,10.394,8.4,11.968a.418.418,0,0,1-.591-.59L9.392,9.8,7.814,8.23A.418.418,0,0,1,8.4,7.64Zm.063,7.545a7.044,7.044,0,1,0-7.03-7.043A7.037,7.037,0,0,0,10.047,16.759Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function accessibleImageMenu(message?: string, className?: string) {
    const title = message ? message : t("Accessibility");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-accessibleImageMenuIcon", className)}
            aria-hidden="true"
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

export function EditIcon(props: { className?: string; title?: string }) {
    const classes = iconClasses();
    return (
        <svg viewBox="0 0 24 24" className={classNames(classes.editIcon, props.className)}>
            <title>{props.title ? props.title : t("Edit")}</title>
            <g transform="translate(4, 3)">
                <polygon fill="currentColor" points="1.05405405 14 3 15.9736842 0 17" />
                <path
                    d="M1.53965611,12.8579964 L14.2200643,0.146669161 C14.4151476,-0.0488897203 14.6102308,-0.0488897203 14.805314,0.146669161 L16.8536876,2.20003741 C17.0487708,2.39559629 17.0487708,2.59115517 16.8536876,2.78671406 L4.17327936,15.4980413 L0.466698493,16.9647329 C0.076532086,17.0625124 -0.118551118,16.9647329 0.076532086,16.5736152 L1.53965611,12.8579964 Z"
                    stroke="currentColor"
                    fill="none"
                />
            </g>
        </svg>
    );
}

export function DeleteIcon(props: { className?: string }) {
    const classes = iconClasses();
    return (
        <svg viewBox="0 0 17 17" className={classNames(classes.deleteIcon, props.className)}>
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

export function DiscussionIcon(props: { className?: string; title?: string }) {
    const classes = iconClasses();
    return (
        <svg className={classNames(classes.discussionIcon, props.className)} viewBox="0 0 24 24" aria-hidden="true">
            <title>{props.title ? props.title : t("Speech Bubble")}</title>
            <path
                fill="currentColor"
                d="M12 17.431c4.418 0 8-2.783 8-6.216C20 7.782 16.418 5 12 5s-8 2.783-8 6.216c0 1.572.75 3.008 1.99 4.102l-.765 3.11 3.28-1.619a9.9 9.9 0 0 0 3.495.623zm-6.332 1.892c-.762.376-1.616-.31-1.414-1.134l.627-2.55C3.678 14.396 3 12.854 3 11.215 3 7.168 7.077 4 12 4s9 3.168 9 7.215c0 4.048-4.077 7.215-9 7.215-1.192 0-2.352-.185-3.43-.54l-2.902 1.433z"
            />
        </svg>
    );
}

export function AlertIcon(props: { className?: string; title?: string; compact?: boolean }) {
    const classes = iconClasses();
    return (
        <svg
            className={classNames(props.compact ? classes.alertIconCompact : classes.alertIcon, props.className)}
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{props.title ? props.title : t("Alert")}</title>

            <circle cx="12" cy="12" r="7" style={{ fill: "#d0021b" }} />
            <path
                d="M12.709,12.905H11.314L11.023,7H13ZM11,14.972a1.048,1.048,0,0,1,.257-.767,1.01,1.01,0,0,1,.749-.26.982.982,0,0,1,.734.266,1.226,1.226,0,0,1,0,1.514.964.964,0,0,1-.731.275.993.993,0,0,1-.743-.269A1.042,1.042,0,0,1,11,14.972Z"
                style={{ fill: "#fff" }}
            />
        </svg>
    );
}

export function GlobeIcon(props: { className?: string; title?: string }) {
    const classes = iconClasses();
    return (
        <svg className={classNames(classes.globeIcon, props.className)} viewBox="0 0 24 24" aria-hidden="true">
            <title>{props.title ? props.title : t("Globe")}</title>
            <path
                d="M18.75,6.055A8.983,8.983,0,0,0,12.082,3l-.019,0H12a8.994,8.994,0,0,0-.035,17.988.415.415,0,0,0,.207,0A8.979,8.979,0,0,0,18.689,18a.382.382,0,0,0,.045-.052,8.966,8.966,0,0,0,.016-11.9ZM18.3,17.089a10.836,10.836,0,0,0-1.975-.815,15.225,15.225,0,0,0,.593-4.06h3.215A7.994,7.994,0,0,1,18.3,17.089ZM3.868,12.214H7.083A15.44,15.44,0,0,0,7.676,16.3a10.707,10.707,0,0,0-1.975.762A7.924,7.924,0,0,1,3.868,12.214ZM5.7,6.794a10.707,10.707,0,0,0,1.975.762,15.667,15.667,0,0,0-.589,3.8H3.879A8.027,8.027,0,0,1,5.7,6.794Zm5.844.577a16.12,16.12,0,0,1-2.829-.33C9.4,5.3,10.47,4.108,11.545,3.893Zm0,.857v3.13h-3.6A13.983,13.983,0,0,1,8.478,7.8,15.459,15.459,0,0,0,11.545,8.228Zm0,3.986v3.547a15.492,15.492,0,0,0-3.066.32,14.064,14.064,0,0,1-.537-3.867Zm0,4.4V20.1c-1.075-.215-2.15-1.41-2.829-3.148A16.312,16.312,0,0,1,11.545,16.618Zm.86,0a13.668,13.668,0,0,1,2.7.327c-.653,1.672-1.628,2.842-2.7,3.119V16.62Zm0-.857v-3.55h3.653a13.891,13.891,0,0,1-.625,3.867,15.962,15.962,0,0,0-3.029-.318Zm0-4.407V8.225A15.634,15.634,0,0,0,15.433,7.8a13.924,13.924,0,0,1,.62,3.555H12.405Zm0-3.99V3.922c1.074.277,2.049,1.446,2.7,3.118a13.521,13.521,0,0,1-2.7.327Zm2.153-3.1A8.164,8.164,0,0,1,17.7,6.19a10.084,10.084,0,0,1-1.648.633A7.94,7.94,0,0,0,14.558,4.271ZM7.95,6.823A10.016,10.016,0,0,1,6.3,6.19a8.166,8.166,0,0,1,3.14-1.92A7.95,7.95,0,0,0,7.95,6.823Zm0,10.342a7.952,7.952,0,0,0,1.492,2.553A8.14,8.14,0,0,1,6.3,17.8,10.177,10.177,0,0,1,7.95,17.165Zm8.1,0A10.129,10.129,0,0,1,17.7,17.8a8.14,8.14,0,0,1-3.14,1.919A7.91,7.91,0,0,0,16.05,17.165Zm.863-5.808a15.454,15.454,0,0,0-.589-3.775A10.7,10.7,0,0,0,18.3,6.767a8.107,8.107,0,0,1,1.822,4.59Z"
                style={{ fill: "currentColor" }}
            />
        </svg>
    );
}
