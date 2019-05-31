/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Heading from "@library/layout/Heading";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { ClearButton } from "@library/forms/select/ClearButton";
import { LinkContext } from "@library/routing/links/LinkContextProvider";
import { MenuProps } from "react-select/lib/components/Menu";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { search } from "@library/icons/header";
import { t } from "@library/utility/appUtils";
import { ButtonTypes, buttonVariables } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { InputActionMeta, ActionMeta } from "react-select/lib/types";
import { RouteComponentProps } from "react-router";
import Translate from "@library/content/Translate";
import classNames from "classnames";
import { components } from "react-select";
import ReactDOM from "react-dom";
import * as selectOverrides from "@library/forms/select/overwrites";
import { OptionProps } from "react-select/lib/components/Option";
import AsyncCreatable from "react-select/lib/AsyncCreatable";
import { visibility } from "@library/styles/styleHelpers";

export interface IComboBoxOption<T = any> {
    value: string | number;
    label: string;
    data?: T;
}

interface IProps extends IOptionalComponentID, RouteComponentProps<any> {
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options?: any[];
    loadOptions?: (inputValue: string) => Promise<any>;
    value: string;
    onChange: (value: string) => void;
    isBigInput?: boolean;
    noHeading: boolean;
    title: string;
    titleAsComponent?: React.ReactNode;
    isLoading?: boolean;
    onSearch: () => void;
    optionComponent?: React.ComponentType<OptionProps<any>>;
    getRef?: any;
    buttonClassName?: string;
    buttonLoaderClassName?: string;
    hideSearchButton?: boolean;
    triggerSearchOnClear?: boolean;
    resultsRef?: React.RefObject<HTMLDivElement>;
    handleOnKeyDown?: (event: React.KeyboardEvent) => void;
    onOpenSuggestions?: () => void;
    onCloseSuggestions?: () => void;
    buttonText?: string;
    disableAutocomplete?: boolean;
    clearButtonClass?: string;
    contentClass?: string;
    buttonBaseClass?: ButtonTypes;
    valueContainerClasses?: string;
}

interface IState {
    forceMenuClosed: boolean;
    focus: boolean;
}

/**
 * Implements the search bar component
 */
export default class SearchBar extends React.Component<IProps, IState> {
    public static contextType = LinkContext;
    public context!: React.ContextType<typeof LinkContext>;

    public static defaultProps: Partial<IProps> = {
        disabled: false,
        isBigInput: false,
        noHeading: false,
        title: t("Search"),
        isLoading: false,
        optionComponent: selectOverrides.SelectOption,
        triggerSearchOnClear: false,
        buttonText: t("Search"),
        disableAutocomplete: false,
        placeholder: "",
    };

    public state: IState = {
        forceMenuClosed: false,
        focus: false,
    };
    private id: string;
    private prefix = "searchBar";
    private searchButtonID: string;
    private searchInputID: string;
    private inputRef: React.RefObject<AsyncCreatable<any>> = React.createRef();

    constructor(props: IProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        this.searchButtonID = this.id + "-searchButton";
        this.searchInputID = this.id + "-searchInput";
    }

    public render() {
        const { className, disabled, isLoading } = this.props;
        return (
            <AsyncCreatable
                id={this.id}
                value={undefined}
                onChange={this.handleOptionChange}
                closeMenuOnSelect={false}
                inputId={this.searchInputID}
                inputValue={this.props.value}
                onInputChange={this.handleInputChange}
                components={this.componentOverwrites}
                isClearable={false}
                blurInputOnSelect={false}
                allowCreateWhileLoading={true}
                controlShouldRenderValue={false}
                isDisabled={disabled}
                loadOptions={this.props.loadOptions!}
                menuIsOpen={this.isMenuVisible}
                classNamePrefix={this.prefix}
                className={classNames(this.prefix, className)}
                placeholder={this.props.placeholder}
                aria-label={t("Search")}
                escapeClearsValue={true}
                pageSize={20}
                theme={this.getTheme}
                styles={this.customStyles}
                backspaceRemovesValue={true}
                createOptionPosition="first"
                formatCreateLabel={this.createFormatLabel}
                ref={this.inputRef}
                onKeyDown={this.props.handleOnKeyDown}
                onMenuOpen={this.props.onOpenSuggestions}
                onMenuClose={this.props.onCloseSuggestions}
                onFocus={this.onFocus}
                onBlur={this.onBlur}
            />
        );
    }

    /**
     * Determine if we should show the menu or not.
     *
     * - Menu can be forced closed through state.
     * - Having no value in the input keeps the search closed.
     * - Otherwise falls back to what is determined by react-select.
     */
    private get isMenuVisible(): boolean | undefined {
        return this.state.forceMenuClosed || this.props.value.length === 0 || this.props.disableAutocomplete
            ? false
            : undefined;
    }

    /**
     * Handle changes in option.
     *
     * - Update the input value.
     * - Force the menu closed.
     * - Trigger a search.
     */
    private handleOptionChange = (option: IComboBoxOption, actionMeta: ActionMeta) => {
        if (option) {
            if (this.props.disableAutocomplete) {
                this.props.onChange(option.label);
                this.props.onSearch();
            } else {
                const data = option.data || {};
                const { url } = data;

                if (actionMeta.action === "select-option" && url) {
                    this.context.pushSmartLocation(url);
                } else {
                    this.props.onChange(option.label);
                    if (this.props.disableAutocomplete) {
                        this.props.onSearch();
                    } else {
                        this.setState({ forceMenuClosed: true }, this.props.onSearch);
                    }
                }
            }
        }
    };

