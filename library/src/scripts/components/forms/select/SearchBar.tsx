/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import AsyncCreatableSelect from "react-select/lib/AsyncCreatable";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import { InputActionMeta, ActionMeta as SelectActionMeta } from "react-select/lib/types";
import * as selectOverrides from "./overwrites";
import ButtonLoader from "@library/components/ButtonLoader";
import { OptionProps } from "react-select/lib/components/Option";
import Translate from "@library/components/translation/Translate";
import { ClearButton } from "@library/components/forms/select/ClearButton";
import ConditionalWrap from "@library/components/ConditionalWrap";
import { search } from "@library/components/icons/header";
import { MenuProps } from "react-select/lib/components/Menu";
import ReactDOM from "react-dom";
import { LinkContext } from "@library/components/navigation/LinkContextProvider";
import { RouteComponentProps } from "react-router";
import { buttonVariables } from "@library/styles/buttonStyles";
import { searchBarClasses } from "@library/styles/searchBarStyles";

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
}

interface IState {
    forceMenuClosed: boolean;
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
    };
    private id: string;
    private prefix = "searchBar";
    private searchButtonID: string;
    private searchInputID: string;
    private inputRef: React.RefObject<AsyncCreatableSelect<any>> = React.createRef();

    constructor(props: IProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        this.searchButtonID = this.id + "-searchButton";
        this.searchInputID = this.id + "-searchInput";
    }

    public render() {
        const { className, disabled, isLoading } = this.props;
        return (
            <AsyncCreatableSelect
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
                loadOptions={this.props.loadOptions}
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
    private handleOptionChange = (option: IComboBoxOption, actionMeta: SelectActionMeta) => {
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
        const buttonVars = buttonVariables();
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
                    <div onClick={this.focus} className={classNames("searchBar-content", classes.content)}>
                        <div
                            className={classNames(
                                `${this.prefix}-valueContainer`,
                                "suggestedTextInput-inputText",
                                "inputText",
                                "isClearable",
                                classes.valueContainer,
                                {
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
                                className={classNames("searchBar-submitButton", this.props.buttonClassName, {
                                    isLarge: this.props.isBigInput,
                                })}
                                tabIndex={!!this.props.hideSearchButton ? -1 : 0}
                            >
                                {this.props.isLoading ? (
                                    <ButtonLoader
                                        buttonType={buttonVars.primary}
                                        className={this.props.buttonLoaderClassName}
                                    />
                                ) : (
                                    this.props.buttonText
                                )}
                            </Button>
                        </ConditionalWrap>
                        <div
                            onClick={this.focus}
                            className={classNames("searchBar-iconContainer", classes.iconContainer)}
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
        return (
            <React.Fragment>
                {ReactDOM.createPortal(
                    <components.Menu {...props} className="suggestedTextInput-menu dropDown-contents isParentWidth" />,
                    this.props.resultsRef!.current!,
                )}
            </React.Fragment>
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
}
