/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { t } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import UserSuggestionActions from "@library/features/users/suggestion/UserSuggestionActions";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import UserSuggestionModel, {
    IInjectableSuggestionsProps,
} from "@library/features/users/suggestion/UserSuggestionModel";
import apiv2 from "@library/apiv2";
import Tokens from "@library/forms/select/Tokens";
import { connect } from "react-redux";

interface IProps extends IInjectableSuggestionsProps {
    suggestionActions: UserSuggestionActions;
    onChange: (users: IComboBoxOption[]) => void;
    value: IComboBoxOption[];
    className?: string;
}

/**
 * Form component for searching/selecting users.
 */
export class MultiUserInput extends React.Component<IProps> {
    public render() {
        const { suggestions, currentUsername } = this.props;
        let options: IComboBoxOption[] | undefined;
        if (suggestions.status === LoadStatus.SUCCESS && suggestions.data) {
            options = suggestions.data.map(suggestion => {
                return {
                    value: suggestion.userID,
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
        this.props.suggestionActions.loadUsers(value);
    };
}

const withRedux = connect(
    UserSuggestionModel.mapStateToProps,
    dispatch => ({
        suggestionActions: new UserSuggestionActions(dispatch, apiv2),
    }),
);

export default withRedux(MultiUserInput);
