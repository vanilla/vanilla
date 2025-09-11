/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import Hamburger from "@library/flyouts/Hamburger";
import { MeBoxDesktop as MeBoxDesktopImpl } from "@library/headers/mebox/MeBoxDesktop";
import { MeBoxMobile as MeBoxMobileImpl } from "@library/headers/mebox/MeBoxMobile";
import CompactSearch from "@library/headers/mebox/pieces/CompactSearch";
import Logo from "@library/headers/mebox/pieces/Logo";
import { LogoType } from "@library/theming/ThemeLogo";
import { SkipNavContent, SkipNavLink } from "@reach/skip-nav";
import { t } from "@vanilla/i18n";
import { SearchPageRoute } from "@library/search/SearchPageRoute";
import { useEffect, useState } from "react";
import type { ISiteSection } from "@library/utility/appUtils";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { getComponent } from "@library/utility/componentRegistry";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { TitleBarLayoutWidget } from "@library/headers/TitleBar.LayoutWidget";
import type { ITitleBarParams } from "@library/headers/TitleBar.ParamContext";

namespace TitleBarFragmentInjectable {
    export interface Props {
        positioning?: ITitleBarParams["positioning"];
        logo?: {
            imageUrl?: string;
            imageUrlMobile?: string;
            url: string;
        };
        navigation: {
            items: INavigationVariableItem[];
        };
    }
}

const LogoDesktop = () => {
    return <Logo logoClassName="titleBar-logo" logoType={LogoType.DESKTOP} />;
};

const LogoMobile = () => {
    return <Logo logoClassName="titleBar-logo" logoType={LogoType.MOBILE} />;
};

const MeBoxDesktop = MeBoxDesktopImpl;
const MeBoxMobile = MeBoxMobileImpl;

const NavigationMobile = ({
    className,
    navigationItems,
    side = "left",
}: {
    className?: string;
    navigationItems?: TitleBarFragmentInjectable.Props["navigation"]["items"];
    side?: "left" | "right";
}) => {
    return <Hamburger side={side} className={className} navigationItems={navigationItems} showCloseIcon={false} />;
};

interface ISearchBoxProps {
    onSearchStateChange?: (boolean) => void;
    placeholder?: string;
    wrapperClassName?: string;
    searchButtonClassName?: string;
    cancelButtonClassName?: string;
}

const SearchBox = (props: ISearchBoxProps) => {
    const { onSearchStateChange, placeholder, wrapperClassName, cancelButtonClassName, searchButtonClassName } = props;
    const [isSearchOpen, setIsSearchOpen] = useState(false);

    useEffect(() => {
        onSearchStateChange?.(isSearchOpen);
    }, [isSearchOpen]);

    return (
        <CompactSearch
            className={wrapperClassName}
            buttonClass={searchButtonClassName}
            cancelButtonClassName={cancelButtonClassName}
            placeholder={placeholder ?? t("Search")}
            open={isSearchOpen}
            onSearchButtonClick={() => {
                SearchPageRoute.preload();
                setIsSearchOpen(true);
            }}
            onCloseSearch={() => {
                setIsSearchOpen(false);
            }}
            cancelContentClassName={"meBox-buttonContent"}
            withLabel={false}
            focusOnMount
        />
    );
};

/**
 * Renders the subcommunity chooser dropdown if subcommunities are enabled on your community.
 */
const SubcommunityPicker = ({
    buttonType,
    buttonClassNames,
}: {
    buttonType?: ButtonTypes;
    buttonClassNames?: string;
}) => {
    const SubcommunityChooserDropdown = getComponent("subcommunity-chooser");
    return SubcommunityChooserDropdown?.Component ? (
        <SubcommunityChooserDropdown.Component buttonType={buttonType} buttonClass={buttonClassNames} />
    ) : null;
};

const TitleBarFragmentInjectable = {
    LogoDesktop,
    LogoMobile,
    MeBoxDesktop,
    MeBoxMobile,
    SkipNavLink,
    SkipNavContent,
    NavigationMobile,
    SearchBox,
    SubcommunityPicker,
    LayoutWidget: TitleBarLayoutWidget,
};

export default TitleBarFragmentInjectable;
