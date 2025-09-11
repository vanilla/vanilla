# Title Bar Fragment

## What is a Title Bar Fragment?

The **Title Bar Fragment** is a comprehensive navigation header widget that serves as the primary navigation interface for your community. It combines your brand identity, navigation structure, search functionality, and user account features into a cohesive header that adapts seamlessly across desktop and mobile devices.

Perfect for:

-   **Site Navigation**: Provide consistent navigation across all community pages
-   **Brand Identity**: Display your logo and maintain visual consistency
-   **User Experience**: Give users access to search, account, and key features
-   **Mobile Responsiveness**: Ensure navigation works perfectly on all devices
-   **Community Features**: Integrate subcommunities, notifications, and user tools

## Title Bar Fragment Props

The Title Bar Fragment accepts these configuration options through the `TitleBar.Props` interface:

**`navigation`** _(NavigationConfig)_  
Navigation items and structure

**`position`** _("static" | "sticky")_  
Whether the title bar sticks to the top

**`logo`** _(LogoConfig)_  
Logo configuration for desktop and mobile

**`searchConfig`** _(SearchConfig)_  
Search functionality settings

**`userMenuConfig`** _(UserMenuConfig)_  
User account menu configuration

### Navigation Configuration

```tsx
interface NavigationConfig {
    items: NavigationItem[]; // Array of navigation items
    maxItems?: number; // Maximum items to show before collapsing
    mobileBreakpoint?: number; // Width at which to switch to mobile view
}

interface NavigationItem {
    id: string;
    name: string;
    url: string;
    children?: NavigationItem[]; // Submenu items
    icon?: string; // Optional icon
    permission?: string; // Required permission to see item
}
```

## Essential Utils Methods for Title Bar Widgets

### 👤 User Context & Permissions

#### `useCurrentUser()` and `usePermissionsContext()`

Customize navigation based on user status and permissions:

**Example Usage:**

```tsx
// Dynamic navigation based on user permissions
const currentUser = Utils.useCurrentUser();
const permissions = Utils.usePermissionsContext();

// Build navigation items based on permissions
const buildNavigationItems = () => {
    const baseItems = [
        { id: "home", name: Utils.t("Home"), url: "/", icon: "home" },
        { id: "discussions", name: Utils.t("Discussions"), url: "/discussions", icon: "discussion" },
        { id: "categories", name: Utils.t("Categories"), url: "/categories", icon: "category" },
    ];

    const additionalItems = [];

    // Add moderation items for moderators
    if (permissions.hasPermission("community.moderate")) {
        additionalItems.push({
            id: "moderation",
            name: Utils.t("Moderation"),
            url: "/moderation",
            icon: "moderation",
            children: [
                { id: "mod-queue", name: Utils.t("Queue"), url: "/moderation/queue" },
                { id: "mod-reports", name: Utils.t("Reports"), url: "/moderation/reports" },
                { id: "mod-logs", name: Utils.t("Logs"), url: "/moderation/logs" },
            ],
        });
    }

    // Add admin items for administrators
    if (permissions.hasPermission("site.manage")) {
        additionalItems.push({
            id: "dashboard",
            name: Utils.t("Dashboard"),
            url: "/dashboard",
            icon: "dashboard",
            children: [
                { id: "settings", name: Utils.t("Settings"), url: "/dashboard/settings" },
                { id: "users", name: Utils.t("Users"), url: "/dashboard/users" },
                { id: "analytics", name: Utils.t("Analytics"), url: "/dashboard/analytics" },
            ],
        });
    }

    return [...baseItems, ...additionalItems];
};

const navigationItems = buildNavigationItems();
```

### 🔧 Site Configuration

#### `getMeta()` and `getSiteSection()`

Adapt title bar behavior based on site configuration:

**Example Usage:**

