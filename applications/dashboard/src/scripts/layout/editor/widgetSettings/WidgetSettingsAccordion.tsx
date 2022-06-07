/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { Accordion, AccordionHeader, AccordionItem, AccordionPanel } from "@vanilla/ui";
import { widgetSettingsAccordionClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsAccordion.classes";

interface IProps {
    header: string;
    children: React.ReactElement;
}

export default function WidgetSettingsAccordion(props: IProps) {
    const classes = widgetSettingsAccordionClasses();
    return (
        <Accordion collapsible multiple>
            <AccordionItem className={classes.item}>
                <AccordionHeader className={classes.header} arrow>
                    {props.header}
                </AccordionHeader>
                <AccordionPanel className={classes.panel}>{props.children}</AccordionPanel>
            </AccordionItem>
        </Accordion>
    );
}
