/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@emotion/css";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { LoadStatus } from "@library/@types/api/core";
import { t } from "@vanilla/i18n";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import AdminLayout from "@dashboard/components/AdminLayout";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import isEmpty from "lodash/isEmpty";
import isEqual from "lodash/isEqual";
import React, { useEffect, useMemo, useState } from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { BrandingAndSEOPageClasses } from "@dashboard/appearance/pages/BrandingAndSEOPage.classes";
import { useToast } from "@library/features/toaster/ToastContext";

const BRANDING_SETTINGS: JsonSchema = {
    type: "object",
    properties: {
        "garden.homepageTitle": {
            type: "string",
            minLength: 1,
            maxLength: 500,
            "x-control": {
                label: t("Homepage Title"),
                description: t(
                    "The homepage title is displayed on your home page. Pick a title that you would want to see appear in search engines.",
                ),
                inputType: "textBox",
            },
            errorMessage: t("Homepage titles can only be between 1 and 500 characters"),
        },
        "garden.description": {
            type: "string",
            maxLength: 350,
            "x-control": {
                label: t("Site Description"),
                description: t(
                    "The site description usually appears in search engines. You should try having a description that is 100-150 characters long.",
                ),
                inputType: "textBox",
                type: "textarea",
            },
        },
        "garden.title": {
            type: "string",
            maxLength: 20,
            minLentgh: 1,
            "x-control": {
                label: t("Banner Title"),
                description: t(
                    "This title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a logo is uploaded, it will replace this title on user-facing forum pages. Also, keep in mind some themes may hide this title.",
                ),
                inputType: "textBox",
                default: "Vanilla",
            },
        },
        "garden.orgName": {
            type: "string",
            maxLength: 50,
            "x-control": {
                label: t("Organization"),
                description: t("Your organization name is used for SEO microdata and JSON+LD"),
                inputType: "textBox",
            },
        },
        "branding.logo": {
            type: "string",
            maxLength: 500,
            "x-control": {
                label: t("Logo"),
                description: t(
                    "This logo appears at the top of your site. Themes made with the theme editor and some custom themes don't use this setting.",
                ),
                inputType: "upload",
            },
        },
        "branding.mobileLogo": {
            type: "string",
            maxLength: 500,
            "x-control": {
                label: t("Mobile Logo"),
                description: t(
                    "The mobile logo appears at the top of your site. Themes made with the theme editor and some custom themes don't use this setting.",
                ),
                inputType: "upload",
            },
        },
        "branding.bannerImage": {
            type: "string",
            maxLength: 500,
            "x-control": {
                label: t("Banner Image"),
                description: t(
                    "The default banner image across the site. This can be overridden on a per category basis.",
                ),
                inputType: "upload",
            },
        },
        "branding.favicon": {
            type: "string",
            maxLength: 500,
            "x-control": {
                label: t("Favicon"),
                description: t(
                    "Your site's favicon appears in your browser's title bar. It will be scaled down appropriately.",
                ),
                inputType: "upload",
            },
        },
        "branding.touchIcon": {
            type: "string",
            maxLength: 500,
            "x-control": {
                label: t("Touch Icon"),
                description: t(
                    "The touch icon appears when you bookmark a website on the homescreen of a mobile device. These are usually 152 pixels.",
                ),
                inputType: "upload",
            },
        },
        "branding.shareImage": {
            type: "string",
            maxLength: 500,
            "x-control": {
                label: t("Share Image"),
                description: t(
                    "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50×50, but we recommend 200×200.",
                ),
                inputType: "upload",
            },
        },
        "branding.addressBarColor": {
            type: "string",
            maxLength: 9,
            "x-control": {
                label: t("Address Bar Color"),
                description: t("Some browsers support a color for the address bar."),
                inputType: "color",
            },
        },
        "labs.deferredLegacyScripts": {
            type: "boolean",
            "x-control": {
                label: t("Defer Javascript Loading"),
                description: (
                    <>
                        {t("This setting loads the page before executing Javascript which can improve your SEO.")}{" "}
                        <b>{t("**Warning: Enabling this feature may cause Javascript errors on your site.**")}</b>
                        <br />
                        <SmartLink
                            to={"https://success.vanillaforums.com/kb/articles/140-defer-javascript-loading-feature"}
                        >
                            {t("More information")}
                        </SmartLink>
                    </>
                ),
                inputType: "toggle",
            },
        },
        "forum.disabled": {
            type: "boolean",
            "x-control": {
                label: t("Disable Forum Pages"),
                description: t(
                    "Remove discussion and categories links from menus. Set discussion and category related pages to return not found page 404.",
                ),
                inputType: "toggle",
            },
        },
    },
};

