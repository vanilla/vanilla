/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { Accordion, AccordionHeader, AccordionItem, AccordionPanel } from "@vanilla/ui";
import { widgetSettingsAccordionClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsAccordion.classes";
import { cx } from "@emotion/css";

interface IProps {
    header: string;
}

export default function WidgetSettingsAccordion(props: React.PropsWithChildren<IProps>) {
    const classes = widgetSettingsAccordionClasses();
    return (
        <Accordion collapsible multiple className={classes.root}>
            <AccordionItem className={cx(classes.item, "item")}>
                <AccordionHeader className={classes.header} arrow>
                    {props.header}
                </AccordionHeader>
                <AccordionPanel className={classes.panel}>{props.children}</AccordionPanel>
            </AccordionItem>
        </Accordion>
    );
}
