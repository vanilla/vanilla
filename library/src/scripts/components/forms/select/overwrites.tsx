/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";
import { clear, close } from "@library/components/Icons";
import { components } from "react-select";
import ButtonLoader from "@library/components/ButtonLoader";
import { OptionProps } from "react-select/lib/components/Option";
import BaseMenu, {
    MenuProps,
    MenuListComponentProps,
    MenuList as BaseMenuList,
} from "react-select/lib/components/Menu";
import {
    ContainerProps,
    SelectContainer as BaseSelectContainer,
    ValueContainerProps,
} from "react-select/lib/components/containers";
import DateTime from "@library/components/DateTime";
import BreadCrumbString from "@library/components/BreadCrumbString";
import { IndicatorProps } from "react-select/lib/components/indicators";
import { ControlProps } from "react-select/lib/components/Control";
import { MultiValueRemoveProps } from "react-select/lib/components/MultiValue";
import { ISearchResult } from "@knowledge/@types/api";

/**
 * Overwrite for the ClearIndicator component in React Select
 */
export function ClearIndicator(props: IndicatorProps<any>) {
    const { children, innerProps, ...rest } = props;

    // We need to bind the function to the props for that component
    const handleKeyDown = event => {
        switch (event.key) {
            case "Enter":
            case "Spacebar":
            case " ":
                innerProps.onMouseDown(event);
                break;
        }
    };

    return (
        <button
            {...innerProps}
            className={classNames(ButtonBaseClass.ICON, rest.className, "suggestedTextInput-clear")}
            type="button"
            style={{}}
            aria-hidden={null} // Unset the prop in innerProps
            onKeyDown={handleKeyDown}
            onClick={innerProps.onMouseDown}
            onTouchEnd={innerProps.onTouchEnd}
            title={t("Clear")}
            aria-label={t("Clear")}
        >
            {clear()}
        </button>
    );
}

/**
 * Overwrite for the controlContainer component in React Select
 * @param props
 */
export function Control(props: ControlProps<any>) {
    const { className, ...rest } = props;
    return <components.Control className={classNames("suggestedTextInput-control", className)} {...rest} />;
}

/**
 * Overwrite for the menuOption component in React Select
 * @param props
 */
export function OptionLoader(props: OptionProps<any>) {
    props = {
        ...props,
        children: <ButtonLoader />,
    };

    return <SelectOption {...props} />;
}

/**
 * Overwrite for the menu component in React Select
 * @param props - menu props
 */
export function Menu(props: MenuProps<any>) {
    return <components.Menu {...props} className="suggestedTextInput-menu dropDown-contents" />;
}

/**
 * Overwrite for the input menuList component in React Select
 * @param props - props for menuList
 */
export function MenuList(props: MenuListComponentProps<any>) {
    let { className, ...rest } = props;
    className = classNames(props.className, "suggestedTextInput-token");
    return (
        <components.MenuList {...rest}>
            <ul className="suggestedTextInput-menuItems">{props.children}</ul>
        </components.MenuList>
    );
}

/**
 * Overwrite for the multiValueRemove component in React Select
 * @param props - props of component
 */
export function MultiValueRemove(props: MultiValueRemoveProps<any>) {
    const { innerProps, selectProps } = props;

    // We need to bind the function to the props for that component
    const handleKeyDown = event => {
        switch (event.key) {
            case "Enter":
            case "Spacebar":
            case " ":
                innerProps.onClick(event);
                break;
        }
    };

    return (
        <components.MultiValueRemove {...props} className="suggestedTextInput-tokenRemove">
            <button
                {...innerProps}
                className={classNames(ButtonBaseClass.CUSTOM, `${selectProps.classNamePrefix}-clear`)}
                type="button"
                style={{}}
                aria-hidden={undefined} // Unset the prop in restInnerProps
                onKeyDown={handleKeyDown}
                onClick={innerProps.onClick}
                onTouchEnd={innerProps.onTouchEnd}
                onMouseDown={innerProps.onMouseDown}
                title={t("Clear")}
                aria-label={t("Clear")}
            >
                {close("suggestedTextInput-tokenRemoveIcon", true)}
            </button>
        </components.MultiValueRemove>
    );
}

/**
 * Overwrite for the noOptionsMessage component in React Select
 * @param props
 */
export function NoOptionsMessage(props: OptionProps<any>) {
    props = {
        ...props,
        className: classNames("suggestedTextInput-noOptions", props.className),
    };
    return <components.NoOptionsMessage {...props} />;
}

/**
 * Overwrite for the menuOption component in React Select
 * @param props
 */
export function SearchResultOption(props: OptionProps<ISearchResult>) {
    const { dateUpdated, locationData } = props.getValue();
    const hasLocationData = locationData && locationData.length > 0;
    console.log(props.getValue());

    const handleClick = e => {
        e.preventDefault();
        props.innerProps.onClick();
    };

    return (
        <li className={classNames("suggestedTextInput-item")}>
            <button
                type="button"
                // title={props.get}
                aria-label={props.children}
                className="suggestedTextInput-option"
                onClick={handleClick}
            >
                <span className="suggestedTextInput-head">
                    <span className="suggestedTextInput-title">{props.children}</span>
                </span>
                {dateUpdated &&
                    hasLocationData && (
                        <span className="suggestedTextInput-main">
                            <span className="metas isFlexed">
                                {dateUpdated && (
                                    <span className="meta">
                                        <DateTime className="meta" timestamp={dateUpdated} />
                                    </span>
                                )}
                                {hasLocationData && (
                                    <BreadCrumbString className="meta">{locationData}</BreadCrumbString>
                                )}
                            </span>
                        </span>
                    )}
            </button>
        </li>
    );
}

export function NullComponent() {
    return null;
}

/**
 * Overwrite for the menuOption component in React Select
 * @param props
 */
export function SelectOption(props: OptionProps<any>) {
    const { isSelected, isFocused } = props;

    return (
        <li className="suggestedTextInput-item">
            <button
                {...props.innerProps}
                type="button"
                className={classNames("suggestedTextInput-option", {
                    isSelected,
                    isFocused,
                })}
            >
                <span className="suggestedTextInput-head">
                    <span className="suggestedTextInput-title">{props.children}</span>
                </span>
            </button>
        </li>
    );
}

/**
 * Overwrite for the valueContainer component in React Select
 * @param children
 * @param props
 */
export function ValueContainer(props: ValueContainerProps<any>) {
    return (
        <components.ValueContainer
            {...props}
            className="suggestedTextInput-valueContainer inputBlock-inputText inputText"
        />
    );
}
