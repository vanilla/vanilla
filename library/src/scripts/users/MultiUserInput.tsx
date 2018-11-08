/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { connect } from "react-redux";
import UserSuggestionModel, { IInjectableSuggestionsProps } from "@library/users/suggestion/UserSuggestionModel";
import UserSuggestionActions from "@library/users/suggestion/UserSuggestionActions";
import apiv2 from "@library/apiv2";
import Tokens from "@library/components/forms/select/Tokens";
import { t } from "@library/application";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import { dummyAuthors } from "@knowledge/modules/search/state/dummyAuthors";
import { LoadStatus } from "@library/@types/api";

interface IProps extends IInjectableSuggestionsProps {
    suggestionActions: UserSuggestionActions;
}

export class MultiUserInput extends React.Component<IProps> {
    public render() {
        const { suggestions, currentUsername } = this.props;
        let options: IComboBoxOption[] | undefined = undefined;
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
                onChange={val => console.log(val)}
                value={[]}
                onInputChange={this.onInputChange}
            />
        );
    }

    private onInputChange = (value: string) => {
        console.log("New input vlaue", value);
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
