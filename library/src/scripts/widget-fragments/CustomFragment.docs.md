# Custom Fragment

## What is Widget Builder?

The **Widget Builder** is Vanilla's powerful system for creating custom, interactive widgets that can be deployed across your community. It allows developers to build sophisticated React components that community management teams can then configure and place anywhere in their forum using the **Layout Editor**.

### How Widget Builder Works

1. **Developers Create**: Build custom widgets using React, TypeScript, and Vanilla's Utils library
2. **Community Managers Configure**: Use the admin dashboard to configure widget settings and content
3. **Layout Editor Deployment**: Place configured widgets anywhere in the forum layout using the visual Layout Editor
4. **Dynamic Updates**: Widgets can fetch live data, respond to user interactions, and update in real-time

### Key Benefits

-   **No Code Required for Deployment**: Community managers can add and configure widgets without technical knowledge
-   **Flexible Placement**: Widgets can be placed in headers, sidebars, footers, or anywhere in the layout
-   **Live Configuration**: Settings can be changed without redeploying code
-   **Responsive Design**: Widgets automatically adapt to different screen sizes
-   **Permission-Aware**: Widgets can show different content based on user permissions
-   **Internationalized**: Full support for multiple languages and locales

### Common Use Cases

-   **Community Stats Dashboards**: Display member counts, discussion metrics, activity feeds
-   **Content Promotion**: Featured discussions, announcements, call-to-action buttons
-   **External Integrations**: Stock tickers, weather widgets, social media feeds
-   **Interactive Tools**: Polls, surveys, calculators, games
-   **Navigation Enhancement**: Custom menus, quick links, search interfaces
-   **Branding Elements**: Custom headers, footers, promotional banners

## Making API Calls in Custom Widgets

Custom widgets can fetch data from external APIs to display dynamic content. Here's how to make API calls effectively:

**Example Usage:**

```tsx
// Example: Fetching stock prices from an external API
const fetchStockPrices = async () => {
    try {
        setLoading(true);
        const fetchedData = await Promise.all(
            STOCK_SYMBOLS.map(async (symbol) => {
                const response = await fetch(
                    `https://api.polygon.io/v2/aggs/ticker/${symbol}/range/1/day/2025-04-01/2025-04-07?apiKey=${POLYGON_API_KEY}`,
                );
                const data = await response.json();
                return { symbol, prices: data.results || [] };
            }),
        );
        setStockData(fetchedData);
        setLoading(false);
    } catch (error) {
        console.error("Error fetching stock data:", error);
        setLoading(false);
    }
};

// Using the API call in a widget
export default function StockWidget(props) {
    const [stockData, setStockData] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchStockPrices();
    }, []);

    return (
        <Components.LayoutWidget>
            <h3>{Utils.t("Stock Prices")}</h3>
            {loading ? (
                <div>{Utils.t("Loading stock data...")}</div>
            ) : (
                <ul>
                    {stockData.map(({ symbol, prices }) => (
                        <li key={symbol}>
                            {symbol}: ${prices[0]?.c || "N/A"}
                        </li>
                    ))}
                </ul>
            )}
        </Components.LayoutWidget>
    );
}
```

**Best Practices for API Calls:**

-   Always handle loading and error states
-   Use environment variables for API keys (accessed via `Utils.getMeta()`)
-   Implement proper error handling with user-friendly messages
-   Consider caching with `Utils.useQuery()` for better performance
-   Respect rate limits and add appropriate delays if needed

## Adding Third-Party Libraries

Custom widgets can use third-party libraries, but they must be hosted and loaded dynamically from CDNs. File uploads are not supported for security reasons.

**Example Usage:**

```tsx
// Example: Loading Chart.js library for data visualization
import { useEffect, useState } from "react";