```tsx
// Site-aware title bar configuration
const siteConfig = {
    siteName: Utils.getMeta("ui.siteName", "Community"),
    logo: Utils.getMeta("ui.logo.desktop.url"),
    mobileLogo: Utils.getMeta("ui.logo.mobile.url"),
    brandColor: Utils.getMeta("ui.color.primary"),
    isSearchEnabled: Utils.getMeta("ui.search.enabled", true),
    hasSubcommunities: Utils.getMeta("ui.subcommunities.enabled", false),
    currentSection: Utils.getSiteSection(),
};

// Dynamic title bar styling
const titleBarStyles = {
    "--brand-color": siteConfig.brandColor,
    "--logo-height": "40px",
    backgroundColor: siteConfig.brandColor + "10", // 10% opacity
};

// Section-specific navigation highlighting
const getActiveNavigationItem = () => {
    const currentPath = window.location.pathname;
    const section = siteConfig.currentSection;

    if (section?.categoryID) {
        return `category-${section.categoryID}`;
    }

    if (currentPath.startsWith("/discussions")) {
        return "discussions";
    }

    return "home";
};
```

### 📏 Responsive Design

#### `useMeasure()`

Create adaptive layouts for different screen sizes:

**Example Usage:**

```tsx
// Responsive title bar with adaptive layout
const [measureRef, bounds] = Utils.useMeasure();

const getResponsiveConfig = () => {
    const width = bounds.width;

    if (width < 768) {
        return {
            layout: "mobile",
            showFullLogo: false,
            maxNavigationItems: 0, // All items in mobile menu
            searchStyle: "icon-only",
            userMenuStyle: "compact",
        };
    } else if (width < 1024) {
        return {
            layout: "tablet",
            showFullLogo: true,
            maxNavigationItems: 4,
            searchStyle: "compact",
            userMenuStyle: "standard",
        };
    } else {
        return {
            layout: "desktop",
            showFullLogo: true,
            maxNavigationItems: 6,
            searchStyle: "full",
            userMenuStyle: "expanded",
        };
    }
};

const responsiveConfig = getResponsiveConfig();

// Adaptive search component
const SearchComponent = ({ style }) => {
    switch (style) {
        case "icon-only":
            return (
                <button className="search-toggle" onClick={() => setSearchOpen(!searchOpen)}>
                    <Components.Icon icon="search" />
                </button>
            );
        case "compact":
            return (
                <div className="search-compact">
                    <input type="search" placeholder={Utils.t("Search...")} />
                    <Components.Icon icon="search" />
                </div>
            );
        case "full":
            return (
                <div className="search-full">
                    <input type="search" placeholder={Utils.t("Search discussions, users, and more...")} />
                    <button className="search-submit">{Utils.t("Search")}</button>
                </div>
            );
        default:
            return null;
    }
};
```

### 🧭 Advanced Navigation

#### `useLinkNavigator()`

Create sophisticated navigation with state management:

**Example Usage:**

```tsx
// Smart navigation with state tracking
const navigate = Utils.useLinkNavigator();
const [activeItem, setActiveItem] = useState(null);
const [searchOpen, setSearchOpen] = useState(false);

// Enhanced navigation handlers
const handleNavigationClick = (item) => {
    // Track navigation for analytics
    Utils.trackEvent("navigation_click", {
        item_id: item.id,
        item_name: item.name,
        user_id: currentUser?.userID,
    });

    // Close mobile menu if open
    if (responsiveConfig.layout === "mobile") {
        setMobileMenuOpen(false);
    }

    // Navigate to destination
    navigate(item.url);

    // Update active state
    setActiveItem(item.id);
};

// Search functionality with navigation
const handleSearchSubmit = (query) => {
    if (query.trim()) {
        navigate(`/search?q=${encodeURIComponent(query)}`);
        setSearchOpen(false);
    }
};

// Keyboard navigation support
const handleKeyDown = (event) => {
    if (event.key === "Escape") {
        setSearchOpen(false);
        setMobileMenuOpen(false);
    }

    if (event.key === "/" && event.ctrlKey) {
        event.preventDefault();
        setSearchOpen(true);
    }
};
```