export default function BrandingAndSEOPage() {
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();

    // A setting values loadable for the schema from the `/config` endpoint
    const settings = useConfigsByKeys(Object.keys(BRANDING_SETTINGS["properties"]));

    const toast = useToast();

    // Load state for the setting values
    const isLoaded = useMemo<boolean>(
        () => [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status),
        [settings],
    );

    const [value, setValue] = useState<JsonSchema>(
        Object.fromEntries(
            Object.keys(BRANDING_SETTINGS["properties"]).map((key) => [
                key,
                BRANDING_SETTINGS["properties"]["type"] === "boolean" ? false : "",
            ]),
        ),
    );

    const dirtySettings = useMemo(() => {
        if (settings.data) {
            return Object.keys(value).reduce(
                (delta: { [key: string]: string | number | boolean }, currentKey: string) => {
                    if (!isEqual(value[currentKey], settings.data?.[currentKey])) {
                        return { ...delta, [currentKey]: value[currentKey] };
                    }
                    return delta;
                },
                {},
            );
        }
        return {};
    }, [settings, value]);

    const handleSubmit = () => {
        if (!isEmpty(dirtySettings)) {
            patchConfig(dirtySettings);
        }
    };

    useEffect(() => {
        if (isLoaded) {
            setValue(() => {
                return Object.fromEntries(Object.keys(settings.data ?? {}).map((key) => [key, settings?.data?.[key]]));
            });
        }
        if (error && error.message) {
            toast.addToast({
                dismissible: true,
                body: <>{error.message}</>,
            });
        }
    }, [isLoaded, settings, error]);

    const settingsSchema = useMemo<JsonSchema>(() => {
        if (isLoaded) {
            return BRANDING_SETTINGS;
        }
        const disabledProperties = Object.fromEntries(
            Object.keys(BRANDING_SETTINGS?.properties).map((key) => {
                return [key, { ...BRANDING_SETTINGS.properties[key], disabled: true }];
            }),
        );

        return { ...BRANDING_SETTINGS, properties: disabledProperties };
    }, [isLoaded]);

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    return (
        <AdminLayout
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            activeSectionID={"appearance"}
            title={t("Branding & SEO")}
            titleBarActions={
                <Button
                    buttonType={ButtonTypes.OUTLINE}
                    onClick={() => handleSubmit()}
                    disabled={isPatchLoading || !Object.keys(dirtySettings).length}
                >
                    {isPatchLoading ? <ButtonLoader buttonType={ButtonTypes.DASHBOARD_PRIMARY} /> : t("Save")}
                </Button>
            }
            leftPanel={!isCompact && <AppearanceNav />}
            contentClassNames={cx(BrandingAndSEOPageClasses.layout)}
            content={
                <section>
                    <JsonSchemaForm
                        schema={settingsSchema}
                        instance={value}
                        FormControlGroup={DashboardFormControlGroup}
                        FormControl={DashboardFormControl}
                        onChange={setValue}
                    />
                </section>
            }
            rightPanel={
                <>
                    <h3>{t("Heads up!")}</h3>
                    <p>
                        {t(
                            "Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.",
                        )}
                    </p>
                </>
            }
        />
    );
}
