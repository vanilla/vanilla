/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button, { ButtonBaseClass } from "./Button";
import { t } from "@library/application";

interface IProps {
    children: React.ReactNode;
    onDismissClick: () => void;
    onRetryClick?: () => void;
}

export default class FormError extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { onRetryClick, onDismissClick, children } = this.props;
        return (
            <div className="formError">
                <p className="formError-message">{children}</p>
                <div className="formError-actions">
                    {onRetryClick && (
                        <Button
                            onClick={onRetryClick}
                            baseClass={ButtonBaseClass.TEXT}
                            className="formError-action formError-action_primary"
                        >
                            {t("Retry")}
                        </Button>
                    )}
                    <Button onClick={onDismissClick} baseClass={ButtonBaseClass.TEXT} className="formError-action">
                        {t("Dismiss")}
                    </Button>
                </div>
            </div>
        );
    }
}
