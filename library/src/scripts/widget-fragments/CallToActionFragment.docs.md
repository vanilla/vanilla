# Call to Action Fragment

## What is a Call to Action Fragment?

The **Call to Action Fragment** is a powerful promotional widget designed to drive user engagement and conversions within your community. It combines compelling messaging with strategic action buttons to guide users toward specific goals, whether that's signing up, participating in discussions, or exploring premium features.

Perfect for:

-   **User Onboarding**: Guide new members through key community features
-   **Feature Promotion**: Highlight new tools, categories, or community initiatives
-   **Event Marketing**: Promote webinars, contests, or special community events
-   **Subscription Drives**: Encourage upgrades to premium membership tiers
-   **Community Growth**: Drive specific actions like posting, following, or sharing

## Call to Action Fragment Props

The Call to Action Fragment accepts these configuration options through the `CallToAction.Props` interface:

**`title`** _(string)_  
Main headline that captures attention

**`description`** _(string)_  
Supporting text that explains the value proposition

**`alignment`** _("left" | "center" | "right")_  
Text and button alignment

**`textColor`** _(string)_  
Color for title and description text

**`button`** _(ButtonConfig)_  
Primary action button configuration

**`secondButton`** _(ButtonConfig)_  
Optional secondary action button

**`background`** _(BackgroundConfig)_  
Background styling and imagery

### Button Configuration

Each button accepts these properties:

```tsx
interface ButtonConfig {
    title: string; // Button text
    url: string; // Destination URL
    type?: "primary" | "standard"; // Button styling type
}
```

### Background Configuration

```tsx
interface BackgroundConfig {
    color?: string; // Solid background color
    image?: string; // Background image URL
    imageUrlSrcSet?: string; // Responsive image source set
}
```

## Essential Utils Methods for Call to Action Widgets

### 🧭 Advanced Navigation

#### `useLinkNavigator()`

Create sophisticated navigation flows that guide users through your community:

**Example Usage:**

```tsx
// Smart navigation with tracking and user context
const navigate = Utils.useLinkNavigator();
const currentUser = Utils.useCurrentUser();

const handleGetStartedClick = () => {
    if (currentUser) {
        // Existing users: Direct to community features
        navigate("/discussions/popular");
    } else {
        // New users: Guide through onboarding
        navigate("/register?source=cta-widget");
    }
};

const handleLearnMoreClick = () => {
    navigate("/help/getting-started");
};

return (
    <div className="cta__actions">
        <button className="btn-primary" onClick={handleGetStartedClick}>
            {currentUser ? Utils.t("Explore Discussions") : Utils.t("Join Community")}
        </button>

        <button className="btn-secondary" onClick={handleLearnMoreClick}>
            {Utils.t("Learn More")}
        </button>
    </div>
);
```

### 👤 User-Aware Content

#### `useCurrentUser()` and `usePermissionsContext()`

Personalize call-to-action messaging based on user status and permissions:

**Example Usage:**

```tsx
// Personalized CTA content based on user state
const currentUser = Utils.useCurrentUser();
const permissions = Utils.usePermissionsContext();

// Dynamic content based on user status
const getCtaContent = () => {
    if (!currentUser) {
        return {
            title: Utils.t("Join Our Community"),
            description: Utils.t("Connect with thousands of members sharing knowledge and ideas"),
            primaryAction: Utils.t("Sign Up Free"),
            secondaryAction: Utils.t("Learn More"),
        };
    }

    if (permissions.hasPermission("community.moderate")) {
        return {
            title: Utils.t("Welcome Back, Moderator"),
            description: Utils.t("Check out the latest reports and community activity"),
            primaryAction: Utils.t("View Moderation Queue"),
            secondaryAction: Utils.t("Community Stats"),
        };
    }

    return {
        title: Utils.t("Welcome Back, {name}!", { name: currentUser.name }),
        description: Utils.t("Discover new discussions and connect with your interests"),
        primaryAction: Utils.t("Browse Discussions"),
        secondaryAction: Utils.t("View Profile"),
    };
};

const ctaContent = getCtaContent();

return (
    <div className="personalized-cta">
        <h2>{ctaContent.title}</h2>
        <p>{ctaContent.description}</p>
        <div className="cta__actions">
            <button className="btn-primary">{ctaContent.primaryAction}</button>
            <button className="btn-secondary">{ctaContent.secondaryAction}</button>
        </div>
    </div>
);
```

