/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import Container from "@library/layout/components/Container";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { sectionEvenColumnsClasses } from "@library/layout/SectionEvenColumns.classes";
import { sectionOneColumnClasses } from "@library/layout/SectionOneColumn.classes";
import { useWidgetSectionClasses, WidgetSectionContext } from "@library/layout/WidgetLayout.context";
import React from "react";

interface IProps extends React.HTMLAttributes<HTMLElement> {
    left: React.ReactNode;
    middle?: React.ReactNode;
    right: React.ReactNode;
    breadcrumbs: React.ReactNode;
    isNarrow?: boolean;
    childrenBefore?: React.ReactNode;
}

export const SectionEvenColumns = React.forwardRef(function SectionEvenColumns(
    props: IProps,
    ref: React.RefObject<HTMLDivElement>,
) {
    const { left, middle, right, breadcrumbs, isNarrow, className, childrenBefore, ...rest } = props;
    const classes = sectionEvenColumnsClasses();
    const widgetClasses = useWidgetSectionClasses();

    return (
        <div ref={ref} className={cx(classes.root, widgetClasses.widgetClass, className)} {...rest}>
            {childrenBefore}
            <Container fullGutter narrow={isNarrow} className={classes.container}>
                <PageBoxDepthContextProvider depth={1}>
                    <div className={classes.breadcrumbs}>{breadcrumbs}</div>
                    <WidgetSectionContext.Provider
                        value={{
                            widgetClass: classes.widget,
                            widgetWithContainerClass: widgetClasses.widgetWithContainerClass,
                            headingBlockClass: widgetClasses.headingBlockClass,
                        }}
                    >
                        <div className={classes.columns}>
                            <div className={classes.column}>{left}</div>
                            {middle && <div className={classes.column}>{middle}</div>}
                            <div className={classes.column}>{right}</div>
                        </div>
                    </WidgetSectionContext.Provider>
                </PageBoxDepthContextProvider>
            </Container>
        </div>
    );
});

export default SectionEvenColumns;
