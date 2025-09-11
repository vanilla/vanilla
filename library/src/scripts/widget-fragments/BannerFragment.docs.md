# Banner Fragment

## What is a Banner Fragment?

The **Banner Fragment** is a hero banner widget designed to create visually striking headers for your community pages. It combines customizable backgrounds, compelling copy, and optional search functionality to create an engaging entry point for your users.

Perfect for:

-   **Homepage Headers**: Welcome visitors with branded messaging
-   **Category Pages**: Highlight specific topics or communities
-   **Landing Pages**: Drive engagement with calls-to-action
-   **Event Announcements**: Promote special events or campaigns
-   **Brand Reinforcement**: Display your community's identity consistently

## Banner Fragment Props

The Banner Fragment accepts these configuration options through the `Banner.Props` interface:

**`title`** _(string)_  
Main heading text displayed prominently

**`titleType`** _("none" | "standard")_  
Controls whether title is displayed

**`description`** _(string)_  
Descriptive text below the title

**`descriptionType`** _("none" | "standard")_  
Controls whether description is displayed

**`background`** _(object)_  
Background configuration (color, image, overlay)

**`textColor`** _(string)_  
Color for title and description text

**`alignment`** _("left" | "center" | "right")_  
Text alignment within the banner

**`showSearch`** _(boolean)_  
Whether to display search functionality

### Background Configuration

The `background` prop accepts an object with these properties:

```tsx
interface BackgroundConfig {
    color?: string; // Solid background color
    imageUrlSrcSet?: string; // Responsive image source set
    imageSource?: string; // Single image URL
    useOverlay?: boolean; // Dark overlay for better text readability
}
```

## Essential Utils Methods for Banner Widgets

### 📏 Responsive Design

#### `useMeasure()`

Track banner dimensions to adapt layout for different screen sizes:

**Example Usage:**

```tsx
// Responsive banner layout
const [measureRef, bounds] = Utils.useMeasure();
const isDesktop = bounds.width > 806;
const isMobile = bounds.width < 768;

return (
    <Components.LayoutWidget ref={measureRef}>
        {/* Desktop: Show full search bar */}
        {isDesktop && showSearch && (
            <div className="banner__search-desktop">
                <input type="search" placeholder={Utils.t("Search our community...")} />
                <button>{Utils.t("Search")}</button>
            </div>
        )}

        {/* Mobile: Show search icon only */}
        {isMobile && showSearch && (
            <button className="banner__search-mobile">
                <Components.Icon icon="search" />
            </button>
        )}
    </Components.LayoutWidget>
);
```

### 🎨 Dynamic Styling

#### `createSourceSetValue()`

Create responsive image source sets for crisp banners on all devices:

**Example Usage:**

```tsx
// Responsive banner images
const bannerImages = {
    mobile: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-mobile.jpg",
    tablet: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-tablet.jpg",
    desktop: "https://us.v-cdn.net/6038267/uploads/ABC123/banner-desktop.jpg",
};

return (
    <picture className="banner__image">
        <source media="(max-width: 768px)" srcSet={Utils.createSourceSetValue(bannerImages.mobile)} />
        <source media="(max-width: 1024px)" srcSet={Utils.createSourceSetValue(bannerImages.tablet)} />
        <img
            src={bannerImages.desktop}
            srcSet={Utils.createSourceSetValue(bannerImages.desktop)}
            alt="Community banner"
        />
    </picture>
);
```

### 🌍 Content Localization

#### `t()` - Internationalization

Ensure your banner text works globally:

**Example Usage:**

```tsx
// Multi-language banner content
const bannerContent = {
    welcomeTitle: Utils.t("Welcome to {siteName}", { siteName: Utils.getMeta("ui.siteName") }),
    tagline: Utils.t("Join thousands of members discussing {topic}", { topic: "technology" }),
    searchPlaceholder: Utils.t("What are you looking for?"),
    ctaButton: Utils.t("Get Started"),
    learnMore: Utils.t("Learn More"),
};

return (
    <div className="banner__content">
        <h1>{bannerContent.welcomeTitle}</h1>
        <p>{bannerContent.tagline}</p>
        <div className="banner__actions">
            <button className="btn-primary">{bannerContent.ctaButton}</button>
            <button className="btn-secondary">{bannerContent.learnMore}</button>
        </div>
    </div>
);
```

