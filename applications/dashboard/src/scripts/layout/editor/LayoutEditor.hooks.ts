/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutDispatch, useLayoutSelector } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import { LayoutViewType, ILayoutDraft } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LoadStatus } from "@library/@types/api/core";
import { logError, RecordID } from "@vanilla/utils";
import { useEffect, useState, useCallback } from "react";
import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import { LayoutEditorAssetUtils } from "@dashboard/layout/editor/LayoutEditorAssetUtils";

export function useLayoutDraft(layoutID?: RecordID, initialViewType?: LayoutViewType) {
    const dispatch = useLayoutDispatch();

    // If no layoutID is passed, initialize new layout draft
    useEffect(() => {
        if (!layoutID && initialViewType) {
            dispatch(
                layoutActions.initializeLayoutDraft({
                    initialLayout: { layoutViewType: initialViewType },
                }),
            );
        }
    }, [layoutID, initialViewType]);

    const layoutDraft = useLayoutSelector(({ layoutSettings }) => layoutSettings.layoutDraft);
    const layoutDraftLoadable = useLayoutSelector(({ layoutSettings }) => {
        return layoutID ? layoutSettings.layoutJsonsByLayoutID[layoutID] : undefined;
    });

    // If a layoutID is passed, load the initial layout into the draft.
    useEffect(() => {
        if (layoutID) {
            if (layoutDraftLoadable?.status) {
                if (layoutDraftLoadable.status === LoadStatus.SUCCESS) {
                    // use existing layout, if it's available
                    dispatch(
                        layoutActions.initializeLayoutDraft({
                            initialLayout: { ...layoutDraftLoadable.data, layoutViewType: initialViewType },
                        }),
                    );
                }
            } else {
                // fetch the layout first, if necessary
                dispatch(layoutActions.fetchLayoutJson(layoutID))
                    .unwrap()
                    .then((editLayout) => {
                        dispatch(
                            layoutActions.initializeLayoutDraft({
                                initialLayout: { ...editLayout, layoutViewType: initialViewType },
                            }),
                        );
                    });
            }
        }
    }, [layoutID, layoutDraftLoadable]);

    const persistDraft = useCallback(
        async (extra: Partial<ILayoutDraft>) => {
            if (!layoutDraft) {
                return;
            }
            dispatch(layoutActions.updateLayoutDraft(extra));
            return dispatch(layoutActions.persistLayoutDraft({ ...layoutDraft, ...extra })).unwrap();
        },
        [dispatch, layoutDraft],
    );

    const updateDraft = useCallback(
        (modifications: Partial<ILayoutDraft>) => {
            dispatch(layoutActions.updateLayoutDraft(modifications));
        },
        [dispatch],
    );
    return { layoutDraft, persistDraft, updateDraft };
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
        (textDraft, layoutViewType?: LayoutViewType): ILayoutDraft | null => {
            try {
                const parsed = JSON.parse(textDraft);
                const validateRequiredAssets = LayoutEditorAssetUtils.validateAssets({
                    ...parsed,
                    layoutViewType: layoutViewType,
                });
                setJsonErrorMessage(validateRequiredAssets.isValid ? null : validateRequiredAssets.message ?? "");
                return validateRequiredAssets.isValid ? (parsed as ILayoutDraft) : null;
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
