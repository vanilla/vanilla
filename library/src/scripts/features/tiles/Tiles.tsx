/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";
import Tile from "@library/features/tiles/Tile";
import { tilesClasses, tilesVariables } from "@library/features/tiles/tilesStyles";
import Container from "@library/layout/components/Container";
import Heading from "@library/layout/Heading";
import { visibility } from "@library/styles/styleHelpers";
import { useLayout } from "@library/layout/LayoutContext";

interface ITile {
    icon: string;
    name: string;
    description: string;
    url: string;
}

interface IProps {
    className?: string;
    items: ITile[];
    title: string;
    titleLevel?: 1 | 2 | 3 | 4 | 5 | 6;
    hiddenTitle?: boolean;
    emptyMessage: string;
    fallbackIcon?: React.ReactNode;
    alignment?: TileAlignment;
    columns?: number;
}

export enum TileAlignment {
    LEFT = "left",
    CENTER = "center",
}

/**
 * Renders list of tiles
 */
export default function Tiles(props: IProps) {
    const optionOverrides = {
        columns: props.columns,
        alignment: props.alignment,
        mediaQueries: useLayout().mediaQueries,
    };
    const options = tilesVariables(optionOverrides).options;
    const { className, items, titleLevel = 2 } = props;
    const { columns } = options;
    const classes = tilesClasses(optionOverrides);

    return (
        <Container fullGutter>
            {items.length === 0 ? (
                <div className={classNames(className, "isEmpty", classes.root)}>
                    <Paragraph>{props.emptyMessage}</Paragraph>
                </div>
            ) : (
                <nav className={classNames(className, classes.root)}>
                    <Heading
                        depth={titleLevel}
                        className={classNames(classes.title, props.hiddenTitle && visibility().visuallyHidden)}
                    >
                        {props.title}
                    </Heading>
                    <ul className={classNames(classes.items)}>
                        {items.map((tile, i) => (
                            <li key={i} className={classNames(classes.item)}>
                                <Tile
                                    icon={tile.icon}
                                    fallbackIcon={props.fallbackIcon}
                                    title={tile.name}
                                    description={tile.description}
                                    url={tile.url}
                                    columns={columns}
                                    headingLevel={(titleLevel + 1) as 2}
                                />
                            </li>
                        ))}
                    </ul>
                </nav>
            )}
        </Container>
    );
}