### 📊 Dynamic Data Integration

#### `useQuery()`

Enhance CTA effectiveness with real-time data:

**Example Usage:**

```tsx
// CTA with dynamic community statistics
const { data: communityStats } = Utils.useQuery({
    queryKey: ["community-stats"],
    queryFn: async () => {
        const response = await fetch("/api/v2/analytics/community-overview");
        return response.json();
    },
    staleTime: 10 * 60 * 1000, // Cache for 10 minutes
});

// A/B test different CTA messages
const { data: activeExperiment } = Utils.useQuery({
    queryKey: ["cta-experiment"],
    queryFn: async () => {
        const response = await fetch("/api/v2/experiments/cta-messages");
        return response.json();
    },
});

const ctaMessage = activeExperiment?.message || {
    title: Utils.t("Join Our Growing Community"),
    description: Utils.t("Be part of the conversation"),
};

return (
    <div className="data-driven-cta">
        <h2>{ctaMessage.title}</h2>

        {/* Social proof with real stats */}
        {communityStats && (
            <div className="cta__social-proof">
                <p>
                    {Utils.t("Join {count} active members", {
                        count: Utils.formatNumberCompact(communityStats.activeMembers),
                    })}
                </p>
                <p>
                    {Utils.t("{count} discussions this week", {
                        count: Utils.formatNumberCompact(communityStats.weeklyDiscussions),
                    })}
                </p>
            </div>
        )}

        <p>{ctaMessage.description}</p>

        <div className="cta__actions">
            <button className="btn-primary">{Utils.t("Get Started")}</button>
        </div>
    </div>
);
```

### 🎨 Visual Customization

#### `createSourceSetValue()` and `Css.background()`

Create visually compelling CTAs with responsive imagery:

**Example Usage:**

```tsx
// Responsive CTA with optimized background images
const ctaImages = {
    mobile: "https://us.v-cdn.net/6038267/uploads/CTA123/mobile-bg.jpg",
    tablet: "https://us.v-cdn.net/6038267/uploads/CTA123/tablet-bg.jpg",
    desktop: "https://us.v-cdn.net/6038267/uploads/CTA123/desktop-bg.jpg",
};

const backgroundConfig = {
    color: "#1976D2",
    image: ctaImages.desktop,
    imageUrlSrcSet: Utils.createSourceSetValue(ctaImages.desktop),
    attachment: "fixed",
    position: "center",
};

return (
    <div
        className="visual-cta"
        style={{
            ...Utils.Css.background(backgroundConfig),
            minHeight: "400px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
        }}
    >
        <div className="cta__content">
            <h2 className="cta__title">{Utils.t("Transform Your Community Experience")}</h2>
            <p className="cta__description">
                {Utils.t("Unlock premium features and take your discussions to the next level")}
            </p>
            <button className="btn-primary btn-large">{Utils.t("Upgrade Now")}</button>
        </div>
    </div>
);
```

### 🌍 Localization & Formatting

#### `t()` and `formatNumber()`

Ensure global reach with proper localization:

**Example Usage:**

```tsx
// Multi-language CTA with locale-specific formatting
const currentLocale = Utils.getCurrentLocale();
const siteConfig = {
    memberCount: 15750,
    weeklyPosts: 1200,
    currency: Utils.getMeta("site.currency", "USD"),
    premiumPrice: 9.99,
};

const localizedContent = {
    title: Utils.t("Join {count} Members Worldwide", {
        count: Utils.formatNumber(siteConfig.memberCount),
    }),
    description: Utils.t("Over {count} new posts this week", {
        count: Utils.formatNumber(siteConfig.weeklyPosts),
    }),
    priceText: Utils.t("Starting at {price}/month", {
        price: Utils.formatNumber(siteConfig.premiumPrice, {
            style: "currency",
            currency: siteConfig.currency,
        }),
    }),
};

return (
    <div className="localized-cta">
        <h2>{localizedContent.title}</h2>
        <p>{localizedContent.description}</p>
        <div className="cta__pricing">
            <span className="price">{localizedContent.priceText}</span>
        </div>
        <button className="btn-primary">{Utils.t("Start Free Trial")}</button>
    </div>
);
```

