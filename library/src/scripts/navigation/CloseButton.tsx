/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import { CloseCompactIcon } from "@library/icons/common";
import { CloseIcon } from "@library/icons/titleBar";

interface IProps {
    className?: string;
    disabled?: boolean;
    onClick: any;
    baseClass?: ButtonTypes;
    title?: string;
    compact?: boolean;
}

/**
 * A standardized close button.
 */
export default class CloseButton extends React.PureComponent<IProps> {
    public static defaultProps = {
        baseClass: ButtonTypes.ICON,
        compact: false,
    };

    /**
     * There are 2 rendering modes. 1 with w real icon, and one using text in place of an icon.
     */
    public render() {
        const closeLabel = this.props.title ? this.props.title : t("Close");
        const componentClasses = classNames("buttonClose", "closeButton", this.props.className);
        return (
            <Button
                disabled={this.props.disabled}
                className={componentClasses}
                title={closeLabel}
                onClick={this.props.onClick}
                baseClass={this.props.baseClass}
            >
                {this.props.compact ? <CloseCompactIcon /> : <CloseIcon />}
            </Button>
        );
    }
}
