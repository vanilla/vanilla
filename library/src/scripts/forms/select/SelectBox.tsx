/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
//import { has, map } from "lodash";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { getRequiredID } from "@library/utility/idUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { metasClasses } from "@library/styles/metasStyles";
import { LocaleDisplayer, ILocale } from "@vanilla/i18n";
import classNames from "classnames";
import { CheckCompactIcon, DownTriangleIcon } from "@library/icons/common";

export interface ISelectBoxItem {
    [x: string]: string | undefined;
    name: string;
    className?: string;
    lang?: string;
    url?: string;
}

interface IProps {
    className?: string;
    id?: string;
    children: ISelectBoxItem[];
    renderAbove?: boolean; // Adjusts the flyout position vertically
    renderLeft?: boolean; // Adjusts the flyout position horizontally
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    widthOfParent?: boolean;
    openAsModal?: boolean;
    localeInfo?: ILocale[];
    languageSelect?: boolean;
    currentLocale?: string;
}

export interface ISelfLabelledProps extends IProps {
    label: string;
    onClick?: () => {};
    selected?: boolean;
    outdated?: boolean;
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
            selectedItem:
                props.selectedItem || props.languageSelect
                    ? props.children[props.selectedIndex]
                    : this.getLanguageName(props.currentLocale),
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

    public getLanguageName = (val: string) => {
        if (this.props.localeInfo !== undefined) {
            let name = this.props.localeInfo.map(l => {
                if (l.localeKey === val) return l.displayNames[`${val}`];
            });
            return name[0];
        } else {
            return "";
        }
    };

    public render() {
        const checkURL = "url" in this.props.children[0];
        const { localeInfo, currentLocale } = this.props;
        const classes = selectBoxClasses();
        const classesDropDown = dropDownClasses();
        const classesMetas = metasClasses();
        const selectItems = this.props.children.map((child, i) => {
            const selected = this.state.selectedIndex === i;

            if (!checkURL) {
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
                        buttonClassName={classNames(
                            "dropDownItem-button",
                            "selectBox-buttonItem",
                            classesDropDown.action,
                            classes.buttonItem,
                            {
                                isInModal: this.props.openAsModal,
                            },
                        )}
                    >
                        <span className={classNames("selectBox-checkContainer", "sc-only", classes.checkContainer)}>
                            {selected && <CheckCompactIcon className={"selectBox-selectedIcon"} />}
                            {!selected && (
                                <span className={classNames("selectBox-spacer", classes.spacer)} aria-hidden={true}>
                                    {` `}
                                </span>
                            )}
                        </span>
                        <span className={classNames("selectBox-itemLabel", classes.itemLabel)}>{child.name}</span>
                        {child.outdated && (
                            <span
                                className={classNames("selectBox-outdated", classesMetas.metaStyle, classes.outdated)}
                            >
                                {t("(Outdated)")}
                            </span>
                        )}
                    </DropDownItemButton>
                );
            } else {
                return (
                    <DropDownItemLink
                        key={i}
                        name={localeInfo.map(l => {
                            if (l.localeKey === child.locale) return l.displayNames[`${child.locale}`];
                        })}
                        to={child.url}
                        className={classNames({ isSelected: child.selected })}
                        lang={child.locale}
                        onClick={this.handleClick.bind(this, child, i)}
                    />
                );
            }
        });

        const buttonContents =
            this.state.selectedItem && this.state.selectedItem.name ? (
                <React.Fragment>
                    {!this.props.languageSelect
                        ? this.state.selectedItem.name
                        : currentLocale !== undefined
                        ? this.getLanguageName(currentLocale)
                        : ""}
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
                    {!checkURL ? (
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
                            renderAbove={this.props.renderAbove}
                            renderLeft={this.props.renderLeft}
                            openAsModal={this.props.openAsModal}
                            flyoutType={FlyoutType.LIST}
                            selfPadded={true}
                        >
                            {selectItems}
                        </DropDown>
                    ) : (
                        <DropDown
                            buttonContents={buttonContents}
                            buttonClassName={classNames(this.props.buttonClassName, "selectBox-toggle", classes.toggle)}
                            contentsClassName={classNames({ isParentWidth: this.props.widthOfParent })}
                            buttonBaseClass={this.props.buttonBaseClass}
                            flyoutType={FlyoutType.LIST}
                        >
                            {selectItems}
                        </DropDown>
                    )}
                </div>
            </div>
        );
    }
}
