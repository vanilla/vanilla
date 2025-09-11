/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor";
import { FragmentEditorContextProvider } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { WidgetBuilderDisclosureAccess } from "@dashboard/appearance/fragmentEditor/FragmentEditorDisclosureAccess";
import { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import {
    useInitialFragmentForm,
    useSaveFragmentFormMutation,
} from "@dashboard/appearance/fragmentEditor/FragmentsApi.hooks";
import { FragmentEditorRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { useToast } from "@library/features/toaster/ToastContext";
import { QueryLoader } from "@library/loaders/QueryLoader";
import ModalLoader from "@library/modal/ModalLoader";
import DocumentTitle from "@library/routing/DocumentTitle";
import { useQueryParam } from "@library/routing/routingUtils";
import { t } from "@library/utility/appUtils";
import { fetchRegisteredInjectablesTypeDefinitions } from "@library/utility/fragmentsRegistry";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { resolveImports } from "@vanilla/utils";
import { useState } from "react";
import { RouteComponentProps } from "react-router-dom";

export default function FragmentEditorPage(
    props: RouteComponentProps<{
        fragmentType: string;
        fragmentUUID?: string;
    }>,
) {
    const { fragmentUUID: _fragmentUUID, fragmentType } = props.match.params;
    const [fragmentUUID, setFragmentUUID] = useState(_fragmentUUID);
    const initialIsCopy = useQueryParam("copy", false);
    const [isCopy, setIsCopy] = useState(initialIsCopy);
    const formQuery = useInitialFragmentForm({ fragmentType, fragmentUUID: fragmentUUID ?? null, isCopy });

    const injectableDefinitionsQuery = useQuery({
        queryKey: ["injectableDefinitions"],
        queryFn: async () => {
            const [reactTypes, ownTypes] = await Promise.all([
                resolveImports(import.meta.glob<string>("~@types/react/*", { query: "raw", import: "default" })),
                fetchRegisteredInjectablesTypeDefinitions(),
            ]);

            return {
                ...reactTypes,
                ...ownTypes,
            };
        },
    });

    const saveMutation = useSaveFragmentFormMutation({
        fragmentUUID: isCopy ? null : fragmentUUID ?? null,
        onSuccess(details) {
            if (isCopy || !fragmentUUID) {
                window.history.replaceState({}, "", FragmentEditorRoute.url(details));
                setIsCopy(false);
                setFragmentUUID(details.fragmentUUID);
            }
        },
    });

    return (
        <>
            <DocumentTitle
                title={fragmentUUID ? t("Edit Fragment") + " - " + formQuery.data?.name : t("Create Fragment")}
            />
            <WidgetBuilderDisclosureAccess type={"redirect"}>
                <QueryLoader
                    query={formQuery}
                    query2={injectableDefinitionsQuery}
                    loader={<ModalLoader />}
                    success={(form, injectableDefinitions) => {
                        return (
                            <>
                                <FragmentEditorContextProvider
                                    initialForm={form}
                                    saveFormMutation={saveMutation}
                                    typeDefinitions={injectableDefinitions}
                                >
                                    <FragmentEditor />
                                </FragmentEditorContextProvider>
                            </>
                        );
                    }}
                />
            </WidgetBuilderDisclosureAccess>
        </>
    );
}
