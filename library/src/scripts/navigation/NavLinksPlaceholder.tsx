/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Container from "@library/layout/components/Container";
import Heading from "@library/layout/Heading";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { navLinksClasses } from "@library/navigation/navLinksStyles";
import { visibility } from "@library/styles/styleHelpers";
import classNames from "classnames";
import random from "lodash/random";
import React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@vanilla/i18n/src";

interface IProps {
    sectionsCount?: number;
    className?: string;
    title: string;
    showTitle?: boolean;
}

export const NavLinksPlaceholder = React.memo(function NavLinksPlaceholder(props: IProps) {
    const { sectionsCount = 6 } = props;
    const classes = navLinksClasses();

    const evenSeparator = <hr className={classNames(classes.separator)} aria-hidden={true} role="presentation" />;
    const oddSeparator = (
        <hr className={classNames(classes.separator, classes.separatorOdd)} aria-hidden={true} role="presentation" />
    );

    return (
        <Container fullGutter narrow className={props.className}>
            <nav className={classNames(classes.linksWithHeadings)}>
                <Heading
                    title={props.title}
                    depth={2}
                    className={classNames(
                        classes.title,
                        classes.topTitle,
                        !props.showTitle && visibility().visuallyHidden,
                    )}
                />
                {Array.from(Array(sectionsCount)).map((_, i) => {
                    return (
                        <React.Fragment key={i}>
                            <SingleNavLinksPlaceholder itemCount={4} />
                            {i !== sectionsCount - 1 && ((i + 1) % 2 === 0 ? evenSeparator : oddSeparator)}
                        </React.Fragment>
                    );
                })}
            </nav>
        </Container>
    );
});

function SingleNavLinksPlaceholder(props: { itemCount: number }) {
    const classes = navLinksClasses();
    return (
        <div className={classes.root}>
            <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
            <LoadingRectangle className={classes.title} height={24} width={random(30, 75, false) + "%"} />
            <div className={classes.items}>
                {Array.from(Array(props.itemCount)).map((_, i) => {
                    return (
                        <React.Fragment key={i}>
                            <LoadingRectangle
                                height={12}
                                className={classes.item}
                                width={random(70, 98, false) + "%"}
                            />
                        </React.Fragment>
                    );
                })}
                <div className={classes.viewAllItem}>
                    <LoadingRectangle width={"70px"} height={12} className={classes.viewAll} />
                </div>
            </div>
        </div>
    );
}
