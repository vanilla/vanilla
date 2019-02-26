/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button, { ButtonBaseClass } from "./Button";
import { t } from "@library/application";
import { formErrorClasses } from "@library/components/forms/formElementStyles";
import classNames from "classnames";
import Paragraph from "@library/components/Paragraph";

interface IProps {
    children: React.ReactNode;
    onDismissClick: () => void;
    onRetryClick?: () => void;
}

export default class FormError extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { onRetryClick, onDismissClick, children } = this.props;
        const classes = formErrorClasses();

        return (
            <div className={classes.root}>
                <Paragraph>{children}</Paragraph>
                <div className={classes.actions}>
                    {onRetryClick && (
                        <Button
                            onClick={onRetryClick}
                            baseClass={ButtonBaseClass.TEXT}
                            className={classNames(classes.actionButton, classes.activeButton)}
                        >
                            {t("Retry")}
                        </Button>
                    )}
                    <Button onClick={onDismissClick} baseClass={ButtonBaseClass.TEXT} className={classes.actionButton}>
                        {t("Dismiss")}
                    </Button>
                </div>
            </div>
        );
    }
}
