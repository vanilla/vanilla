/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import { userWarning } from "@library/icons/header";
import CloseButton from "@library/navigation/CloseButton";
import { t } from "@library/utility/appUtils";

interface IProps {
    children: string;
    id: string;
    onDismissClick: () => void;
}

export default class StandardEmbedError extends React.Component<IProps> {
    public render() {
        const descriptionId = this.props.id + "-description";

        return (
            <div
                className={classNames("embedLoader-error", FOCUS_CLASS)}
                aria-describedby={descriptionId}
                aria-label={t("Error")}
                role="alert"
                aria-live="assertive"
            >
                {userWarning("embedLoader-icon embedLoader-warningIcon")}
                <span id={descriptionId} className="embedLoader-errorMessage">
                    {this.props.children}
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
