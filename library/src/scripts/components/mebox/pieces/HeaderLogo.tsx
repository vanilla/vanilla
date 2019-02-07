/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { vanillaLogo } from "@library/components/icons/header";
import SmartLink from "@library/components/navigation/SmartLink";
import { vanillaHeaderLogoClasses } from "@library/styles/vanillaHeaderStyles";

export interface IHeaderLogo {
    className?: string;
    to: string;
    logoUrl?: string;
    logoClassName?: string;
    alt?: string;
    color?: string;
}

/**
 * Implements Logo component
 */
export default class HeaderLogo extends React.Component<IHeaderLogo> {
    public constructor(props: IHeaderLogo) {
        super(props);
        if (props.logoUrl && !props.alt) {
            throw Error("You need alt text if you are setting your own logo");
        }
    }
    public render() {
        let contents;
        const classes = vanillaHeaderLogoClasses();
        const logoClassName = classNames("headerLogo-logo", this.props.logoClassName, classes.logo);
        if (this.props.logoUrl) {
            contents = <img src={this.props.logoUrl} alt={this.props.alt} className={logoClassName} />;
        } else {
            contents = vanillaLogo(logoClassName, this.props.color);
        }
        return (
            <SmartLink to={this.props.to} className={classNames("headerLogo", classes.link, this.props.className)}>
                <span className={classNames("headerLogo-logoFrame", classes.logoFrame)}>{contents}</span>
            </SmartLink>
        );
    }
}