export default function ChartWidget(props) {
    const [chartLoaded, setChartLoaded] = useState(false);
    const [stockData, setStockData] = useState([]);

    /* 
    Dynamically load third-party dependencies Chart.js and chartjs-adapter from a CDN
    These files could be hosted on a self-hosted CDN or on a public one like CDNjs
    */
    useEffect(() => {
        const scriptChartJs = document.createElement("script");
        scriptChartJs.src = "https://cdn.jsdelivr.net/npm/chart.js";
        scriptChartJs.onload = () => {
            const scriptDateFns = document.createElement("script");
            scriptDateFns.src = "https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns";
            scriptDateFns.onload = () => {
                setChartLoaded(true);
                if (stockData.length > 0) {
                    renderAllCharts(); // Render all charts after loading
                }
            };
            scriptDateFns.onerror = (err) => {
                console.error("Error loading chartjs-adapter-date-fns:", err);
            };
            document.body.appendChild(scriptDateFns);
        };
        scriptChartJs.onerror = (err) => {
            console.error("Error loading Chart.js:", err);
        };
        document.body.appendChild(scriptChartJs);

        /* 
        UseEffect return is a cleanup step to prevent memory leaks
        This is to clean up scripts when the component unmounts and prevent multiple loads
        */
        return () => {
            // Clean up scripts when component unmounts
            const existingChartJs = document.querySelector('script[src="https://cdn.jsdelivr.net/npm/chart.js"]');
            const existingDateFns = document.querySelector(
                'script[src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"]',
            );
            if (existingChartJs) document.body.removeChild(existingChartJs);
            if (existingDateFns) document.body.removeChild(existingDateFns);
        };
    }, [stockData]); // Rerun if stockData changes

    const renderAllCharts = () => {
        if (!window.Chart || !chartLoaded) return;

        // Use the loaded Chart.js library
        const ctx = document.getElementById("myChart");
        new window.Chart(ctx, {
            type: "line",
            data: {
                labels: stockData.map((d) => d.date),
                datasets: [
                    {
                        label: "Stock Price",
                        data: stockData.map((d) => d.price),
                        borderColor: "rgb(75, 192, 192)",
                        tension: 0.1,
                    },
                ],
            },
        });
    };

    return (
        <Components.LayoutWidget>
            <h3>{Utils.t("Stock Chart")}</h3>
            {!chartLoaded ? (
                <div>{Utils.t("Loading chart library...")}</div>
            ) : (
                <canvas id="myChart" width="400" height="200"></canvas>
            )}
        </Components.LayoutWidget>
    );
}
```

**Supported Third-Party Library Sources:**

-   **CDNjs**: `https://cdnjs.cloudflare.com/ajax/libs/`
-   **jsDelivr**: `https://cdn.jsdelivr.net/npm/`
-   **unpkg**: `https://unpkg.com/`
-   **Self-hosted CDN**: Your own hosted JavaScript files
-   **Popular Libraries**: Chart.js, D3.js, Moment.js, Lodash, etc.

**Important Notes:**

-   Libraries must be loaded from HTTPS URLs
-   Always include error handling for failed loads
-   Clean up scripts in useEffect return function
-   Check if library is already loaded before adding scripts
-   Consider library size and loading performance

## Uploading and Using Images/Videos

Custom widgets can include images and videos by uploading them through the Widget Builder interface. Once uploaded, these files are automatically hosted on your CDN and can be referenced in your widget code.

### How to Upload Media Files

1. **In the Widget Builder Interface**: Use the file upload section to upload images, videos, or other media files
2. **Supported Formats**: JPG, PNG, GIF, WebP, MP4, WebM, and other common media formats
3. **Automatic CDN Hosting**: Files are automatically uploaded to your CDN and optimized for delivery
4. **URL Generation**: The system generates stable URLs that can be referenced in your widget code

### Referencing Uploaded Files

**Example Usage:**

