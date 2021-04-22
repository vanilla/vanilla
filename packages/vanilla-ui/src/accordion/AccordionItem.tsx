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

export interface IAccordionItemProps extends Reach.AccordionItemProps {}

export const AccordionItem = React.forwardRef(function AccordionItemImpl(props, forwardedRef) {
    const classes = accordionClasses();

    return (
        <Reach.AccordionItem ref={forwardedRef} {...props} className={cx(classes.item, props.className)}>
            {props.children}
        </Reach.AccordionItem>
    );
}) as Polymorphic.ForwardRefComponent<"div", IAccordionItemProps>;
