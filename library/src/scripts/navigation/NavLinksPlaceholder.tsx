/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { navLinksClasses } from "@library/navigation/navLinksStyles";
import Heading from "@library/layout/Heading";
import { LoadingRectange } from "@library/loaders/LoadingRectangle";
import classNames from "classnames";
import { classes } from "typestyle";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Container from "@library/layout/components/Container";
import random from "lodash/random";

interface IProps {}

export function NavLinksPlaceholder(props: IProps) {
    const classes = navLinksClasses();

    const evenSeparator = <hr className={classNames(classes.separator)} aria-hidden={true} role="presentation" />;
    const oddSeparator = (
        <hr className={classNames(classes.separator, classes.separatorOdd)} aria-hidden={true} role="presentation" />
    );

    return (
        <Container fullGutter narrow>
            <h2>NavLinks Placeholder</h2>
            <nav className={classNames(classes.linksWithHeadings)}>
                <SingleNavLinksPlaceholder itemCount={4} />
                {oddSeparator}
                <SingleNavLinksPlaceholder itemCount={5} />
                {evenSeparator}
                <SingleNavLinksPlaceholder itemCount={3} />
                {oddSeparator}
                <SingleNavLinksPlaceholder itemCount={4} />
                {evenSeparator}
                <SingleNavLinksPlaceholder itemCount={2} />
                {oddSeparator}
                <SingleNavLinksPlaceholder itemCount={3} />
                {evenSeparator}
            </nav>
        </Container>
    );
}

function SingleNavLinksPlaceholder(props: { itemCount: number }) {
    const classes = navLinksClasses();
    return (
        <div className={classes.root}>
            <LoadingRectange
                className={classes.title}
                height={24}
                width={random(30, 75, false) + "%"}
            ></LoadingRectange>
            <div className={classes.items}>
                {Array.from(Array(props.itemCount)).map(i => {
                    return (
                        <React.Fragment key={i}>
                            <LoadingRectange height={12} className={classes.item} width={random(70, 98, false) + "%"} />
                        </React.Fragment>
                    );
                })}
                <div className={classes.viewAllItem}>
                    <LoadingRectange width={"70px"} height={12} className={classes.viewAll} />
                </div>
            </div>
        </div>
    );
}
