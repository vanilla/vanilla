/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import {
    subcommunityListClasses,
    subcommunityListVariables,
} from "@library/features/subcommunities/subcommunityListStyles";

import { subcommunityTileClasses } from "@library/features/subcommunities/subcommunityTitleStyles";
import SubcommunityTile from "@library/features/subcommunities/SubcommunityTile";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";

interface ISubcommunity {
    icon: string;
    name: string;
    description: string;
    url: string;
}

interface IProps {
    className?: string;
    items: ISubcommunity[];
    title: string;
    titleLevel?: 1 | 2 | 3 | 4 | 5 | 6;
    hiddenTitle?: boolean;
    emptyMessage: string;
    fallbackIcon?: React.ReactNode;
    alignment?: SubcommunityListAlignment;
    columns?: number;
}

export enum SubcommunityListAlignment {
    LEFT = "left",
    CENTER = "center",
}

/**
 * Renders list of subcommunities
 */
export default class SubcommunityList extends React.Component<IProps> {
    public render() {
        const vars = subcommunityListVariables();
        const { className, items, alignment = vars.options.alignment, columns = vars.options.columns } = this.props;
        const classes = subcommunityListClasses();

        if (items.length === 0) {
            return (
                <div className={classNames("subcommunityList", className, "isEmpty", classes.root(columns))}>
                    <Paragraph className="subcommunityList-emptyMessage">{this.props.emptyMessage}</Paragraph>
                </div>
            );
        } else {
            return (
                <div className={classNames(className, classes.root(columns))}>
                    <ul className={classNames(classes.items(alignment))}>
                        {items.map((subcommunity, i) => (
                            <li key={i} className={classNames(classes.item(columns))}>
                                <SubcommunityTile
                                    icon={subcommunity.icon}
                                    fallbackIcon={this.props.fallbackIcon}
                                    title={subcommunity.name}
                                    description={subcommunity.description}
                                    url={subcommunity.url}
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