```tsx
// Example: Using uploaded images and videos in a banner widget
import { useEffect, useState } from "react";
import Utils from "@vanilla/injectables/Utils";
import Components from "@vanilla/injectables/Components";

export default function MediaBannerWidget(props) {
    const [measure, setMeasure] = useState({ clientWidth: 0 });
    const [rootRef, bounds] = Utils.useMeasure();

    // Reference uploaded media files using their CDN URLs
    const newimage: string =
        "https://us.v-cdn.net/6038267/uploads/ENLYZBLKDMCA/vecteezy-graph-stock-market-footage-4k-background-60239022.gif";

    const backgroundVideo: string = "https://us.v-cdn.net/6038267/uploads/XYZABC123/background-video.mp4";

    // Responsive design based on screen size
    const isDesktop = bounds.width > 806;
    const isMobile = bounds.width <= 768;

    // Configuration options (could come from props)
    const useOverlay = props.useOverlay || false;
    const color = props.backgroundColor || "#000000";
    const textColor = props.textColor || "#ffffff";
    const alignment = props.alignment || "center";
    const hasBackgroundImage = props.hasBackgroundImage !== false;
    const imageUrlSrcSet = props.imageUrlSrcSet;
    const imageSource = props.imageSource || "uploaded";
    const useVideo = props.useVideo || false;

    return (
        <Components.LayoutWidget
            as="section"
            // Banner's typically have their own padding so we can use `interWidgetSpacing`
            // to prevent extra padding between the banner and other widgets with no interWidgetSpacing.
            interWidgetSpacing={"none"}
            ref={rootRef}
            className={`bannerFragment__root ${useOverlay ? "hasOverlay" : ""}`}
            style={
                {
                    ...(color && { "--background-color": color }),
                    ...(textColor && { "--text-color": textColor }),
                    ...(alignment && { "--alignment": alignment }),
                } as React.CSSProperties
            }
        >
            <div className={"bannerFragment__image_container"}>
                {useOverlay && <span className={"bannerFragment__overlay_container"} />}

                {/* Video Background Option */}
                {useVideo && (
                    <video
                        autoPlay
                        loop
                        muted
                        playsInline
                        className="bannerFragment__video"
                        style={{ width: "100%", height: "100%", objectFit: "cover" }}
                    >
                        <source src={backgroundVideo} type="video/mp4" />
                        {/* Fallback to image if video fails */}
                        <img
                            src={newimage}
                            alt="Background"
                            style={{ width: "100%", height: "100%", objectFit: "cover" }}
                        />
                    </video>
                )}

                {/* Image Background Option */}
                {hasBackgroundImage && !useVideo && (
                    <picture>
                        {imageSource !== "styleGuide" ? (
                            <img
                                role="presentation"
                                src={newimage}
                                {...(imageUrlSrcSet && {
                                    srcSet: Utils.createSourceSetValue(imageUrlSrcSet),
                                })}
                                alt="Banner background"
                                style={{
                                    width: "100%",
                                    height: "auto",
                                    objectFit: "cover",
                                }}
                            />
                        ) : (
                            <Components.Banner.DefaultBannerImage backgroundColor={color} />
                        )}
                    </picture>
                )}
            </div>

            {/* Content Overlay */}
            <div className="bannerFragment__content">
                <h2 style={{ color: textColor }}>{Utils.t("Welcome to our Community")}</h2>
                <p style={{ color: textColor }}>{Utils.t("Join thousands of members in our discussions")}</p>
            </div>
        </Components.LayoutWidget>
    );
}
```

### Best Practices for Media Files

**File Management:**

-   **Optimize images** before uploading (compress, resize appropriately)
-   **Use appropriate formats**: WebP for images, MP4 for videos when possible
-   **Consider file sizes**: Large files impact page load times
-   **Provide alt text** for accessibility
-   **Use responsive images** with `Utils.createSourceSetValue()`

**Performance Considerations:**

-   **Lazy loading**: Use `loading="lazy"` attribute for images below the fold
-   **Video optimization**: Use `muted`, `autoplay`, and `playsInline` for background videos
-   **Fallback options**: Always provide fallback images for videos
-   **Mobile optimization**: Consider different assets for mobile vs desktop

**Example with Advanced Features:**

