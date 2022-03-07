/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { compatibilityStyles } from "@dashboard/compatibilityStyles";
import { applyCompatibilityIcons } from "@dashboard/compatibilityStyles/compatibilityIcons";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { LoadStatus } from "@library/@types/api/core";
import { initAllUserContent } from "@library/content";
import { applyCompatibilityUserCards } from "@library/features/userCard/UserCard.compat";
import { NO_WRAPPER_CONFIG, useStoryConfig } from "@library/storybook/StoryContext";
import { onReady, setMeta, _executeReady } from "@library/utility/appUtils";
import { loadedCSS } from "@rich-editor/quill/components/loadedStyles";
import { storiesOf } from "@storybook/react";
import { applySharedPortalContext } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useEffect, useRef } from "react";
import "./windowGlobalsKludge";
import "../../addons/themes/theme-foundation/src/scss/custom.scss";
import "../../applications/vanilla/src/scripts/entries/forum";
import "../../plugins/rich-editor/src/scripts/entries/forum";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

///
/// These imports are just so that the files get loaded into storybook.
/// They may be dynamically added through some of the legacy HTML or CSS.
///
require("!file-loader?name=[path][name].[ext]!../../resources/fonts/vanillicon/vanillicon.ttf");
require("../../applications/dashboard/design/images/defaulticon.png");
allContextFiles(
    require.context("!file-loader?name=[path][name].[ext]!../../applications", true, /\/design\/.*\.css$/),
    "applications",
);
allContextFiles(
    require.context("!file-loader?name=[path][name].[ext]!../../cloud/applications", true, /\/design\/.*\.css$/),
    "cloud/applications",
);
allContextFiles(
    require.context("!file-loader?name=[path][name].[ext]!../../plugins", true, /\/design\/.*\.css$/),
    "plugins",
);
allContextFiles(
    require.context("!file-loader?name=[path][name].[ext]!../../cloud/plugins", true, /\/design\/.*\.css$/),
    "cloud/plugins",
);
allContextFiles(
    require.context("!file-loader?name=[path][name].[ext]!../../resources", true, /\/design\/.*\.css$/),
    "resources",
);

const specialSymlinkedCSS = ["/applications/groups", "/plugins/privatediscussions"];

const allHtmls = allContextFiles(require.context("../.storybookAppPages", false, /.*\.html/));
const allJsons = allContextFiles(require.context("../.storybookAppPages", false, /.*\.json/));

const storyOfsByFirstWord = [];
Object.entries(allHtmls).forEach(([name, htmlModule]) => {
    const html = (htmlModule as any).default;
    const data = getDataForHtml(name);

    const prettyName = name.replace("./", "").replace(".html", "");
    const [firstWord, ...otherWords] = prettyName.split(" ");
    let storyOf = storyOfsByFirstWord[firstWord];
    if (!storyOf) {
        storyOf = storiesOf(`Foundation/${firstWord}`, module);
        storyOfsByFirstWord[firstWord] = storyOf;
    }
    const storyName = otherWords.join(" ");

    storyOf.add(
        storyName,
        () => {
            return <HtmlRenderComponent html={html} data={data} />;
        },
        {
            chromatic: {
                viewports: [1300],
            },
        },
    );
});

function HtmlRenderComponent(props: { html: string; data: IHtmlData }) {
    const { html, data } = props;

    useStoryConfig({
        ...NO_WRAPPER_CONFIG,
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: true,
                        permissions: [],
                    },
                },
            },
        },
    });

    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        applySharedPortalContext((props) => {
            return <Provider store={getStore()}>{props.children}</Provider>;
        });
    }, []);

    useEffect(() => {
        compatibilityStyles();
        applyCompatibilityIcons();
        applyCompatibilityUserCards();
        loadedCSS();
        initAllUserContent();
        setMeta("themeFeatures.NewQuickLinks", true);

        // Add HTML links.
        const dynamicCssFiles = data.cssFiles;

        dynamicCssFiles.reverse().forEach((file) => {
            for (const specialDir of specialSymlinkedCSS) {
                if (file.startsWith(specialDir)) {
                    file = `/cloud${file}`;
                }
            }
            const existingStylesheet = document.querySelector(`link[href="${file}"]`);
            if (existingStylesheet) {
                return;
            }
            const link = document.createElement("link");
            link.setAttribute("href", file);
            link.setAttribute("rel", "stylesheet");
            document.head.insertBefore(link, document.head.firstElementChild);
        });

        // Kludge to prevent some flashing during loading.
        cssOut(".Flyout", { display: "none !important" });

        // Copy body classes.
        document.body.className = classNames(document.body.className, data.bodyClasses);

        _executeReady();
    }, []);
    return <div ref={ref} dangerouslySetInnerHTML={{ __html: html }} />;
}

type ContextFiles = Record<string, any>;
interface IHtmlData {
    cssFiles: string[];
    bodyClasses: string;
}

/**
 * Get the metadata about an HTML fixture.
 */
function getDataForHtml(name: string): IHtmlData {
    const jsonName = name.replace(".html", ".json");
    const json = allJsons[jsonName];
    if (!json) {
        console.error(`Failed to find json data for HTML file: ${name}`);
        return {
            cssFiles: [],
            bodyClasses: "",
        };
    }
    return json;
}

/**
 * Have webpack load all files matching a context.
 *
 * @param context
 * @param prefix
 */
function allContextFiles(context: __WebpackModuleApi.RequireContext, prefix?: string): ContextFiles {
    let keys = context.keys();
    let values = keys.map(context);
    return keys.reduce((o, k, i) => {
        if (prefix) {
            k = k.replace("./", `./${prefix}/`);
        }
        o[k] = values[i];
        return o;
    }, {});
}