    /**
     * Create a label for React Select's "Add option" option.
     */
    private createFormatLabel = (inputValue: string) => {
        return <Translate source="Search for <0/>" c0={<strong>{inputValue}</strong>} />;
    };

    /**
     * Handle changes in the select's text input.
     *
     * Ignores change caused by blurring or closing the menu. These normally clear the input.
     */
    private handleInputChange = (value: string, reason: InputActionMeta) => {
        if (!["input-blur", "menu-close"].includes(reason.action)) {
            this.props.onChange(value);
            this.setState({ forceMenuClosed: false });
        }
    };

    /**
     * Unset some of the inline styles of react select.
     */
    private customStyles = {
        option: (provided: React.CSSProperties) => ({
            ...provided,
        }),
        menu: (provided: React.CSSProperties, state) => {
            return { ...provided, backgroundColor: undefined, boxShadow: undefined };
        },
        menuList: (provided: React.CSSProperties, state) => {
            return { ...provided, maxHeight: undefined };
        },
        control: (provided: React.CSSProperties) => ({
            ...provided,
            borderWidth: 0,
        }),
    };

    /**
     * Unset many of react-selects theme values.
     */
    private getTheme = theme => {
        return {
            ...theme,
            borderRadius: {},
            colors: {},
            spacing: {},
        };
    };

    /**
     * Overwrite for the Control component in react select
     * @param props
     */
    private SearchControl = props => {
        const classes = searchBarClasses();
        return (
            <div className={classNames("searchBar", classes.root)}>
                <form className={classNames("searchBar-form", classes.form)} onSubmit={this.onFormSubmit}>
                    {!this.props.noHeading && (
                        <Heading
                            depth={1}
                            className={classNames("searchBar-heading", "pageSmallTitle", classes.heading)}
                            title={this.props.title}
                        >
                            <label
                                className={classNames("searchBar-label", classes.label)}
                                htmlFor={this.searchInputID}
                            >
                                {this.props.titleAsComponent ? this.props.titleAsComponent : this.props.title}
                            </label>
                        </Heading>
                    )}
                    <div
                        onClick={this.focus}
                        className={classNames("searchBar-content", classes.content, this.props.contentClass, {
                            hasFocus: this.state.focus,
                        })}
                    >
                        <div
                            className={classNames(
                                `${this.prefix}-valueContainer`,
                                "suggestedTextInput-inputText",
                                "inputText",
                                "isClearable",
                                classes.valueContainer,
                                this.props.valueContainerClasses,
                                {
                                    [classes.compoundValueContainer]: !this.props.hideSearchButton,
                                    isLarge: this.props.isBigInput,
                                },
                            )}
                        >
                            <components.Control {...props} />
                            {this.props.value && (
                                <ClearButton
                                    onClick={this.clear}
                                    className={classNames(classes.clear, this.props.clearButtonClass)}
                                />
                            )}
                        </div>
                        <ConditionalWrap condition={!!this.props.hideSearchButton} className="sr-only">
                            <Button
                                type="submit"
                                id={this.searchButtonID}
                                baseClass={this.props.buttonBaseClass}
                                className={classNames(
                                    "searchBar-submitButton",
                                    classes.actionButton,
                                    this.props.buttonClassName,
                                    {
                                        isLarge: this.props.isBigInput,
                                    },
                                )}
                                tabIndex={!!this.props.hideSearchButton ? -1 : 0}
                            >
                                {this.props.isLoading ? (
                                    <ButtonLoader
                                        className={this.props.buttonLoaderClassName}
                                        buttonType={this.props.buttonBaseClass}
                                    />
                                ) : (
                                    this.props.buttonText
                                )}
                            </Button>
                        </ConditionalWrap>
                        <div
                            onClick={this.focus}
                            className={classNames("searchBar-iconContainer", classes.iconContainer, {
                                [classes.iconContainerBigInput]: this.props.isBigInput,
                            })}
                        >
                            {search(classNames("searchBar-icon", classes.icon))}
                        </div>
                    </div>
                </form>
            </div>
        );
    };

    private clear = (event: React.SyntheticEvent) => {
        event.preventDefault();
        this.props.onChange("");
        if (this.props.triggerSearchOnClear) {
            this.props.onSearch();
        }
        this.inputRef.current!.focus();
    };

    /**
     * Handle the form submission.
     */
    private onFormSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        this.props.onSearch();
    };

    /**
     * Pass menu function with ref to results container
     */

    private Menu = (props: MenuProps<any>) => {
        const classes = searchBarClasses();
        return ReactDOM.createPortal(
            <components.Menu
                {...props}
                className={classNames("suggestedTextInput-menu", "dropDown-contents", "isParentWidth", classes.menu)}
            />,
            this.props.resultsRef!.current!,
        );
    };

    /*
     * Overwrite components in Select component
     */
    private componentOverwrites = {
        Control: this.SearchControl,
        IndicatorSeparator: selectOverrides.NullComponent,
        Menu: !!this.props.resultsRef ? this.Menu : selectOverrides.Menu,
        MenuList: selectOverrides.MenuList,
        Option: this.props.optionComponent!,
        NoOptionsMessage: selectOverrides.NoOptionsMessage,
        ClearIndicator: selectOverrides.NullComponent,
        DropdownIndicator: selectOverrides.NullComponent,
        LoadingMessage: selectOverrides.OptionLoader,
    };

    public focus = () => {
        this.inputRef.current!.focus();
    };

    /**
     * Set class for focus
     */
    private onFocus = () => {
        this.setState({
            focus: true,
        });
    };

    /**
     * Set class for blur
     */
    private onBlur = () => {
        this.setState({
            focus: false,
        });
    };
}
