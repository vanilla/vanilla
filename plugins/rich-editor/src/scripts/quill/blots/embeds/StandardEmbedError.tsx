/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import CloseButton from "@library/navigation/CloseButton";
import { t } from "@library/utility/appUtils";
import { UserWarningIcon } from "@library/icons/titleBar";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { EmbedTitle } from "@library/embeddedContent/components/EmbedTitle";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";

interface IProps {
    error: IError;
    id: string;
    onDismissClick: () => void;
}

export default class StandardEmbedError extends React.Component<IProps> {
    public render() {
        const descriptionId = this.props.id + "-description";

        const title = this.props.error.description ? this.props.error.message : null;
        const description = this.props.error.description ?? this.props.error.message;

        return (
            <div
                className={classNames("embedLoader-error", EMBED_FOCUS_CLASS)}
                aria-describedby={descriptionId}
                aria-label={t("Error")}
                role="alert"
                aria-live="assertive"
                tabIndex={-1}
            >
                <UserWarningIcon className={"embedLoader-icon embedLoader-warningIcon"} />
                {title && <EmbedTitle>{title}</EmbedTitle>}
                <span id={descriptionId} className="embedLoader-errorMessage">
                    {description}
                </span>
                <CloseButton title={t("Remove")} onClick={this.handleDismissClick} />
            </div>
        );
    }

    private handleDismissClick = (event: MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        this.props.onDismissClick();
    };
}
