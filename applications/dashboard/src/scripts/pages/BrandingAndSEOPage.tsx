/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * FIXME: [VNLA-1020] This page needs to appear within the new appearance tab
 * at: /appearance/branding
 */

import { ButtonTypes } from "@library/forms/buttonTypes";
import { css } from "@emotion/css";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { DashboardFormSkeleton } from "@dashboard/forms/DashboardFormSkeleton";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { LoadStatus } from "@library/@types/api/core";
import { MemoryRouter } from "react-router";
import { t } from "@vanilla/i18n";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import isEmpty from "lodash/isEmpty";
import isEqual from "lodash/isEqual";
import React, { useEffect, useMemo, useState } from "react";
import SmartLink from "@library/routing/links/SmartLink";

const BRANDING_SETTINGS: JsonSchema = {
    type: "object",
    properties: {
        "garden.homepageTitle": {
            type: "string",
            "x-control": {
                label: "Homepage Title",
                description:
                    "The homepage title is displayed on your home page. Pick a title that you would want to see appear in search engines.",
                inputType: "textBox",
            },
        },
        "garden.description": {
            type: "string",
            "x-control": {
                label: "Site Description",
                description:
                    "The site description usually appears in search engines. You should try having a description that is 100–150 characters long.",
                inputType: "textBox",
                type: "textarea",
            },
        },
        "garden.title": {
            type: "string",
            "x-control": {
                label: "Banner Title",
                description:
                    "This title appears on your site's banner and in your browser's title bar. It should be less than 20 characters. If a logo is uploaded, it will replace this title on user-facing forum pages. Also, keep in mind some themes may hide this title.",
                inputType: "textBox",
            },
        },
        "garden.orgName": {
            type: "string",
            "x-control": {
                label: "Organization",
                description: "Your organization name is used for SEO microdata and JSON+LD",
                inputType: "textBox",
            },
        },
        "branding.logo": {
            type: "string",
            "x-control": {
                label: "Logo",
                description:
                    "This logo appears at the top of your site. Themes made with the theme editor and some custom themes don't use this setting.",
                inputType: "upload",
            },
        },
        "branding.mobileLogo": {
            type: "string",
            "x-control": {
                label: "Mobile Logo",
                description:
                    "The mobile logo appears at the top of your site. Themes made with the theme editor and some custom themes don't use this setting.",
                inputType: "upload",
            },
        },
        "branding.bannerImage": {
            type: "string",
            "x-control": {
                label: "Banner Image",
                description:
                    "The default banner image across the site. This can be overridden on a per category basis.",
                inputType: "upload",
            },
        },
        "branding.favicon": {
            type: "string",
            "x-control": {
                label: "Favicon",
                description:
                    "Your site's favicon appears in your browser's title bar. It will be scaled down appropriately.",
                inputType: "upload",
            },
        },
        "branding.touchIcon": {
            type: "string",
            "x-control": {
                label: "Touch Icon",
                description:
                    "The touch icon appears when you bookmark a website on the homescreen of a mobile device. These are usually 152 pixels.",
                inputType: "upload",
            },
        },
        "branding.shareImage": {
            type: "string",
            "x-control": {
                label: "Share Image",
                description:
                    "When someone shares a link from your site we try and grab an image from the page. If there isn't an image on the page then we'll use this image instead. The image should be at least 50×50, but we recommend 200×200.",
                inputType: "upload",
            },
        },
        "branding.addressBarColor": {
            type: "string",
            "x-control": {
                label: "Address Bar Color",
                description: "Some browsers support a color for the address bar.",
                inputType: "color",
            },
        },
        "labs.deferredLegacyScripts": {
            type: "boolean",
            "x-control": {
                label: "Defer Javascript Loading",
                description: (
                    <>
                        This setting loads the page before executing Javascript which can improve your SEO.
                        <b>**Warning: Enabling this feature may cause Javascript errors on your site.**</b>
                        <br />
                        <SmartLink
                            to={"https://success.vanillaforums.com/kb/articles/140-defer-javascript-loading-feature"}
                        >
                            More information
                        </SmartLink>
                    </>
                ),
                inputType: "toggle",
            },
        },
        "forum.disabled": {
            type: "boolean",
            "x-control": {
                label: "Disable forum pages",
                description:
                    "Remove discussion and categories links from menus. Set discussion and category related pages to return not found page 404.",
                inputType: "toggle",
            },
        },
    },
};

const BrandingAndSEOPageClasses = {
    layout: css({
        "& > li > div:last-child > label": {
            float: "right",
        },
    }),
    button: css({
        marginTop: 18,
        float: "right",
    }),
};

export function BrandingAndSEOPage() {
    const { isLoading: isPatchLoading, patchConfig } = useConfigPatcher();

    // A setting values loadable for the schema from the `/config` endpoint
    const settings = useConfigsByKeys(Object.keys(BRANDING_SETTINGS["properties"]));

    // Load state for the setting values
    const isLoaded = useMemo<boolean>(() => [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status), [
        settings,
    ]);

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
                    if (!isEqual(value[currentKey], settings.data[currentKey])) {
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
    }, [isLoaded, settings]);

    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("Branding & SEO")} />
            <section className={BrandingAndSEOPageClasses.layout}>
                {isLoaded ? (
                    <>
                        <JsonSchemaForm
                            schema={BRANDING_SETTINGS}
                            instance={value}
                            FormControlGroup={DashboardFormControlGroup}
                            FormControl={DashboardFormControl}
                            onChange={setValue}
                        />
                        <Button
                            className={BrandingAndSEOPageClasses.button}
                            buttonType={ButtonTypes.OUTLINE}
                            onClick={() => handleSubmit()}
                            disabled={isPatchLoading || !Object.keys(dirtySettings).length}
                        >
                            {isPatchLoading ? <ButtonLoader buttonType={ButtonTypes.DASHBOARD_PRIMARY} /> : t("Save")}
                        </Button>
                    </>
                ) : (
                    [...new Array(4)].map((_, index) => <DashboardFormSkeleton key={index} />)
                )}
            </section>
            <DashboardHelpAsset>
                <h3>{t("Heads up!")}</h3>
                <p>
                    {t(
                        "Spend a little time thinking about how you describe your site here. Giving your site a meaningful title and concise description could help your position in search engines.",
                    )}
                </p>
                <h3>{t("Need more help?")}</h3>
                <p>
                    <SmartLink to="settings/tutorials/appearance">
                        {t("Video tutorial on managing appearance")}
                    </SmartLink>
                </p>
            </DashboardHelpAsset>
        </MemoryRouter>
    );
}
