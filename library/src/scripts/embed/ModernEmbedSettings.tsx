/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import isEqual from "lodash/isEqual";
import isEmpty from "lodash/isEmpty";
import React, { useEffect, useMemo, useState } from "react";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { siteUrl, t } from "@library/utility/appUtils";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { userContentClasses } from "@library/content/UserContent.styles";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";

const EMBED_SETTINGS: JsonSchema = {
    type: "object",
    properties: {
        "embed.enabled": {
            type: "boolean",
            default: false,
            "x-control": {
                label: "Embed My Community",
                description:
                    "Allow your community to be embedded inside of another site. If you aren't making use of this feature it's recommended to leave it off.",
                inputType: "toggle",
            },
        },
        "embed.remoteUrl": {
            type: "string",
            maxLength: 350,
            "x-control": {
                label: "Remote URL",
                description: "This should be a full URL to the site that you will be embedding your community in.",
                placeholder: "https://my-site.com/forum",
                inputType: "textBox",
            },
        },
        "embed.forceEmbed": {
            type: "boolean",
            default: false,
            "x-control": {
                label: "Force Embedding",
                description: "If enabled, visitors of the site will be redirected to the Remote URL.",
                inputType: "toggle",
            },
        },
    },
};

export default function ModernEmbedSettings() {
    const { isLoading: isPatchLoading, patchConfig } = useConfigPatcher();

    // A setting values loadable for the schema from the `/config` endpoint
    const settings = useConfigsByKeys(Object.keys(EMBED_SETTINGS["properties"]));

    // Load state for the setting values
    const isLoaded = useMemo<boolean>(
        () => [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status),
        [settings],
    );

    const [value, setValue] = useState<JsonSchema>(
        Object.fromEntries(
            Object.keys(EMBED_SETTINGS["properties"]).map((key) => [
                key,
                EMBED_SETTINGS["properties"]["type"] === "boolean" ? false : "",
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
    }, [isLoaded, settings]);

    const settingsSchema = useMemo<JsonSchema>(() => {
        if (isLoaded) {
            return EMBED_SETTINGS;
        }
        const disabledProperties = Object.fromEntries(
            Object.keys(EMBED_SETTINGS?.properties).map((key) => {
                return [key, { ...EMBED_SETTINGS.properties[key], disabled: true }];
            }),
        );

        return { ...EMBED_SETTINGS, properties: disabledProperties };
    }, [isLoaded]);

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                handleSubmit();
            }}
        >
            <DashboardHelpAsset>
                <h3>{t("What is Embedding")}</h3>
                <p>
                    {t(
                        "Sometimes you want to embed your Vanilla site",
                        "Sometimes you want to embed your Vanilla site inside of another site. Vanilla's embed system offers an easy way to that. There are performance tradeoffs when using an embedded site, so check the documentation for alternatives and ideal use cases.",
                    )}
                </p>
                <p>
                    <SmartLink to="">{t("See the documentation")}</SmartLink>
                </p>
                <h3>{t("Looking for the Old Embed System?")}</h3>
                <p>
                    <Translate
                        source={`Disable the "New Embed System" lab on the <0>Vanilla Labs Page</0>`}
                        c0={(content) => <SmartLink to="/settings/labs">{content}</SmartLink>}
                    />
                </p>
            </DashboardHelpAsset>
            <DashboardHeaderBlock
                title={t("Embed Settings")}
                actionButtons={<Button type="submit">{isPatchLoading ? <ButtonLoader /> : t("Save")}</Button>}
            ></DashboardHeaderBlock>
            <JsonSchemaForm
                schema={settingsSchema}
                instance={value}
                FormControlGroup={DashboardFormControlGroup}
                FormControl={DashboardFormControl}
                onChange={setValue}
            />
            <h2>Usage</h2>
            <div className={userContentClasses().root}>
                <pre className="code codeBlock">
                    {`<!-- Vanilla's Embed Javascript.-->
<script defer src="${siteUrl("/api/v2/assets/embed-script")}"></script>
<!--
PARAMETER DOCUMENTATION

\`remote-url\` - This should be the base url of the site you are embedding. REQUIRED
  - For hub/node sites you have 2 options. If you leave the /hub or node slug on the URL,
    The embed will only work for that node. To make your embed work for all nodes, remove the /hub or node slug.
\`initial-path\` - The initial path the embed should start on if there isn't one in the URL already.
\`position\` - This has two possible values
  - \`sticky\` - The community content will stick to the top of the page when scrolling, pushing the header out of the viewport. DEFAULT
  - \`static\` - Users will have to scroll outside of the community to scroll the header off of the page.
  - \`sso-string\` - Pass a community members unique string to provide them access through SSO or define it on the \`vanilla_sso\` variable in the window object of the page

NOTES
- The \`height: 100vh\` is recommended for the best user experience.
-->
<vanilla-embed
    remote-url="${siteUrl("")}"
    style="height: 100vh"
    position="sticky"
>
    <noscript>Enable Javascript to view this Community.</noscript>
</vanilla-embed>`}
                </pre>
            </div>
        </form>
    );
}
