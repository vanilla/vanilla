/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LoadingRectangle, LoadingSpacer, LoadingCircle } from "@library/loaders/LoadingRectangle";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@library/utility/appUtils";
import { useLayout } from "@library/layout/LayoutContext";
import { searchResultClasses } from "@library/features/search/searchResultsStyles";
import classNames from "classnames";

interface IProps {
    headingLevel?: 2 | 3;
}

export default function CollapseCommentsSearchMetaLoader(props: IProps) {
    const { headingLevel = 1 } = props;
    const HeadingTag = `h${headingLevel}` as "h1";

    const layoutContext = useLayout();
    const classes = searchResultClasses(layoutContext.mediaQueries, true);
    return (
        <div className={classNames(classes.content, classes.commentWrap)}>
            <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
            <div className={classes.iconWrap}>
                <LoadingCircle height={26} width={26} />
            </div>
            <div className={classNames(classes.main, { hasIcon: true })}>
                <div className={classes.metas}>
                    <HeadingTag className={classes.title}>
                        <LoadingRectangle height={15} width={300} />
                    </HeadingTag>
                    <LoadingSpacer height={8} width={250} />
                    <div style={{ display: "flex" }}>
                        <LoadingRectangle height={12} width={230} />
                        <LoadingRectangle height={12} width={50} style={{ marginLeft: 10 }} />
                    </div>
                    <LoadingSpacer height={6} width={250} />
                </div>
            </div>
        </div>
    );
}
