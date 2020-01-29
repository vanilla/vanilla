/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";
import Card from "@library/features/articleCards/Card";
import { tilesClasses, tilesVariables } from "@library/features/tiles/tilesStyles";

interface ICard {
    icon: string;
    name: string;
    description: string;
    url: string;
}

interface IProps {
    className?: string;
    items: ICard[];
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
export default class ArticleCards extends React.Component<IProps> {
    public render() {
        const vars = tilesVariables();
        const { className, items, alignment = vars.options.alignment, columns = vars.options.columns } = this.props;
        const classes = tilesClasses();

        if (items.length === 0) {
            return (
                <div className={classNames(className, "isEmpty", classes.root(columns))}>
                    <Paragraph>{this.props.emptyMessage}</Paragraph>
                </div>
            );
        } else {
            return (
                <div className={classNames(className, classes.root(columns))}>
                    <ul className={classNames(classes.items(alignment))}>
                        {items.map((tile, i) => (
                            <li key={i} className={classNames(classes.item(columns))}>
                                <Card
                                    icon={tile.icon}
                                    fallbackIcon={this.props.fallbackIcon}
                                    title={tile.name}
                                    description={tile.description}
                                    url={tile.url}
                                    columns={columns}
                                />
                            </li>
                        ))}
                    </ul>
                </div>
            );
        }
    }
}
