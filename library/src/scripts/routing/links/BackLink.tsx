/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { formatUrl, t } from "@library/utility/appUtils";
import { RouteComponentProps, withRouter } from "react-router";
import { Link } from "react-router-dom";
import { LeftChevronCompactIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";

interface IProps extends RouteComponentProps<{}> {
    fallbackUrl?: string;
    title?: React.ReactNode;
    className?: string;
    linkClassName?: string;
    visibleLabel?: boolean;
    onClick?: (e: React.MouseEvent) => void;
    fallbackElement?: React.ReactNode;
}

/**
 * A link button for navigating backwards. Uses a back arrow icon.
 */
export class BackLink extends React.Component<IProps> {
    public static defaultProps = {
        title: t("Back"),
        visibleLabel: false,
    };
    public render() {
        if (this.props.history.length === 0 && !this.props.fallbackUrl && this.props.fallbackElement) {
            // Optional fallback element to render if no history exists and no fallback url given
            return this.props.fallbackElement;
        } else {
            const classes = backLinkClasses();
            const routingUrl = this.props.fallbackUrl ? this.props.fallbackUrl : formatUrl("/kb");
            const content = (
                <>
                    <LeftChevronCompactIcon className={classes.icon} />
                    {this.props.visibleLabel && <span className={classes.label}>{this.props.title}</span>}
                </>
            );
            const className = classNames(
                classes.link,
                { hasVisibleLabel: !!this.props.visibleLabel },
                this.props.linkClassName,
            );

            return (
                <div className={classNames(classes.root, this.props.className)}>
                    {this.props.onClick ? (
                        <Button
                            baseClass={ButtonTypes.RESET}
                            className={className}
                            aria-label={this.props.title as string}
                            title={this.props.title as string}
                            onClick={this.props.onClick}
                        >
                            {content}
                        </Button>
                    ) : (
                        <Link
                            to={routingUrl}
                            aria-label={this.props.title as string}
                            title={this.props.title as string}
                            className={className}
                        >
                            <LeftChevronCompactIcon className={classes.icon} />
                            {this.props.visibleLabel && <span className={classes.label}>{this.props.title}</span>}
                        </Link>
                    )}
                </div>
            );
        }
    }

    /**
     * If we can do an actual back action on the history object we should.
     * Otherwise fallback to the default behaviour.
     */
    private clickHandler = (event: React.MouseEvent) => {
        if (!this.props.fallbackUrl) {
            event.preventDefault();
            event.stopPropagation();
            this.props.history.goBack();
        }
    };
}

export default withRouter(BackLink);
