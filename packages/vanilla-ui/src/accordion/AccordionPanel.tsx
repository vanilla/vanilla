/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { cx } from "@emotion/css";
import { accordionClasses } from "./Accordion.styles";
import * as Polymorphic from "../polymorphic";
import * as Reach from "@reach/accordion";

export interface IAccordionPanelProps extends Reach.AccordionPanelProps {}

export const AccordionPanel = React.forwardRef(function AccordionPanelImpl(props, forwardedRef) {
    const classes = accordionClasses();

    return (
        <Reach.AccordionPanel ref={forwardedRef} {...props} className={cx(classes.panel, props.className)}>
            {props.children}
        </Reach.AccordionPanel>
    );
}) as Polymorphic.ForwardRefComponent<"div", IAccordionPanelProps>;
