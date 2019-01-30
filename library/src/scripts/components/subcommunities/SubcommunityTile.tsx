/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import SmartLink from "@library/components/navigation/SmartLink";
import Paragraph from "@library/components/Paragraph";
import classNames from "classnames";
import { t } from "@library/application";

interface IProps {
    icon: string;
    iconAltText?: string; // If you want alternative alt text, title is passed in
    title: string;
    description: string;
    url: string;
    className?: string;
    headingLevel?: 2 | 3 | 4 | 5 | 6;
    fallbackIcon: React.ReactNode;
}

/**
 * Render a subcommunity tile
 */
export default class SubcommunityTile extends React.Component<IProps> {
    public static defaultProps = {
        headingLevel: 3,
    };
    public render() {
        const { icon, title, description, url, className, iconAltText, headingLevel } = this.props;
        const H = `h${headingLevel}`;
        const alt = iconAltText ? iconAltText : `${t("Icon for: ")} ${this.props.title}`;

        return (
            <div className={classNames("subcommunityTile", className)}>
                <SmartLink className="subcommunityTile-link" to={url}>
                    <div className="subcommunityTile">
                        <div className="subcommunityTile-iconFrame">
                            {icon && <img className="subcommunityTile-icon" src={icon} alt={alt} />}
                            {!icon && this.props.fallbackIcon}
                        </div>
                        <H className="subcommunityTile-title">{title}</H>
                        {description && <Paragraph className="subcommunityTile-description">{description}</Paragraph>}
                    </div>
                </SmartLink>
            </div>
        );
    }
}
