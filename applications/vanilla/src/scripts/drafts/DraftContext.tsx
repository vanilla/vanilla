/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useDebouncedInput } from "@dashboard/hooks";
import {
    useDraftDeleteMutation,
    useDraftPostPatchMutation,
    useLocalDraft,
    useLocalDraftStore,
} from "@vanilla/addon-vanilla/drafts/Draft.hooks";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { IDraft, ILegacyDraft } from "@vanilla/addon-vanilla/drafts/types";
import { getParamsFromPath, isEditExistingPostParams, isLegacyDraft } from "@vanilla/addon-vanilla/drafts/utils";
import { useIsMounted } from "@vanilla/react-utils";
import { logDebug, logError, RecordID } from "@vanilla/utils";
import debounce from "lodash-es/debounce";
import { createContext, PropsWithChildren, useContext, useEffect, useMemo, useRef, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useLocation } from "react-router-dom";

interface IDraftContext {
    /** When this is null, there is no current draft */
    draftID: RecordID | null;
    updateDraft: (draftPayload: DraftsApi.PostParams) => void;
    updateImmediate: (draftPayload: DraftsApi.PostParams, forceUpdateLocal?: boolean) => Promise<void>;
    removeDraft: (localOnly?: boolean) => boolean;
    draft?: IDraft | null;
    draftLoaded: boolean;
    draftLastSavedToServer?: string;
    enableAutosave: () => void;
    disableAutosave: () => void;
    getDraftByMatchers: (matchers: Record<string, unknown>) => Array<[RecordID, IDraft]>;
    recordID?: RecordID;
}

export const DraftContext = createContext<IDraftContext>({
    draftID: null,
    updateDraft: () => null,
    updateImmediate: async () => Promise.resolve(),
    removeDraft: () => false,
    draft: null,
    draftLoaded: false,
    draftLastSavedToServer: undefined,
    enableAutosave: () => null,
    disableAutosave: () => null,
    getDraftByMatchers: () => [],

    recordID: undefined,
});

export function useDraftContext() {
    return useContext(DraftContext);
}

type DraftProviderProps = PropsWithChildren<{
    serverDraftID?: RecordID;
    serverDraft?: IDraft | ILegacyDraft;
    recordType: IDraft["recordType"];
    parentRecordID: RecordID;
    recordID?: RecordID;
    autosaveEnabled?: boolean;

    /** Use this to disable attempting to load a draft from local storage, when no draft ID is supplied, using matchers */
    loadLocalDraftByMatchers?: boolean;
}>;