```tsx
// Advanced media widget with responsive images and video fallbacks
export default function AdvancedMediaWidget(props) {
    const [mediaLoaded, setMediaLoaded] = useState(false);
    const [hasVideoSupport, setHasVideoSupport] = useState(true);
    const [measureRef, bounds] = Utils.useMeasure();

    // Multiple image sizes for responsive design
    const imageUrls = {
        mobile: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-mobile.jpg",
        tablet: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-tablet.jpg",
        desktop: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-desktop.jpg",
        video: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-video.mp4",
    };

    // Determine which image to use based on screen size
    const getCurrentImage = () => {
        if (bounds.width <= 768) return imageUrls.mobile;
        if (bounds.width <= 1024) return imageUrls.tablet;
        return imageUrls.desktop;
    };

    const handleVideoError = () => {
        setHasVideoSupport(false);
    };

    return (
        <Components.LayoutWidget ref={measureRef}>
            <div className="advanced-media-widget">
                {/* Video with fallback */}
                {props.useVideo && hasVideoSupport && (
                    <video
                        autoPlay
                        loop
                        muted
                        playsInline
                        onError={handleVideoError}
                        onLoadedData={() => setMediaLoaded(true)}
                        className="media-video"
                    >
                        <source src={imageUrls.video} type="video/mp4" />
                        {/* Fallback image */}
                        <img src={getCurrentImage()} alt="Video fallback" onLoad={() => setMediaLoaded(true)} />
                    </video>
                )}

                {/* Responsive image */}
                {(!props.useVideo || !hasVideoSupport) && (
                    <picture>
                        {/* Mobile */}
                        <source media="(max-width: 768px)" srcSet={Utils.createSourceSetValue(imageUrls.mobile)} />
                        {/* Tablet */}
                        <source media="(max-width: 1024px)" srcSet={Utils.createSourceSetValue(imageUrls.tablet)} />
                        {/* Desktop */}
                        <img
                            src={imageUrls.desktop}
                            srcSet={Utils.createSourceSetValue(imageUrls.desktop)}
                            alt="Responsive banner"
                            loading="lazy"
                            onLoad={() => setMediaLoaded(true)}
                        />
                    </picture>
                )}

                {/* Loading state */}
                {!mediaLoaded && <div className="media-loading">{Utils.t("Loading...")}</div>}
            </div>
        </Components.LayoutWidget>
    );
}
```

### File URL Structure

Uploaded files follow this URL pattern:

```
https://us.v-cdn.net/[SITE_ID]/uploads/[FILE_ID]/[FILENAME]
```

-   **SITE_ID**: Your community's unique identifier
-   **FILE_ID**: Unique identifier for the uploaded file
-   **FILENAME**: Original filename of the uploaded file

### Security and Access

-   **Automatic CDN**: Files are automatically served from a CDN for fast delivery
-   **HTTPS**: All uploaded files are served over HTTPS
-   **Access Control**: Files inherit the same permissions as your community
-   **Caching**: Files are automatically cached for optimal performance

## Overview

The **Custom Fragment** is a template for creating your own custom widgets using Vanilla's Widget Builder system. It provides access to powerful utilities through injectable dependencies, allowing you to build sophisticated, interactive widgets for your community.

## Available Injectables

When building custom fragments, you have access to these injectable dependencies:

-   **`Utils`** - Collection of utility functions, React hooks, and helper methods
-   **`Components`** - Pre-built UI components for consistent styling
-   **`Api`** - API utilities for data fetching
-   **`Custom`** - Custom props and configuration specific to your fragment

## Utils Library Reference

The **Utils library** (`@vanilla/injectables/Utils`) is the most comprehensive injectable available, providing everything you need to build powerful custom widgets.

### 🧭 Navigation & Routing

#### `useLinkNavigator()`

**Type**: `() => (url: string) => void`

Returns a smart navigation function that automatically determines whether to use fast in-app navigation or a full page refresh based on the destination URL.

**Perfect for**: Creating clickable discussion lists, category navigation, user profile links, "Read More" buttons, and any other interactive elements that need to redirect users.

**Example Usage:**

```tsx
// Get the navigation function
const navigate = Utils.useLinkNavigator();

// Navigate to different pages
navigate("/discussions/123/my-discussion"); // Discussion page
navigate("/categories/general"); // Category page
navigate("/profile/john-doe"); // User profile

// Use in click handlers
const handleDiscussionClick = (discussionID) => {
    navigate(`/discussions/${discussionID}`);
};
```

### 👤 User Context & Permissions

#### `useCurrentUser()`

**Type**: `() => IUser | null`

Returns the complete user object for the currently logged-in user, or `null` if no user is signed in.

**Perfect for**: Welcome messages, user avatars, personalized content recommendations, displaying user-specific stats.

**Example Usage:**

```tsx
// Get current user information
const currentUser = Utils.useCurrentUser();

// Conditional rendering based on user state
if (currentUser) {
    return (
        <div className="user-welcome">
            <img src={currentUser.photoUrl} alt={currentUser.name} />
            <span>Welcome back, {currentUser.name}!</span>
            <p>You have {currentUser.countDiscussions} discussions</p>
        </div>
    );
} else {
    return <div>Please sign in to see personalized content</div>;
}
```

#### `useCurrentUserSignedIn()`

