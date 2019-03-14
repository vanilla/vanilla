/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { buttonClasses, ButtonTypes } from "@library/forms/buttonStyles";
import { formErrorClasses } from "@library/forms/formElementStyles";
import classNames from "classNames";

interface IProps {
    children: React.ReactNode;
    isRetryLoading?: boolean;
    onDismissClick: () => void;
    onRetryClick?: () => void; // If this is passed a rety button will appear to call it.
}

/**
 * Component for representing a Form Error.
 */
export default class FormError extends React.Component<IProps> {
    private selfRef = React.createRef<HTMLDivElement>();

    public render(): React.ReactNode {
        const { onRetryClick, onDismissClick, children } = this.props;
        const classes = formErrorClasses();
        const classesButtons = buttonClasses();

        return (
            <div ref={this.selfRef} className={classes.root}>
                <p role="alert">{children}</p>
                <div className={classes.actions}>
                    {onRetryClick && (
                        <Button
                            onClick={onRetryClick}
                            baseClass={ButtonTypes.TEXT}
                            className={classNames(classes.actionButton, classes.activeButton)}
                        >
                            {this.props.isRetryLoading ? (
                                <ButtonLoader buttonType={classesButtons.standard} />
                            ) : (
                                t("Retry")
                            )}
                        </Button>
                    )}
                    <Button onClick={onDismissClick} baseClass={ButtonTypes.TEXT} className={classes.actionButton}>
                        {t("Dismiss")}
                    </Button>
                </div>
            </div>
        );
    }

    /**
     * Scroll to ourselves when added to the DOM.
     */
    public componentDidMount() {
        if (this.selfRef.current) {
            this.selfRef.current.scrollIntoView();
        }
    }
}
