/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import { BrandingAndSEOPageClasses } from "@dashboard/appearance/pages/BrandingAndSEOPage.classes";
import AdminLayout from "@dashboard/components/AdminLayout";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import PanelWidget from "@library/layout/components/PanelWidget";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@vanilla/i18n";
import { JsonSchemaForm, JSONSchemaType } from "@vanilla/json-schema-forms";
import { extractDataByKeyLookup } from "@vanilla/json-schema-forms/src/utils";
import { useCollisionDetector } from "@vanilla/react-utils";
import { useFormik } from "formik";
import React, { useEffect, useMemo } from "react";

const BRANDING_SETTINGS: JSONSchemaType<{
    "garden.homepageTitle": string;
    "garden.description": string;
    "garden.title": string;
    "garden.orgName": string;
    "branding.logo": string;
    "branding.mobileLogo": string;
    "branding.bannerImage": string;
    "branding.favicon": string;
    "branding.touchIcon": string;
    "branding.shareImage": string;
    "branding.addressBarColor": string;
    "seo.metaHtml": string;
    "labs.deferredLegacyScripts": boolean;
    "forum.disabled": boolean;
}> = {
    type: "object",
    properties: {
        "garden.homepageTitle": {
            type: "string",
            nullable: true,
            minLength: 1,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 350,
            default: "",
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
            nullable: true,
            minLength: 1,
            default: "",
            "x-control": {
                label: t("Banner Title"),
                description: t(
                    "This title appears on your site's banner and in your browser's title bar. If a logo is uploaded, it will replace this title on user-facing forum pages. Also, keep in mind some themes may hide this title.",
                ),
                inputType: "textBox",
                default: "Vanilla",
            },
        },
        "garden.orgName": {
            type: "string",
            nullable: true,
            maxLength: 50,
            default: "",
            "x-control": {
                label: t("Organization"),
                description: t("Your organization name is used for SEO microdata and JSON+LD"),
                inputType: "textBox",
            },
        },
        "branding.logo": {
            type: "string",
            nullable: true,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 500,
            default: "",
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
            nullable: true,
            maxLength: 9,
            default: "",
            "x-control": {
                label: t("Address Bar Color"),
                description: t("Some browsers support a color for the address bar."),
                inputType: "color",
            },
        },
        "seo.metaHtml": {
            type: "string",
            nullable: true,
            default: "",
            "x-control": {
                label: t("Meta Tags"),
                description: t(
                    "Meta Tags are used for domain verification for Google Search Console and other services. Copy the required Meta Tags from your source and paste onto a new line.",
                ),
                inputType: "codeBox",
            },
        },
        "labs.deferredLegacyScripts": {
            type: "boolean",
            nullable: true,
            default: false,
            "x-control": {
                label: t("Defer Javascript Loading"),
                description: `${t(
                    "This setting loads the page before executing Javascript which can improve your SEO.",
                )}.<b>${t(
                    "**Warning: Enabling this feature may cause Javascript errors on your site.**",
                )}</b><br/><a href="https://success.vanillaforums.com/kb/articles/140-defer-javascript-loading-feature">${t(
                    "More information",
                )}</a>`,
                inputType: "toggle",
            },
        },
        "forum.disabled": {
            type: "boolean",
            nullable: true,
            default: false,
            "x-control": {
                label: t("Disable Forum Pages"),
                description: t(
                    "Remove discussion and categories links from menus. Set discussion and category related pages to return not found page 404.",
                ),
                inputType: "toggle",
            },
        },
    },
    required: [],
};

export default function BrandingAndSEOPage() {
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();

    // A setting values loadable for the schema from the `/config` endpoint
    const settings = useConfigsByKeys(Object.keys(BRANDING_SETTINGS["properties"]));

    const toast = useToast();

    useEffect(() => {
        // When we first receive an error message add a toast.
        if (error?.message) {
            toast.addToast({
                dismissible: true,
                body: <>{error.message}</>,
            });
        }
    }, [error?.message]);

    // Load state for the setting values
    const isLoaded = [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status);

    const { values, submitForm, setValues } = useFormik<object>({
        initialValues:
            isLoaded && !!settings.data ? settings.data : extractDataByKeyLookup(BRANDING_SETTINGS, "default"),

        enableReinitialize: true,

        onSubmit: async (values) => {
            await patchConfig(values);
        },
    });

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
                    onClick={async () => {
                        await submitForm();
                    }}
                    disabled={isPatchLoading || !isLoaded}
                >
                    {isPatchLoading ? <ButtonLoader buttonType={ButtonTypes.DASHBOARD_PRIMARY} /> : t("Save")}
                </Button>
            }
            leftPanel={!isCompact && <AppearanceNav />}
            contentClassNames={cx(BrandingAndSEOPageClasses.layout)}
            content={
                <section>
                    <JsonSchemaForm
                        disabled={!isLoaded}
                        fieldErrors={error?.errors ?? {}}
                        schema={BRANDING_SETTINGS}
                        instance={values}
                        FormControlGroup={DashboardFormControlGroup}
                        FormControl={DashboardFormControl}
                        onChange={setValues}
                    />
                </section>
            }
            rightPanel={
                <PanelWidget>
                    <h3>{t("Heads up!")}</h3>
                    <p>
                        {t(
                            "Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.",
                        )}
                    </p>
                </PanelWidget>
            }
        />
    );
}
