/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ILayout,
    ILayoutsState,
    LayoutEditSchema,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import {
    getLayoutJsonDraftByID,
    getLayoutJsonByLayoutID,
    useLayoutDispatch,
    useLayoutSelector as useSelector,
    getLayoutsByViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import { useCallback, useEffect, useMemo } from "react";
import { ILayoutViewQuery } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { bindActionCreators } from "redux";
import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import { uuidv4 } from "@vanilla/utils";

export function useLayoutsActions() {
    const dispatch = useLayoutDispatch();
    return useMemo(() => bindActionCreators(layoutActions, dispatch), [dispatch]);
}
export function useLayouts() {
    const { fetchAllLayouts } = useLayoutsActions();
    const layoutsListStatus = useSelector(({ layoutSettings }) => layoutSettings.layoutsListStatus);
    const layoutsByViewType = useSelector(({ layoutSettings }) => getLayoutsByViewType(layoutSettings));

    useEffect(() => {
        if (layoutsListStatus.status === LoadStatus.PENDING) {
            fetchAllLayouts();
        }
    }, [layoutsListStatus, fetchAllLayouts]);

    return {
        isLoading: [LoadStatus.PENDING, LoadStatus.LOADING].includes(layoutsListStatus.status),
        error: layoutsListStatus.status === LoadStatus.ERROR && layoutsListStatus.error,
        layoutsByViewType,
    };
}

export function useLayout(layoutID: ILayout["layoutID"]) {
    const { fetchLayout } = useLayoutsActions();
    const layout = useSelector(({ layoutSettings }) => layoutSettings.layoutsByID[layoutID]);

    useEffect(() => {
        if (!layout) {
            fetchLayout(layoutID);
        }
    }, [fetchLayout, layout, layoutID]);

    return layout ?? { status: LoadStatus.PENDING };
}

export function useLayoutJson(layoutID: ILayout["layoutID"]): Loadable<LayoutEditSchema> {
    const { fetchLayoutJson } = useLayoutsActions();
    const layoutJson = useSelector(({ layoutSettings }) => getLayoutJsonByLayoutID(layoutSettings, layoutID));

    useEffect(() => {
        if (!layoutJson) {
            fetchLayoutJson(layoutID);
        }
    }, [fetchLayoutJson, layoutJson, layoutID]);

    return layoutJson ?? { status: LoadStatus.PENDING };
}

export function useLayoutJsonDraft(
    existingDraftID?: ILayout["layoutID"],
    sourceLayoutJsonID?: ILayout["layoutID"],
    layoutViewType = "home" as LayoutViewType,
    copy = false,
): Loadable<LayoutEditSchema> {
    const { fetchLayoutJson, copyLayoutJsonToNewDraft, createNewLayoutJsonDraft } = useLayoutsActions();

    const isCopy = copy || (existingDraftID !== undefined && existingDraftID === sourceLayoutJsonID);
    const isNewDraft = !existingDraftID && !!(!sourceLayoutJsonID || (sourceLayoutJsonID && isCopy));

    const draftID = useMemo(() => {
        return isNewDraft ? uuidv4() : existingDraftID ?? sourceLayoutJsonID!;
    }, [isNewDraft, existingDraftID, sourceLayoutJsonID]);

    const draft = useSelector(({ layoutSettings }) => getLayoutJsonDraftByID(layoutSettings, draftID));

    const sourceLayoutJson = useSelector(({ layoutSettings }) =>
        sourceLayoutJsonID ? getLayoutJsonByLayoutID(layoutSettings, sourceLayoutJsonID) : undefined,
    );

    useEffect(() => {
        if (sourceLayoutJsonID && !sourceLayoutJson) {
            fetchLayoutJson(sourceLayoutJsonID);
        }
    }, [draft, fetchLayoutJson, sourceLayoutJson, sourceLayoutJsonID]);

    useEffect(() => {
        if (!draft) {
            if (!!sourceLayoutJsonID && sourceLayoutJson?.status === LoadStatus.SUCCESS) {
                copyLayoutJsonToNewDraft({ sourceLayoutJsonID, draftID: isCopy ? draftID : sourceLayoutJsonID });
            } else if (!sourceLayoutJsonID) {
                createNewLayoutJsonDraft({ draftID, layoutViewType });
            }
        }
    }, [draft, draftID, sourceLayoutJson, sourceLayoutJsonID, isCopy]);

    return draft
        ? {
              status: LoadStatus.SUCCESS,
              data: {
                  ...draft,
                  layoutID: draftID,
              },
          }
        : {
              status: LoadStatus.PENDING,
          };
}

export function useLayoutJsonDraftActions(draftID: keyof ILayoutsState["layoutJsonDraftsByID"]) {
    const draft = useSelector(({ layoutSettings }) => getLayoutJsonDraftByID(layoutSettings, draftID))!;
    const dispatch = useLayoutDispatch();
    const save = useCallback(
        async function () {
            const resultAction = dispatch(layoutActions.postOrPatchLayoutJsonDraft(draft));
            const unwrappedResult = await resultAction.unwrap();
            return unwrappedResult;
        },
        [dispatch, draft],
    );

    function update(modifiedDraft: LayoutEditSchema) {
        return dispatch(layoutActions.updateLayoutJsonDraft({ draftID, modifiedDraft }));
    }
    return { save, update };
}

export function usePutLayoutView(layoutID: ILayout["layoutID"]) {
    const { putLayoutView } = useLayoutsActions();

    return useCallback(
        (query: Omit<ILayoutViewQuery, "layoutID">) => {
            putLayoutView({
                layoutID,
                ...query,
            });
        },

        [putLayoutView, layoutID],
    );
}
