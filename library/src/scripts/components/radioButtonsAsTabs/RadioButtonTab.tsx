/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

interface IRadioButtonTab {
    groupID: string;
    label: string;
    checked?: boolean;
    className?: string;
    setData: (data: any) => void;
    data: any;
}

/**
 * Implement what looks like a tab, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
// export default class RadioButtonTab extends React.Component<IRadioButtonTab> {
export default class RadioButtonTab extends React.Component<IRadioButtonTab> {
    private onClick = e => {
        this.props.setData(this.props.data);
    };

    //{groupID: string, setData: (data: any) => void, selectedTab: ISearchDomain}
    public render() {
        return (
            <label className={classNames("radioButtonsAsTabs-tab", this.props.className)}>
                <input
                    className="radioButtonsAsTabs-input sr-only"
                    type="radio"
                    onClick={this.onClick}
                    checked={this.props.checked}
                    name={this.props.groupID}
                    value={this.props.label}
                />
                <span className="radioButtonsAsTabs-label">{this.props.label}</span>
            </label>
        );
    }
}
