/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import CategorySuggestionActions from "@vanilla/addon-vanilla/categories/CategorySuggestionActions";
import { IForumStoreState } from "@vanilla/addon-vanilla/redux/state";
import React from "react";
import { connect } from "react-redux";
import { OptionProps } from "react-select/lib/components/Option";
import { NoOptionsMessage } from "@library/forms/select/overwrites";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";
import Tokens, { ITokenProps } from "@vanilla/library/src/scripts/forms/select/Tokens";
import SelectOne from "@vanilla/library/src/scripts/forms/select/SelectOne";

interface IProps {
    multiple?: boolean;
    lookup: (value: string, first?: boolean) => {};
    suggestions: ILoadable<any>;
    onChange: (tokens: IComboBoxOption[]) => void;
    value: IComboBoxOption[];
    label: string | null;
    labelNote?: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    isLoading: boolean;
    hideTitle?: boolean;
}

/**
 * Form component for searching/selecting a category.
 */
export class CommunityCategoryInput extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        isLoading: false,
        suggestions: {
            status: LoadStatus.PENDING,
        },
    };

    lookupOnFocus = () => {
        if (!this.props.suggestions.data || this.props.suggestions.data.length === 0) {
            this.props.lookup("", true);
        }
    };

    public render() {
        const { suggestions, multiple } = this.props;
        let options: IComboBoxOption[] | undefined;
        if (suggestions.status === LoadStatus.SUCCESS && suggestions.data) {
            options = suggestions.data.map((suggestion) => {
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

        if (multiple) {
            return (
                <Tokens
                    onFocus={this.lookupOnFocus}
                    placeholder={t("Search...")}
                    {...this.props}
                    label={this.props.label ?? t("Community Category")}
                    showIndicator
                    options={options}
                />
            );
        }
        return (
            <SelectOne
                onFocus={this.lookupOnFocus}
                placeholder={t("Search...")}
                {...this.props}
                onChange={(option) => {
                    if (this.props.onChange) this.props.onChange([option]);
                }}
                options={options}
                label={this.props.label ?? t("Community Category")}
                value={(options || [])[0]}
            />
        );
    }

    private noOptionsMessage(props: OptionProps<any>): JSX.Element | null {
        let text = "";
        if (props.selectProps.inputValue === "") {
            text = t("Search for a category");
        } else {
            text = t("No categories found");
        }
        return <NoOptionsMessage {...props}>{text}</NoOptionsMessage>;
    }
}

function mapStateToProps(state: IForumStoreState, ownProps: IProps) {
    return {
        isLoading: state.forum.categories.suggestions.status === LoadStatus.LOADING,
        suggestions: state.forum.categories.suggestions,
    };
}

function mapDispatchToProps(dispatch: any) {
    const categorySuggestionActions = new CategorySuggestionActions(dispatch, apiv2);

    return {
        lookup: (query: string, first = false) => {
            query = query.trim();
            if (!first && !query) return;
            categorySuggestionActions.loadCategories(query);
        },
    };
}

const withRedux = connect(mapStateToProps, mapDispatchToProps);

export default withRedux(CommunityCategoryInput);
