/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React, { cloneElement, ReactElement, useState } from "react";
import { useTooltip, TooltipPopup } from "@reach/tooltip";
import Portal from "@reach/portal";
import { toolTipClasses, tooltipVariables } from "@library/toolTip/toolTipStyles";
import { globalVariables } from "@library/styles/globalStyleVars";

const nubPosition = (triggerRect, hasOverflow) => {
    const toolTipVars = tooltipVariables();
    const globalVars = globalVariables();

    const overTriggerPosition =
        triggerRect.top - toolTipVars.nub.width * 2 + globalVars.border.width * 2 + window.scrollY;
    const underTriggerPosition = triggerRect.bottom - globalVars.border.width * 2 + window.scrollY;

    return {
        left: triggerRect.left + triggerRect.width / 2 - toolTipVars.nub.width,
        top: hasOverflow ? overTriggerPosition : underTriggerPosition,
    };
};

function TriangleTooltip(props: { children: React.ReactNode; label: React.ReactNode; ariaLabel?: string }) {
    const globalVars = globalVariables();
    const { children, label, ariaLabel } = props;

    // get the props from useTooltip
    const [trigger, tooltip] = useTooltip();

    // destructure off what we need to position the triangle
    const { isVisible, triggerRect } = tooltip;

    const [hasOverflow, setHasOverflow] = useState(false);
    const classes = toolTipClasses();
    const toolTipVars = tooltipVariables();
    const borderOffset = globalVars.border.width * 2;

    const toolBoxPosition = (triggerRect, tooltipRect) => {
        const triangleHeight = toolTipVars.nub.width / 2;
        const triggerCenter = triggerRect.left + triggerRect.width / 2;
        const left = triggerCenter - tooltipRect.width / 2;
        const maxLeft = window.innerWidth - tooltipRect.width - 2;
        const hasOverflow = triggerRect.bottom + tooltipRect.height + triangleHeight > window.innerHeight;

        setHasOverflow(hasOverflow);

        const overTriggerPosition =
            triggerRect.top - tooltipRect.height + borderOffset - toolTipVars.nub.width + window.scrollY;
        const underTriggerPosition = triggerRect.bottom - borderOffset + toolTipVars.nub.width + window.scrollY;

        return {
            left: Math.min(Math.max(2, left), maxLeft) + window.scrollX,
            top: hasOverflow ? overTriggerPosition : underTriggerPosition,
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
                    <div className={classes.nubPosition} style={nubPosition(triggerRect, hasOverflow) as any}>
                        <div className={classNames(classes.nub, hasOverflow ? "isDown" : "isUp")} />
                    </div>
                </Portal>
            )}
            <TooltipPopup
                {...tooltip}
                label={label}
                ariaLabel={ariaLabel ? ariaLabel : label}
                position={toolBoxPosition}
                className={classes.box}
            />
        </>
    );
}

/**
 * Tooltip component.
 *
 * Custom children (not base dom nodes), must use React.forwardRef().
 */
export function ToolTip(props: { children: React.ReactNode; label: React.ReactNode; ariaLabel?: string }) {
    const { children, label, ariaLabel } = props;

    return (
        <TriangleTooltip label={label} ariaLabel={ariaLabel}>
            {children}
        </TriangleTooltip>
    );
}

interface IIconProps extends React.HTMLAttributes<HTMLSpanElement> {}

/**
 * Class for reprenting to wrap an icon inside of a tooltip.
 */
export const ToolTipIcon = React.forwardRef(function ToolTipIcon(
    props: IIconProps,
    ref: React.RefObject<HTMLSpanElement>,
) {
    const classes = toolTipClasses();
    return (
        <span {...props} ref={ref} className={classes.noPointerContent}>
            {props.children}
        </span>
    );
});
