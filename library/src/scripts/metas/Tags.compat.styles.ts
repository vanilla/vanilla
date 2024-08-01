/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { negative } from "@library/styles/styleHelpers";
import { Mixins } from "@library/styles/Mixins";
import { tagCloudVariables, TagListStyle, tagPresetVariables, tagsVariables } from "@library/metas/Tags.variables";
import { important, percent } from "csx";
import { injectGlobal } from "@emotion/css";
import { tagMixin } from "@library/metas/Tags.styles";
import { metasVariables } from "@library/metas/Metas.variables";

export const forumTagCSS = () => {
    const tagCloudVars = tagCloudVariables();
    const tagItemWidth = tagCloudVars.type === TagListStyle.LIST ? "100%" : "auto";
    const tagItemListStyle = tagCloudVars.type === TagListStyle.LIST ? Mixins.flex.spaceBetween() : {};

    injectGlobal({
        [`.TagCloud`]: {
            ...Mixins.margin({
                vertical: negative(tagCloudVars.margin.vertical),
                horizontal: negative(tagCloudVars.margin.horizontal),
            }),
            padding: "0 !important",
        },
    });

    const tagsVars = tagsVariables();
    const presets = tagPresetVariables();

    injectGlobal({
        ".TagCloud li.TagCloud-Item.TagCloud-Item.TagCloud-Item": {
            padding: 0,
            ...Mixins.margin(tagCloudVars.margin),
            width: tagItemWidth,
            maxWidth: percent(100),
            [`a`]: {
                width: tagItemWidth,
                ...tagItemListStyle,
                ".Count": {
                    display: tagCloudVars.showCount ? "inherit" : "none",
                },
                ...tagMixin(tagsVars, presets[tagCloudVars.tagPreset], true),
            },
        },
    });

    injectGlobal({
        ".AuthorInfo .MItem.RoleTracker a": {
            textDecoration: important("none"),
        },
    });

    const metasVars = metasVariables();

    injectGlobal({
        [`.Container .MessageList .ItemComment .MItem.RoleTracker a.Tag,
        .MessageList .ItemComment .MItem.RoleTracker a.Tag,
        .MessageList .ItemDiscussion .MItem.RoleTracker a.Tag`]: {
            ...tagMixin(tagsVars, presets.standard, true),
            ...Mixins.margin(metasVars.spacing),
        },
    });
};
