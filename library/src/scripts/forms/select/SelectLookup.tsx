/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne from "@library/forms/select/SelectOne";
import React from "react";

export interface ISelectLookupProps {
    label: string;
    lookup: (value: string) => {};
    onChange: (category: IComboBoxOption) => void;
    suggestions: ILoadable<any>;
    value: IComboBoxOption | undefined;
}

/**
 * Form component for searching/selecting a category.
 */
export class SelectLookup<P extends ISelectLookupProps = ISelectLookupProps> extends React.Component<P> {
    public static defaultProps: Partial<ISelectLookupProps> = {
        suggestions: {
            status: LoadStatus.PENDING,
        },
    };

    public render() {
        const { label, suggestions } = this.props;
        let options: IComboBoxOption[] | undefined;
        if (suggestions.status === LoadStatus.SUCCESS && suggestions.data) {
            options = suggestions.data.map(suggestion => {
                return {
                    value: suggestion.categoryID,
                    label: suggestion.name,
                };
            });
        }

        return (
            <SelectOne
                label={label}
                options={options}
                onChange={this.props.onChange}
                onInputChange={this.onInputChange}
                value={this.props.value}
            />
        );
    }

    /**
     * React to changes in the token input.
     */
    private onInputChange = (value: string) => {
        this.props.lookup(value);
    };
}