## Advanced Call to Action Examples

### Conditional Feature Promotion

**Example Usage:**

```tsx
// CTA that promotes features based on user engagement
export default function FeaturePromotionCTA(props) {
    const currentUser = Utils.useCurrentUser();
    const navigate = Utils.useLinkNavigator();

    const { data: userActivity } = Utils.useQuery({
        queryKey: ["user-activity", currentUser?.userID],
        queryFn: async () => {
            const response = await fetch(`/api/v2/users/${currentUser.userID}/activity`);
            return response.json();
        },
        enabled: !!currentUser,
    });

    // Determine which feature to promote based on user behavior
    const getFeaturePromotion = () => {
        if (!userActivity) return null;

        const { postCount, reactionCount, viewCount } = userActivity;

        if (postCount < 5) {
            return {
                title: Utils.t("Share Your First Discussion"),
                description: Utils.t("Start a conversation and connect with community members"),
                action: Utils.t("Create Discussion"),
                url: "/post/discussion",
                color: "#2E7D32",
            };
        }

        if (reactionCount < 10) {
            return {
                title: Utils.t("Discover Reaction Features"),
                description: Utils.t("Express yourself with likes, insights, and custom reactions"),
                action: Utils.t("Learn About Reactions"),
                url: "/help/reactions",
                color: "#FF6B35",
            };
        }

        return {
            title: Utils.t("Unlock Premium Features"),
            description: Utils.t("Get advanced tools and exclusive content access"),
            action: Utils.t("Upgrade Account"),
            url: "/premium",
            color: "#6B46C1",
        };
    };

    const promotion = getFeaturePromotion();

    if (!promotion) {
        return null;
    }

    return (
        <Components.LayoutWidget>
            <div
                className="feature-promotion-cta"
                style={{
                    "--promotion-color": promotion.color,
                    background: `linear-gradient(135deg, ${promotion.color}15, ${promotion.color}05)`,
                }}
            >
                <div className="cta__content">
                    <h2>{promotion.title}</h2>
                    <p>{promotion.description}</p>

                    <button className="btn-primary" onClick={() => navigate(promotion.url)}>
                        {promotion.action}
                    </button>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

### Time-Sensitive Campaign CTA

**Example Usage:**

```tsx
// CTA with countdown timer and urgency messaging
export default function CampaignCTA(props) {
    const navigate = Utils.useLinkNavigator();
    const [timeRemaining, setTimeRemaining] = useState(null);

    // Campaign configuration
    const campaign = {
        title: Utils.t("Limited Time: Premium Access"),
        endDate: new Date("2024-12-31T23:59:59"),
        discount: 50,
        originalPrice: 19.99,
        campaignUrl: "/premium?campaign=holiday2024",
    };

    // Calculate time remaining
    useEffect(() => {
        const calculateTimeRemaining = () => {
            const now = new Date();
            const timeDiff = campaign.endDate.getTime() - now.getTime();

            if (timeDiff <= 0) {
                setTimeRemaining(null);
                return;
            }

            const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));

            setTimeRemaining({ days, hours, minutes });
        };

        calculateTimeRemaining();
        const timer = setInterval(calculateTimeRemaining, 60000); // Update every minute

        return () => clearInterval(timer);
    }, []);

    if (!timeRemaining) {
        return null; // Campaign has ended
    }

    const discountedPrice = campaign.originalPrice * (1 - campaign.discount / 100);

    return (
        <Components.LayoutWidget>
            <div className="campaign-cta">
                <div className="cta__urgency">
                    <span className="urgency-badge">{Utils.t("Limited Time")}</span>
                </div>

                <h2>{campaign.title}</h2>

                <div className="cta__countdown">
                    <span className="countdown-label">{Utils.t("Ends in:")}</span>
                    <div className="countdown-timer">
                        <span className="time-unit">
                            {timeRemaining.days}
                            <small>{Utils.t("days")}</small>
                        </span>
                        <span className="time-unit">
                            {timeRemaining.hours}
                            <small>{Utils.t("hours")}</small>
                        </span>
                        <span className="time-unit">
                            {timeRemaining.minutes}
                            <small>{Utils.t("min")}</small>
                        </span>
                    </div>
                </div>

                <div className="cta__pricing">
                    <span className="original-price">
                        {Utils.formatNumber(campaign.originalPrice, {
                            style: "currency",
                            currency: "USD",
                        })}
                    </span>
                    <span className="discounted-price">
                        {Utils.formatNumber(discountedPrice, {
                            style: "currency",
                            currency: "USD",
                        })}
                    </span>
                    <span className="savings">{Utils.t("Save {percent}%", { percent: campaign.discount })}</span>
                </div>

                <button className="btn-primary btn-large" onClick={() => navigate(campaign.campaignUrl)}>
                    {Utils.t("Claim Discount")}
                </button>
            </div>
        </Components.LayoutWidget>
    );
}
```

### Social Proof CTA

**Example Usage:**

```tsx
// CTA enhanced with social proof and testimonials
export default function SocialProofCTA(props) {
    const navigate = Utils.useLinkNavigator();

    const { data: socialProof } = Utils.useQuery({
        queryKey: ["social-proof"],
        queryFn: async () => {
            const response = await fetch("/api/v2/testimonials/featured");
            return response.json();
        },
    });

    const testimonials = socialProof?.testimonials || [];
    const stats = socialProof?.stats || {};

    return (
        <Components.LayoutWidget>
            <div className="social-proof-cta">
                <div className="cta__stats">
                    <div className="stat-item">
                        <span className="stat-number">{Utils.formatNumberCompact(stats.totalMembers || 0)}</span>
                        <span className="stat-label">{Utils.t("Members")}</span>
                    </div>
                    <div className="stat-item">
                        <span className="stat-number">{Utils.formatNumberCompact(stats.totalDiscussions || 0)}</span>
                        <span className="stat-label">{Utils.t("Discussions")}</span>
                    </div>
                    <div className="stat-item">
                        <span className="stat-number">{stats.averageRating || "5.0"}</span>
                        <span className="stat-label">{Utils.t("Rating")}</span>
                    </div>
                </div>

                <h2>{Utils.t("Join the Community Leaders Trust")}</h2>

                {/* Featured testimonial */}
                {testimonials.length > 0 && (
                    <div className="cta__testimonial">
                        <blockquote>"{testimonials[0].content}"</blockquote>
                        <cite>
                            <strong>{testimonials[0].author}</strong>
                            <span>{testimonials[0].role}</span>
                        </cite>
                    </div>
                )}

                <div className="cta__actions">
                    <button className="btn-primary" onClick={() => navigate("/register")}>
                        {Utils.t("Join {count} Members", {
                            count: Utils.formatNumberCompact(stats.totalMembers || 0),
                        })}
                    </button>

                    <button className="btn-secondary" onClick={() => navigate("/testimonials")}>
                        {Utils.t("Read More Reviews")}
                    </button>
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

## Call to Action Best Practices

### Conversion Optimization

-   **Clear Value Proposition**: Communicate benefits, not just features
-   **Action-Oriented Language**: Use verbs like "Get," "Join," "Start," "Discover"
-   **Social Proof**: Include member counts, testimonials, or activity stats
-   **Urgency Elements**: Limited-time offers, countdown timers, or scarcity messaging

### Visual Design

-   **Contrasting Colors**: Make buttons stand out from the background
-   **Responsive Layouts**: Use `Utils.useMeasure()` for device adaptation
-   **Whitespace**: Give your CTA room to breathe and draw attention
-   **Consistent Branding**: Match your community's visual identity

### User Experience

-   **Relevance**: Show different CTAs based on user status and behavior
-   **Positioning**: Place CTAs where users naturally look or pause
-   **Testing**: Use A/B testing to optimize messaging and design
-   **Mobile First**: Ensure CTAs work well on all device sizes

### Performance Tracking

-   **Analytics Integration**: Track clicks, conversions, and user flow
-   **Experimentation**: Test different messages, colors, and placements
-   **User Feedback**: Monitor how CTAs affect user satisfaction
-   **Continuous Improvement**: Regularly update based on performance data

The Call to Action Fragment is your community's conversion engine - use it strategically to guide users toward meaningful engagement and growth.
