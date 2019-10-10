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
import React, { cloneElement, ReactElement, useState } from "react";
import Tooltip, { useTooltip, TooltipPopup } from "@reach/tooltip";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { url } from "csx";
import ReactDOM from "react-dom";
import { mountModal } from "@library/modal/Modal";
import { ConvertDiscussionModal } from "@knowledge/articleDiscussion/ConvertDiscussionModal";
import Portal from "@reach/portal";
import { toolTipClasses } from "@library/toolTip/toolTipStyles";
import { NestedCSSProperties } from "typestyle/lib/types";

const trianglePosition = (triggerRect, hasOverflow) => {
    return {
        left: triggerRect && triggerRect.left - 10 + triggerRect.width / 2,
        top: hasOverflow ? triggerRect.top - 10 + 2 + window.scrollY : triggerRect.bottom + window.scrollY,
    };
};

function TriangleTooltip(props: { children: React.ReactNode; label: string; ariaLabel?: string }) {
    const { children, label, ariaLabel } = props;

    // get the props from useTooltip
    const [trigger, tooltip] = useTooltip();

    // destructure off what we need to position the triangle
    const { isVisible, triggerRect } = tooltip;

    const [hasOverflow, setHasOverflow] = useState(false);
    const classes = toolTipClasses();

    const toolTipPosition = (triggerRect, tooltipRect) => {
        const triangleHeight = 10;
        const borderWidth = 2;

        const triggerCenter = triggerRect.left + triggerRect.width / 2;
        const left = triggerCenter - tooltipRect.width / 2;
        const maxLeft = window.innerWidth - tooltipRect.width - 2;
        const maxBottom = window.innerHeight - tooltipRect.height - borderWidth + triangleHeight;
        const hasOverflow = triggerRect.bottom + tooltipRect.height + borderWidth + triangleHeight > maxBottom;

        setHasOverflow(hasOverflow);

        return {
            position: "absolute",
            left: Math.min(Math.max(2, left), maxLeft) + window.scrollX,
            top: hasOverflow
                ? triggerRect.top - triangleHeight - tooltipRect.height + window.scrollY
                : triggerRect.bottom + triangleHeight + window.scrollY,
        };
    };

    return (
        <>
            {cloneElement(children as any, trigger)}
            {isVisible && triggerRect && (
                // The Triangle. We position it relative to the trigger, not the popup
                // so that collisions don't have a triangle pointing off to nowhere.
                // Using a Portal may seem a little extreme, but we can keep the
                // positioning logic simpler here instead of needing to consider
                // the popup's position relative to the trigger and collisions
                <Portal>
                    <div className={classes.nubPosition} style={trianglePosition(triggerRect, hasOverflow) as any}>
                        <div className={classNames(classes.nub, hasOverflow ? "isDown" : "isUp")} />
                    </div>
                </Portal>
            )}
            <TooltipPopup
                {...tooltip}
                label={label}
                ariaLabel={ariaLabel ? ariaLabel : label}
                position={toolTipPosition}
                className={classes.box}
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
