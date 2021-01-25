/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import {
    IHomeWidgetContainerOptions,
    homeWidgetContainerClasses,
    homeWidgetContainerVariables,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { useMeasure } from "@vanilla/react-utils";
import classNames from "classnames";
import Heading from "@library/layout/Heading";
import { BorderType } from "@library/styles/styleHelpers";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import Container from "@library/layout/components/Container";
import { navLinksClasses } from "@library/navigation/navLinksStyles";

export interface IHomeWidgetContainerProps {
    options?: IHomeWidgetContainerOptions;
    children: React.ReactNode;
    title?: string;
    isGrid?: boolean;
}

export function HomeWidgetContainer(props: IHomeWidgetContainerProps) {
    const isGrid = props.isGrid ?? true;
    const options = homeWidgetContainerVariables(props.options).options;
    const classes = homeWidgetContainerClasses(props.options);

    const firstItemRef = useRef<HTMLDivElement | null>(null);
    const firstItemMeasure = useMeasure(firstItemRef);

    const grid = isGrid ? (
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
    ) : (
        props.children
    );

    const gridHasBorder = [BorderType.BORDER, BorderType.SHADOW].includes(options.borderType as BorderType);

    const viewAllButton = options?.viewAll?.to && (
        <LinkAsButton to={options?.viewAll?.to} baseClass={options.viewAll.displayType} className={classes.viewAll}>
            {options?.viewAll?.name ?? t("View All")}
        </LinkAsButton>
    );

    const subtitle = props.options?.subtitle?.content && (
        <h2 className={classes.subtitle}>{props.options?.subtitle?.content}</h2>
    );

    let content = (
        <div className={classes.container}>
            {options.borderType === "navLinks" && (
                <hr className={classNames(navLinksClasses().separator, classes.separator)}></hr>
            )}
            <div className={classNames(!options.noGutter && classes.verticalContainer)}>
                <div className={classes.content}>
                    {options.subtitle.type === "overline" && subtitle}
                    <div className={classes.viewAllContainer}>
                        {props.title && (
                            <Heading className={classes.title} renderAsDepth={1}>
                                {props.title}
                            </Heading>
                        )}
                        {options.viewAll.position === "top" && viewAllButton}
                    </div>
                    {options.subtitle.type === "standard" && subtitle}
                    {props.options?.description && (
                        <div className={classes.description}>{props.options.description}</div>
                    )}
                    {!gridHasBorder && grid}
                </div>
                {gridHasBorder && <div className={classes.borderedContent}>{grid}</div>}
                {viewAllButton && options.viewAll.position === "bottom" && (
                    <div className={classes.viewAllContent}>
                        <div className={classes.viewAllContainer}>{viewAllButton}</div>{" "}
                    </div>
                )}
            </div>
        </div>
    );

    if (!options.noGutter) {
        content = <Container fullGutter>{content}</Container>;
    }

    return <div className={classes.root}>{content}</div>;
}