export function DraftContextProvider(props: DraftProviderProps) {
    const {
        children,
        serverDraftID,
        serverDraft,
        recordType,
        parentRecordID,
        recordID,
        autosaveEnabled: autosaveInitiallyEnabled = true,
        loadLocalDraftByMatchers = true,
    } = props;

    const initialServerDraft: IDraft | undefined =
        serverDraft && isLegacyDraft(serverDraft) ? convertLegacyPostDraft(serverDraft) : serverDraft;

    // Can't pass drafts from the server on new posts, need to look them up here.

    const { pathname, search } = useLocation();

    const [autosaveEnabled, setAutosaveEnabled] = useState(autosaveInitiallyEnabled);

    const { getDraftByMatchers } = useLocalDraftStore();

    let initialDraftID = serverDraftID;
    if (!initialDraftID) {
        const parameters = getParamsFromPath(pathname, search);
        // We only get parameter values for create/edit post pages
        if (parameters && isEditExistingPostParams(parameters)) {
            initialDraftID = parameters.draftID ?? undefined;
        } else if (loadLocalDraftByMatchers) {
            // Recover draft from local storage if not on server
            const matchers = {
                recordType,
                parentRecordID,
                "attributes.draftType": recordType,
            };
            const lookup = getDraftByMatchers(matchers);
            if (lookup && lookup.length > 0) {
                const firstMatched = lookup[0];
                const id = firstMatched[0];
                initialDraftID = id;
            }
        }
    }

    const draftID = useRef<RecordID | null>(initialDraftID ?? null);

    const { localDraft, setLocalDraft, updateUnsavedDraftID, removeDraftAtID } = useLocalDraft(draftID.current);

    const draftMutation = useDraftPostPatchMutation();
    const draftServerDelete = useDraftDeleteMutation();

    const unsavedLocalDraftID = !draftID.current && !!localDraft ? pathname : undefined;

    const saveServerDraft = async (payload: DraftsApi.PostParams, forcePost?: boolean) => {
        if (!draftMutation.isLoading && !draftServerDelete.isLoading && payload) {
            const response = await draftMutation.mutateAsync({
                ...(draftID.current &&
                    draftID.current !== unsavedLocalDraftID &&
                    !forcePost && { draftID: draftID.current }),
                body: {
                    ...payload,
                    ...(forcePost && { draftID: undefined }),
                },
            });
            handleServerDraftChange(response);
        }
    };

    const draftLastSavedToServer = useRef<string | undefined>(undefined);

    function handleServerDraftChange(draft: IDraft) {
        const shouldRemoveDraft = !!draftID.current && `${draft.draftID}` !== `${draftID.current}`;
        const shouldUpdateUnsavedDraft = !!localDraft && !draftID.current;

        setLocalDraft(draft);

        // If we get a new ID back, update the local store
        if (shouldRemoveDraft) {
            removeDraftAtID(draftID.current!);
        }

        draftID.current = draft.draftID;
        draftLastSavedToServer.current = new Date().toISOString();

        if (shouldUpdateUnsavedDraft) {
            updateUnsavedDraftID(draft.draftID);
        }
    }

    async function handleServerDraftFetched(serverDraft: IDraft) {
        if (localDraft && new Date(localDraft.attributes.lastSaved) > new Date(serverDraft.attributes.lastSaved)) {
            // if we have a local draft and it's more recent than the server draft, save it to the server
            const autosaveEnabledBefore = autosaveEnabled;
            setAutosaveEnabled(false);
            await saveServerDraft(localDraft);
            setAutosaveEnabled(autosaveEnabledBefore);
        } else {
            handleServerDraftChange(serverDraft);
        }
    }

    // this is like the success callback for the query, but we have the initialServerDraft as initialData.
    useEffect(() => {
        if (initialServerDraft) {
            void handleServerDraftFetched(initialServerDraft);
        }
    }, []);

    const serverDraftQuery = useQuery({
        queryKey: ["draft", draftID.current],
        queryFn: async () => {
            try {
                const serverDraft = await DraftsApi.getEdit({ draftID: draftID.current! });
                await handleServerDraftFetched(serverDraft);
                return serverDraft;
            } catch (err) {
                logError("Error fetching draft from server", err);

                if (localDraft) {
                    // If for some reason, we had a local draft (that matched serverID), but it's not on the server,
                    // then POST it to the server
                    await saveServerDraft(localDraft, true);
                } else {
                    // If there was neither a local draft nor a server draft,clear the draftID.
                    draftID.current = null;
                }
                return null;
            }
        },

        enabled: !!draftID.current,
        initialData: initialServerDraft ?? undefined,
    });

    const debouncedToSendToServer = useDebouncedInput(localDraft, 5000);

    useEffect(() => {
        void (async () => {
            if (!localDraft) {
                return;
            }
            if (!autosaveEnabled) {
                return;
            }
            if (serverDraftQuery.isFetching) {
                return;
            }
            if (
                draftLastSavedToServer.current &&
                new Date(localDraft.attributes.lastSaved) < new Date(draftLastSavedToServer.current)
            ) {
                return;
            }
            try {
                await saveServerDraft(localDraft);
            } catch (err) {
                logError("Error autosaving draft", err);
            }
        })();
    }, [debouncedToSendToServer]);

    const updateDraftImpl = (payload: IDraft) => {
        // Stop updating the draft if disabled or its being deleted
        if (!autosaveEnabled || draftServerDelete.isLoading) {
            return;
        }
        try {
            setLocalDraft(payload);
        } catch (e) {
            logDebug("Error updating draft");
        }
    };

    // This whole little dance is to make sure that we aren't actually triggered state updates to this context very frequently, as it can cause a LOT of stuff to need to re-render.
    const updateDraftImplRef = useRef(updateDraftImpl);
    updateDraftImplRef.current = updateDraftImpl;

    const isMounted = useIsMounted();
    const updateDraft = useMemo(() => {
        return debounce(
            (payload: IDraft) => {
                requestAnimationFrame(() => {
                    if (isMounted()) {
                        updateDraftImplRef.current(payload);
                    }
                });
            },
            1000,
            {
                leading: true,
            },
        );
    }, []);

    function enableAutosave() {
        setAutosaveEnabled(true);
    }

    function disableAutosave() {
        updateDraft.cancel(); // Remove any pending updates to the local draft
        setAutosaveEnabled(false);
    }

    const removeDraft = (localOnly?: boolean) => {
        const id = draftID.current ?? unsavedLocalDraftID;
        // Short circuit if the draft is already being mutated or deleted
        if (!id || draftMutation.isLoading || draftServerDelete.isLoading) return false;
        removeDraftAtID(id);
        if (!(localOnly || id === unsavedLocalDraftID)) draftServerDelete.mutate(id);
        draftID.current = null;
        return true;
    };

    return (
        <DraftContext.Provider
            value={{
                draftID: draftID.current,
                updateImmediate: saveServerDraft,
                updateDraft,
                removeDraft,
                draft: localDraft,
                draftLoaded: !!localDraft,
                draftLastSavedToServer: draftLastSavedToServer.current,
                enableAutosave,
                disableAutosave,
                getDraftByMatchers,
                recordID: localDraft?.recordID ?? recordID,
            }}
        >
            {children}
        </DraftContext.Provider>
    );
}
function convertLegacyPostDraft(serverDraft: ILegacyDraft): IDraft {
    throw new Error("Function not implemented.");
}
