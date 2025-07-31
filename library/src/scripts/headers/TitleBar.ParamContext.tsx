/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { createContext, useContext, useMemo } from "react";

import type { BorderType } from "@library/styles/styleHelpersBorders";
import type { ColorHelper } from "csx";
import type { DeepPartial } from "redux";
import type { IThemeVariables } from "@library/theming/themeReducer";
import type { LogoAlignment } from "@library/headers/LogoAlignment";
import type TitleBarFragmentInjectable from "@vanilla/injectables/TitleBarFragment";
import { TitleBarTransparentContext } from "@library/headers/TitleBar.TransparentContext";
import { getPixelNumber } from "@library/styles/styleUtils";
import { navigationVariables } from "@library/headers/navigationVariables";
import { stableObjectHash } from "@vanilla/utils";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { useCurrentUser } from "@library/features/users/userHooks";

export const TitleBarPositioning = {
    StickySolid: "StickySolid",
    StickyTransparent: "StickyTransparent",
    StaticSolid: "StaticSolid",
    StaticTransparent: "StaticTransparent",
} as const;
export type TitleBarPositioning = (typeof TitleBarPositioning)[keyof typeof TitleBarPositioning];

export type ITitleBarParams = {
    positioning: TitleBarPositioning;
    backgroundColor: string | ColorHelper;
    foregroundColor: string | ColorHelper;
    height: number;
    heightMobile: number;
    borderType: BorderType;
    logoType: "styleguide" | "custom";
    logo: {
        alignment: LogoAlignment;
        alignmentMobile: LogoAlignment;
    } & TitleBarFragmentInjectable.Props["logo"];
    navigationType: "styleguide" | "custom";
    navigation: {
        alignment: "left" | "center";
    } & TitleBarFragmentInjectable.Props["navigation"];
};

export type ITitleBarParamsResolved = Omit<ITitleBarParams, "logoType" | "navigationType"> & {};

const defaultParams: ITitleBarParamsResolved = {
    positioning: TitleBarPositioning.StickySolid,
    backgroundColor: undefined as any, // Will be resolved at runtime
    foregroundColor: undefined as any, // Will be resolved at runtime
    height: 48, // Default from titleBarVariables().sizing.height
    heightMobile: 44, // Default from titleBarVariables().sizing.mobile.height
    borderType: "none" as BorderType, // Default from titleBarVariables().border.type
    logo: {
        alignment: "left" as LogoAlignment, // Default from titleBarVariables().logo.justifyContent
        alignmentMobile: "center" as LogoAlignment, // Default from titleBarVariables().mobileLogo.justifyContent
        imageUrl: undefined, // Default from titleBarVariables().logo.desktop.url
        imageUrlMobile: undefined, // Default from titleBarVariables().logo.mobile.url
        url: "/", // Default from navigationVariables().logo.url
    },
    navigation: {
        alignment: "left" as "left" | "center", // Default from titleBarVariables().navAlignment.alignment
        items: [], // Default from navigationVariables().navigationItems
    },
};

export const TitleBarParamContext = createContext<ITitleBarParamsResolved>(defaultParams);

export function useTitleBarParams() {
    return useContext(TitleBarParamContext);
}

export function useTitleBarParamVarOverrides(): IThemeVariables {
    const params: Partial<ITitleBarParamsResolved> = useTitleBarParams();
    return useMemo(() => {
        const varOverrides: {
            titleBar: DeepPartial<ReturnType<typeof titleBarVariables>>;
            navigation: DeepPartial<ReturnType<typeof navigationVariables>>;
        } = {
            titleBar: {
                logo: {
                    justifyContent: params?.logo?.alignment, // alignment for desktop logo.
                },
                mobileLogo: {
                    justifyContent: params?.logo?.alignmentMobile,
                },
                colors: {
                    bg: params?.backgroundColor as ColorHelper,
                    fg: params?.foregroundColor as ColorHelper,
                },
                sizing: {
                    height: params?.height,
                    mobile: {
                        height: params?.heightMobile,
                    },
                },
                navAlignment: {
                    alignment: params?.navigation?.alignment,
                },
                border: {
                    type: params?.borderType,
                },
                positioning: {
                    mode: params?.positioning,
                },
            },
            navigation: {
                navigationItems: params?.navigation?.items,
                logo: {
                    url: params?.logo?.url,
                },
            },
        };

        return varOverrides;
    }, [stableObjectHash(params)]);
}

