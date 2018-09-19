/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { close } from "@library/components/Icons";
import { t } from "@library/application";
import { ILegacyProps } from "@library/@types/legacy";

interface IProps extends Partial<ILegacyProps> {
    className?: string;
    disabled?: boolean;
    onClick: any;
}

/**
 * A standardized close button.
 */
export default class CloseButton extends React.Component<IProps> {
    public static defaultProps: ILegacyProps = {
        legacyMode: false,
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
            const componentClasses = classNames("button", "button-icon", "button-close", this.props.className);
            return (
                <button
                    disabled={this.props.disabled}
                    type="button"
                    className={componentClasses}
                    title={closeLabel}
                    onClick={this.props.onClick}
                >
                    {close()}
                </button>
            );
        }
    }
}