**Type**: `() => boolean`

Returns a simple boolean indicating whether someone is currently logged in.

**Perfect for**: Showing/hiding "Sign In" buttons, conditional rendering based on authentication status.

#### `usePermissionsContext()`

**Type**: `() => IPermissionsContext`

Returns the current user's permissions context, allowing you to check what specific actions they're allowed to perform.

**Perfect for**: Hiding "Delete" buttons from non-moderators, showing admin-only widgets, enabling/disabling features based on permissions.

**Example Usage:**

```tsx
// Get user permissions
const permissions = Utils.usePermissionsContext();

// Conditionally render based on permissions
return (
    <div className="discussion-actions">
        {/* Regular users can create discussions */}
        {permissions.hasPermission("discussions.add") && <button>Create Discussion</button>}

        {/* Only users with edit permissions can edit */}
        {permissions.hasPermission("discussions.edit") && <button>Edit Discussion</button>}

        {/* Only moderators see moderation tools */}
        {permissions.hasPermission("community.moderate") && (
            <div className="moderation-tools">
                <button>Pin Discussion</button>
                <button>Close Discussion</button>
                <button>Delete Discussion</button>
            </div>
        )}
    </div>
);
```

### 🌍 Internationalization & Formatting

#### `t(translationKey, options?)`

**Type**: `(key: string, options?: Record<string, any>) => string`

Translates text into the user's preferred language using the forum's translation system.

**Perfect for**: All user-facing text in your widgets. Never hardcode English text!

**Example Usage:**

```tsx
// Basic translation
const title = Utils.t("Welcome");
const loading = Utils.t("Loading...");

// Translation with variables
const greeting = Utils.t("Hello, {name}!", { name: currentUser.name });
const lastSeen = Utils.t("Last seen {time}", { time: "2 hours ago" });

// Pluralization handling
const postCount = Utils.t("You have {count} {count, plural, one {post} other {posts}}", {
    count: userPosts,
});

// Common forum translations
const labels = {
    discussions: Utils.t("Discussions"),
    comments: Utils.t("Comments"),
    loadMore: Utils.t("Load More"),
    signIn: Utils.t("Sign In"),
    reply: Utils.t("Reply"),
    edit: Utils.t("Edit"),
    delete: Utils.t("Delete"),
};
```

#### `formatNumber(number, options?)`

**Type**: `(number: number, options?: Intl.NumberFormatOptions) => string`

Formats numbers according to the user's locale and specified formatting rules.

**Perfect for**: Displaying view counts, user statistics, currency amounts, percentages.

**Example Usage:**

```tsx
// Basic number formatting (respects user's locale)
const views = Utils.formatNumber(1234); // "1,234" (US) or "1.234" (EU)
const members = Utils.formatNumber(25678); // "25,678" or "25.678"

// Currency formatting
const price = Utils.formatNumber(1234.56, {
    style: "currency",
    currency: "USD",
}); // "$1,234.56"

const euroPrice = Utils.formatNumber(99.99, {
    style: "currency",
    currency: "EUR",
}); // "€99.99"

// Percentage formatting
const successRate = Utils.formatNumber(0.75, {
    style: "percent",
}); // "75%"

// Decimal places
const rating = Utils.formatNumber(4.67, {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
}); // "4.7"
```

#### `formatNumberCompact(number)`

**Type**: `(number: number) => string`

Formats large numbers in a compact, human-readable form using abbreviations.

**Perfect for**: View counts, member counts where space is limited.

**Example Usage:**

```tsx
// Compact number formatting for large numbers
const views = Utils.formatNumberCompact(1234); // "1.2K"
const members = Utils.formatNumberCompact(15000); // "15K"
const bigViews = Utils.formatNumberCompact(1500000); // "1.5M"
const massive = Utils.formatNumberCompact(2300000000); // "2.3B"

// Perfect for dashboard widgets
const stats = {
    totalMembers: Utils.formatNumberCompact(userCount),
    totalDiscussions: Utils.formatNumberCompact(discussionCount),
    totalViews: Utils.formatNumberCompact(viewCount),
};

// Use in templates
return (
    <div className="compact-stats">
        <span>{stats.totalMembers} members</span>
        <span>{stats.totalDiscussions} discussions</span>
        <span>{stats.totalViews} views</span>
    </div>
);
```

