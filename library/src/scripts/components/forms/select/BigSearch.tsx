/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select, { components } from "react-select";
import { getOptionalID, uniqueIDFromPrefix } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { close, downTriangle } from "@library/components/Icons";
import Heading from "@library/components/Heading";

export interface IComboBoxOption {
    value: string;
    label: string;
    data: any;
}

interface IProps {
    query: string;
    disabled?: boolean;
    className?: string;
    placeholder: string;
    options?: any[];
    loadOptions?: any[];
    setQuery: (value) => void;
}

interface IState {
    value: IComboBoxOption;
}

/**
 * Implements the search bar component
 */
export default class BigSearch extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
    };

    private id;
    private prefix = "bigSearch";
    private searchButtonID;
    private searchInputID;
    private ref = React.createRef();

    constructor(props) {
        super(props);
        this.id = getOptionalID(props, this.prefix);
        this.searchButtonID = this.id + "-searchButton";
        this.searchInputID = this.id + "-searchInput";
    }

    private handleOnChange = chosenValue => {
        this.props.setQuery(chosenValue);
    };

    public render() {
        const { className, disabled, options, loadOptions } = this.props;

        /** The children to be rendered inside the indicator. */
        const componentOverwrites = {
            Control: this.SearchLabel,
            IndicatorSeparator: this.DoNotRender,
            DropdownIndicator: this.DoNotRender,
            ClearIndicator: this.ClearIndicator,
            SelectContainer: this.SelectContainer,
            ValueContainer: this.ValueContainer,
        };

        const getStyles = (key, props) => {
            return {
                borderRadius: {},
                colors: {},
                spacing: {},
            };
        };

        const getTheme = theme => {
            return {
                ...theme,
                borderRadius: {},
                colors: {},
                spacing: {},
            };
        };

        return (
            <Select
                id={this.id}
                components={componentOverwrites}
                isClearable={true}
                isDisabled={disabled}
                loadOptions={loadOptions}
                options={options}
                classNamePrefix={this.prefix}
                className={classNames(this.prefix, className)}
                styles={{}}
                placeholder={this.props.placeholder}
                value={this.props.query}
                onChange={this.handleOnChange}
                aria-label={t("Search")}
                escapeClearsValue={true}
                inputId={this.searchInputID}
                pageSize={20}
                NoOptionsMessage={t("No Results Found")}
                theme={getTheme}
            />
        );
    }

    public getValue = value => {
        return value;
    };

    public ClearIndicator(props) {
        const {
            innerProps: { ref, ...restInnerProps },
            isDisabled,
        } = props;

        // We need to bind the function to the props for that component
        const handleKeyDown = event => {
            switch (event.key) {
                case "Enter":
                case "Spacebar":
                case " ":
                    restInnerProps.onMouseDown(event);
                    break;
            }
        };

        window.console.log("restInnerProps:", restInnerProps);

        return (
            <button
                {...restInnerProps}
                className={classNames(ButtonBaseClass.ICON, "bigSearch-clear")}
                type="button"
                ref={ref}
                style={{}}
                aria-hidden={null} // Unset the prop in restInnerProps
                onKeyDown={handleKeyDown}
                onClick={restInnerProps.onMouseDown}
                onTouchEnd={restInnerProps.onTouchEnd}
                disabled={isDisabled}
                title={t("Clear")}
                aria-label={t("Clear")}
            >
                {close("isSmall")}
            </button>
        );
    }

    public SearchLabel = props => {
        const id = uniqueIDFromPrefix("searchInputBlock");
        const labelID = id + "-label";

        const preventFormSubmission = e => {
            e.preventDefault();
            this.props.setQuery(props.getValue());
        };

        return (
            <form className="bigSearch-form" onSubmit={preventFormSubmission}>
                <Heading depth={1} className="inputBlock-labelAndDescription searchInputBlock-labelAndDescription">
                    <label className="searchInputBlock-label" htmlFor={this.searchInputID}>
                        {t("Search")}
                    </label>
                </Heading>
                <div className="bigSearch-content">
                    <div className="bigSearch-inputWrap">
                        <components.Control {...props} />
                    </div>
                    <Button type="submit" id={this.searchButtonID} className={"buttonPrimary"}>
                        {t("Search")}
                    </Button>
                </div>
            </form>
        );
    };

    public DoNotRender = props => {
        return null;
    };

    // public ValueContainer = props => {
    //     return <components.IndicatorsContainer {...props} styles={{}} className="bigSearch-indicatorsContainer" />;
    // };

    // public ControlComponent = props => {
    //     return (
    //         <div style={{}} className="bigSearch-here">
    //             <components.Control {...props} />
    //         </div>
    //     );
    // };

    public SelectContainer = ({ children, ...props }) => {
        return (
            <components.SelectContainer {...props} styles={{}} className="bigInput-selectContainer">
                {children}
            </components.SelectContainer>
        );
    };

    public ValueContainer = ({ children, ...props }) => (
        <components.ValueContainer
            styles={{}}
            className="bigInput-valueContainer inputBlock-inputText InputBox inputText"
        >
            {children}
        </components.ValueContainer>
    );

    public Input = props => {
        if (props.isHidden) {
            return <components.Input {...props} />;
        }
        return <components.Input styles={{}} className="bigInput-realInput" />;
    };

    //MenuList

    //NoOptionsMessage

    //option
}

// Wrap in form if not already
// Role search on input
