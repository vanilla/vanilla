/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import Addon, { IAddon } from "@library/addons/Addon";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { getMeta, setMeta, t } from "@library/utility/appUtils";
import React, { useDebugValue } from "react";

interface IProps extends Omit<IAddon, "onEnabledChange" | "enabled" | "isLoading"> {
    labName: string;
    themeFeatureName?: string;
}

export function VanillaLabsItem(props: IProps) {
    const { labName, themeFeatureName, ...passthru } = props;
    const { enabled, toggleEnabled, isLoading, isAddonFeatureEnabled } = useLab(labName, themeFeatureName);

    return (
        <Addon
            {...passthru}
            isLoading={isLoading}
            enabled={enabled}
            onEnabledChange={toggleEnabled}
            disabled={isAddonFeatureEnabled}
            disabledNote={t("This lab cannot be disabled because it is required by the current theme.")}
        />
    );
}

function useLab(labName: string, themeFeatureName?: string) {
    const configKey = `labs.${labName}`;
    const configs = useConfigsByKeys(["labs.*"]);
    const { isLoading: isPatchLoading, patchConfig } = useConfigPatcher();
    const isConfigLoading = configs.status === LoadStatus.LOADING;
    const isLoading = isPatchLoading || isConfigLoading;
    const metaKey = themeFeatureName ? `addonFeatures.${themeFeatureName}` : null;

    // Has an addon/theme forced the feature to be enabled.
    const isAddonFeatureEnabled = metaKey ? getMeta(metaKey, false) : false;
    const configEnabled = configs.data?.[configKey] ?? false;
    const enabled = isAddonFeatureEnabled || configEnabled;

    useDebugValue({
        configs,
        configKey,
        isLoading,
        isAddonFeatureEnabled,
        enabled,
    });

    function toggleEnabled() {
        if (isLoading) {
            return;
        }
        const newValue = !enabled;
        patchConfig({
            [configKey]: newValue,
        });
    }

    return {
        isLoading,
        isAddonFeatureEnabled,
        enabled,
        toggleEnabled,
    };
}
