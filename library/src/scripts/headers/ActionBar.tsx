/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { ReactNode, useRef } from "react";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useMeasure } from "@vanilla/react-utils";
import { actionBarClasses } from "@library/headers/ActionBarStyles";
import PanelArea from "@library/layout/components/PanelArea";
import PanelWidgetHorizontalPadding from "@library/layout/components/PanelWidgetHorizontalPadding";

interface IProps {
    callToActionTitle?: string;
    anotherCallToActionTitle?: string;
    isCallToActionDisabled?: boolean;
    anotherCallToActionDisabled?: boolean;
    className?: string;
    isCallToActionLoading?: boolean;
    anotherCallToActionLoading?: boolean;
    optionsMenu?: React.ReactNode;
    statusItem?: React.ReactNode;
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts
    mobileDropDownTitle?: string; // For mobile
    useShadow?: boolean;
    selfPadded?: boolean;
    title?: React.ReactNode;
    fullWidth?: boolean;
    backTitle?: string;
    handleCancel?: (e: React.MouseEvent) => void;
    handleAnotherSubmit?: (e: React.MouseEvent) => void;
}

/**
 * Implement editor header component
 */
export function ActionBar(props: IProps) {
    const device = useDevice();
    const showMobileDropDown = (device === Devices.MOBILE || device === Devices.XS) && props.mobileDropDownTitle;
    const restoreRef = useRef<HTMLLIElement | null>(null);
    const restoreSize = useMeasure(restoreRef);
    const backRef = useRef<HTMLLIElement | null>(null);
    const backSize = useMeasure(backRef);
    const largerWidth = backSize.width > restoreSize.width ? backSize.width : restoreSize.width;
    const classesModal = modalClasses();
    const classes = actionBarClasses();
    const globalVars = globalVariables();
    const Wrapper = props.fullWidth ? React.Fragment : Container;

    const minButtonSizeStyles: React.CSSProperties =
        restoreSize.width && backSize.width
            ? { minWidth: styleUnit(largerWidth) }
            : { minWidth: styleUnit(globalVars.icon.sizes.default) };

    const content = (
        <ul className={classNames(classes.items)}>
            <li className={classNames(classes.item, "isPullLeft")} ref={backRef} style={minButtonSizeStyles}>
                <BackLink
                    title={props.backTitle || t("Cancel")}
                    visibleLabel={true}
                    className={classes.backLink}
                    onClick={props.handleCancel}
                />
            </li>
            {props.statusItem}
            {showMobileDropDown ? (
                <li className={classes.centreColumn}>
                    <MobileDropDown title={props.mobileDropDownTitle!} frameBodyClassName="isSelfPadded">
                        {props.mobileDropDownContent}
                    </MobileDropDown>
                </li>
            ) : null}
            {props.title}
            {props.anotherCallToActionTitle && (
                <li
                    ref={restoreRef}
                    className={classNames(classes.item, "isPullRight", classes.anotherCallToAction)}
                    style={minButtonSizeStyles}
                >
                    <Button
                        submit={false}
                        onClick={props.handleAnotherSubmit}
                        title={props.anotherCallToActionTitle}
                        disabled={props.anotherCallToActionDisabled}
                        baseClass={ButtonTypes.TEXT_PRIMARY}
                        className={classNames(classes.callToAction, classes.itemMarginLeft)}
                    >
                        {props.anotherCallToActionLoading ? <ButtonLoader /> : props.anotherCallToActionTitle}
                    </Button>
                </li>
            )}
            <li
                ref={restoreRef}
                className={classNames(classes.item, { isPullRight: !props.anotherCallToActionTitle })}
                style={minButtonSizeStyles}
            >
                <Button
                    submit={true}
                    title={props.callToActionTitle}
                    disabled={props.isCallToActionDisabled}
                    baseClass={ButtonTypes.TEXT_PRIMARY}
                    className={classNames(classes.callToAction, classes.itemMarginLeft)}
                >
                    {props.isCallToActionLoading ? <ButtonLoader /> : props.callToActionTitle}
                </Button>
            </li>

            {props.optionsMenu && <li className={classes.itemMarginLeft}>{props.optionsMenu}</li>}
        </ul>
    );

    return (
        <nav
            className={classNames(props.className, classesModal.pageHeader, {
                noShadow: !props.useShadow,
            })}
        >
            {!props.selfPadded && (
                <PanelArea>
                    <Wrapper>
                        <div
                            className={classNames({
                                [classes.fullWidth]: props.fullWidth,
                            })}
                        >
                            <PanelWidgetHorizontalPadding>{content}</PanelWidgetHorizontalPadding>
                        </div>
                    </Wrapper>
                </PanelArea>
            )}
            {props.selfPadded && content}
        </nav>
    );
}

(ActionBar as React.FC).defaultProps = {
    canSubmit: true,
    isCallToActionLoading: false,
    useShadow: true,
};