export function TitleBarParamContextProvider(
    props: Partial<ITitleBarParams> & {
        children: React.ReactNode;
    },
) {
    const { children, ...params } = props;

    const navVars = navigationVariables.useAsHook();
    const titleVars = titleBarVariables.useAsHook();
    const transparentContext = useContext(TitleBarTransparentContext);

    // On layout pages, we don't consult the styleguide for the positioning mode.
    // It needs to be set per-layout.
    let positioning: TitleBarPositioning;

    if (transparentContext.isLegacyPage) {
        // If we are on a legacy page, we need to use the styleguide positioning mode.
        // This is because the layout pages are not aware of the new positioning mode.
        // We need to set the positioning mode per-layout.
        positioning = titleVars.positioning.mode;

        // On legacy pages, we only want to use the transparent mode if there is a banner in the page (which sets this value in context).
        if (!transparentContext.allowTransparency) {
            if (positioning === TitleBarPositioning.StickyTransparent) {
                positioning = TitleBarPositioning.StickySolid;
            } else if (positioning === TitleBarPositioning.StaticTransparent) {
                positioning = TitleBarPositioning.StaticSolid;
            }
        }
    } else {
        // On custom layout pages, the admin can visualize if a transparent title bar works with widgets on the page.
        // Just respect it and don't do any magic.
        positioning = props.positioning ?? TitleBarPositioning.StickySolid;
    }

    const currentUser = useCurrentUser();

    const navigation = useMemo(() => {
        const items = props.navigationType === "custom" ? props.navigation?.items ?? [] : navVars.navigationItems;

        const filteredItems = items.filter((item) => {
            const roleIDs = item.roleIDs ?? [];
            return roleIDs.length === 0 || roleIDs.some((roleID) => currentUser?.roleIDs.includes(roleID));
        });

        const navigation: ITitleBarParams["navigation"] = {
            items: filteredItems,
            alignment:
                props.navigationType === "custom" && props.navigation?.alignment
                    ? props.navigation.alignment
                    : titleVars.navAlignment.alignment,
        };
        return navigation;
    }, [
        props.navigationType,
        stableObjectHash(props.navigation ?? {}),
        navVars,
        titleVars,
        props.navigation?.alignment,
        currentUser,
    ]);

    const logo = useMemo(() => {
        const logo: ITitleBarParams["logo"] = {
            imageUrl: props.logo?.imageUrl ?? titleVars.logo.desktop.url,
            imageUrlMobile: props.logo?.imageUrlMobile ?? titleVars.logo.mobile.url,
            url: props.logo?.url ?? navVars.logo.url,
            alignment: props.logo?.alignment ?? titleVars.logo.justifyContent,
            alignmentMobile: props.logo?.alignmentMobile ?? titleVars.mobileLogo.justifyContent,
        };
        return logo;
    }, [stableObjectHash(props.logo ?? {}), props.logoType, titleVars, navVars]);

    return (
        <TitleBarParamContext.Provider
            value={{
                positioning,
                logo,
                navigation,
                backgroundColor: props.backgroundColor ?? titleVars.colors.bg,
                foregroundColor: props.foregroundColor ?? titleVars.colors.fg,
                height: props.height ?? titleVars.sizing.height,
                heightMobile: props.heightMobile ?? titleVars.sizing.mobile.height,
                borderType: props.borderType ?? titleVars.border.type,
            }}
        >
            {children}
        </TitleBarParamContext.Provider>
    );
}
