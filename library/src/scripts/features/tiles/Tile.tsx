/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";
import { tileClasses } from "@library/features/tiles/Tile.classes";

interface IProps {
    icon: string;
    iconAltText?: string; // If you want alternative alt text, title is passed in
    title: string;
    description: string;
    url: string;
    className?: string;
    headingLevel?: 2 | 3 | 4 | 5 | 6;
    fallbackIcon?: React.ReactNode;
    columns?: number;
}

/**
 * Render a subcommunity tile
 */
export default class Tile extends React.Component<IProps> {
    public static defaultProps = {
        headingLevel: 3,
    };
    public render() {
        const { icon, title, description, url, className, iconAltText, headingLevel, columns } = this.props;
        const H = `h${headingLevel}` as "h1";
        const alt = iconAltText ? iconAltText : `${t("Icon for: ")} ${this.props.title}`;
        const classes = tileClasses();
        return (
            <div className={classNames(className, classes.root)}>
                <SmartLink className={classNames(classes.link(columns))} to={url}>
                    <div className={classNames(classes.frame)}>
                        {icon && <img className={classNames(classes.icon)} src={icon} alt={alt} loading="lazy" />}
                        {!icon && (this.props.fallbackIcon ? this.props.fallbackIcon : "")}
                    </div>
                    <H className={classNames(classes.title)}>{title}</H>
                    {description && <Paragraph className={classNames(classes.description)}>{description}</Paragraph>}
                </SmartLink>
            </div>
        );
    }
}
