/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

import { getRequiredID } from "../componentIDs";
import { t } from "../application";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import { checkCompact, downTriangle } from "@library/components/Icons";
import DropDown from "@library/components/dropdown/DropDown";
import { ButtonBaseClass } from "@library/components/forms/Button";

export interface ISelectBoxItem {
    name: string;
    className?: string;
    onClick: () => {};
    selected?: boolean;
    outdated?: boolean;
}

interface IProps {
    className?: string;
    id?: string;
    children: ISelectBoxItem[];
    stickTop?: boolean; // Adjusts the flyout position vertically
    stickRight?: boolean; // Adjusts the flyout position horizontally
}

export interface ISelfLabelledProps extends IProps {
    label: string;
}

export interface IExternalLabelledProps extends IProps {
    describedBy: string;
}

interface IState {
    id: string;
    selectedIndex: number;
    selectedItem: any;
}

/**
 * Generates Select Box component (similar to a select)
 */
export default class SelectBox extends React.Component<ISelfLabelledProps | IExternalLabelledProps, IState> {
    public static defaultProps = {
        selectedIndex: 0,
    };

    public constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "selectBox-"),
            selectedIndex: props.selectedIndex,
            selectedItem: props.selectedItem || props.children[props.selectedIndex],
        };
    }

    /**
     * Handle click on item in select box.
     * @param selectedItem data for item
     * @param index the index of the item
     */
    public handleClick = (selectedItem: ISelectBoxItem, index: number) => {
        this.setState({
            selectedIndex: index,
            selectedItem,
        });
    };

    public render() {
        const selectItems = this.props.children.map((child, i) => {
            const selected = this.state.selectedIndex === i;
            return (
                <DropDownItemButton
                    key={this.props.id + "-item" + i}
                    className={classNames({ isSelected: child.selected })}
                    name={child.name}
                    onClick={this.handleClick.bind(this, child, i)}
                    disabled={i === this.state.selectedIndex}
                    clickData={child}
                    index={i}
                    current={selected}
                    buttonClassName="dropDownItem-button selectBox-buttonItem"
                >
                    <span className="selectBox-checkContainer sc-only">
                        {selected && checkCompact("selectBox-selectedIcon")}
                        {!selected && (
                            <span className="selectBox-spacer" aria-hidden={true}>
                                {` `}
                            </span>
                        )}
                    </span>
                    <span className="selectBox-itemLabel">{child.name}</span>
                    {child.outdated && <span className="selectBox-outdated metaStyle">{t("(Outdated)")}</span>}
                </DropDownItemButton>
            );
        });
        const buttonContents =
            this.state.selectedItem && this.state.selectedItem.name ? (
                <React.Fragment>
                    {this.state.selectedItem.name}
                    {downTriangle("selectBox-buttonIcon")}
                </React.Fragment>
            ) : null;
        return (
            <div
                aria-describedby={"describedBy" in this.props ? this.props.describedBy : undefined}
                className={classNames("selectBox", this.props.className)}
            >
                {"label" in this.props && <span className="selectBox-label">{this.props.label}</span>}
                <div className="selectBox-content">
                    <DropDown
                        id={this.state.id}
                        className="selectBox-dropDown"
                        name={"label" in this.props ? this.props.label : this.state.selectedItem.name}
                        buttonContents={buttonContents}
                        buttonClassName="selectBox-toggle"
                        buttonBaseClass={ButtonBaseClass.TEXT}
                        stickTop={this.props.stickTop}
                        stickRight={this.props.stickRight}
                    >
                        {selectItems}
                    </DropDown>
                </div>
            </div>
        );
    }
}
