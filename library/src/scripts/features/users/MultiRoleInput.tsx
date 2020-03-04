/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { t } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import RoleSuggestionActions from "@library/features/users/suggestion/RoleSuggestionActions";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import RoleSuggestionModel, {
    IInjectableSuggestionsProps,
} from "@library/features/users/suggestion/RoleSuggestionModel";
import apiv2 from "@library/apiv2";
import Tokens from "@library/forms/select/Tokens";
import { connect } from "react-redux";

interface IProps extends IInjectableSuggestionsProps {
    suggestionActions: RoleSuggestionActions;
    onChange: (roles: IComboBoxOption[]) => void;
    value: IComboBoxOption[];
    className?: string;
}

/**
 * Form component for searching/selecting roles.
 */
export class MultiRoleInput extends React.Component<IProps> {
    public render() {
        const { suggestions } = this.props;
        let options: IComboBoxOption[] | undefined;
        if (suggestions.status === LoadStatus.SUCCESS && suggestions.data) {
            options = suggestions.data.map(suggestion => {
                return {
                    value: suggestion.roleID,
                    label: suggestion.name,
                };
            });
        }

        return (
            <Tokens
                label={t("Author")}
                options={options}
                isLoading={suggestions.status === LoadStatus.LOADING || suggestions.status === LoadStatus.PENDING}
                onChange={this.props.onChange}
                value={this.props.value}
                onInputChange={this.onInputChange}
                className={this.props.className}
            />
        );
    }

    /**
     * React to changes in the token input.
     */
    private onInputChange = (value: string) => {
        this.props.suggestionActions.loadRoles(value);
    };
}

const withRedux = connect(RoleSuggestionModel.mapStateToProps, dispatch => ({
    suggestionActions: new RoleSuggestionActions(dispatch, apiv2),
}));

export default withRedux(MultiRoleInput);
