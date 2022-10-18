/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ILoadable, Loadable, LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { stableObjectHash } from "@vanilla/utils";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import * as ConfigActions from "@library/config/configActions";
import { bindActionCreators } from "@reduxjs/toolkit";
import { useUniqueID } from "@library/utility/idUtils";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { IAddon, ILocale } from "@dashboard/languages/LanguageSettingsTypes";
import { useConfigDispatch } from "@library/config/configReducer";
import { patchConfigThunk } from "@library/config/configActions";

const LOCALE_KEY = "garden.locale";

export function useConfigActions() {
    const dispatch = useConfigDispatch();
    return useMemo(() => {
        return bindActionCreators(ConfigActions, dispatch);
    }, [dispatch]);
}

export function useConfigsByKeys(keys: string[]) {
    const hash = stableObjectHash(keys);
    const existing = useSelector((state: ICoreStoreState) => {
        if (keys.length === 0) {
            return {
                status: LoadStatus.SUCCESS,
                data: {},
            } as Loadable<{}>;
        }
        return (
            state.config.configsByLookupKey[hash] ??
            ({
                status: LoadStatus.PENDING,
            } as Loadable<{}>)
        );
    });

    const actions = useConfigActions();

    useEffect(() => {
        if (existing.status === LoadStatus.PENDING && keys.length > 0) {
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

    const errorByID = useSelector((state: ICoreStoreState) => state.config.configPatchesByID[watchID]);

    const dispatch = useConfigDispatch();

    const patchConfig = useCallback(
        async (values: T) => {
            return dispatch(patchConfigThunk({ values, watchID }));
        },
        [watchID],
    );

    return {
        patchConfig,
        isLoading: existing.status === LoadStatus.LOADING,
        error: existing.status === LoadStatus.ERROR ? errorByID.error : null,
    };
}

const useKnowledgeEnabled = () => {
    const allAddons = useSelector((state: ICoreStoreState) => state.config.addons?.addon?.data);
    const [enabled, setEnabled] = useState(false);
    useEffect(() => {
        setEnabled(() => {
            const knowledge = allAddons && allAddons.find((addon) => addon.addonID === "knowledge");
            return knowledge ? knowledge.enabled : false;
        });
    }, [allAddons]);
    return enabled;
};

export const useLanguageConfig = (serviceType: string | undefined) => {
    // The result of the /translation-services endpoint
    const translationServices = useSelector((state: ICoreStoreState) => state.config.machineTranslation.services.data);
    const { getAllTranslationServicesThunk, putTranslationServiceThunk, getAddonsByTypeThunk } = useConfigActions();

    const setTranslationService = (newConfig: any) => {
        serviceType && putTranslationServiceThunk({ values: serviceType, newConfig });
    };

    useEffect(() => {
        getAllTranslationServicesThunk && getAllTranslationServicesThunk();
    }, [getAllTranslationServicesThunk]);

    useEffect(() => {
        getAddonsByTypeThunk && getAddonsByTypeThunk({ values: "addon" });
    }, [getAddonsByTypeThunk]);

    const hasMachineTranslation = useKnowledgeEnabled();

    return {
        translationServices,
        setTranslationService,
        hasMachineTranslation,
    };
};

const useDefaultLocaleCode = () => {
    return useConfigsByKeys([LOCALE_KEY])?.data?.[LOCALE_KEY] ?? null;
};

const useEnabledLocales = () => {
    return useSelector((state: ICoreStoreState) => state.config.locales.data);
};

const useAllLocales = () => {
    return useSelector((state: ICoreStoreState) => state.config.addons["locale"]?.data) ?? [];
};

export const useDefaultLocales = () => {
    const defaultLocaleCode = useDefaultLocaleCode();
    const availableLocales = useEnabledLocales();
    const { patchConfig } = useConfigPatcher();
    const { getAvailableLocalesThunk } = useConfigActions();
    const [defaultLocale, setDefault] = useState<IComboBoxOption | undefined>();

    const setDefaultLocale = (option: IComboBoxOption) => {
        patchConfig({
            [LOCALE_KEY]: option.value,
        });
    };

    // Maintain state for enabled and default locales
    useEffect(() => {
        if (defaultLocaleCode && availableLocales) {
            setDefault(() => {
                const selected = availableLocales.find((locale: ILocale) => locale.localeKey === defaultLocaleCode);
                return {
                    value: defaultLocaleCode,
                    label: selected.displayNames[defaultLocaleCode],
                };
            });
        }
    }, [defaultLocaleCode, availableLocales]);

    useEffect(() => {
        getAvailableLocalesThunk && getAvailableLocalesThunk();
    }, [getAvailableLocalesThunk]);

    return {
        defaultLocale,
        setDefaultLocale,
    };
};

export const useLocales = () => {
    const { getAddonsByTypeThunk, patchAddonByIdThunk, getAvailableLocalesThunk } = useConfigActions();
    const defaultLocaleCode = useDefaultLocaleCode();
    const allLocales = useAllLocales();
    const availableLocales = useEnabledLocales();

    const [localeOptions, setLocaleOptions] = useState<IComboBoxOption[] | undefined>([]);

    const patchLocale = ({ isEnabled, addonID }: { isEnabled: boolean; addonID: string }) => {
        patchAddonByIdThunk({ values: addonID, newConfig: { enabled: isEnabled, type: "locale" } });
    };

    // Get all the addons of type locale
    useEffect(() => {
        getAddonsByTypeThunk && getAddonsByTypeThunk({ values: "locale" });
    }, [getAddonsByTypeThunk]);

    useEffect(() => {
        if (allLocales.length > 0 && getAvailableLocalesThunk) {
            getAvailableLocalesThunk();
        }
    }, [allLocales, getAvailableLocalesThunk]);

    // Maintain state for enabled and default locales
    useEffect(() => {
        if (defaultLocaleCode && availableLocales) {
            setLocaleOptions(() => {
                return availableLocales.map((locale: ILocale) => ({
                    value: locale.localeKey,
                    label: locale.displayNames[defaultLocaleCode],
                }));
            });
        }
    }, [defaultLocaleCode, availableLocales]);

    return {
        allLocales,
        setLocale: patchLocale,
        localeOptions,
    };
};

const useLocaleTranslationService = (localeID: string) => {
    const serviceType = useSelector(
        (state: ICoreStoreState) => state.config.localeTranslationService[localeID]?.data?.translationService,
    );
    const translationServices = useSelector((state: ICoreStoreState) => state.config.machineTranslation.services.data);
    const [service, setService] = useState<{ name: string; type: string } | undefined>();

    useEffect(() => {
        if (serviceType !== undefined) {
            setService((prevState) => {
                const name = translationServices?.find(({ type }) => type === serviceType)?.name ?? "None";
                return {
                    name,
                    type: serviceType,
                };
            });
        }
    }, [serviceType, translationServices]);

    return service;
};

const useConfiguredTranslationServices = () => {
    const allServices = useSelector((state: ICoreStoreState) => state.config.machineTranslation.services.data);

    return allServices?.filter((service) => service.isConfigured === true) ?? null;
};

export const useLocaleConfig = ({ localeID }) => {
    const { getServicesByLocaleThunk, patchServicesByLocaleThunk } = useConfigActions();
    const translationService = useLocaleTranslationService(localeID);
    const configuredServices = useConfiguredTranslationServices();

    useEffect(() => {
        if (localeID && getServicesByLocaleThunk) {
            getServicesByLocaleThunk({ localeID });
        }
    }, [localeID, getServicesByLocaleThunk]);

    return {
        translationService,
        configuredServices,
        setTranslationService: patchServicesByLocaleThunk,
    };
};
