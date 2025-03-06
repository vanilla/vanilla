/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import LayoutPreviewList from "@dashboard/appearance/components/LayoutPreviewList";
import { AppearanceNav } from "@dashboard/components/navigation/AppearanceNav";
import { layoutOptionsClasses } from "@dashboard/appearance/pages/LayoutOptions.classes";
import AdminLayout from "@dashboard/components/AdminLayout";
import AdminTitleBar from "@dashboard/components/AdminTitleBar";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import InputBlock from "@library/forms/InputBlock";
import RadioButton from "@library/forms/RadioButton";
import { RadioGroupContext } from "@library/forms/RadioGroupContext";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Loader from "@library/loaders/Loader";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { notEmpty } from "@vanilla/utils";
import React, { useEffect, useState } from "react";
import { sprintf } from "sprintf-js";
import { AppearanceAdminLayout } from "@dashboard/components/navigation/AppearanceAdminLayout";

interface IFormProps {
    legacyLayoutTypes?: Array<{
        type: string;
        label: string;
        thumbnailComponent: React.ComponentType;
        editUrl?: string;
    }>;
    layoutTypeLabel: string;
    customLayoutConfigKey: string;
    legacyLayoutConfigKey?: string;
    configs: Record<string, any>;
    onChange: (newConfigs: Record<string, any>) => void;
    legacyTitle: React.ReactNode;
    legacyDescription: React.ReactNode;
    radios: {
        legendLabel: React.ReactNode;
        legacyLabel: React.ReactNode;
        customLabel: React.ReactNode;
    };
}

function LegacyLayoutForm(props: IFormProps) {
    const { configs, onChange, customLayoutConfigKey, legacyLayoutConfigKey, radios } = props;

    const isCustom = configs[props.customLayoutConfigKey] === true;
    let rawLegacyLayout = props.legacyLayoutConfigKey ? configs[props.legacyLayoutConfigKey] ?? null : null;
    // Support the home route format.
    let isRouteConfig = legacyLayoutConfigKey === "routes.defaultController";

    const appliedLegacyLayout = isRouteConfig
        ? Array.isArray(rawLegacyLayout)
            ? rawLegacyLayout?.[0]
            : null
        : rawLegacyLayout;

    const classes = layoutOptionsClasses();

    return (
        <>
            <InputBlock legend={radios.legendLabel}>
                <RadioGroupContext.Provider
                    value={{
                        value: isCustom ? "custom" : "legacy",
                        onChange: (value) => {
                            onChange({
                                [customLayoutConfigKey]: value === "custom",
                            });
                        },
                    }}
                >
                    <RadioButton label={radios.customLabel} value={"custom"} />
                    <RadioButton label={radios.legacyLabel} value={"legacy"} />
                </RadioGroupContext.Provider>
            </InputBlock>
            {props.legacyLayoutTypes && legacyLayoutConfigKey && (
                <div className={cx(classes.legacyOptions, { disabled: isCustom })}>
                    <PageHeadingBox
                        depth={3}
                        title={
                            <span className={classes.legacyOptionTitle}>
                                <span className="disablable">{props.legacyTitle}</span>
                                {isCustom && (
                                    <ToolTip
                                        label={
                                            <Translate
                                                source="Legacy Layouts are unavailable while using <0/>"
                                                c0={<strong>{radios.customLabel}</strong>}
                                            />
                                        }
                                    >
                                        <ToolTipIcon className={classes.legacyOptionsTooltip}>
                                            <Icon icon="info" />
                                        </ToolTipIcon>
                                    </ToolTip>
                                )}
                            </span>
                        }
                        description={<span className="disablable">{props.legacyDescription}</span>}
                    ></PageHeadingBox>
                    <LayoutPreviewList
                        className="disablable"
                        options={props.legacyLayoutTypes.map((option) => {
                            return {
                                label: option.label,
                                thumbnailComponent: option.thumbnailComponent,
                                active: !isCustom && appliedLegacyLayout === option.type,
                                onApply: () => {
                                    onChange({
                                        [legacyLayoutConfigKey]: isRouteConfig
                                            ? [option.type, "Internal"]
                                            : option.type,
                                    });
                                },
                            };
                        })}
                    />
                </div>
            )}
        </>
    );
}

interface IProps extends Omit<IFormProps, "configs" | "onChange"> {
    title: React.ReactNode;
    description?: React.ReactNode;
}

export function LegacyLayoutFormPage(props: IProps) {
    const initialConfigs = useConfigsByKeys(
        [props.legacyLayoutConfigKey, props.customLayoutConfigKey].filter(notEmpty),
    );
    const [configs, setConfigs] = useState<Record<string, any>>({});
    const [showWarning, setShowWarning] = useState(false);
    useEffect(() => {
        if (initialConfigs.data) {
            setConfigs(initialConfigs.data);
        }
    }, [initialConfigs.data]);

    const configPatcher = useConfigPatcher();

    const device = useTitleBarDevice();
    const isCompact = device === TitleBarDevices.COMPACT;

    const content = (() => {
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(initialConfigs.status)) {
            return <Loader />;
        }

        if (!initialConfigs.data || initialConfigs.error) {
            return <ErrorMessages errors={[initialConfigs.error].filter(notEmpty)} />;
        }

        return (
            <>
                <LegacyLayoutForm
                    {...props}
                    configs={configs}
                    onChange={(newConfigs) => {
                        setConfigs({
                            ...configs,
                            ...newConfigs,
                        });
                    }}
                />
                <ModalConfirm
                    size={ModalSizes.MEDIUM}
                    isVisible={showWarning}
                    title={sprintf(t("Change %s"), props.radios.legendLabel)}
                    onCancel={() => setShowWarning(false)}
                    onConfirm={() => {
                        void configPatcher.patchConfig(configs);
                        setShowWarning(false);
                    }}
                >
                    <strong>{sprintf(t("Are you sure you want to use %s?"), props.radios.customLabel)}</strong>
                    <p>
                        <Translate
                            source="If you proceed, any <0/> that do not have a custom layout pre-applied will have the default layouts applied."
                            c0={<strong>{props.layoutTypeLabel}</strong>}
                        />
                    </p>
                </ModalConfirm>
            </>
        );
    })();
    return (
        <AppearanceAdminLayout
            customTitleBar={
                <AdminTitleBar
                    useTwoColumnContainer
                    title={props.title}
                    description={
                        <>
                            <Translate
                                source="Change layout versions for the <0/>."
                                c0={<strong>{props.layoutTypeLabel}</strong>}
                            />{" "}
                            <Translate
                                source={"To learn more, <0>see the documentation</0>."}
                                c0={(content) => (
                                    <SmartLink to={"https://success.vanillaforums.com/kb/articles/430"}>
                                        {content}
                                    </SmartLink>
                                )}
                            />
                        </>
                    }
                    actions={
                        <Button
                            buttonType={ButtonTypes.OUTLINE}
                            onClick={() => {
                                if (
                                    configs[props.customLayoutConfigKey] === true &&
                                    !initialConfigs.data?.[props.customLayoutConfigKey]
                                ) {
                                    setShowWarning(true);
                                    return;
                                }
                                void configPatcher.patchConfig(configs);
                            }}
                            disabled={configPatcher.isLoading}
                        >
                            {configPatcher.isLoading ? <ButtonLoader /> : t("Save")}
                        </Button>
                    }
                />
            }
            content={content}
        />
    );
}
