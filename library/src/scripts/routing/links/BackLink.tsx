/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { LeftChevronCompactIcon } from "@library/icons/common";
import { useHistoryDepth } from "@library/routing/HistoryDepthContext";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { t, siteUrl } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { useHistory } from "react-router";

interface IProps {
    ///
    /// Routing options.
    ///

    /** The URL to navigate to if we can't do a dynamic browser back navigation. */
    fallbackUrl?: string;

    /** A component to render if we can't do a dynamic browser back navigation. */
    fallbackElement?: React.ReactNode;

    /** An action to if the component is clicked */
    onClick?: (e: React.MouseEvent) => void;

    ///
    /// Display options
    ///
    /** Title contents for the backlink */
    title?: React.ReactNode;

    /** CSS class to apply to the container */
    className?: string;

    /** CSS class to apply to the contents. */
    linkClassName?: string;

    /** Whether or not to display the label visibly. */
    visibleLabel?: boolean;
}

/**
 * A link button for navigating backwards. Uses a back arrow icon.
 *
 * Render priority:
 * - Render a button w/ the provided click handler.
 * - Render a button that navigates back using (dynamic routing, uses real browser history back & preserves scroll).
 * - Render the provided fallbackElement if we can't navigate back.
 * - Render the a link to one of the following if we can't navigate back.
 *   - The `fallbackUrl` prop.
 *   - The site homepage.
 */
export default function BackLink(props: IProps) {
    const history = useHistory();
    const { canGoBack } = useHistoryDepth();

    const classes = backLinkClasses();
    const className = classNames(classes.link, { hasVisibleLabel: !!props.visibleLabel }, props.linkClassName);

    let content = (
        <>
            <LeftChevronCompactIcon className={classes.icon} />
            {props.visibleLabel && <span className={classes.label}>{props.title}</span>}
        </>
    );

    if (props.onClick) {
        content = (
            <Button
                baseClass={ButtonTypes.RESET}
                className={className}
                aria-label={props.title as string}
                title={props.title as string}
                onClick={props.onClick}
            >
                {content}
            </Button>
        );
    } else if (canGoBack) {
        // We can go back.
        content = (
            <Button
                baseClass={ButtonTypes.RESET}
                className={className}
                aria-label={props.title as string}
                title={props.title as string}
                onClick={(event: React.MouseEvent) => {
                    event.preventDefault();
                    event.stopPropagation();
                    history.goBack();
                }}
            >
                {content}
            </Button>
        );
    } else if (props.fallbackElement) {
        return props.fallbackElement;
    } else {
        // Fallback to URL navigation.
        const routingUrl = props.fallbackUrl ?? siteUrl("/");
        content = (
            <SmartLink
                to={routingUrl}
                aria-label={props.title as string}
                title={props.title as string}
                className={className}
            >
                {content}
            </SmartLink>
        );
    }

    return <div className={classNames(classes.root, props.className)}>{content}</div>;
}

BackLink.defaultProps = {
    title: t("Back"),
    visibleLabel: false,
};
