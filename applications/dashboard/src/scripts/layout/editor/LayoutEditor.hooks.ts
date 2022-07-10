/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutDispatch, useLayoutSelector } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import { LayoutViewType, ILayoutDraft } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import { logError, RecordID } from "@vanilla/utils";
import { useEffect, useDebugValue, useState, useCallback } from "react";
import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import { getRelativeUrl } from "@library/utility/appUtils";
import { useLastValue } from "@vanilla/react-utils";
import { useHistory } from "react-router";
import { LayoutEditorRoute } from "@dashboard/appearance/routes/appearanceRoutes";

export function useLayoutDraft(layoutID?: RecordID, initialViewType?: LayoutViewType) {
    const dispatch = useLayoutDispatch();

    const layoutDraft = useLayoutSelector(({ layoutSettings }) => layoutSettings.layoutDraft);
    const layoutDraftLoadable = useLayoutSelector(
        ({ layoutSettings }): Loadable<any> => {
            if (layoutID == null) {
                return {
                    status: LoadStatus.SUCCESS,
                    data: {},
                };
            } else {
                return (
                    layoutSettings.layoutJsonsByLayoutID[layoutID] ?? {
                        status: LoadStatus.PENDING,
                    }
                );
            }
        },
    );

    // Load the initial layout into the draft.
    useEffect(() => {
        if (layoutID != null) {
            if (layoutDraft?.layoutID != layoutID) {
                // We need to load the draft.
                dispatch(layoutActions.fetchLayoutJson(layoutID))
                    .unwrap()
                    .then((editLayout) => {
                        dispatch(layoutActions.initializeLayoutDraft({ initialLayout: editLayout }));
                    });
            }
        } else if (initialViewType != null && layoutDraft?.layoutViewType != initialViewType) {
            dispatch(
                layoutActions.initializeLayoutDraft({
                    initialLayout: { layoutViewType: initialViewType },
                }),
            );
        }
    }, [layoutID, layoutDraft]);

    const persistLoadable = useLayoutSelector(
        ({ layoutSettings }) =>
            layoutSettings.layoutDraftPersistLoadable ?? {
                status: LoadStatus.PENDING,
            },
    );

    // Replace our route after a save.
    const history = useHistory();
    const persistStatus = persistLoadable.status;
    const lastPersistStatus = useLastValue(persistStatus);
    useEffect(() => {
        if (lastPersistStatus !== LoadStatus.SUCCESS && persistStatus === LoadStatus.SUCCESS && persistLoadable.data) {
            history.replace(getRelativeUrl(LayoutEditorRoute.url(persistLoadable.data)));
        }
    }, [history, persistStatus, lastPersistStatus, persistLoadable]);

    const persistDraft = useCallback(
        async (extra: Partial<ILayoutDraft>) => {
            if (!layoutDraft) {
                return;
            }
            dispatch(layoutActions.updateLayoutDraft(extra));
            dispatch(layoutActions.persistLayoutDraft({ ...layoutDraft, ...extra }));
        },
        [dispatch, layoutDraft],
    );

    useDebugValue({
        layoutDraft,
        persistLoadable,
        layoutDraftLoadable,
    });

    const updateDraft = useCallback(
        (modifications: Partial<ILayoutDraft>) => {
            dispatch(layoutActions.updateLayoutDraft(modifications));
        },
        [dispatch],
    );
    return { layoutDraft, layoutDraftLoadable, persistLoadable, persistDraft, updateDraft };
}

export function useTextEditorJsonBuffer() {
    const [textContent, setTextContent] = useState("");
    const [jsonErrorMessage, setJsonErrorMessage] = useState<string | null>(null);

    const dismissJsonError = useCallback(() => {
        setJsonErrorMessage(null);
    }, [setJsonErrorMessage]);

    const loadTextDraft = useCallback(
        (layoutDraft: ILayoutDraft) => {
            setJsonErrorMessage(null);
            setTextContent(
                JSON.stringify(
                    { ...layoutDraft, layoutID: undefined, name: undefined, layoutViewType: undefined },
                    null,
                    4,
                ),
            );
        },
        [setJsonErrorMessage, setTextContent],
    );

    const validateTextDraft = useCallback(
        (textDraft): ILayoutDraft | null => {
            try {
                const parsed = JSON.parse(textDraft);
                setJsonErrorMessage(null);
                return parsed as ILayoutDraft;
            } catch (err) {
                logError(err);
                setJsonErrorMessage(err.message);
                return null;
            }
        },
        [setJsonErrorMessage],
    );

    return { textContent, setTextContent, jsonErrorMessage, dismissJsonError, loadTextDraft, validateTextDraft };
}
