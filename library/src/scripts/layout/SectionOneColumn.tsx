/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Container } from "@library/layout/components/Container";
import { sectionOneColumnClasses } from "@library/layout/SectionOneColumn.classes";
import { useWidgetSectionClasses, WidgetSectionContext } from "@library/layout/WidgetLayout.context";
import React from "react";
import { cx } from "@emotion/css";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    className?: string;
    children: React.ReactNode;
    isNarrow?: boolean;
    childrenBefore?: React.ReactNode;
    childrenAfter?: React.ReactNode;
    contentRef?: React.RefObject<HTMLDivElement>;
}

export function SectionOneColumn(props: IProps) {
    const { className, isNarrow, children, childrenBefore, childrenAfter, contentRef, ...elementProps } = props;
    const widgetClasses = useWidgetSectionClasses();
    const oneColumnClasses = sectionOneColumnClasses();

    return (
        <div
            {...elementProps}
            ref={contentRef}
            className={cx(oneColumnClasses.root, widgetClasses.widgetClass, className)}
        >
            {childrenBefore}
            <Container fullGutter narrow={isNarrow} className={oneColumnClasses.container}>
                <WidgetSectionContext.Provider
                    value={{
                        widgetClass: oneColumnClasses.widgetClass,
                        widgetWithContainerClass: widgetClasses.widgetWithContainerClass,
                        headingBlockClass: widgetClasses.headingBlockClass,
                    }}
                >
                    <>{children}</>
                </WidgetSectionContext.Provider>
            </Container>
            {childrenAfter}
        </div>
    );
}

export default SectionOneColumn;