### 📊 Data Fetching (React Query)

#### `useQuery(options)`

**Type**: `(options: UseQueryOptions) => UseQueryResult`

Fetches data from APIs with automatic caching, background updates, retry logic, and loading states.

**Perfect for**: Loading discussion lists, user profiles, category information, search results.

**Example Usage:**

```tsx
// Basic data fetching for discussions
const {
    data: discussions,
    isLoading,
    error,
    refetch,
} = Utils.useQuery({
    queryKey: ["discussions", "recent"],
    queryFn: async () => {
        const response = await fetch("/api/v2/discussions?limit=10&sort=-dateLastComment");
        if (!response.ok) throw new Error("Failed to fetch discussions");
        return response.json();
    },
    staleTime: 5 * 60 * 1000, // Data is fresh for 5 minutes
    refetchOnWindowFocus: true, // Refetch when user returns to tab
});

// Handle different states in your component
if (isLoading) {
    return <div className="loading">{Utils.t("Loading discussions...")}</div>;
}

if (error) {
    return (
        <div className="error">
            <p>{Utils.t("Error loading discussions")}</p>
            <button onClick={refetch}>{Utils.t("Try Again")}</button>
        </div>
    );
}

// Render the fetched data
return (
    <div className="discussions-list">
        {discussions?.map((discussion) => (
            <div key={discussion.discussionID} className="discussion-item">
                <h3>{discussion.name}</h3>
                <p>{Utils.formatNumberCompact(discussion.countViews)} views</p>
                <p>{Utils.formatNumber(discussion.countComments)} comments</p>
            </div>
        ))}
    </div>
);
```

#### `useMutation(options)`

**Type**: `(options: UseMutationOptions) => UseMutationResult`

Handles data modifications with automatic loading states and error handling.

**Perfect for**: Creating posts, voting/liking content, following users, bookmarking discussions.

#### `useQueryClient()`

**Type**: `() => QueryClient`

Returns the React Query client for advanced cache management.

**Perfect for**: Optimistic updates, prefetching data, clearing cache after user actions.

### 📏 DOM & Measurement

#### `useMeasure()`

**Type**: `() => [React.RefCallback<Element>, DOMRect]`

Measures the actual dimensions of a DOM element in real-time.

**Perfect for**: Responsive layouts, conditional rendering based on size, adaptive navigation.

**Example Usage:**

```tsx
// Measure element dimensions for responsive design
const [measureRef, bounds] = Utils.useMeasure();

// Adapt layout based on available space
const isCompact = bounds.width < 300;
const isMobile = bounds.width < 768;
const showFullDetails = bounds.width > 600;

return (
    <div ref={measureRef} className="responsive-widget">
        {/* Conditional rendering based on size */}
        {isCompact ? (
            // Compact view for narrow spaces
            <div className="compact-layout">
                <h4>{discussion.name}</h4>
                <span>{Utils.formatNumberCompact(discussion.countViews)}</span>
            </div>
        ) : (
            // Full view for wider spaces
            <div className="full-layout">
                <h3>{discussion.name}</h3>
                <p>{discussion.excerpt}</p>
                <div className="meta-info">
                    <span>{Utils.formatNumber(discussion.countViews)} views</span>
                    <span>{Utils.formatNumber(discussion.countComments)} replies</span>
                    {showFullDetails && <span>Last activity: {discussion.dateLastComment}</span>}
                </div>
            </div>
        )}

        {/* Debug information (remove in production) */}
        <small className="debug-info">
            Size: {Math.round(bounds.width)}×{Math.round(bounds.height)}px
        </small>
    </div>
);
```

#### `useIsOverflowing()`

**Type**: `() => [React.RefCallback<Element>, boolean]`

Detects when an element's content is overflowing its container.

**Perfect for**: Adding "Show More" buttons, displaying ellipsis indicators, handling long content gracefully.

### 🎨 Styling

#### `Css.background(background)`

**Type**: `(background: Partial<IBackground> | undefined) => CSSProperties`

Converts a background configuration object into proper CSS style properties.

**Perfect for**: Custom widget backgrounds, category-specific styling, theme-aware widgets.

#### `classnames(...args)`

**Type**: `(...args: any[]) => string`

Combines multiple CSS class names intelligently, handling conditional classes.

