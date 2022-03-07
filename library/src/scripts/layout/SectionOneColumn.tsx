/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Container } from "@library/layout/components/Container";
import { sectionOneColumnClasses } from "@library/layout/SectionOneColumn.classes";
import { useWidgetSectionClasses, WidgetSectionContext } from "@library/layout/WidgetLayout.context";
import React from "react";
import classNames from "classnames";
import { PanelArea } from "@library/layout/components/PanelArea";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    className?: string;
    children: React.ReactNode;
    isNarrow: boolean;
    childrenAfter?: React.ReactNode;
    contentRef?: React.RefObject<HTMLDivElement>;
}

export function SectionOneColumn(props: IProps) {
    const { className, isNarrow, children, childrenAfter, contentRef, ...elementProps } = props;
    const widgetClasses = useWidgetSectionClasses();
    const oneColumnClasses = sectionOneColumnClasses();

    return (
        <div {...elementProps} ref={contentRef}>
            <Container fullGutter narrow={isNarrow} className={classNames(widgetClasses.widgetClass, className)}>
                <WidgetSectionContext.Provider
                    value={{
                        widgetClass: oneColumnClasses.widgetClass,
                        widgetWithContainerClass: widgetClasses.widgetWithContainerClass,
                        headingBlockClass: widgetClasses.headingBlockClass,
                    }}
                >
                    <>
                        {children}
                        {childrenAfter}
                    </>
                </WidgetSectionContext.Provider>
            </Container>
        </div>
    );
}
