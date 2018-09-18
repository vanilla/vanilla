/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { close } from "@library/components/Icons";
import { t } from "../application";
import { IWithEditorProps, withEditor } from "@rich-editor/components/context";

interface IProps extends IWithEditorProps {
    className?: string;
    disabled?: boolean;
    onClick: any;
}

export class CloseButton extends React.Component<IProps> {
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

export default withEditor<IProps>(CloseButton);
