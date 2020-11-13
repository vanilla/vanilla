/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useState, useRef } from "react";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { NBSP, DownTriangleIcon, CheckCompactIcon } from "@library/icons/common";

export interface ISelectBoxItem {
    value: string;
    name?: string;
    content?: React.ReactNode;
    className?: string;
    icon?: React.ReactNode;
    url?: string;
}

export interface ISelectBoxProps {
    className?: string;
    options: ISelectBoxItem[];
    value?: ISelectBoxItem;
    onChange?: (value: ISelectBoxItem) => void;
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    widthOfParent?: boolean;
    openAsModal?: boolean;
    renderLeft?: boolean;
    offsetPadding?: boolean;
    verticalPadding?: boolean;
    describedBy: string;
    labelWrap?: string; // conditional wrap around text to separate it from the icon.
    horizontalOffset?: boolean;
    afterButton?: React.ReactNode;
    overwriteButtonContents?: React.ReactNode;
}

/**
 * Generates Select Box component (similar to a select)
 */
export default function SelectBox(props: ISelectBoxProps) {
    const id = useUniqueID("selectBox");
    const firstValue = props.options.length > 0 ? props.options[0] : null;
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const [ownValue, setOwnValue] = useState(firstValue);
    const {
        renderLeft = true,
        verticalPadding = true,
        horizontalOffset = true,
        afterButton,
        overwriteButtonContents,
    } = props;
    const selectedOption = props.value || ownValue;
    const [isVisible, setIsVisible] = useState(false);
    const ignoreRef = useRef<boolean>(false);
    const onChange = (value: ISelectBoxItem) => {
        const funct = props.onChange || setOwnValue;
        ignoreRef.current = true;
        setIsVisible(false);
        funct(value);
        setTimeout(() => {
            ignoreRef.current = false;
        }, 2);
    };

    const classes = selectBoxClasses();
    const classesDropDown = dropDownClasses();
    return (
        <div aria-describedby={props.describedBy} className={classNames("selectBox", props.className)}>
            <DropDown
                isVisible={isVisible}
                onVisibilityChange={(val) => {
                    if (ignoreRef.current !== true) {
                        if (!ignoreRef.current) {
                            setIsVisible(val);
                        }
                    }
                }}
                key={selectedOption ? selectedOption.value : undefined}
                buttonRef={buttonRef}
                contentID={id + "-content"}
                handleID={id + "-handle"}
                className={classNames(
                    "selectBox-dropDown",
                    {
                        "dropDownItem-verticalPadding": verticalPadding,
                        [classesDropDown.verticalPadding]: verticalPadding,
                    },
                    { [classes.offsetPadding]: props.offsetPadding },
                )}
                buttonContents={
                    <>
                        <SelectBoxButton
                            activeItem={selectedOption}
                            labelWrap={props.labelWrap}
                            overwriteButtonContents={overwriteButtonContents}
                        />
                        {afterButton}
                    </>
                }
                buttonClassName={classNames(props.buttonClassName, classes.toggle)}
                contentsClassName={classNames({ isParentWidth: props.widthOfParent })}
                buttonBaseClass={props.buttonBaseClass}
                openAsModal={props.openAsModal}
                flyoutType={FlyoutType.LIST}
                renderLeft={renderLeft}
                horizontalOffset={horizontalOffset}
            >
                {props.options.map((option, i) => {
                    const isSelected = selectedOption && option.value === selectedOption.value;
                    return <SelectBoxItem key={i} item={option} isSelected={!!isSelected} onClick={onChange} />;
                })}
            </DropDown>
        </div>
    );
}

SelectBox.defaultProps = {
    selectedIndex: 0,
    buttonBaseClass: ButtonTypes.TEXT,
};

function SelectBoxButton(props: {
    activeItem: ISelectBoxItem | null;
    labelWrap?: string;
    overwriteButtonContents?: React.ReactNode;
}) {
    const { activeItem, overwriteButtonContents } = props;
    const classes = selectBoxClasses();

    return activeItem && (activeItem.name || activeItem.content) ? (
        <React.Fragment>
            <ConditionalWrap tag={"span"} condition={!!props.labelWrap} className={props.labelWrap}>
                {overwriteButtonContents ?? (activeItem.content || activeItem.name)}
            </ConditionalWrap>
            {NBSP}
            <DownTriangleIcon className={classNames("selectBox-buttonIcon", classes.buttonIcon)} />
        </React.Fragment>
    ) : null;
}

function SelectBoxItem(props: { item: ISelectBoxItem; isSelected: boolean; onClick: (item: ISelectBoxItem) => void }) {
    const { item, isSelected, onClick } = props;
    if ("url" in item) {
        return (
            <DropDownItemLink className={classNames({ isSelected: isSelected })} name={item.name} to={item.url || ""}>
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
                aria-current={isSelected}
                buttonClassName={classNames(classesDropDown.action, classes.buttonItem)}
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
            <span className={classNames("sc-only", classes.checkContainer)}>
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
