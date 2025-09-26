/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import getStore from "@library/redux/getStore";
import { globalVariables } from "@library/styles/globalStyleVars";
import { assetUrl } from "@library/utility/appUtils";
import { useEffect } from "react";

export function DynamicCustomFontLoader() {
    const fontUrls = useCurrentFontUrls();
    useEffect(() => {
        for (const fontUrl of fontUrls) {
            const existingLink = document.querySelector(`link[href="${fontUrl}"]`);
            if (existingLink) {
                continue;
            }

            // Add the link.
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = fontUrl;
            document.head.appendChild(link);
        }
    }, [fontUrls]);

    return <></>;
}

function useCurrentFontUrls(): string[] {
    const fontVars = globalVariables.useAsHook().fonts;
    const fontsAsset = getStore().getState().theme.assets.data?.fonts?.data;
    const fontsAssetUrls = fontsAsset?.map((assetFont) => assetFont.url) ?? [];

    const customFontUrl = fontVars.customFont.url ?? fontVars.customFontUrl ?? null;
    const forceGoogleFont = fontVars.forceGoogleFont;
    const googleFont = fontVars.googleFontFamily ?? "Open Sans";
    const googleFontUrl = assetUrl(`/resources/fonts/${encodeURIComponent(googleFont)}/font.css`);

    if (forceGoogleFont) {
        return [googleFontUrl];
    } else if (customFontUrl) {
        // We have a custom font to load.
        return [customFontUrl];
    } else if (fontsAssetUrls.length > 0) {
        return fontsAssetUrls;
    } else {
        // Default fallback.
        return [googleFontUrl];
    }
}
