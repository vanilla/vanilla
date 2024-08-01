/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { cx } from "@emotion/css";
import { accordionClasses } from "./Accordion.styles";
import * as Polymorphic from "../polymorphic";
import * as Reach from "@reach/accordion";

export interface IAccordionProps extends Reach.AccordionProps {
    expandAll?: boolean;
}

export const Accordion = React.forwardRef(function AccordionImpl(props, forwardedRef) {
    const { expandAll, ...otherProps } = props;
    const classes = accordionClasses();

    const childrenCount = React.Children.count(props.children);
    const defaultIndex = useMemo(() => {
        if (!expandAll) return [];
        return [...Array(childrenCount).keys()];
    }, [expandAll, childrenCount]);

    return (
        <Reach.Accordion
            ref={forwardedRef}
            defaultIndex={defaultIndex}
            {...otherProps}
            className={cx(classes.panel, props.className)}
        >
            {props.children}
        </Reach.Accordion>
    );
}) as Polymorphic.ForwardRefComponent<"div", IAccordionProps>;
