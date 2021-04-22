/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { accordionClasses } from "./Accordion.styles";
import { DropDownArrow } from "../forms/shared/DropDownArrow";
import { AccordionButton } from "./AccordionButton";
import * as Reach from "@reach/accordion";
import * as Polymorphic from "../polymorphic";

export interface IAccordionHeaderArrowProps {
    isExpanded?: boolean;
    children?: React.ReactNode | ((props: { index: number; isExpanded: boolean }) => React.ReactNode);
}

function AccordionHeaderArrow(props: IAccordionHeaderArrowProps) {
    const { children } = props;
    const classes = accordionClasses();
    const { index, isExpanded } = Reach.useAccordionItemContext();
    return typeof children === "function" ? (
        children({ index, isExpanded })
    ) : (
        <DropDownArrow className={classes.arrow} style={{ transform: isExpanded ? undefined : "rotate(-90deg)" }} />
    );
}

export interface IAccordionHeaderProps extends Reach.AccordionButtonProps {
    arrow?: boolean | ((props: { isExpanded?: boolean }) => React.ReactNode) | React.ReactNode;
}

export const AccordionHeader = React.forwardRef(function AccordionHeaderImpl(props, forwardedRef) {
    const { arrow, ...otherProps } = props;
    const classes = accordionClasses();

    return (
        <AccordionButton ref={forwardedRef} {...otherProps} className={classNames(classes.header, props.className)}>
            {arrow && <AccordionHeaderArrow>{arrow}</AccordionHeaderArrow>}
            {props.children}
        </AccordionButton>
    );
}) as Polymorphic.ForwardRefComponent<"button", IAccordionHeaderProps>;
