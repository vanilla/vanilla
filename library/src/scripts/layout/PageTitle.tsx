/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import * as React from "react";
import { metasClasses } from "@library/metas/Metas.styles";
import { PageHeading } from "@library/layout/PageHeading";
import { pageTitleClasses } from "@library/layout/pageTitleStyles";
import { useFontSizeCalculator } from "@library/layout/pageHeadingContext";
import { typographyClasses } from "@library/styles/typographyStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useSection } from "@library/layout/LayoutContext";

export interface IPageTitle {
    title: React.ReactNode;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    className?: string;
    includeBackLink?: boolean;
    headingClassName?: string;
    isLarge?: boolean;
    children?: React.ReactNode;
}

/**
 * Generates main title for page as well as possibly a back link and some meta information about the page
 */
export function PageTitle(props: IPageTitle) {
    const globalVars = globalVariables();
    const {
        title,
        children,
        actions,
        meta,
        className,
        includeBackLink = true,
        headingClassName,
        isLarge = false,
    } = props;
    const { setFontSize } = useFontSizeCalculator();

    React.useEffect(() => {
        if (isLarge) {
            setFontSize(globalVars.fonts.size.largeTitle);
        } else {
            setFontSize(globalVars.fonts.size.title);
        }
    }, [isLarge]);

    const isCompact = useSection().isCompact;
    const classes = pageTitleClasses();
    const classesMetas = metasClasses();

    return (
        <div className={cx("pageTitleContainer", className)}>
            <PageHeading
                actions={actions}
                title={title}
                depth={isLarge ? 1 : undefined}
                includeBackLink={!isCompact && includeBackLink}
                headingClassName={cx(classes.root, headingClassName, {
                    [typographyClasses().largeTitle]: isLarge,
                })}
            >
                {children}
            </PageHeading>
            {meta && <div className={cx("pageMetas", "pageTitleContainer-metas", classesMetas.root)}>{meta}</div>}
        </div>
    );
}