**Perfect for**: State-based styling, permission-based classes, responsive classes.

**Example Usage:**

```tsx
// Basic class name combination
const className = Utils.classnames(
    "base-class", // Always included
    "widget-component", // Always included
    { active: isActive }, // Conditional
    { disabled: isDisabled }, // Conditional
    { loading: isLoading }, // Conditional
);

// Complex example with multiple conditions
const discussionClasses = Utils.classnames(
    "discussion-item", // Base class
    "widget-content", // Component class
    {
        "discussion-pinned": discussion.pinned, // State-based
        "discussion-closed": discussion.closed, // State-based
        "discussion-unread": !discussion.read, // State-based
        "discussion-own": discussion.insertUserID === currentUser?.userID, // User-based
        "can-moderate": permissions.hasPermission("community.moderate"), // Permission-based
    },
    discussion.featured && "discussion-featured", // Conditional class
    `discussion-category-${discussion.categoryID}`, // Dynamic class
    bounds.width < 400 && "compact-mode", // Responsive class
);

// Button styling with multiple states
const buttonClasses = Utils.classnames("btn", "btn-primary", {
    "btn-loading": isLoading,
    "btn-success": isSuccess,
    "btn-error": hasError,
    "btn-disabled": !canPerformAction,
});

return (
    <div className={discussionClasses}>
        <h3>{discussion.name}</h3>
        <button className={buttonClasses}>{isLoading ? Utils.t("Loading...") : Utils.t("Reply")}</button>
    </div>
);
```

### 🔧 Application Context

#### `getMeta(key, defaultValue?)`

**Type**: `(key: string, defaultValue?: any) => any`

Retrieves configuration values and metadata from the forum's application context.

**Perfect for**: Adapting to site settings, checking feature flags, getting API endpoints.

**Example Usage:**

```tsx
// Basic site configuration
const siteName = Utils.getMeta("ui.siteName", "Community");
const siteDescription = Utils.getMeta("ui.description", "");
const baseUrl = Utils.getMeta("context.host");
const currentTheme = Utils.getMeta("ui.themeKey");

// Feature flags - check if features are enabled
const isSearchEnabled = Utils.getMeta("ui.search.enabled", true);
const allowGuestPosting = Utils.getMeta("Garden.Registration.Method") !== "Invitation";
const isKnowledgeEnabled = Utils.getMeta("ui.knowledge.enabled", false);

// User and session information
const currentUserID = Utils.getMeta("context.userID", 0);
const userPermissions = Utils.getMeta("context.permissions", []);

// Branding and customization
const logoUrl = Utils.getMeta("ui.logo.desktop.url");
const primaryColor = Utils.getMeta("ui.color.primary");
const customCSS = Utils.getMeta("ui.customCSS");

// Adapt widget behavior based on configuration
return (
    <div className="forum-widget">
        <header>
            <h2>
                {siteName} - {Utils.t("Latest Discussions")}
            </h2>
            {siteDescription && <p>{siteDescription}</p>}
        </header>

        {/* Conditional features based on site settings */}
        {isSearchEnabled && (
            <div className="search-section">
                <input type="search" placeholder={Utils.t("Search discussions...")} />
            </div>
        )}

        {/* Style based on theme */}
        <div
            style={{
                color: primaryColor,
                backgroundColor: currentTheme === "dark" ? "#1a1a1a" : "#ffffff",
            }}
        >
            {/* Widget content */}
        </div>

        {/* Show posting options based on permissions */}
        {(allowGuestPosting || currentUserID > 0) && <button>{Utils.t("Start Discussion")}</button>}
    </div>
);
```

#### `getSiteSection()`

**Type**: `() => ISiteSection | null`

Returns information about the current section of the forum the user is viewing.

**Perfect for**: Section-specific content, context-aware widgets, multi-tenant customization.

#### `createSourceSetValue(imageUrl, sizes?)`

**Type**: `(imageUrl: string, sizes?: number[]) => string`

Generates responsive image source sets for different screen densities.

**Perfect for**: User avatars, category banners, ensuring images look great on all devices.

## Best Practices