### 🌍 Internationalization

#### `t()` and `getCurrentLocale()`

Support multiple languages and locales:

**Example Usage:**

```tsx
// Multi-language navigation labels
const navigationLabels = {
    home: Utils.t("Home"),
    discussions: Utils.t("Discussions"),
    categories: Utils.t("Categories"),
    knowledge: Utils.t("Knowledge Base"),
    search: Utils.t("Search"),
    profile: Utils.t("Profile"),
    settings: Utils.t("Settings"),
    signIn: Utils.t("Sign In"),
    register: Utils.t("Register"),
    signOut: Utils.t("Sign Out"),
    menu: Utils.t("Menu"),
    close: Utils.t("Close"),
    skipToContent: Utils.t("Skip to content"),
};

// Locale-specific formatting
const currentLocale = Utils.getCurrentLocale();
const isRTL = ["ar", "he", "fa"].includes(currentLocale);

// Accessibility labels
const accessibilityLabels = {
    mainNavigation: Utils.t("Main navigation"),
    userMenu: Utils.t("User account menu"),
    searchBox: Utils.t("Search the community"),
    mobileMenuToggle: Utils.t("Toggle mobile menu"),
    subcommunityPicker: Utils.t("Select subcommunity"),
};
```

## Advanced Title Bar Examples

### Multi-Level Navigation

**Example Usage:**

```tsx
// Title bar with complex navigation structure
export default function EnhancedTitleBar(props) {
    const [measureRef, bounds] = Utils.useMeasure();
    const [activeSubmenu, setActiveSubmenu] = useState(null);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const currentUser = Utils.useCurrentUser();
    const permissions = Utils.usePermissionsContext();

    // Complex navigation structure
    const navigationStructure = [
        {
            id: "discussions",
            name: Utils.t("Discussions"),
            url: "/discussions",
            icon: "discussion",
            children: [
                { id: "recent", name: Utils.t("Recent"), url: "/discussions/recent" },
                { id: "popular", name: Utils.t("Popular"), url: "/discussions/popular" },
                { id: "unanswered", name: Utils.t("Unanswered"), url: "/discussions/unanswered" },
            ],
        },
        {
            id: "categories",
            name: Utils.t("Categories"),
            url: "/categories",
            icon: "category",
            children: Utils.getMeta("categories", []).map((cat) => ({
                id: `category-${cat.categoryID}`,
                name: cat.name,
                url: cat.url,
                icon: cat.iconUrl,
            })),
        },
        {
            id: "community",
            name: Utils.t("Community"),
            url: "#",
            icon: "users",
            children: [
                { id: "members", name: Utils.t("Members"), url: "/members" },
                { id: "leaderboard", name: Utils.t("Leaderboard"), url: "/leaderboard" },
                { id: "events", name: Utils.t("Events"), url: "/events" },
            ],
        },
    ];

    // Add admin items if user has permissions
    if (permissions.hasPermission("site.manage")) {
        navigationStructure.push({
            id: "admin",
            name: Utils.t("Admin"),
            url: "/dashboard",
            icon: "settings",
            children: [
                { id: "dashboard", name: Utils.t("Dashboard"), url: "/dashboard" },
                { id: "users", name: Utils.t("Users"), url: "/dashboard/users" },
                { id: "settings", name: Utils.t("Settings"), url: "/dashboard/settings" },
            ],
        });
    }

    const isDesktop = bounds.width > 1024;

    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className="enhanced-title-bar">
                <div className="title-bar__container">
                    {/* Logo Section */}
                    <div className="title-bar__logo">
                        {isDesktop ? <Components.Logo type="desktop" /> : <Components.Logo type="mobile" />}
                    </div>

                    {/* Navigation Section */}
                    <nav className="title-bar__navigation">
                        {isDesktop ? (
                            <DesktopNavigation
                                items={navigationStructure}
                                activeSubmenu={activeSubmenu}
                                onSubmenuChange={setActiveSubmenu}
                            />
                        ) : (
                            <MobileNavigation
                                items={navigationStructure}
                                isOpen={mobileMenuOpen}
                                onToggle={setMobileMenuOpen}
                            />
                        )}
                    </nav>

                    {/* Search Section */}
                    <div className="title-bar__search">
                        <Components.SearchBox
                            placeholder={Utils.t("Search...")}
                            onSubmit={handleSearchSubmit}
                            compact={!isDesktop}
                        />
                    </div>

                    {/* User Section */}
                    <div className="title-bar__user">
                        <Components.UserMenu user={currentUser} compact={!isDesktop} />
                    </div>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}

// Desktop navigation component
function DesktopNavigation({ items, activeSubmenu, onSubmenuChange }) {
    const navigate = Utils.useLinkNavigator();

    return (
        <ul className="desktop-navigation">
            {items.map((item) => (
                <li
                    key={item.id}
                    className={`nav-item ${activeSubmenu === item.id ? "active" : ""}`}
                    onMouseEnter={() => item.children && onSubmenuChange(item.id)}
                    onMouseLeave={() => onSubmenuChange(null)}
                >
                    <a
                        href={item.url}
                        className="nav-link"
                        onClick={(e) => {
                            if (item.children) {
                                e.preventDefault();
                                onSubmenuChange(item.id);
                            } else {
                                navigate(item.url);
                            }
                        }}
                    >
                        <Components.Icon icon={item.icon} />
                        {item.name}
                    </a>

                    {/* Submenu */}
                    {item.children && activeSubmenu === item.id && (
                        <ul className="nav-submenu">
                            {item.children.map((child) => (
                                <li key={child.id}>
                                    <a
                                        href={child.url}
                                        onClick={(e) => {
                                            e.preventDefault();
                                            navigate(child.url);
                                            onSubmenuChange(null);
                                        }}
                                    >
                                        {child.icon && <Components.Icon icon={child.icon} />}
                                        {child.name}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </li>
            ))}
        </ul>
    );
}
```

