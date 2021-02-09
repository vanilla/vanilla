/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@vanilla/library/src/scripts/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IApiError } from "@vanilla/library/src/scripts/@types/api/core";
import { ITag } from "@library/features/tags/TagsReducer";
import debounce from "lodash/debounce";

const createAction = actionCreatorFactory("@@tags");

export class TagsAction extends ReduxActions {
    public static getTagsACs = createAction.async<{ name: string }, ITag[], IApiError>("GET");
    private getTagsInternal = (options: { name: string }) => {
        const thunk = bindThunkAction(TagsAction.getTagsACs, async () => {
            const response = await this.api.get(`/tags?query=${options.name}`);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    };

    public getTags = debounce(this.getTagsInternal, 100);
}

export function useTagsActions() {
    return useReduxActions(TagsAction);
}
