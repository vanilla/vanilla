import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUserSignedIn, useCurrentUser } from "@library/features/users/userHooks";
import type { IBackground } from "@library/styles/cssUtilsTypes";
import { Mixins } from "@library/styles/Mixins";
import { createSourceSetValue, getMeta, getSiteSection } from "@library/utility/appUtils";
import { translate, formatNumber, formatNumberCompact, getCurrentLocale } from "@vanilla/i18n";
import { type CSSProperties, type MutableRefObject, useCallback, useRef } from "react";
import { cx } from "@emotion/css";
import { useIsOverflowing, useMeasure } from "@vanilla/react-utils";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

/**
 * Get function that smartly navigates to a link.
 *
 * This function behaves the same as clicking on a Smart <Link /> component in that it will do a dynamic navigation
 * to the link if possible, and fall back to a full page load if it has too.
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
function useLinkNavigator() {
    const { pushSmartLocation } = useLinkContext();

    const pushLocation = useCallback(
        (url: string) => {
            pushSmartLocation(url);
        },
        [pushSmartLocation],
    );

    return pushLocation;
}

const Utils = {
    Css: {
        background: (background: Partial<IBackground> | undefined) =>
            Mixins.background(background ?? {}) as CSSProperties,
    },
    t: translate,
    useQuery,
    useMutation,
    useQueryClient,
    translate,
    formatNumber,
    formatNumberCompact,
    useMeasure,
    useIsOverflowing,
    useLinkNavigator,
    useCurrentUser,
    createSourceSetValue,
    useCurrentUserSignedIn,
    usePermissionsContext,
    getMeta,
    classnames: cx,
    getCurrentLocale,
    getSiteSection,
};

export default Utils;
