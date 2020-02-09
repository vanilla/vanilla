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

export interface IHomeWidgetContainerProps {
    options?: IHomeWidgetContainerOptions;
    children: React.ReactNode;
    title?: string;
}

export function HomeWidgetContainer(props: IHomeWidgetContainerProps) {
    const options = homeWidgetContainerVariables(props.options).options;
    const classes = homeWidgetContainerClasses(props.options);

    const firstItemRef = useRef<HTMLDivElement | null>(null);
    const firstItemMeasure = useMeasure(firstItemRef);

    const grid = (
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

    const gridHasBorder = options.borderType !== BorderType.NONE;

    return (
        <div className={classes.root}>
            <div className={classes.content}>
                {props.title && <Heading className={classes.title}>{props.title}</Heading>}
                {!gridHasBorder && grid}
            </div>
            {gridHasBorder && <div className={classes.borderedContent}>{grid}</div>}
        </div>
    );
}