1. **Always use Utils methods** - This ensures compatibility and security within the widget builder environment
2. **Handle loading and error states** - When using data fetching methods, always handle loading and error states appropriately
3. **Use internationalization** - Always use `Utils.t()` for text that users will see
4. **Check permissions** - Use `Utils.usePermissionsContext()` to conditionally render content based on user permissions
5. **Optimize performance** - Use React Query's caching features through `Utils.useQuery()`

## Example: Complete Custom Widget

This is a comprehensive example that demonstrates many of the Utils library features working together:

```tsx
import Utils from "@vanilla/injectables/Utils";
import Components from "@vanilla/injectables/Components";
import Custom from "@vanilla/injectables/CustomFragment";

export default function CustomFragment(props) {
    // === USER CONTEXT & PERMISSIONS ===
    const currentUser = Utils.useCurrentUser();
    const navigate = Utils.useLinkNavigator();
    const permissions = Utils.usePermissionsContext();

    // === RESPONSIVE DESIGN ===
    const [measureRef, bounds] = Utils.useMeasure();
    const isCompact = bounds.width < 400;

    // === SITE CONFIGURATION ===
    const siteName = Utils.getMeta("ui.siteName", "Community");

    // === DATA FETCHING ===
    const {
        data: discussions,
        isLoading,
        error,
        refetch,
    } = Utils.useQuery({
        queryKey: ["recent-discussions"],
        queryFn: async () => {
            const response = await fetch("/api/v2/discussions?limit=5&sort=-dateLastComment");
            if (!response.ok) throw new Error("Failed to fetch discussions");
            return response.json();
        },
        staleTime: 5 * 60 * 1000, // 5 minutes
    });

    // === DYNAMIC STYLING ===
    const widgetClasses = Utils.classnames("custom-discussions-widget", {
        "widget-compact": isCompact,
        "widget-full": !isCompact,
        "has-user": !!currentUser,
    });

    // === LOADING STATE ===
    if (isLoading) {
        return (
            <Components.LayoutWidget>
                <div className="loading-state">{Utils.t("Loading discussions...")}</div>
            </Components.LayoutWidget>
        );
    }

    // === ERROR STATE ===
    if (error) {
        return (
            <Components.LayoutWidget>
                <div className="error-state">
                    <p>{Utils.t("Error loading discussions")}</p>
                    <button onClick={refetch}>{Utils.t("Try Again")}</button>
                </div>
            </Components.LayoutWidget>
        );
    }

    // === MAIN RENDER ===
    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className={widgetClasses}>
                {/* === HEADER === */}
                <header className="widget-header">
                    <h2>{Utils.t("Recent Discussions from {siteName}", { siteName })}</h2>

                    {/* Personalized greeting */}
                    {currentUser && (
                        <p className="greeting">{Utils.t("Welcome back, {name}!", { name: currentUser.name })}</p>
                    )}
                </header>

                {/* === DISCUSSIONS LIST === */}
                <ul className="discussions-list">
                    {discussions?.map((discussion) => (
                        <li key={discussion.discussionID} className="discussion-item">
                            <button onClick={() => navigate(discussion.url)} className="discussion-link">
                                {discussion.name}
                            </button>

                            {/* Responsive stats display */}
                            <div className="discussion-stats">
                                {isCompact ? (
                                    <span>{Utils.formatNumberCompact(discussion.countViews)}</span>
                                ) : (
                                    <>
                                        <span>{Utils.formatNumber(discussion.countViews)} views</span>
                                        <span>{Utils.formatNumber(discussion.countComments)} replies</span>
                                    </>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>

                {/* === ACTIONS === */}
                <div className="widget-actions">
                    {/* Permission-based action buttons */}
                    {permissions.hasPermission("discussions.add") && (
                        <button onClick={() => navigate("/post/discussion")} className="create-button">
                            {Utils.t("Start New Discussion")}
                        </button>
                    )}

                    <button onClick={refetch} className="refresh-button">
                        {Utils.t("Refresh")}
                    </button>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

**This example demonstrates:**

-   🧭 **Navigation** with `useLinkNavigator()`
-   👤 **User context** with `useCurrentUser()` and `usePermissionsContext()`
-   🌍 **Internationalization** with `Utils.t()` and `formatNumber()`
-   📊 **Data fetching** with `useQuery()` including loading and error states
-   📏 **Responsive design** with `useMeasure()`
-   🎨 **Dynamic styling** with `classnames()`
-   🔧 **Site configuration** with `getMeta()`