### Contextual Title Bar

**Example Usage:**

```tsx
// Title bar that adapts based on current page context
export default function ContextualTitleBar(props) {
    const siteSection = Utils.getSiteSection();
    const currentUser = Utils.useCurrentUser();
    const navigate = Utils.useLinkNavigator();

    // Get context-specific configuration
    const getContextConfig = () => {
        const currentPath = window.location.pathname;

        if (currentPath.startsWith("/discussions/")) {
            return {
                type: "discussion",
                showBackButton: true,
                contextActions: [
                    { name: Utils.t("Reply"), icon: "reply", action: () => navigate("#reply") },
                    { name: Utils.t("Share"), icon: "share", action: () => handleShare() },
                ],
            };
        }

        if (siteSection?.categoryID) {
            return {
                type: "category",
                title: siteSection.categoryName,
                contextActions: [
                    { name: Utils.t("New Discussion"), icon: "edit", action: () => navigate("/post/discussion") },
                    { name: Utils.t("Follow"), icon: "follow", action: () => handleFollow() },
                ],
            };
        }

        if (currentPath.startsWith("/profile/")) {
            return {
                type: "profile",
                showBackButton: true,
                contextActions: [
                    { name: Utils.t("Message"), icon: "message", action: () => handleMessage() },
                    { name: Utils.t("Follow"), icon: "follow", action: () => handleFollow() },
                ],
            };
        }

        return {
            type: "default",
            contextActions: [],
        };
    };

    const contextConfig = getContextConfig();

    return (
        <Components.LayoutWidget>
            <div className="contextual-title-bar">
                <div className="title-bar__main">
                    {/* Back button for certain contexts */}
                    {contextConfig.showBackButton && (
                        <button className="back-button" onClick={() => window.history.back()}>
                            <Components.Icon icon="arrow-left" />
                            {Utils.t("Back")}
                        </button>
                    )}

                    {/* Standard title bar content */}
                    <div className="title-bar__standard">
                        <Components.Logo />
                        <Components.Navigation />
                        <Components.Search />
                        <Components.UserMenu />
                    </div>
                </div>

                {/* Context-specific actions */}
                {contextConfig.contextActions.length > 0 && (
                    <div className="title-bar__context-actions">
                        {contextConfig.contextActions.map((action, index) => (
                            <button key={index} className="context-action" onClick={action.action}>
                                <Components.Icon icon={action.icon} />
                                {action.name}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </Components.LayoutWidget>
    );
}
```

