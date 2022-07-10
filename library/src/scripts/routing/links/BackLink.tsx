/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { LeftChevronCompactIcon } from "@library/icons/common";
import { useBackRouting } from "@library/routing/links/BackRoutingProvider";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { useHistory } from "react-router";
import LinkAsButton from "@library/routing/LinkAsButton";

interface IProps {
    ///
    /// Routing options.
    ///

    /** The URL to navigate to if we can't do a dynamic browser back navigation. */
    fallbackUrl?: string;

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

    /** Optional extra class for chevron **/
    chevronClass?: string;

    /** Check history and hide if there's no where to go **/
    hideIfNoHistory?: boolean;
}

/**
 * A link button for navigating backwards. Uses a back arrow icon.
 *
 * Render priority:
 * - Render a button w/ the provided click handler.
 * - Render a button that navigates back using (dynamic routing, uses real browser history back & preserves scroll).
 * - Render the a link to one of the following if we can't navigate back.
 *   - The `fallbackUrl` prop.
 *   - The site homepage.
 */
export default function BackLink(props: IProps) {
    const history = useHistory();
    const { canGoBack, backFallbackUrl, navigateBack } = useBackRouting();

    const classes = backLinkClasses();
    const className = classNames(classes.link, { hasVisibleLabel: !!props.visibleLabel }, props.linkClassName);
    const title = props.title || t("Back");

    if (!canGoBack && props.hideIfNoHistory && !props.fallbackUrl) {
        return null;
    }

    let content = (
        <>
            <LeftChevronCompactIcon className={classNames(classes.icon, props.chevronClass)} />
            {props.visibleLabel && <span className={classes.label}>{title}</span>}
        </>
    );

    if (props.onClick) {
        content = (
            <Button
                buttonType={ButtonTypes.TEXT}
                className={className}
                aria-label={title as string}
                title={title as string}
                onClick={props.onClick}
            >
                {content}
            </Button>
        );
    } else if (canGoBack) {
        // We can go back.
        content = (
            <Button
                buttonType={ButtonTypes.TEXT}
                className={className}
                aria-label={title as string}
                title={title as string}
                onClick={(event: React.MouseEvent) => {
                    event.preventDefault();
                    event.stopPropagation();
                    history.goBack();
                }}
            >
                {content}
            </Button>
        );
    } else {
        content = (
            <LinkAsButton
                buttonType={ButtonTypes.TEXT}
                to={props.fallbackUrl ?? backFallbackUrl} // Only here for showing the URL on hover.
                className={className}
                aria-label={title as string}
                title={title as string}
                onClick={(event: React.MouseEvent) => {
                    // We don't use a real link navigation.
                    event.preventDefault();
                    event.stopPropagation();
                    navigateBack(props.fallbackUrl);
                }}
            >
                {content}
            </LinkAsButton>
        );
    }

    return <div className={classNames("backLink", classes.root, props.className)}>{content}</div>;
}

BackLink.defaultProps = {
    visibleLabel: false,
};
