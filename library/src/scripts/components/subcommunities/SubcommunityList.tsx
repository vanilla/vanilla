/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Paragraph from "@library/components/Paragraph";
import SubcommunityTile from "@library/components/subcommunities/SubcommunityTile";
import { subcommunityListClasses } from "@library/styles/subcommunityListStyles";
import { subcommunityTileClasses } from "@library/styles/subcommunityTitleStyles";

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
}

/**
 * Renders list of subcommunities
 */
export default class SubcommunityList extends React.Component<IProps> {
    public render() {
        const { className, items } = this.props;
        const classes = subcommunityListClasses();
        const classesSubCommunityTile = subcommunityTileClasses();

        if (items.length === 0) {
            return (
                <div className={classNames("subcommunityList", className, "isEmpty", classes.root)}>
                    <Paragraph className="subcommunityList-emptyMessage">{this.props.emptyMessage}</Paragraph>
                </div>
            );
        } else {
            return (
                <div className={classNames("subcommunityList", className, classes.root)}>
                    <ul className={classNames("subcommunityList-items", classes.items)}>
                        {items.map((subcommunity, i) => (
                            <li key={i} className={classNames("subcommunityList-item", classes.item)}>
                                <SubcommunityTile
                                    icon={subcommunity.icon}
                                    fallbackIcon={this.props.fallbackIcon}
                                    title={subcommunity.name}
                                    description={subcommunity.description}
                                    url={subcommunity.url}
                                />
                            </li>
                        ))}
                    </ul>
                </div>
            );
        }
    }
}
