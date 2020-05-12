/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { ITabContext, withTabs } from "@library/contexts/TabContext";
import { radioTabClasses } from "@library/forms/radioTabs/radioTabStyles";
import { IRadioTabClasses } from "@library/forms/radioTabs/RadioTabs";

export interface IBaseTabProps {
    label: string;
    data: string | number;
    className?: string;
    position?: "left" | "right";
    classes?: IRadioTabClasses;
    customTabActiveClass?: string;
    customTabInactiveClass?: string;
    disabled?: boolean;
}

export interface ITabProps extends IBaseTabProps, ITabContext {}

/**
 * Implement what looks like a tab, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
class RadioTab extends React.Component<ITabProps> {
    public render() {
        const classes = this.props.classes ?? radioTabClasses();
        const active = this.props.activeTab === this.props.data;
        return (
            <label
                className={classNames(
                    "radioButtonsAsTabs-tab",
                    { [this.props.childClass]: this.props.childClass },
                    this.props.className,
                    classes.tab,
                )}
            >
                <input
                    className={classNames("radioButtonsAsTabs-input", "sr-only", classes.input)}
                    type="radio"
                    onClick={this.onClick}
                    onKeyDown={this.onKeyDown}
                    onChange={this.handleOnChange}
                    checked={active}
                    name={this.props.groupID}
                    value={this.props.label}
                    disabled={this.props.disabled}
                />
                <span
                    className={classNames(
                        { isDisabled: this.props.disabled },
                        classes.label,
                        {
                            "radioButtonsAsTabs-label":
                                !this.props.customTabActiveClass && !this.props.customTabInactiveClass,
                            [`${this.props.customTabActiveClass}`]: this.props.customTabActiveClass && active,
                            [`${this.props.customTabInactiveClass}`]: this.props.customTabInactiveClass && !active,
                        },
                        this.props.position === "left" ? classes.leftTab : undefined,
                        this.props.position === "right" ? classes.rightTab : undefined,
                    )}
                >
                    {this.props.label}
                </span>
            </label>
        );
    }

    private onClick = event => {
        this.props.setData(this.props.data);
    };

    private handleOnChange = event => {
        return;
    };

    private onKeyDown = event => {
        switch (event.key) {
            case "Enter":
            case "Spacebar":
            case " ":
                this.props.setData(this.props.data);
                break;
        }
    };
}

export default withTabs<ITabProps>(RadioTab);
