/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { close } from "@library/components/icons/common";
import { t } from "@library/application";
import { ILegacyProps } from "@library/@types/legacy";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";

interface IProps extends Partial<ILegacyProps> {
    className?: string;
    disabled?: boolean;
    onClick: any;
    baseClass?: ButtonBaseClass;
}

/**
 * A standardized close button.
 */
export default class CloseButton extends React.PureComponent<IProps> {
    public static defaultProps = {
        legacyMode: false,
        baseClass: ButtonBaseClass.ICON,
    };

    /**
     * There are 2 rendering modes. 1 with w real icon, and one using text in place of an icon.
     */
    public render() {
        const closeLabel = t("Close");

        if (this.props.legacyMode) {
            const componentClasses = classNames("Close", this.props.className);
            const closeChar = `×`;
            return (
                <button type="button" title={closeLabel} onClick={this.props.onClick} className={componentClasses}>
                    <span className="Close-x" aria-hidden="true">
                        {closeChar}
                    </span>
                    <span className="sr-only">{closeLabel}</span>
                </button>
            );
        } else {
            const componentClasses = classNames("buttonClose", this.props.className);
            return (
                <Button
                    disabled={this.props.disabled}
                    type="button"
                    className={componentClasses}
                    title={closeLabel}
                    onClick={this.props.onClick}
                    baseClass={this.props.baseClass}
                >
                    {close()}
                </Button>
            );
        }
    }
}
