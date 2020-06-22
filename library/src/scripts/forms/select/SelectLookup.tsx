/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import React from "react";

export interface ISelectLookupProps extends ISelectOneProps {
    lookup: (value: string) => {};
    suggestions: ILoadable<any>;
}

interface IState {
    hasFocus: boolean;
}

/**
 * Form component for searching/selecting a category.
 */
export class SelectLookup extends React.Component<ISelectLookupProps, IState> {
    public state = {
        hasFocus: false,
    };

    public static defaultProps: Partial<ISelectLookupProps> = {
        suggestions: {
            status: LoadStatus.PENDING,
        },
    };

    public render() {
        const { suggestions } = this.props;
        let options: IComboBoxOption[] | undefined;
        if (suggestions.status === LoadStatus.SUCCESS && suggestions.data) {
            options = suggestions.data.map(suggestion => {
                let parentLabel;
                const crumbLength = suggestion.breadcrumbs?.length ?? 0;
                if (crumbLength > 1) {
                    parentLabel = suggestion.breadcrumbs[crumbLength - 2].name;
                }

                return {
                    value: suggestion.categoryID,
                    label: suggestion.name,
                    data: {
                        parentLabel,
                    },
                };
            });
        }

        return <SelectOne {...this.props} options={options} onInputChange={this.onInputChange} />;
    }

    /**
     * React to changes in the token input.
     */
    private onInputChange = (value: string) => {
        this.props.lookup(value);
    };
}
