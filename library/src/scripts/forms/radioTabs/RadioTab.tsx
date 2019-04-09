/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { ITabProps, withTabs } from "@library/contexts/TabContext";
import { radioTabClasses } from "@library/forms/radioTabs/radioTabStyles";

interface IProps extends ITabProps {
    label: string;
    className?: string;
    data: string | number;
    activeTab: string | number;
    position?: "left" | "right";
}

/**
 * Implement what looks like a tab, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
class RadioTab extends React.Component<IProps> {
    public render() {
        const classes = radioTabClasses();
        return (
            <label
                className={classNames(
                    "radioButtonsAsTabs-tab",
                    this.props.childClass,
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
                    checked={this.props.activeTab === this.props.data}
                    name={this.props.groupID}
                    value={this.props.label}
                />
                <span
                    className={classNames(
                        "radioButtonsAsTabs-label",
                        classes.label,
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

export default withTabs<IProps>(RadioTab);
