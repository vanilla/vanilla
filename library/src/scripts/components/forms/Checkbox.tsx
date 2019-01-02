/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/application";
import React from "react";
import classNames from "classnames";
import { IOptionalComponentID, getOptionalID } from "@library/componentIDs";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked: boolean;
    disabled?: boolean;
    onChange: any;
    label: string;
}

interface IState {
    id: string;
}

/**
 * A styled, accessible checkbox component.
 */
export default class Checkbox extends React.Component<IProps, IState> {
    public static defaultProps = {
        disabled: false,
        id: false,
    };

    constructor(props) {
        super(props);
        this.state = {
            id: getOptionalID(props, "checkbox") as string,
        };
    }

    get labelID(): string {
        return this.state.id + "-label";
    }

    public render() {
        const componentClasses = classNames("checkbox", this.props.className);

        return (
            <label id={this.state.id} className={componentClasses}>
                <input
                    className="checkbox-input"
                    aria-labelledby={this.labelID}
                    type="checkbox"
                    onChange={this.props.onChange}
                    checked={this.props.checked}
                />
                <span className="checkbox-box" aria-hidden="true">
                    <span className="checkbox-state">
                        <svg
                            className="checkbox-icon checkbox-checkIcon"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 10 10"
                        >
                            <title>{t("✓")}</title>
                            <path
                                fill="currentColor"
                                d="M10,2.7c0-0.2-0.1-0.3-0.2-0.4L8.9,1.3c-0.2-0.2-0.6-0.2-0.9,0L3.8,5.6L1.9,3.7c-0.2-0.2-0.6-0.2-0.9,0L0.2,4.6c-0.2,0.2-0.2,0.6,0,0.9l3.2,3.2c0.2,0.2,0.6,0.2,0.9,0l5.5-5.5C9.9,3,10,2.8,10,2.7z"
                            />
                        </svg>
                    </span>
                </span>
                <span id={this.labelID} className="checkbox-label">
                    {this.props.label}
                </span>
            </label>
        );
    }
}