### 🧭 Navigation Integration

#### `useLinkNavigator()`

Create interactive banner elements that navigate users:

**Example Usage:**

```tsx
// Interactive banner with navigation
const navigate = Utils.useLinkNavigator();

const handleExploreClick = () => {
    navigate("/categories");
};

const handleStartDiscussionClick = () => {
    navigate("/post/discussion");
};

return (
    <div className="banner__interactive">
        <h1>{Utils.t("Welcome to our Community")}</h1>
        <p>{Utils.t("Discover discussions, share ideas, and connect with others")}</p>

        <div className="banner__cta-buttons">
            <button className="btn-primary" onClick={handleExploreClick}>
                {Utils.t("Explore Discussions")}
            </button>

            <button className="btn-secondary" onClick={handleStartDiscussionClick}>
                {Utils.t("Start a Discussion")}
            </button>
        </div>
    </div>
);
```

### 🔧 Site Configuration

#### `getMeta()` - Dynamic Content

Adapt banner content based on site configuration:

**Example Usage:**

```tsx
// Site-aware banner content
const siteConfig = {
    name: Utils.getMeta("ui.siteName", "Community"),
    description: Utils.getMeta("ui.description", ""),
    brandColor: Utils.getMeta("ui.color.primary", "#0291db"),
    allowGuestViewing: Utils.getMeta("Garden.Registration.Method") !== "Invitation",
};

return (
    <div
        className="banner__branded"
        style={{
            "--brand-color": siteConfig.brandColor,
            "--brand-text": siteConfig.name,
        }}
    >
        <h1>{Utils.t("Welcome to {siteName}", { siteName: siteConfig.name })}</h1>

        {siteConfig.description && <p>{siteConfig.description}</p>}

        {/* Show different content based on site permissions */}
        {siteConfig.allowGuestViewing ? (
            <p>{Utils.t("Browse our discussions and join the conversation")}</p>
        ) : (
            <p>{Utils.t("Sign up to join our exclusive community")}</p>
        )}
    </div>
);
```

## Advanced Banner Examples

### Dynamic Content Banner

**Example Usage:**

