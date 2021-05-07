/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import {
    homeWidgetContainerClasses,
    homeWidgetContainerVariables,
    IHomeWidgetContainerOptions,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import Container from "@library/layout/components/Container";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { useWidgetLayoutClasses } from "@library/layout/WidgetLayout.context";
import { navLinksClasses } from "@library/navigation/navLinksStyles";
import LinkAsButton from "@library/routing/LinkAsButton";
import { BorderType } from "@library/styles/styleHelpers";
import { Variables } from "@library/styles/Variables";
import { t } from "@vanilla/i18n";
import { useMeasure } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { ReactNode, useMemo, useRef } from "react";

export interface IHomeWidgetContainerProps {
    options?: IHomeWidgetContainerOptions;
    children: React.ReactNode;
    title?: string;
    subtitle?: string;
    description?: string;
}

export function HomeWidgetContainer(props: IHomeWidgetContainerProps) {
    const vars = homeWidgetContainerVariables(props.options);
    const { options } = vars;
    const classes = homeWidgetContainerClasses(props.options);
    const isGrid = options.isGrid;
    const widgetClasses = useWidgetLayoutClasses();

    const content = isGrid ? (
        <HomeWidgetGridContainer {...props}>{props.children}</HomeWidgetGridContainer>
    ) : (
        props.children
    );

    let viewAllLinkOrButton: ReactNode;

    if (options?.viewAll) {
        const label = t(options?.viewAll?.name ?? "View All");
        if (options?.viewAll.onClick) {
            viewAllLinkOrButton = (
                <Button onClick={options?.viewAll?.onClick} buttonType={options.viewAll.displayType}>
                    {label}
                </Button>
            );
        }
        if (options?.viewAll.to) {
            viewAllLinkOrButton = (
                <LinkAsButton to={options?.viewAll?.to} buttonType={options.viewAll.displayType}>
                    {label}
                </LinkAsButton>
            );
        }
    }

    const hasOuterBg = Variables.boxHasBackground(Variables.box({ background: options.outerBackground }));

    const isNavLinks = options.borderType === "navLinks";
    const widgetClass = hasOuterBg ? widgetClasses.widgetWithContainerClass : widgetClasses.widgetClass;

    return (
        <>
            {isNavLinks && (
                <Container fullGutter narrow>
                    <div className={classes.separator}>
                        <hr className={classNames(navLinksClasses().separator)}></hr>
                        {/* Needed to bypass a :last-child check that hides these */}
                        <span></span>
                    </div>
                </Container>
            )}
            <div className={cx(!isNavLinks && widgetClass, classes.root)}>
                <Container fullGutter narrow={options.maxColumnCount <= 2 || isNavLinks}>
                    <div className={classes.container}>
                        <PageHeadingBox
                            title={props.title}
                            actions={options.viewAll.position === "top" && viewAllLinkOrButton}
                            description={props.subtitle ?? options.description}
                            subtitle={props.subtitle ?? options?.subtitle?.content}
                            options={{
                                subtitleType: options.subtitle.type,
                                alignment: options.headerAlignment,
                            }}
                        />
                        <div className={classes.content}>
                            <div className={classes.itemWrapper}>{content}</div>
                            {viewAllLinkOrButton && options.viewAll.position === "bottom" && (
                                <div className={classes.viewAllContainer}>{viewAllLinkOrButton}</div>
                            )}
                        </div>
                    </div>
                </Container>
            </div>
        </>
    );
}

export function HomeWidgetGridContainer(props: IHomeWidgetContainerProps) {
    const classes = homeWidgetContainerClasses(props.options);

    const firstItemRef = useRef<HTMLDivElement | null>(null);
    const firstItemMeasure = useMeasure(firstItemRef);

    return (
        <div className={classes.grid}>
            {React.Children.map(props.children, (child, i) => {
                return (
                    <div
                        ref={i === 0 ? firstItemRef : undefined}
                        className={classNames(
                            classes.gridItem,

                            // Constrain grid items to the same max width in each row.
                            // Workaround for flex-box limiations.
                            i !== 0 && classes.gridItemWidthConstraint(firstItemMeasure.width),
                        )}
                        key={i}
                    >
                        <div className={classes.gridItemContent}>{child}</div>
                    </div>
                );
            })}
        </div>
    );
}
