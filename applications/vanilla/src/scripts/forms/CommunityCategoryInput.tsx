/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import apiv2 from "@library/apiv2";
import { ISelectLookupProps, SelectLookup } from "@library/forms/select/SelectLookup";
import { t } from "@library/utility/appUtils";
import CategorySuggestionActions from "@vanilla/addon-vanilla/categories/CategorySuggestionActions";
import { IForumStoreState } from "@vanilla/addon-vanilla/redux/state";
import React from "react";
import { connect } from "react-redux";
import { OptionProps } from "react-select/lib/components/Option";
import { NoOptionsMessage } from "@library/forms/select/overwrites";
import { LoadStatus } from "@library/@types/api/core";

interface IProps extends ISelectLookupProps {
    isLoading: boolean;
}

/**
 * Form component for searching/selecting a category.
 */
export class CommunityCategoryInput extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        isLoading: false,
        label: t("Community Category"),
    };

    public render() {
        return (
            <SelectLookup
                {...this.props}
                label={this.props.label}
                noOptionsMessage={this.noOptionsMessage}
                placeholder=""
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

function mapStateToProps(state: IForumStoreState, ownProps: ISelectLookupProps) {
    return {
        isLoading: state.forum.categories.suggestions.status === LoadStatus.LOADING,
        suggestions: state.forum.categories.suggestions,
    };
}

function mapDispatchToProps(dispatch: any) {
    const categorySuggestionActions = new CategorySuggestionActions(dispatch, apiv2);

    return {
        lookup: categorySuggestionActions.loadCategories,
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(CommunityCategoryInput);
