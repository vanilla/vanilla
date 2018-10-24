/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select from "react-select";
import { getOptionalID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import { close } from "@library/components/Icons";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";

// import ClearIndicator from "@library/components/forms/select/overwrites/ClearIndicator";

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
    private ref = React.createRef();

    constructor(props) {
        super(props);
        this.id = getOptionalID(props, this.prefix);
    }

    // private Placeholder = props => <div className={props.className}>{t("Placeholder")}</div>;

    // public ClearIndicator = props => {
    //     return (
    //         <div {...props.restInnerProps} ref={props.ref} className="bigSearch-close">
    //             {/*<CloseButton disabled={props.isDisabled} onClick={props.onMouseDown} />*/}
    //             <span className="bigSearch-clear">
    //                 <span className={classNames(ButtonBaseClass.ICON)} role="button" onClick={this.preventDefault}>
    //                     {close("isSmall")}
    //                 </span>
    //                 <span className="sr-only">{t("Clear")}</span>
    //             </span>
    //         </div>
    //     );
    // };

    // We need to manually trigger the clear function
    private ClearIndicator = props => {
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

        return (
            <button
                {...restInnerProps}
                className={classNames(ButtonBaseClass.ICON, "bigSearch-clear")}
                type="button"
                ref={ref}
                style={{}}
                aria-hidden={null} // Unset the prop in restInnerProps
                onKeyDown={handleKeyDown}
                disabled={isDisabled}
                title={t("Clear")}
                aria-label={t("Clear")}
            >
                {close("isSmall")}
            </button>
        );
    };

    private handleOnChange = chosenValue => {
        this.props.setQuery(chosenValue);
    };

    public render() {
        const { className, disabled, options, loadOptions } = this.props;

        /** The children to be rendered inside the indicator. */
        const components = {
            ClearIndicator: this.ClearIndicator,
        };

        return (
            <Select
                id={this.id}
                components={components}
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
            />
        );
    }

    public getValue = value => {
        return value;
    };
}

/**
 const componentsList = {
ClearIndicator: <this.Placeholder />,
Control: <this.Placeholder />,
DropdownIndicator: <this.Placeholder />,
Group: <this.Placeholder />,
groupHeading: <this.Placeholder />,
IndicatorsContainer: <this.Placeholder />,
IndicatorSeparator: <this.Placeholder />,
Input: <this.Placeholder />,
LoadingIndicator: <this.Placeholder />,
Menu: <this.Placeholder />,
MenuList: <this.Placeholder />,
LoadingMessage: <this.Placeholder />,
NoOptionsMessage: <this.Placeholder />,
MultiValue: <this.Placeholder />,
MultiValueLabel: <this.Placeholder />,
MultiValueRemove: <this.Placeholder />,
Option: <this.Placeholder />,
Placeholder: <this.Placeholder />,
SelectContainer: <this.Placeholder />,
SingleValue: <this.Placeholder />,
ValueContainer: <this.Placeholder />,
};
 *
 */
