/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { ILegacyMode } from "@rich-editor/components/editor/editor";
import { close } from "@library/components/Icons";
import { t } from "@library/application";

interface IProps extends ILegacyMode {
    className?: string;
    disabled?: boolean;
    onClick: any;
}

export default class CloseButton extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
    };

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
            const componentClasses = classNames("button", "button-close", this.props.className);
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
