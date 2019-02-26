/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

import { getRequiredID } from "../componentIDs";
import { t } from "../application";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import { checkCompact, downTriangle } from "@library/components/icons/common";
import DropDown from "@library/components/dropdown/DropDown";
import { ButtonBaseClass } from "@library/components/forms/Button";
import Frame from "@library/components/frame/Frame";
import FrameBody from "@library/components/frame/FrameBody";
import { selectBoxClasses } from "@library/styles/selectBoxStyles";

export interface ISelectBoxItem {
    name: string;
    className?: string;
    onClick?: () => {};
    selected?: boolean;
    outdated?: boolean;
    lang?: string;
}

interface IProps {
    className?: string;
    id?: string;
    children: ISelectBoxItem[];
    renderAbove?: boolean; // Adjusts the flyout position vertically
    renderLeft?: boolean; // Adjusts the flyout position horizontally
    buttonClassName?: string;
    buttonBaseClass?: ButtonBaseClass;
    widthOfParent?: boolean;
    openAsModal?: boolean;
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
        buttonBaseClass: ButtonBaseClass.TEXT,
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
        const classes = selectBoxClasses();
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
                    lang={child.lang}
                    buttonClassName={classNames("dropDownItem-button", "selectBox-buttonItem", classes.buttonItem, {
                        isInModal: this.props.openAsModal,
                    })}
                >
                    <span className={classNames("selectBox-checkContainer", "sc-only", classes.checkContainer)}>
                        {selected && checkCompact("selectBox-selectedIcon")}
                        {!selected && (
                            <span className={classNames("selectBox-spacer", classes.spacer)} aria-hidden={true}>
                                {` `}
                            </span>
                        )}
                    </span>
                    <span className={classNames("selectBox-itemLabel", classes.itemLabel)}>{child.name}</span>
                    {child.outdated && (
                        <span className={classNames("selectBox-outdated", "metaStyle", classes.outdated)}>
                            {t("(Outdated)")}
                        </span>
                    )}
                </DropDownItemButton>
            );
        });
        const buttonContents =
            this.state.selectedItem && this.state.selectedItem.name ? (
                <React.Fragment>
                    {this.state.selectedItem.name}
                    {downTriangle("selectBox-buttonIcon", classes.buttonIcon)}
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
                        className="selectBox-dropDown"
                        name={"label" in this.props ? this.props.label : this.state.selectedItem.name}
                        buttonContents={buttonContents}
                        buttonClassName={classNames(this.props.buttonClassName, "selectBox-toggle", classes.toggle)}
                        contentsClassName={classNames({ isParentWidth: this.props.widthOfParent })}
                        buttonBaseClass={this.props.buttonBaseClass}
                        renderAbove={this.props.renderAbove}
                        renderLeft={this.props.renderLeft}
                        openAsModal={this.props.openAsModal}
                    >
                        <Frame>
                            <FrameBody className="dropDownItem-verticalPadding">{selectItems}</FrameBody>
                        </Frame>
                    </DropDown>
                </div>
            </div>
        );
    }
}
