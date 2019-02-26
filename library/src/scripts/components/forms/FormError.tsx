/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button, { ButtonBaseClass } from "./Button";
import { t } from "@library/application";
import { formErrorClasses } from "@library/components/forms/formElementStyles";
import { classes } from "typestyle";
import { buttonClasses } from "@library/styles/buttonStyles";

interface IProps {
    children: React.ReactNode;
    onDismissClick: () => void;
    onRetryClick?: () => void;
}

export default class FormError extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { onRetryClick, onDismissClick, children } = this.props;
        const mClasses = formErrorClasses();
        const mButtonclasses = buttonClasses();

        return (
            <div className={mClasses.root}>
                <p className={mClasses.message}>{children}</p>
                <div className={mClasses.actions}>
                    {onRetryClick && (
                        <Button
                            onClick={onRetryClick}
                            baseClass={ButtonBaseClass.TEXT}
                            className={classes(mClasses.actionButton, mButtonclasses.primary)}
                        >
                            {t("Retry")}
                        </Button>
                    )}
                    <Button
                        onClick={onDismissClick}
                        baseClass={ButtonBaseClass.TEXT}
                        className={classes(mClasses.actionButton)}
                    >
                        {t("Dismiss")}
                    </Button>
                </div>
            </div>
        );
    }
}
