/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import IndependentSearch from "@library/features/search/IndependentSearch";
import { buttonClasses, ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import { Devices, IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import Heading from "@library/layout/Heading";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { splashClasses, splashVariables } from "@library/splash/splashStyles";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { cloneElement, ReactElement } from "react";
import Tooltip, { useTooltip, TooltipPopup } from "@reach/tooltip";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { url } from "csx";
import ReactDOM from "react-dom";
import { mountModal } from "@library/modal/Modal";
import { ConvertDiscussionModal } from "@knowledge/articleDiscussion/ConvertDiscussionModal";
import Portal from "@reach/portal";

const toolTipPosition = (triggerRect, tooltipRect) => {
    const triggerCenter = triggerRect.left + triggerRect.width / 2;
    const left = triggerCenter - tooltipRect.width / 2;
    const maxLeft = window.innerWidth - tooltipRect.width - 2;
    const maxBottom = window.innerHeight - tooltipRect.height - 2 + 10;

    return {
        position: "absolute",
        left: Math.min(Math.max(2, left), maxLeft) + window.scrollX,
        top: triggerRect.bottom + 8 + window.scrollY,
    };
};

function TriangleTooltip(props: { children: React.ReactNode; label: string; ariaLabel?: string }) {
    const { children, label, ariaLabel } = props;

    // get the props from useTooltip
    const [trigger, tooltip] = useTooltip();

    // destructure off what we need to position the triangle
    const { isVisible, triggerRect } = tooltip;

    return (
        <>
            {cloneElement(children as any, trigger)}
            {isVisible && (
                // The Triangle. We position it relative to the trigger, not the popup
                // so that collisions don't have a triangle pointing off to nowhere.
                // Using a Portal may seem a little extreme, but we can keep the
                // positioning logic simpler here instead of needing to consider
                // the popup's position relative to the trigger and collisions
                <Portal>
                    <div
                        aria-hidden={true}
                        style={{
                            position: "absolute",
                            left: triggerRect && triggerRect.left - 10 + triggerRect.width / 2,
                            top: triggerRect && triggerRect.bottom + window.scrollY,
                            width: 0,
                            height: 0,
                            borderLeft: "10px solid transparent",
                            borderRight: "10px solid transparent",
                            borderBottom: "10px solid black",
                        }}
                    />
                </Portal>
            )}
            <TooltipPopup
                {...tooltip}
                label={label}
                ariaLabel={ariaLabel ? ariaLabel : label}
                style={{
                    background: "black",
                    color: "white",
                    border: "none",
                    borderRadius: "3px",
                    padding: "0.5em 1em",
                    maxWidth: "205px",
                }}
                position={toolTipPosition}
            />
        </>
    );
}

export function ToolTip(props: { children: React.ReactNode; label: string; ariaLabel?: string }) {
    const { children, label, ariaLabel } = props;

    return (
        <TriangleTooltip label={label} ariaLabel={ariaLabel ? ariaLabel : label}>
            {children}
        </TriangleTooltip>
    );
}