### Progressive Enhancement Title Bar

**Example Usage:**

```tsx
// Title bar that progressively enhances features
export default function ProgressiveTitleBar(props) {
    const [measureRef, bounds] = Utils.useMeasure();
    const [features, setFeatures] = useState({
        search: false,
        notifications: false,
        subcommunities: false,
        advancedNavigation: false,
    });

    // Progressive feature detection
    useEffect(() => {
        const detectFeatures = async () => {
            const detectedFeatures = {
                search: Utils.getMeta("ui.search.enabled", true),
                notifications: Utils.getMeta("ui.notifications.enabled", false),
                subcommunities: Utils.getMeta("ui.subcommunities.enabled", false),
                advancedNavigation: bounds.width > 1200,
            };

            setFeatures(detectedFeatures);
        };

        detectFeatures();
    }, [bounds.width]);

    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className="progressive-title-bar">
                <div className="title-bar__core">
                    {/* Core features always present */}
                    <Components.Logo />
                    <Components.BasicNavigation />
                    <Components.UserMenu />
                </div>

                {/* Progressive enhancement features */}
                <div className="title-bar__enhanced">
                    {/* Advanced search */}
                    {features.search && (
                        <Components.AdvancedSearch features={["autocomplete", "filters", "recent-searches"]} />
                    )}

                    {/* Notification center */}
                    {features.notifications && (
                        <Components.NotificationCenter
                            realTime={true}
                            categories={["mentions", "reactions", "follows"]}
                        />
                    )}

                    {/* Subcommunity picker */}
                    {features.subcommunities && <Components.SubcommunityPicker style="dropdown" showCounts={true} />}

                    {/* Advanced navigation */}
                    {features.advancedNavigation && (
                        <Components.MegaMenu items={props.navigation.items} columns={3} showDescriptions={true} />
                    )}
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

## Title Bar Best Practices

### User Experience

-   **Consistent Positioning**: Keep navigation in expected locations
-   **Clear Hierarchy**: Use visual weight to show importance
-   **Responsive Design**: Adapt gracefully to all screen sizes
-   **Accessibility**: Support keyboard navigation and screen readers

### Performance

-   **Lazy Loading**: Load heavy features only when needed
-   **Efficient Rendering**: Minimize re-renders on scroll or resize
-   **Image Optimization**: Use appropriate logo formats and sizes
-   **Caching**: Cache navigation data and user preferences

### Navigation Structure

-   **Logical Grouping**: Group related items together
-   **Breadth vs Depth**: Balance menu width with nesting levels
-   **Progressive Disclosure**: Show more options as space allows
-   **User Personalization**: Adapt to user roles and preferences

### Mobile Optimization

-   **Touch Targets**: Ensure buttons are large enough for touch
-   **Gesture Support**: Support swipe gestures where appropriate
-   **Simplified UI**: Reduce complexity on smaller screens
-   **Fast Loading**: Optimize for mobile network conditions

The Title Bar Fragment serves as your community's primary navigation hub - design it to be intuitive, accessible, and adaptable to your users' needs across all devices and contexts.
