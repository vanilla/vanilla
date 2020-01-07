/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { ReactNode, useRef } from "react";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useMeasure } from "@vanilla/react-utils";
import { actionBarClasses } from "@library/headers/ActionBarStyles";

interface IProps {
    callToActionTitle?: string;
    isCallToActionDisabled?: boolean;
    className?: string;
    isCallToActionLoading?: boolean;
    optionsMenu?: React.ReactNode;
    statusItem?: React.ReactNode;
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts
    mobileDropDownTitle?: string; // For mobile
    useShadow?: boolean;
    selfPadded?: boolean;
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

    const minButtonSizeStyles: React.CSSProperties =
        restoreSize.width && backSize.width
            ? { minWidth: unit(largerWidth) }
            : { minWidth: unit(globalVars.icon.sizes.default) };

    const content = (
        <ul className={classNames(classes.items)}>
            <li className={classNames(classes.item, "isPullLeft")} ref={backRef} style={minButtonSizeStyles}>
                <BackLink title={t("Cancel")} visibleLabel={true} className={classes.backLink} />
            </li>
            {props.statusItem}
            {showMobileDropDown ? (
                <li className={classes.centreColumn}>
                    <MobileDropDown title={props.mobileDropDownTitle!} frameBodyClassName="isSelfPadded">
                        {props.mobileDropDownContent}
                    </MobileDropDown>
                </li>
            ) : null}
            <li ref={restoreRef} className={classNames(classes.item, "isPullRight")} style={minButtonSizeStyles}>
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
                    <Container>
                        <PanelWidgetHorizontalPadding>{content}</PanelWidgetHorizontalPadding>
                    </Container>
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
