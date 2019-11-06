/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { getRequiredID } from "@library/utility/idUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import DropDown, { FlyoutType, DropDownOpenDirection } from "@library/flyouts/DropDown";
import classNames from "classnames";
import { CheckCompactIcon, DownTriangleIcon, AlertIcon } from "@library/icons/common";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

export interface ISelectBoxItem {
    name: string;
    content?: React.ReactNode;
    className?: string;
    selected?: boolean;
    icon?: React.ReactNode;
    url?: string;
}

interface IProps {
    className?: string;
    id?: string;
    children: ISelectBoxItem[];
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    widthOfParent?: boolean;
    openAsModal?: boolean;
    selectedIndex?: number;
    renderLeft?: boolean;
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
        buttonBaseClass: ButtonTypes.TEXT,
    };

    public constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "selectBox-"),
            selectedIndex: props.selectedIndex,
            selectedItem: props.children[props.selectedIndex],
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

    private renderChild = (child, selected, classes) => {
        return (
            <>
                <span className={classNames("selectBox-itemLabel", classes.itemLabel)}>
                    {child.content || child.name}
                </span>
                <span className={classNames("selectBox-checkContainer", "sc-only", classes.checkContainer)}>
                    {selected && <CheckCompactIcon className={"selectBox-selectedIcon"} />}
                    {!selected && (
                        <span className={classNames("selectBox-spacer", classes.spacer)} aria-hidden={true}>
                            {` `}
                        </span>
                    )}
                    {child.icon}
                </span>
            </>
        );
    };
    public render() {
        const classes = selectBoxClasses();

        const classesDropDown = dropDownClasses();
        const selectItems = this.props.children.map((child, i) => {
            const selected = this.state.selectedIndex === i;
            const key = this.props.id + "-item" + i;

            if ("url" in child) {
                return (
                    <DropDownItemLink
                        key={key}
                        className={classNames({ isSelected: child.selected })}
                        // name={child.name}
                        to={child.url || ""}
                        isModalLink={this.props.openAsModal}
                    >
                        {this.renderChild(child, selected, classes)}
                    </DropDownItemLink>
                );
            } else {
                return (
                    <DropDownItemButton
                        key={key}
                        className={classNames({ isSelected: child.selected })}
                        // name={child.name}
                        onClick={this.handleClick}
                        //isModalLink={this.props.openAsModal}
                    >
                        {this.renderChild(child, selected, classes)}
                    </DropDownItemButton>
                );
            }
        });
        const buttonContents =
            this.state.selectedItem && this.state.selectedItem.name ? (
                <React.Fragment>
                    {this.state.selectedItem.content || this.state.selectedItem.name}
                    <DownTriangleIcon className={classNames("selectBox-buttonIcon", classes.buttonIcon)} />
                </React.Fragment>
            ) : null;
        return (
            <div
                aria-describedby={"describedBy" in this.props ? this.props.describedBy : undefined}
                className={classNames("selectBox", this.props.className)}
            >
                {"label" in this.props && <span className="selectBox-label sr-only">{this.props.label}</span>}
                <div className="selectBox-content">
                    <DropDown
                        id={this.state.id}
                        className={classNames(
                            "selectBox-dropDown",
                            "dropDownItem-verticalPadding",
                            classesDropDown.verticalPadding,
                        )}
                        name={"label" in this.props ? this.props.label : this.state.selectedItem.name}
                        buttonContents={buttonContents}
                        buttonClassName={classNames(this.props.buttonClassName, "selectBox-toggle", classes.toggle)}
                        contentsClassName={classNames({ isParentWidth: this.props.widthOfParent })}
                        buttonBaseClass={this.props.buttonBaseClass}
                        openAsModal={this.props.openAsModal}
                        flyoutType={FlyoutType.LIST}
                        selfPadded={true}
                        renderLeft={true}
                    >
                        {selectItems}
                    </DropDown>
                </div>
            </div>
        );
    }
}
