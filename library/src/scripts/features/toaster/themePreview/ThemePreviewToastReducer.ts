/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemesActions, { PreviewStatusType } from "@themingapi/theming-ui-settings/ThemesActions";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

export interface IPreviewToasterState {
    applyStatus: ILoadable<{ themeID: number | string }>;
    cancelStatus: ILoadable<{ themeID: number | string }>;
}

export interface IPreviewToasterStoreState extends ICoreStoreState {
    themePreviewToaster: IPreviewToasterState;
}

const DEFAULT_PREVIEW_TOASTER_STATE: IPreviewToasterState = {
    applyStatus: {
        status: LoadStatus.PENDING,
    },
    cancelStatus: {
        status: LoadStatus.PENDING,
    },
};

export const themePreviewToastReducer = produce(
    reducerWithInitialState<IPreviewToasterState>(DEFAULT_PREVIEW_TOASTER_STATE)
        .case(ThemesActions.putCurrentThemeACs.started, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.LOADING;
            nextState.applyStatus.data = {
                themeID: payload.themeID,
            };
            return nextState;
        })
        .case(ThemesActions.putCurrentThemeACs.done, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.SUCCESS;
            nextState.applyStatus.error = undefined;

            return nextState;
        })
        .case(ThemesActions.putCurrentThemeACs.failed, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.ERROR;
            nextState.applyStatus.error = payload.error;
            return nextState;
        })
        .case(ThemesActions.putPreviewThemeACs.started, (nextState, payload) => {
            if (payload.type === PreviewStatusType.CANCEL) {
                nextState.cancelStatus.status = LoadStatus.LOADING;
            }
            nextState.cancelStatus.data = {
                themeID: payload.themeID,
            };
            return nextState;
        })
        .case(ThemesActions.putPreviewThemeACs.done, (nextState, payload) => {
            if (payload.params.type === PreviewStatusType.CANCEL) {
                nextState.cancelStatus.status = LoadStatus.SUCCESS;
            }
            nextState.cancelStatus.error = undefined;

            return nextState;
        })
        .case(ThemesActions.putPreviewThemeACs.failed, (nextState, payload) => {
            nextState.cancelStatus.status = LoadStatus.ERROR;
            nextState.cancelStatus.error = payload.error;
            return nextState;
        }),
);

export function useThemePreviewToasterState() {
    return useSelector((state: IPreviewToasterStoreState) => {
        return state.themePreviewToaster;
    });
}
