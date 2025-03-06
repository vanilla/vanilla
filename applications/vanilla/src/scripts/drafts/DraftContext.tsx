/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useDebouncedInput } from "@dashboard/hooks";
import {
    useDraftDeleteMutation,
    useDraftPostPatchMutation,
    useDraftQuery,
    useLocalDraft,
} from "@vanilla/addon-vanilla/drafts/Draft.hooks";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { getParamsFromPath, isEditExistingPostParams } from "@vanilla/addon-vanilla/drafts/utils";
import { useIsMounted } from "@vanilla/react-utils";
import { logDebug, logError, RecordID } from "@vanilla/utils";
import debounce from "lodash/debounce";
import { createContext, PropsWithChildren, useContext, useEffect, useMemo, useRef, useState } from "react";

interface IDraftContext {
    /** When this is null, there is no current draft */
    draftID: RecordID | null;
    updateDraft: (draftPayload: DraftsApi.PostParams) => void;
    updateImmediate: (draftPayload: DraftsApi.PostParams) => Promise<void>;
    removeDraft: (draftID: RecordID, localOnly?: boolean) => boolean;
    draft?: IDraft | DraftsApi.PostParams | null;
    draftLoaded?: boolean;
    draftLastSaved: string | null;
    enable: () => void;
    disable: () => void;
    getDraftByMatchers: (matchers: Record<string, unknown>) => Array<[RecordID, DraftsApi.PostParams]>;
}

export const DraftContext = createContext<IDraftContext>({
    draftID: null,
    updateDraft: () => null,
    updateImmediate: async () => Promise.resolve(),
    removeDraft: () => false,
    draft: null,
    draftLoaded: false,
    draftLastSaved: "",
    enable: () => null,
    disable: () => null,
    getDraftByMatchers: () => [],
});

export function useDraftContext() {
    return useContext(DraftContext);
}

type DraftProviderProps = PropsWithChildren<{
    serverDraftID?: RecordID;
    serverDraft?: IDraft;
    recordType: "discussion" | "comment";
    parentRecordID: RecordID;
}>;

export function DraftContextProvider(props: DraftProviderProps) {
    const { children, serverDraftID, serverDraft, recordType, parentRecordID } = props;

    const [enabled, setEnabled] = useState(true);

    const draftID = useRef<RecordID | null>(serverDraftID ?? null);

    // Can't pass drafts from the server on new posts, need to look them up here.
    const { pathname, search } = window.location;
    const parameters = getParamsFromPath(pathname, search);

    // Only on first mount
    useEffect(() => {
        // If none on server, check local
        if (!serverDraftID && !parameters) {
            const matchers = {
                recordType,
                parentRecordID,
                "attributes.draftType": "discussion",
            };
            const lookup = getDraftByMatchers(matchers);
            if (lookup && lookup.length > 0) {
                const firstMatched = lookup[0];
                const id = firstMatched[0];
                draftID.current = id;
            }
        }
        // We only get parameter values for create post pages
        if (parameters && isEditExistingPostParams(parameters)) {
            draftID.current = parameters.draftID ?? null;
        }
    }, []);

    // Recover draft from local storage if not on server
    useEffect(() => {
        if (!serverDraftID && !serverDraft && !draftID.current) {
            const matchers = {
                recordType,
                parentRecordID,
                "attributes.draftType": "comment",
            };
            const lookup = getDraftByMatchers(matchers);
            if (lookup && lookup.length > 0) {
                const firstMatched = lookup[0];
                const id = firstMatched[0];
                draftID.current = id;
            }
        }
    }, [serverDraftID, serverDraft]);

    const draft = useDraftQuery(
        draftID.current !== window.location.pathname ? draftID.current : undefined,
        serverDraft,
    );
    const draftMutation = useDraftPostPatchMutation();
    const draftServerDelete = useDraftDeleteMutation();

    const { localDraft, setLocalDraft, updateUnsavedDraftID, removeDraftAtID, getDraftByMatchers } = useLocalDraft(
        draftID.current,
    );
    const serverLastSaved = useRef<string | null>(null);

    const saveServerDraft = async (payload: DraftsApi.PostParams) => {
        if (!draftMutation.isLoading && !draftServerDelete.isLoading && payload) {
            const response = await draftMutation.mutateAsync({
                ...(draftID.current && draftID.current !== window.location.pathname && { draftID: draftID.current }),
                body: payload,
            });
            // If we get a new ID back, update the local store
            if (`${response.draftID}` !== `${draftID.current}`) {
                draftID.current && removeDraftAtID(draftID.current);
            }
            draftID.current = response.draftID;
            serverLastSaved.current = new Date().toISOString();
            updateUnsavedDraftID(response.draftID);
        }
    };

    const debouncedToSendToServer = useDebouncedInput(localDraft, 5000);

    useEffect(() => {
        void (async () => {
            try {
                await saveServerDraft(localDraft);
            } catch (err) {
                logError("Error saving draft", err);
            }
        })();
    }, [debouncedToSendToServer]);

    const updateDraftImpl = (payload: DraftsApi.PostParams) => {
        // Stop updating the draft if disabled or its being deleted
        if (!enabled || draftServerDelete.isLoading) {
            logDebug("Error updating draft");
            return false;
        }
        setLocalDraft(payload);
        return true;
    };

    // This whole little dance is to make sure that we aren't actually triggered state updates to this context very frequently, as it can cause a LOT of stuff to need to re-render.
    const updateDraftImplRef = useRef(updateDraftImpl);
    updateDraftImplRef.current = updateDraftImpl;

    const isMounted = useIsMounted();
    const updateDraft = useMemo(() => {
        return debounce((payload: DraftsApi.PostParams) => {
            requestAnimationFrame(() => {
                if (isMounted()) {
                    updateDraftImplRef.current(payload);
                }
            });
        }, 1000);
    }, []);

    // Needed for immediate updates, when draft buttons are clicked, etc.
    const updateImmediate = async (payload: DraftsApi.PostParams) => {
        updateDraftImpl(payload);
        await saveServerDraft(payload);
    };

    const removeDraft = (id: RecordID, localOnly?: boolean) => {
        // Short circuit if the draft is already being mutated or deleted
        if (!id || !enabled || draftMutation.isLoading || draftServerDelete.isLoading) return false;
        removeDraftAtID(id);
        !localOnly && id !== window.location.pathname && draftServerDelete.mutate(id);
        draftID.current = null;
        return true;
    };

    return (
        <DraftContext.Provider
            value={{
                draftID: draftID.current,
                updateDraft,
                updateImmediate,
                removeDraft,
                draft: localDraft,
                draftLoaded: !!localDraft,
                draftLastSaved: serverLastSaved.current,
                enable: () => setEnabled(true),
                disable: () => setEnabled(false),
                getDraftByMatchers,
            }}
        >
            {children}
        </DraftContext.Provider>
    );
}
