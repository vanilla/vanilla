/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";
import SubcommunityTile from "@library/components/subcommunities/SubcommunityTile";

interface IProps {
    className?: string;
    items: any[];
    title: string;
    titleLevel?: 1 | 2 | 3 | 4 | 5 | 6;
    hiddenTitle?: boolean;
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
                    <Paragraph className="subcommunityList-emptyMessage">{t("No knowledge bases found.")}</Paragraph>
                </div>
            );
        } else {
            return (
                <div className={classNames("subcommunityList", className)}>
                    <ul className="subcommunityList-items">
                        {items.map(kb => (
                            <li key={kb.knowledgeBaseID} className="subcommunityList-item">
                                <SubcommunityTile
                                    icon={kb.icon}
                                    title={kb.name}
                                    description={kb.description}
                                    url={kb.url}
                                />
                            </li>
                        ))}
                    </ul>
                </div>
            );
        }
    }
}
