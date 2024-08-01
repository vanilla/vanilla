/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import Addon, { IAddon } from "@library/addons/Addon";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { useToast } from "@library/features/toaster/ToastContext";
import { getMeta, setMeta, t } from "@library/utility/appUtils";
import React, { useDebugValue, useMemo, useState } from "react";

interface IProps extends Omit<IAddon, "onEnabledChange" | "enabled" | "isLoading"> {
    labName: string;
    themeFeatureName?: string;
    reloadPageAfterToggle?: boolean; //in some cases we need tu reload the page so the legacy links (or other code) update
    disabled?: boolean;
    disabledNote?: string;
}

export function VanillaLabsItem(props: IProps) {
    const { labName, themeFeatureName, reloadPageAfterToggle, disabled, disabledNote, ...passthru } = props;
    const { enabled, toggleEnabled, isLoading, isAddonFeatureEnabled } = useLab(
        labName,
        themeFeatureName,
        reloadPageAfterToggle,
    );

    const disabledProps = useMemo(() => {
        if (disabled) {
            return {
                disabled,
                disabledNote,
            };
        }
        return {
            disabled: isAddonFeatureEnabled,
            disabledNote: t("This lab cannot be disabled because it is required by the current theme."),
        };
    }, [isAddonFeatureEnabled, disabled, disabledNote]);

    return (
        <Addon
            {...passthru}
            isLoading={isLoading}
            enabled={enabled}
            onEnabledChange={toggleEnabled}
            {...disabledProps}
        />
    );
}

function useLab(labName: string, themeFeatureName?: string, reloadPageAfterToggle?: boolean) {
    const configKey = `labs.${labName}`;
    const configs = useConfigsByKeys(["labs.*"]);
    const { isLoading: isPatchLoading, patchConfig } = useConfigPatcher();
    const isConfigLoading = configs.status === LoadStatus.LOADING;
    const isLoading = isPatchLoading || isConfigLoading;
    const metaKey = themeFeatureName ? `addonFeatures.${themeFeatureName}` : null;

    const toast = useToast();

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

    async function toggleEnabled() {
        if (isLoading) {
            return;
        }
        const newValue = !enabled;
        await patchConfig({
            [configKey]: newValue,
        });

        if (reloadPageAfterToggle) {
            toast.addToast({
                dismissible: true,
                body: <>{t("Reload the page to apply these changes.")}</>,
            });
        }
    }

    return {
        isLoading,
        isAddonFeatureEnabled,
        enabled,
        toggleEnabled,
    };
}
