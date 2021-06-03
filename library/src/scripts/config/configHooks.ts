/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { stableObjectHash } from "@vanilla/utils";
import { useCallback, useEffect, useMemo } from "react";
import { useDispatch, useSelector } from "react-redux";
import * as ConfigActions from "@library/config/configActions";
import { bindActionCreators } from "@reduxjs/toolkit";
import { useUniqueID } from "@library/utility/idUtils";

export function useConfigActions() {
    const dispatch = useDispatch();
    return useMemo(() => {
        return bindActionCreators(ConfigActions, dispatch);
    }, [dispatch]);
}

export function useConfigsByKeys(keys: string[]) {
    const hash = stableObjectHash(keys);
    const existing = useSelector((state: ICoreStoreState) => {
        return (
            state.config.configsByLookupKey[hash] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const actions = useConfigActions();

    useEffect(() => {
        if (existing.status === LoadStatus.PENDING) {
            actions.getConfigsByKeyThunk(keys);
        }
    }, [hash, existing.status]);

    return existing;
}

type ConfigValues = Record<string, any>;
export function useConfigPatcher<T extends ConfigValues = ConfigValues>() {
    const watchID = useUniqueID("configPatch");

    const existing = useSelector((state: ICoreStoreState) => {
        return (
            state.config.configPatchesByID[watchID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const actions = useConfigActions();

    const patchConfig = useCallback(
        (values: T) => {
            return actions.patchConfigThunk({ values, watchID });
        },
        [watchID],
    );

    return {
        patchConfig,
        isLoading: existing.status === LoadStatus.LOADING,
    };
}

export const useLanguageConfig = (serviceType: string | undefined) => {
    const translationServices = useSelector((state: ICoreStoreState) => state.config.machineTranslation.services.data);
    const { getAllTranslationServicesThunk, putTranslationServiceThunk } = useConfigActions();

    const setTranslationService = (newConfig: any) => {
        serviceType && putTranslationServiceThunk({ values: serviceType, newConfig });
    };

    useEffect(() => {
        getAllTranslationServicesThunk && getAllTranslationServicesThunk();
    }, [getAllTranslationServicesThunk]);

    return {
        setTranslationService,
        translationServices,
    };
};
