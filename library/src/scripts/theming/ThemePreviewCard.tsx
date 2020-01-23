/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import { themeCardClasses } from "./themePreviewCardStyles";
import Button from "@library/forms/Button";
import { t } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, emphasizeLightness } from "@library/styles/styleHelpersColors";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useFocusWatcher } from "@vanilla/react-utils";
import classNames from "classnames";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { WarningIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconClasses";
import { ButtonTypes } from "@library/forms/buttonStyles";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import LinkAsButton from "@library/routing/LinkAsButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { color, ColorHelper } from "csx";

type VoidFunction = () => void;
type ClickHandlerOrUrl = string | VoidFunction;

interface IProps {
    name?: string;
    previewImage?: string;
    globalBg?: string;
    globalFg?: string;
    globalPrimary?: string;
    titleBarBg?: string;
    titleBarFg?: string;
    headerImg?: string;
    onApply?: VoidFunction;
    isApplyLoading?: boolean;
    onPreview?: VoidFunction;
    onCopy?: ClickHandlerOrUrl;
    onEdit?: ClickHandlerOrUrl;
    onDelete?: ClickHandlerOrUrl;
    isActiveTheme: boolean;
    noActions?: boolean;
    canCopy?: boolean;
    canDelete?: boolean;
    canEdit?: boolean;
    canCopyCustom?: boolean;
    forceHover?: boolean;
}

export default function ThemePreviewCard(props: IProps) {
    const vars = calculateVars(props);

    const [hasFocus, setHasFocus] = useState(false);
    const containerRef = useRef<HTMLDivElement | null>(null);
    useFocusWatcher(containerRef, setHasFocus);
    const classes = themeCardClasses();

    return (
        <div className={classes.constraintContainer} style={{ background: vars.globalBg }}>
            <div className={classes.ratioContainer}>
                <div
                    ref={containerRef}
                    className={classNames(
                        hasFocus && classes.isFocused,
                        classes.container,
                        props.forceHover && "forceHover",
                    )}
                    tabIndex={0}
                    title={props.name}
                >
                    <div className={classes.menuBar}>
                        {[0, 1, 2].map(key => (
                            <span key={key} className={classes.menuBarDots}></span>
                        ))}
                    </div>
                    {props.previewImage ? (
                        <img className={classes.previewImage} src={props.previewImage} />
                    ) : (
                        <svg
                            className={classes.svg}
                            width="310px"
                            height="205px"
                            viewBox="0 0 310 205"
                            preserveAspectRatio="xMidYMin"
                        >
                            <rect width="100%" height="100%" fill={vars.globalBg} />
                            <g stroke="none" strokeWidth="1" fill={vars.globalBg} fillRule="evenodd">
                                <g>
                                    <polygon
                                        fill={vars.splashBg}
                                        fillRule="nonzero"
                                        points="0 0 310 0 310 61 0 61"
                                    ></polygon>
                                    <polygon
                                        fill={vars.titleBarBg}
                                        fillRule="nonzero"
                                        points="0 0 310 0 310 10 0 10"
                                    ></polygon>
                                    <path
                                        d="M49,4 L65,4 L65,6 L49,6 L49,4 Z M73,4 L89,4 L89,6 L73,6 L73,4 Z M100,28 L210,28 L210,32 L100,32 L100,28 Z M83,44.283 C83,43.574 83.577,43 84.29,43 L203.41,43 L203.41,51.57 L84.29,51.57 C83.578,51.57 83,50.994 83,50.287 L83,44.283 L83,44.283 Z"
                                        fill={vars.globalBg}
                                        fillRule="nonzero"
                                    ></path>
                                    <path
                                        d="M203.343,43.15 L203.343,51.42 L225.683,51.42 C226.311,51.42 226.819,50.911 226.819,50.284 L226.819,44.285 C226.819,43.658 226.311,43.15 225.684,43.15 L203.343,43.15 L203.343,43.15 Z"
                                        stroke="#FFFFFF"
                                        strokeWidth="0.3"
                                        fillOpacity="0.1"
                                        fill="#000000"
                                        fillRule="nonzero"
                                    ></path>
                                    <path
                                        d="M208,46.5 L222,46.5 L222,48.5 L208,48.5 L208,46.5 Z M26,4 L42,4 L42,6 L26,6 L26,4 Z"
                                        fill={vars.titleBarFg}
                                        fillRule="nonzero"
                                    ></path>
                                    <g transform="translate(27.000000, 78.000000)">
                                        <path
                                            d="M15,23 L41,23 L41,27 L15,27 L15,23 Z M1,4 L27,4 L27,8 L1,8 L1,4 Z M217,73 L239,73 L239,77 L217,77 L217,73 Z M217,98 L239,98 L239,102 L217,102 L217,98 Z M15,31 L85,31 L85,33 L15,33 L15,31 Z M106,31 L136,31 L136,33 L106,33 L106,31 Z M106,106 L136,106 L136,108 L106,108 L106,106 Z M103,56 L133,56 L133,58 L103,58 L103,56 Z M217,16 L254,16 L254,18 L217,18 L217,16 Z M217,22 L260,22 L260,24 L217,24 L217,22 Z M217,28 L242,28 L242,30 L217,30 L217,28 Z M217,34 L254,34 L254,36 L217,36 L217,34 Z M217,40 L266,40 L266,42 L217,42 L217,40 Z M217,46 L250,46 L250,48 L217,48 L217,46 Z M217,52 L249,52 L249,54 L217,54 L217,52 Z M217,58 L255,58 L255,60 L217,60 L217,58 Z M217,64 L249,64 L249,66 L217,66 L217,64 Z"
                                            fill={vars.globalFg}
                                            fillRule="nonzero"
                                        ></path>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.8"
                                            cx="6"
                                            cy="53"
                                            r="5"
                                        ></circle>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.8"
                                            cx="6"
                                            cy="78"
                                            r="5"
                                        ></circle>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.8"
                                            cx="6"
                                            cy="103"
                                            r="5"
                                        ></circle>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.8"
                                            cx="6"
                                            cy="28"
                                            r="5"
                                        ></circle>
                                        <path
                                            d="M0.5,15.5 L191.5,15.5"
                                            stroke={vars.globalFg}
                                            strokeWidth="0.5"
                                            strokeLinecap="square"
                                        ></path>
                                        <path
                                            d="M15,98 L41,98 L41,102 L15,102 L15,98 Z M15,106 L85,106 L85,108 L15,108 L15,106 Z"
                                            fill={vars.globalFg}
                                            fillRule="nonzero"
                                        ></path>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.6"
                                            cx="220"
                                            cy="109"
                                            r="3"
                                        ></circle>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.6"
                                            cx="230"
                                            cy="109"
                                            r="3"
                                        ></circle>
                                        <circle
                                            stroke={vars.globalPrimary}
                                            strokeWidth="0.6"
                                            cx="240"
                                            cy="109"
                                            r="3"
                                        ></circle>
                                        <path
                                            d="M0.5,90.5 L191.5,90.5"
                                            stroke={vars.globalFg}
                                            strokeWidth="0.5"
                                            strokeLinecap="square"
                                        ></path>
                                        <path
                                            d="M15,48 L41,48 L41,52 L15,52 L15,48 Z M15,56 L55,56 L55,58 L15,58 L15,56 Z M59,56 L99,56 L99,58 L59,58 L59,56 Z"
                                            fill={vars.globalFg}
                                            fillRule="nonzero"
                                        ></path>
                                        <path
                                            d="M0.5,40.5 L191.5,40.5"
                                            stroke={vars.globalFg}
                                            strokeWidth="0.5"
                                            strokeLinecap="square"
                                        ></path>
                                        <path
                                            d="M15,73 L41,73 L41,77 L15,77 L15,73 Z M15,81 L96,81 L96,83 L15,83 L15,81 Z"
                                            fill={vars.globalFg}
                                            fillRule="nonzero"
                                        ></path>
                                        <path
                                            d="M0.5,65.5 L191.5,65.5"
                                            stroke={vars.globalFg}
                                            strokeWidth="0.5"
                                            strokeLinecap="square"
                                        ></path>
                                        <rect
                                            fill={vars.globalPrimary}
                                            fillRule="nonzero"
                                            x="216"
                                            y="0"
                                            width="50"
                                            height="8"
                                            rx="2"
                                        ></rect>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="217"
                                            y="81"
                                            width="13"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="89"
                                            y="30"
                                            width="13"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                        <g transform="translate(134.500000, 0.000000)" stroke="#979797">
                                            <rect strokeWidth="0.4" x="0.5" y="0" width="56" height="8" rx="1"></rect>
                                            <path
                                                d="M10.5,0.2 L10.5,7.9 M22.5,0.2 L22.5,7.9 M34.5,0.2 L34.5,7.9 M46.5,0.2 L46.5,7.9"
                                                strokeWidth="0.5"
                                                strokeLinecap="square"
                                            ></path>
                                        </g>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="89"
                                            y="105"
                                            width="13"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="232"
                                            y="81"
                                            width="13"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="247"
                                            y="81"
                                            width="11"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="238"
                                            y="87"
                                            width="11"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                        <rect
                                            stroke="#979797"
                                            strokeWidth="0.4"
                                            x="217"
                                            y="87"
                                            width="19"
                                            height="4"
                                            rx="1"
                                        ></rect>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    )}
                    {!props.noActions && (
                        <div className={classes.overlay}>
                            <div className={classes.overlayBg}></div>
                            {(props.canEdit || props.canDelete) && (
                                <div className={classes.actionDropdown}>
                                    <DropDown
                                        buttonBaseClass={ButtonTypes.ICON}
                                        flyoutType={FlyoutType.LIST}
                                        renderLeft={true}
                                    >
                                        {props.canEdit && props.onEdit && (
                                            <LinkOrButton isDropdown onClick={props.onEdit}>
                                                {t("Edit")}
                                            </LinkOrButton>
                                        )}
                                        {props.canCopyCustom && props.onCopy && (
                                            <LinkOrButton isDropdown onClick={props.onCopy}>
                                                {t("Copy")}
                                            </LinkOrButton>
                                        )}
                                        <DropDownItemSeparator />
                                        {props.canDelete && props.isActiveTheme ? (
                                            <DropDownItemButton onClick={props.onDelete} disabled={props.isActiveTheme}>
                                                <span className={classNames("selectBox-itemLabel", classes.itemLabel)}>
                                                    Delete
                                                </span>
                                                <span className={classNames("sc-only")}>
                                                    <ToolTip
                                                        label={
                                                            "This theme cannot be deleted because it is the currently applied theme"
                                                        }
                                                    >
                                                        <ToolTipIcon>
                                                            <span>
                                                                <WarningIcon
                                                                    className={classNames(iconClasses().errorFgColor)}
                                                                />
                                                            </span>
                                                        </ToolTipIcon>
                                                    </ToolTip>
                                                </span>
                                            </DropDownItemButton>
                                        ) : (
                                            <DropDownItemButton name={t("Delete")} onClick={props.onDelete} />
                                        )}
                                    </DropDown>
                                </div>
                            )}
                            <div className={classes.actionButtons}>
                                <Button
                                    className={classes.actionButton}
                                    onClick={() => {
                                        containerRef.current?.focus();
                                        props.onApply?.();
                                    }}
                                >
                                    {props.isApplyLoading ? <ButtonLoader /> : t("Apply")}
                                </Button>
                                <Button
                                    className={classes.actionButton}
                                    onClick={() => {
                                        containerRef.current?.focus();
                                        props.onPreview?.();
                                    }}
                                >
                                    {t("Preview")}
                                </Button>
                                {props.canCopy && props.onCopy && (
                                    <LinkOrButton onClick={props.onCopy}>{t("Copy")}</LinkOrButton>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function LinkOrButton(props: { onClick: ClickHandlerOrUrl; children: React.ReactNode; isDropdown?: boolean }) {
    const classes = themeCardClasses();
    if (typeof props.onClick === "string") {
        if (props.isDropdown) {
            return <DropDownItemLink to={props.onClick}>{props.children}</DropDownItemLink>;
        } else {
            return (
                <LinkAsButton className={classes.actionButton} to={props.onClick}>
                    {props.children}
                </LinkAsButton>
            );
        }
    } else {
        if (props.isDropdown) {
            return <DropDownItemButton onClick={props.onClick}>{props.children}</DropDownItemButton>;
        } else {
            return (
                <Button className={classes.actionButton} onClick={props.onClick}>
                    {props.children}
                </Button>
            );
        }
    }
}

function calculateVars(props: IProps) {
    const gVars = globalVariables();
    const titleVars = titleBarVariables();
    const globalBg = props.globalBg ?? gVars.mainColors.bg;
    let globalFg = props.globalFg ? color(props.globalFg) : gVars.mainColors.fg;
    // Add a little opacity to the FG so it doesn't stick out so much.
    // Normal text isn't nearly so thick.
    globalFg = emphasizeLightness(globalFg, 0.15) as ColorHelper;

    const globalPrimary = props.globalPrimary ? color(props.globalPrimary) : gVars.mainColors.primary;
    const titleBarBg = props.titleBarBg ? color(props.titleBarBg) : globalPrimary;
    const splashBg = emphasizeLightness(globalPrimary, 0.2);
    const titleBarFg = props.titleBarFg ?? titleVars.colors.fg;
    return {
        globalFg: colorOut(globalFg),
        globalBg: colorOut(globalBg),
        globalPrimary: colorOut(globalPrimary),
        splashBg: colorOut(splashBg),
        titleBarBg: colorOut(titleBarBg),
        titleBarFg: colorOut(titleBarFg),
    };
}
