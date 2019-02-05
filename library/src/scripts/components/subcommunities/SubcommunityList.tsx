/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Paragraph from "@library/components/Paragraph";
import SubcommunityTile from "@library/components/subcommunities/SubcommunityTile";

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
    fallbackIcon: React.ReactNode;
}

/**
 * Renders list of subcommunities
 */
export default class SubcommunityList extends React.Component<IProps> {
    public render() {
        const { className, items } = this.props;

        if (items.length === 0) {
            return (
                <div className={classNames("subcommunityList", className, "isEmpty")}>
                    <Paragraph className="subcommunityList-emptyMessage">{this.props.emptyMessage}</Paragraph>
                </div>
            );
        } else {
            return (
                <div className={classNames("subcommunityList", className)}>
                    <ul className="subcommunityList-items">
                        {items.map((subcommunity, i) => (
                            <li key={i} className="subcommunityList-item">
                                <SubcommunityTile
                                    icon={subcommunity.icon}
                                    title={subcommunity.name}
                                    description={subcommunity.description}
                                    url={subcommunity.url}
                                    fallbackIcon={this.props.fallbackIcon}
                                />
                            </li>
                        ))}
                    </ul>
                </div>
            );
        }
    }
}
