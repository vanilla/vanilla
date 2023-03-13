/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { linkToolbarVariables } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkToolbar.variables";

export const linkToolbarClasses = useThemeCache(() => {
    const vars = linkToolbarVariables();

    const menuBar = css({
        maxWidth: vars.menuBar.maxWidth,
    });

    const linkPreviewMenuBarItem = css({
        maxWidth: vars.linkPreviewMenuBarItem.maxWidth,
        ...Mixins.padding(vars.linkPreviewMenuBarItem.spacing),
    });

    const linkPreview = css({
        lineHeight: 2,
        display: "flex",
        alignItems: "center",
    });

    const linkPreviewIcon = css({
        width: vars.externalIcon.size,
        height: vars.externalIcon.size,
        flexShrink: 0,
    });

    const linkFormContainer = css({
        ...Mixins.padding(vars.form.spacing),
    });

    return { menuBar, linkPreviewMenuBarItem, linkPreview, linkPreviewIcon, linkFormContainer };
});
