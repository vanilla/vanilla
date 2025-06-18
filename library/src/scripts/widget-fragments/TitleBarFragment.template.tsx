import Components from "@vanilla/injectables/Components";
import TitleBar from "@vanilla/injectables/TitleBarFragment";
import Utils from "@vanilla/injectables/Utils";
import React, { useEffect, useRef } from "react";

export default function TitleBarFragment(props: TitleBar.Props) {
    const { navigation } = props;

    // You may want to make use of these.
    const locale = Utils.getCurrentLocale();
    const siteSection = Utils.getSiteSection();
    const currentUser = Utils.useCurrentUser();

    // Some state to manage
    const [isSearchOpen, setIsSearchOpen] = React.useState(false);

    // Responsively render specific components.
    const rootRef = useRef<HTMLElement>(null);
    const rootMeasure = Utils.useMeasure(rootRef);
    const isDesktop = rootMeasure.clientWidth > 806;

    return (
        // This layout widget implements handling of the "position" property configurable on the TitleBar
        // This can be "sticky" or "static" (default) and handles cases where the titlebar must overlay the content below it.
        // It also handles layout editor compatibility for the widget.
        <TitleBar.LayoutWidget ref={rootRef} className={"titleBarFragment__root"}>
            {/*
                This is a hidden link that will allows screen reader users to skip over the TitleBar.
                Good for accessibility!
            */}
            <TitleBar.SkipNavLink className={"titleBarFragment__skip-navigation"}>
                {Utils.t("Skip to content")}
            </TitleBar.SkipNavLink>
            <Components.Gutters className={"titleBarFragment__gutters"}>
                <div className={"titleBarFragment__container"}>
                    <div className={`titleBarFragment__logo ${isSearchOpen ? "search-open" : ""}`}>
                        {isDesktop ? <TitleBar.LogoDesktop /> : <TitleBar.LogoMobile />}
                    </div>
                    <nav className={"titleBarFragment__navigation"}>
                        {/*
                            This is where the navigation items are rendered.
                            The navigation items passed in will be from the layout or the styleguide.

                            If you want something custom here, just create the navigation items dynamically!
                        */}
                        {isDesktop ? (
                            <NavigationDesktop navigationItems={navigation.items} />
                        ) : (
                            <TitleBar.NavigationMobile
                                className={"titleBarFragment__navigation_mobile"}
                                navigationItems={navigation.items}
                            />
                        )}
                    </nav>
                    <div className={`titleBarFragment__search ${isSearchOpen ? "active" : ""}`}>
                        <TitleBar.SearchBox
                            onSearchStateChange={setIsSearchOpen}
                            searchButtonClassName={"titleBarFragment__search_button"}
                            cancelButtonClassName={"titleBarFragment__search_cancel"}
                        />
                    </div>
                    <div className={"titleBarFragment__user"}>
                        {/*
                            Not using subcommunities?
                            Not to worry, this won't render if you don't have any.
                        */}
                        <TitleBar.SubcommunityPicker
                            buttonType={"icon"}
                            buttonClassNames={"titleBarFragment__subcommunity-picker"}
                        />
                        {/*
                            The MeBox has information about the current user
                            (Messages, Notifications, Account related links).

                            If the user is not logged in, it will show a login/register buttons instead.

                        */}
                        {isDesktop ? <TitleBar.MeBoxDesktop /> : <TitleBar.MeBoxMobile />}
                    </div>
                </div>
            </Components.Gutters>
            {/*
                This is where the skip navigation navigates to.
            */}
            <TitleBar.SkipNavContent />
        </TitleBar.LayoutWidget>
    );
}

interface INavMenuProps {
    navigationItems: TitleBar.Props["navigation"]["items"];
    onFocused?: (id: string) => void;
}

/**
 * Basic navigation structure for desktop view.
 * This component will render a list of navigation items and handle mouse events to show submenus.
 */
function NavigationDesktop(props: INavMenuProps) {
    const { navigationItems } = props;
    const [currentFocus, setCurrentFocus] = React.useState<string | null>(null);
    const subMenuRef = React.useRef<HTMLDivElement>(null);

    const offset = subMenuRef.current?.getBoundingClientRect().x ?? 0;

    if (!navigationItems || navigationItems.length === 0) {
        return null;
    }

    const handleMouseLeave = (e: React.MouseEvent<HTMLUListElement>) => {
        const submenu = document.querySelector(".titleBarFragment__navigation_submenu");
        if (submenu && e.relatedTarget && submenu.contains(e.relatedTarget as Node)) {
            return;
        }
        setCurrentFocus(null);
    };

    const renderSubMenus = () => {
        const children = navigationItems.find((item) => item.id === currentFocus)?.children;

        return (
            <div
                ref={subMenuRef}
                className={"titleBarFragment__navigation_submenu active"}
                style={{ "--offset": `${offset}px` } as React.CSSProperties}
            >
                {children && <NavigationDesktop navigationItems={children} />}
            </div>
        );
    };

    return (
        <ul className={`titleBarFragment__navigation_list`} onMouseLeave={handleMouseLeave}>
            {navigationItems.map((item) => (
                <li key={item.id} className={"titleBarFragment__navigation_item"}>
                    <a
                        href={item.url}
                        role="menuitem"
                        onMouseEnter={() => setCurrentFocus(item.id)}
                        onFocus={() => setCurrentFocus(item.id)}
                    >
                        {item.name}
                    </a>
                </li>
            ))}
            {renderSubMenus()}
        </ul>
    );
}
