/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import apiv2 from "@library/apiv2";
import { ISelectLookupProps, SelectLookup } from "@library/forms/select/SelectLookup";
import { t } from "@library/utility/appUtils";
import CategorySuggestionActions from "@vanilla/categories/CategorySuggestionActions";
import { IForumStoreState } from "@vanilla/redux/state";
import React from "react";
import { connect } from "react-redux";

/**
 * Form component for searching/selecting a category.
 */
export class CommunityCategoryInput extends React.Component<ISelectLookupProps> {
    public static defaultProps: Partial<ISelectLookupProps> = {
        label: t("Community Category"),
    };

    public render() {
        return <SelectLookup {...this.props} label={this.props.label} />;
    }
}

function mapStateToProps(state: IForumStoreState, ownProps: ISelectLookupProps) {
    return {
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