```tsx
// Banner that displays different content based on user status and time
export default function DynamicBanner(props) {
    const currentUser = Utils.useCurrentUser();
    const permissions = Utils.usePermissionsContext();
    const [measureRef, bounds] = Utils.useMeasure();

    // Time-based content
    const currentHour = new Date().getHours();
    const greeting =
        currentHour < 12
            ? Utils.t("Good morning")
            : currentHour < 17
            ? Utils.t("Good afternoon")
            : Utils.t("Good evening");

    // User-specific content
    const userContent = currentUser
        ? {
              title: Utils.t("{greeting}, {name}!", { greeting, name: currentUser.name }),
              description: Utils.t("Welcome back to your community. Check out what's new!"),
          }
        : {
              title: Utils.t("{greeting}! Welcome to our community", { greeting }),
              description: Utils.t("Join thousands of members sharing knowledge and ideas"),
          };

    // Site statistics for engagement
    const { data: stats } = Utils.useQuery({
        queryKey: ["site-stats"],
        queryFn: async () => {
            const response = await fetch("/api/v2/site/stats");
            return response.json();
        },
    });

    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className="dynamic-banner">
                <div className="banner__hero">
                    <h1>{userContent.title}</h1>
                    <p>{userContent.description}</p>

                    {/* Show stats if available */}
                    {stats && (
                        <div className="banner__stats">
                            <span>{Utils.formatNumberCompact(stats.totalUsers)} members</span>
                            <span>{Utils.formatNumberCompact(stats.totalDiscussions)} discussions</span>
                            <span>{Utils.formatNumberCompact(stats.totalComments)} comments</span>
                        </div>
                    )}

                    {/* Responsive actions */}
                    <div className="banner__actions">
                        {bounds.width > 768 && (
                            <Components.SearchBox placeholder={Utils.t("Search discussions...")} buttonType="primary" />
                        )}

                        {!currentUser && <button className="btn-primary">{Utils.t("Join Our Community")}</button>}
                    </div>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

### Event Promotion Banner

**Example Usage:**

```tsx
// Special banner for promoting events or announcements
export default function EventBanner(props) {
    const navigate = Utils.useLinkNavigator();
    const [measureRef, bounds] = Utils.useMeasure();

    // Event configuration (could come from props or API)
    const eventConfig = {
        title: Utils.t("Community Meetup 2024"),
        date: new Date("2024-06-15T18:00:00"),
        location: Utils.t("Virtual Event"),
        registrationUrl: "/events/meetup-2024/register",
        backgroundImage: "https://us.v-cdn.net/6038267/uploads/EVENT123/meetup-banner.jpg",
    };

    // Time calculations
    const timeUntilEvent = eventConfig.date.getTime() - Date.now();
    const daysUntil = Math.ceil(timeUntilEvent / (1000 * 60 * 60 * 24));

    const handleRegisterClick = () => {
        navigate(eventConfig.registrationUrl);
    };

    return (
        <Components.LayoutWidget>
            <div
                ref={measureRef}
                className="event-banner"
                style={{
                    backgroundImage: `url(${eventConfig.backgroundImage})`,
                    backgroundSize: "cover",
                    backgroundPosition: "center",
                }}
            >
                <div className="event-banner__overlay">
                    <div className="event-banner__content">
                        <h1>{eventConfig.title}</h1>

                        {/* Event details */}
                        <div className="event-banner__details">
                            <p className="event-date">
                                {eventConfig.date.toLocaleDateString(Utils.getCurrentLocale())}
                            </p>
                            <p className="event-location">{eventConfig.location}</p>

                            {/* Countdown */}
                            {daysUntil > 0 && (
                                <p className="event-countdown">
                                    {Utils.t("{count} days until the event", { count: daysUntil })}
                                </p>
                            )}
                        </div>

                        {/* Registration CTA */}
                        <div className="event-banner__actions">
                            <button className="btn-primary btn-large" onClick={handleRegisterClick}>
                                {Utils.t("Register Now")}
                            </button>

                            {bounds.width > 768 && <button className="btn-secondary">{Utils.t("Learn More")}</button>}
                        </div>
                    </div>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

### Category-Specific Banner

**Example Usage:**

```tsx
// Banner that adapts to different categories
export default function CategoryBanner(props) {
    const siteSection = Utils.getSiteSection();
    const navigate = Utils.useLinkNavigator();

    // Category-specific configuration
    const categoryConfig = {
        general: {
            title: Utils.t("General Discussion"),
            description: Utils.t("Share your thoughts on any topic"),
            color: "#2D7D32",
            icon: "chat",
        },
        tech: {
            title: Utils.t("Technology Hub"),
            description: Utils.t("Discuss the latest in technology"),
            color: "#1976D2",
            icon: "code",
        },
        support: {
            title: Utils.t("Help & Support"),
            description: Utils.t("Get help from our community"),
            color: "#F57C00",
            icon: "help",
        },
    };

    const currentCategory = siteSection?.categoryID ? categoryConfig[siteSection.categoryID] : categoryConfig.general;

    return (
        <Components.LayoutWidget>
            <div
                className="category-banner"
                style={{
                    "--category-color": currentCategory.color,
                    background: `linear-gradient(135deg, ${currentCategory.color}20, ${currentCategory.color}05)`,
                }}
            >
                <div className="category-banner__content">
                    <div className="category-banner__icon">
                        <Components.Icon icon={currentCategory.icon} />
                    </div>

                    <div className="category-banner__text">
                        <h1>{currentCategory.title}</h1>
                        <p>{currentCategory.description}</p>
                    </div>

                    <div className="category-banner__actions">
                        <button className="btn-primary" onClick={() => navigate("/post/discussion")}>
                            {Utils.t("Start Discussion")}
                        </button>
                    </div>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

## Banner Best Practices

### Performance Optimization

-   Use `Utils.createSourceSetValue()` for responsive images
-   Implement lazy loading for large background images
-   Optimize image formats (WebP when possible)

### Accessibility

-   Always provide alt text for banner images
-   Ensure sufficient color contrast for text
-   Use semantic HTML structure (h1, p, button)

### Mobile Responsiveness

-   Use `Utils.useMeasure()` to adapt layouts
-   Prioritize content hierarchy on smaller screens
-   Test touch interactions on mobile devices

### Content Strategy

-   Keep banner text concise and actionable
-   Use `Utils.t()` for all user-facing text
-   Align banner content with your community's goals

The Banner Fragment is your community's first impression - make it count by combining compelling visuals with clear, actionable messaging that welcomes users and guides them toward engagement.
