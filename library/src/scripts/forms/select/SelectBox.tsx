/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { CheckCompactIcon, DownTriangleIcon } from "@library/icons/common";
import { useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useState, useRef } from "react";

export interface ISelectBoxItem {
    value: string;
    name: string;
    content?: React.ReactNode;
    className?: string;
    icon?: React.ReactNode;
    url?: string;
}

interface IProps {
    className?: string;
    options: ISelectBoxItem[];
    value?: ISelectBoxItem;
    onChange?: (value: ISelectBoxItem) => void;
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    widthOfParent?: boolean;
    openAsModal?: boolean;
    renderLeft?: boolean;
}

export interface ISelfLabelledProps extends IProps {
    label: string;
}

export interface IExternalLabelledProps extends IProps {
    describedBy: string;
}

/**
 * Generates Select Box component (similar to a select)
 */
export default function SelectBox(props: ISelfLabelledProps | IExternalLabelledProps) {

    const id = useUniqueID("selectBox");
    const firstValue = props.options.length > 0 ? props.options[0] : null;
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const [ownValue, setOwnValue] = useState(firstValue);
    const selectedOption = props.value || ownValue;
    const onChange = (value: ISelectBoxItem) => {
        const funct = props.onChange || setOwnValue;
        funct(value);
        setImmediate(() => {
            buttonRef.current && buttonRef.current.focus();
        });
    }

    const classes = selectBoxClasses();
    const classesDropDown = dropDownClasses();
    return (
        <div
            aria-describedby={"describedBy" in props ? props.describedBy : undefined}
            className={classNames("selectBox", props.className)}
        >
            {"label" in props && <span className="selectBox-label sr-only">{props.label}</span>}
            <div className="selectBox-content">
                <DropDown
                    key={selectedOption?.value}
                    buttonRef={buttonRef}
                    id={id}
                    className={classNames(
                        "selectBox-dropDown",
                        "dropDownItem-verticalPadding",
                        classesDropDown.verticalPadding,
                    )}
                    buttonContents={<SelectBoxButton activeItem={selectedOption} />}
                    buttonClassName={classNames(props.buttonClassName, "selectBox-toggle", classes.toggle)}
                    contentsClassName={classNames({ isParentWidth: props.widthOfParent })}
                    buttonBaseClass={props.buttonBaseClass}
                    openAsModal={props.openAsModal}
                    flyoutType={FlyoutType.LIST}
                    selfPadded={true}
                    renderLeft={true}
                >
                    {props.options.map((option, i) => {
                        const isSelected = selectedOption && option.value === selectedOption.value;
                        return <SelectBoxItem key={i} item={option} isSelected={!!isSelected} onClick={onChange}/>
                    })}
                </DropDown>
            </div>
        </div>
    );
}

SelectBox.defaultProps = {
    selectedIndex: 0,
    buttonBaseClass: ButtonTypes.TEXT,
};

function SelectBoxButton(props: { activeItem: ISelectBoxItem | null }) {
    const { activeItem } = props;
    const classes = selectBoxClasses();

    return (activeItem && activeItem.name ? (
        <React.Fragment>
            {activeItem.content || activeItem.name}
            <DownTriangleIcon className={classNames("selectBox-buttonIcon", classes.buttonIcon)} />
        </React.Fragment>
    ) : null);
}


function SelectBoxItem(props: { item: ISelectBoxItem; isSelected: boolean, onClick: (item: ISelectBoxItem) => void }) {
    const { item, isSelected, onClick } = props;
    if ("url" in item) {
        return (
            <DropDownItemLink
                className={classNames({ isSelected: isSelected })}
                name={item.name}
                to={item.url || ""}
            >
                <SelectBoxContents item={item} isSelected={isSelected} />
            </DropDownItemLink>
        );
    } else {
        const classes = selectBoxClasses();
        const classesDropDown = dropDownClasses();
        return (
            <DropDownItemButton
                className={classNames({ isSelected: isSelected })}
                onClick={() => onClick(item)}
                disabled={isSelected}
                buttonClassName={classNames(
                    classesDropDown.action,
                    classes.buttonItem,
                )}
            >
                <SelectBoxContents item={item} isSelected={isSelected} />
            </DropDownItemButton>
        );
    }
}

function SelectBoxContents(props: { item: ISelectBoxItem; isSelected: boolean }) {
    const { item, isSelected } = props;
    const classes = selectBoxClasses();

    return (
        <>
            <span className={classNames("selectBox-itemLabel", classes.itemLabel)}>{item.content || item.name}</span>
            <span className={classNames("selectBox-checkContainer", "sc-only", classes.checkContainer)}>
                {isSelected && <CheckCompactIcon className={"selectBox-isSelectedIcon"} />}
                {!isSelected && (
                    <span className={classNames("selectBox-spacer", classes.spacer)} aria-hidden={true}>
                        {` `}
                    </span>
                )}
                {item.icon}
            </span>
        </>
    );
}
