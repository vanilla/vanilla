/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, LoadStatus } from "@library/@types/api/core";
import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { userContentVariables } from "@library/content/UserContent.variables";
import { DetailedErrors } from "@library/errorPages/DetailedErrorMessages";
import { pageErrorMessageClasses } from "@library/errorPages/pageErrorMessageStyles";
import { useCurrentUser } from "@library/features/users/userHooks";
import { isUserGuest } from "@library/features/users/userModel";
import { buttonClasses } from "@library/forms/Button.styles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { SearchErrorIcon } from "@library/icons/common";
import Heading from "@library/layout/Heading";
import Paragraph from "@library/layout/Paragraph";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";
import { escapeHTML } from "@vanilla/dom-utils";
import { debug, logError } from "@vanilla/utils";
import classNames from "classnames";
import React, { ReactNode } from "react";

export function CoreErrorMessages(props: IProps) {
    const classes = pageErrorMessageClasses();
    const error = {
        ...parseErrorCode(getErrorCode(props)),
        ...(props.error || {}),
    };
    const { message, messageAsParagraph, description } = error;
    return (
        <main className={classNames(props.className, classes.root)}>
            {error.icon}
            {!messageAsParagraph && <Heading depth={1} className={classes.title} title={message} />}
            {messageAsParagraph && <Paragraph className={classes.titleAsParagraph}>{message}</Paragraph>}
            {error.description && <Paragraph className={classes.description}>{description}</Paragraph>}
            {debug() && <DetailedErrors detailedErrors={error?.response?.data?.errors} />}
            {debug() && error.trace && (
                <div className={classes.trace}>
                    <h2>Stack Trace</h2>
                    <CollapsableContent maxHeight={300} bgColor={userContentVariables().codeBlock.bg}>
                        <UserContent
                            content={`<pre class="code codeBlock">${escapeHTML(error.trace)}</pre>`}
                        ></UserContent>
                    </CollapsableContent>
                </div>
            )}
            {error.actionButton ? (
                <div className={classes.cta}>
                    <LinkAsButton to={error.actionButton.url} target={error.actionButton.target}>
                        {error.actionButton.label}
                    </LinkAsButton>
                </div>
            ) : (
                error.actionItem && <div className={classes.cta}>{error.actionItem}</div>
            )}
        </main>
    );
}

export function parseErrorCode(errorCode?: string | number): IError {
    const classes = pageErrorMessageClasses();
    const buttons = buttonClasses();
    const message = messageFromErrorCode(errorCode);

    switch (errorCode) {
        case 403:
        case DefaultError.PERMISSION: {
            return {
                message,
                description: t("You don't have permission to view this resource."),
                actionItem: <ErrorSignIn />,
                icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
            };
        }
        case 404:
        case DefaultError.NOT_FOUND: {
            return {
                message,
                description: t("The page you were looking for could not be found."),
                actionItem: (
                    <LinkAsButton buttonType={ButtonTypes.PRIMARY} to={"/"}>
                        {t("Back to home page")}
                    </LinkAsButton>
                ),
                icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
            };
        }
        case DefaultError.GENERIC:
        default: {
            return {
                message,
                description: t("Please try again later."),
                actionItem: (
                    <LinkAsButton className={buttons.primary} to={"/"}>
                        {t("Back to Home")}
                    </LinkAsButton>
                ),
                icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
            };
        }
    }
}

function ErrorSignIn() {
    const currentUser = useCurrentUser();
    if (!!currentUser && isUserGuest(currentUser)) {
        return (
            <LinkAsButton to={`/entry/signin?Target=${encodeURIComponent(window.location.href)}`}>
                {t("Sign In")}
            </LinkAsButton>
        );
    } else {
        return null;
    }
}

export function messageFromErrorCode(errorCode?: string | number) {
    switch (errorCode) {
        case 403:
        case DefaultError.PERMISSION:
            return t("Permission Problem");
        case 404:
        case DefaultError.GENERIC:
        default:
            return t("There was an error");
    }
}

export function getErrorCode(errorMessageProps: IErrorMessageProps) {
    if (errorMessageProps.apiError && errorMessageProps.apiError.response) {
        return errorMessageProps.apiError.response.status;
    } else if (errorMessageProps.error && errorMessageProps.error.status) {
        return errorMessageProps.error.status;
    } else if (errorMessageProps.defaultError) {
        return errorMessageProps.defaultError;
    } else {
        return errorMessageProps.defaultError;
    }
}

export function extractErrorMessage(throwable: unknown): string {
    if (throwable instanceof Error) {
        return throwable.message;
    }

    if (typeof throwable === "string") {
        return throwable;
    }

    if (throwable && typeof throwable === "object" && "message" in throwable && typeof throwable.message === "string") {
        return throwable.message;
    }

    logError(throwable);
    return "Something went wrong.";
}

export interface IAPIErrorFragment {
    response: {
        status: number;
    };
}

export interface IErrorMessageProps {
    defaultError?: string;
    error?: Partial<IError>;
    apiError?: IAPIErrorFragment;
}

export interface IError {
    status?: number;
    message: string;
    messageAsParagraph?: boolean;
    description?: ReactNode;
    actionItem?: ReactNode;
    icon?: ReactNode;
    response?: any;
    trace?: string;
    actionButton?: IApiError["actionButton"];
}

export enum DefaultError {
    GENERIC = "generic",
    PERMISSION = "permission",
    NOT_FOUND = "notfound",
}

interface IProps extends IErrorMessageProps {
    className?: string;
}
