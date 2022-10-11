/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { useAsyncFn, useLastValue, useSessionStorage } from "@vanilla/react-utils";
import { useEffect, useRef } from "react";

const CONFIG_KEY = "labs.useCustomLayout";

const HARDCODED_DB_ID = 1;
const HARDCODED_FILE_ID = "home";

async function ensureDbSpecExists() {
    try {
        await apiv2.get(`/layouts/${HARDCODED_DB_ID}/edit`);
    } catch (err) {
        // Doesn't exist.
        const defaultSpec = (await apiv2.get(`/layouts/${HARDCODED_FILE_ID}/edit`)).data;
        delete defaultSpec.layoutID;
        defaultSpec.layoutViewType = "home";
        await apiv2.post("/layouts", defaultSpec);
    }
}

export function usePlaygroundSetup() {
    const configPatcher = useConfigPatcher();
    const configs = useConfigsByKeys(["labs.useCustomLayout"]);
    const isSetup = configs.data?.[CONFIG_KEY];
    const isSetupLoading = configPatcher.isLoading;
    const setIsSetup = async (isSetup: boolean) => {
        await ensureDbSpecExists();
        await configPatcher.patchConfig({
            [CONFIG_KEY]: isSetup,
        });
    };
    return {
        isSetup,
        isSetupLoading,
        setIsSetup,
    };
}

export function usePlaygroundSpec() {
    const { isSetup } = usePlaygroundSetup();
    const [localSpec, setLocalSpec] = useSessionStorage("hydrateSpec", "");
    const lastSetup = useLastValue(isSetup);

    const loadRemoteRef = useRef("");

    async function loadRemoteSpec() {
        const refKey = (loadRemoteRef.current = isSetup);
        const requestUrl = isSetup ? `/layouts/${HARDCODED_DB_ID}/edit` : `/layouts/${HARDCODED_FILE_ID}/edit`;
        const result = await apiv2.get(requestUrl);
        if (loadRemoteRef.current !== refKey) {
            // Someone toggled while we were requesting.
            return;
        }

        const spec = result.data;
        spec.layoutViewType = "home";

        setLocalSpec(JSON.stringify(result.data, null, 4));
    }

    // First mount only.
    useEffect(() => {
        if (!localSpec) {
            loadRemoteSpec();
        }
    }, []);

    useEffect(() => {
        if (!lastSetup && isSetup) {
            // Reload.
            loadRemoteSpec();
        }
    }, [lastSetup, isSetup]);

    const [updateDbSpecState, _updateDbSpec] = useAsyncFn(async (spec) => {
        await apiv2.patch("/layouts/1", JSON.parse(spec));
    });

    const updateDbSpec = () => {
        _updateDbSpec(localSpec);
    };

    return {
        localSpec,
        setLocalSpec,
        updateDbSpec,
        isUpdating: updateDbSpecState.status === "loading",
    };
}
