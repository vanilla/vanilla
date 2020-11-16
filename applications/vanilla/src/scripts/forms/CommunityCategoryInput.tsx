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
import { LoadStatus } from "@library/@types/api/core";
import { CategorySelectLookup, ICategorySelectLookupProps } from "@vanilla/addon-vanilla/forms/CategorySelectLookup";

interface IProps extends ICategorySelectLookupProps {
    isLoading: boolean;
    hideTitle?: boolean;
}

/**
 * Form component for searching/selecting a category.
 */
export class CommunityCategoryInput extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        isLoading: false,
    };

    lookupOnFocus = () => {
        if (!this.props.suggestions.data || this.props.suggestions.data.length === 0) {
            this.props.lookup("", true);
        }
    };

    public render() {
        return (
            <CategorySelectLookup
                onFocus={this.lookupOnFocus}
                placeholder={t("Search...")}
                {...this.props}
                label={this.props.label ?? t("Community Category")}
                noOptionsMessage={this.noOptionsMessage}
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

function mapStateToProps(state: IForumStoreState, ownProps: ICategorySelectLookupProps) {
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
