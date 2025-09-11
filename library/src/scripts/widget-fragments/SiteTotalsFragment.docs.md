# Site Totals Fragment

## What is a Site Totals Fragment?

The **Site Totals Fragment** is a statistics display widget that showcases your community's growth and engagement metrics. It presents key numbers like member counts, discussion totals, and activity statistics in an attractive, easy-to-read format that builds social proof and encourages participation.

Perfect for:

-   **Community Dashboards**: Display key metrics prominently on your homepage
-   **Social Proof**: Show potential members how active your community is
-   **Growth Tracking**: Highlight milestones and community achievements
-   **Engagement Metrics**: Feature discussion counts, comments, and user activity
-   **Seasonal Campaigns**: Showcase special events, contests, or milestone celebrations

## Site Totals Fragment Props

The Site Totals Fragment accepts these configuration options through the `SiteTotals.Props` interface:

**`totals`** _(TotalItem[])_  
Array of statistics to display

**`containerOptions`** _(ContainerOptions)_  
Styling and layout configuration

**`formatNumbers`** _(boolean)_  
Whether to use compact number formatting

### Total Item Configuration

Each statistic in the `totals` array accepts these properties:

```tsx
interface TotalItem {
    recordType: string; // Unique identifier for the statistic
    label: string; // Display name for the statistic
    count: number; // The actual number value
    iconName: string; // Icon to display next to the statistic
    isCalculating?: boolean; // Whether the count is still being calculated
}
```

### Container Options

```tsx
interface ContainerOptions {
    background?: IBackground; // Background styling configuration
    alignment?: "left" | "center" | "right"; // Content alignment
    textColor?: string; // Text color for labels and numbers
}
```

## Essential Utils Methods for Site Totals Widgets

### 📊 Number Formatting

#### `formatNumber()` and `formatNumberCompact()`

Display statistics in user-friendly, locale-aware formats:

**Example Usage:**

```tsx
// Different formatting approaches for statistics
const memberCount = 15750;
const discussionCount = 2500;
const commentCount = 45000;

// Standard formatting (respects user's locale)
const standardFormat = {
    members: Utils.formatNumber(memberCount), // "15,750" or "15.750"
    discussions: Utils.formatNumber(discussionCount), // "2,500" or "2.500"
    comments: Utils.formatNumber(commentCount), // "45,000" or "45.000"
};

// Compact formatting for space-constrained displays
const compactFormat = {
    members: Utils.formatNumberCompact(memberCount), // "15.8K"
    discussions: Utils.formatNumberCompact(discussionCount), // "2.5K"
    comments: Utils.formatNumberCompact(commentCount), // "45K"
};

// Use based on available space
const [measureRef, bounds] = Utils.useMeasure();
const useCompactFormat = bounds.width < 400;

const formatFn = useCompactFormat ? Utils.formatNumberCompact : Utils.formatNumber;

return (
    <div ref={measureRef} className="site-totals">
        <div className="stat-item">
            <span className="stat-number">{formatFn(memberCount)}</span>
            <span className="stat-label">{Utils.t("Members")}</span>
        </div>
    </div>
);
```

### 🌍 Internationalization

#### `t()` and `translate()`

Ensure statistics labels work globally:

**Example Usage:**

```tsx
// Multi-language statistics labels
const statisticLabels = {
    members: Utils.t("Members"),
    discussions: Utils.t("Discussions"),
    comments: Utils.t("Comments"),
    categories: Utils.t("Categories"),
    onlineNow: Utils.t("Online Now"),
    todaysPosts: Utils.t("Today's Posts"),
    thisWeek: Utils.t("This Week"),
    thisMonth: Utils.t("This Month"),
    allTime: Utils.t("All Time"),
    activeUsers: Utils.t("Active Users"),
};

// Dynamic labels with pluralization
const getDynamicLabel = (count, singular, plural) => {
    return Utils.t("{count} {label}", {
        count: Utils.formatNumber(count),
        label: count === 1 ? singular : plural,
    });
};

const dynamicLabels = {
    memberCount: getDynamicLabel(memberCount, Utils.t("member"), Utils.t("members")),
    discussionCount: getDynamicLabel(discussionCount, Utils.t("discussion"), Utils.t("discussions")),
};
```

### 📊 Real-Time Data

#### `useQuery()`

Fetch and display live community statistics:

**Example Usage:**

```tsx
// Live statistics with auto-refresh
const {
    data: liveStats,
    isLoading,
    error,
} = Utils.useQuery({
    queryKey: ["site-totals", "live"],
    queryFn: async () => {
        const response = await fetch("/api/v2/analytics/site-totals");
        if (!response.ok) throw new Error("Failed to fetch statistics");
        return response.json();
    },
    refetchInterval: 30000, // Refresh every 30 seconds
    staleTime: 15000, // Data is fresh for 15 seconds
});

// Enhanced statistics with growth indicators
const { data: growthStats } = Utils.useQuery({
    queryKey: ["site-totals", "growth"],
    queryFn: async () => {
        const response = await fetch("/api/v2/analytics/growth-metrics");
        return response.json();
    },
    staleTime: 5 * 60 * 1000, // Cache for 5 minutes
});

// Combine live data with growth trends
const enhancedStats = liveStats?.map((stat) => ({
    ...stat,
    growthRate: growthStats?.find((g) => g.type === stat.recordType)?.growthRate || 0,
    previousPeriod: growthStats?.find((g) => g.type === stat.recordType)?.previousValue || 0,
}));

return (
    <div className="live-site-totals">
        {enhancedStats?.map((stat) => (
            <div key={stat.recordType} className="stat-item">
                <Components.Icon icon={stat.iconName} />
                <span className="stat-number">{Utils.formatNumber(stat.count)}</span>
                <span className="stat-label">{Utils.translate(stat.label)}</span>

                {/* Growth indicator */}
                {stat.growthRate > 0 && (
                    <span className="growth-indicator positive">+{Utils.formatNumber(stat.growthRate)}%</span>
                )}
            </div>
        ))}
    </div>
);
```

### 🎨 Dynamic Styling

#### `Css.background()` and `classnames()`

Create visually appealing statistics displays:

**Example Usage:**

```tsx
// Themed statistics with background styling
const containerOptions = {
    background: {
        color: "#f8f9fa",
        image: "https://us.v-cdn.net/6038267/uploads/STATS123/stats-bg.jpg",
        attachment: "fixed",
        position: "center",
    },
    alignment: "center",
    textColor: "#333",
};

const statsClasses = Utils.classnames("site-totals-widget", "stats-enhanced", {
    "stats-dark-theme": Utils.getMeta("ui.themeKey") === "dark",
    "stats-compact": bounds.width < 600,
    "stats-loading": isLoading,
});

return (
    <div
        className={statsClasses}
        style={{
            ...Utils.Css.background(containerOptions.background),
            color: containerOptions.textColor,
            textAlign: containerOptions.alignment,
        }}
    >
        <div className="stats-container">{/* Statistics content */}</div>
    </div>
);
```

### 📱 Responsive Design

#### `useMeasure()`

Adapt statistics layout for different screen sizes:

**Example Usage:**

```tsx
// Responsive statistics layout
const [measureRef, bounds] = Utils.useMeasure();

const getLayoutConfig = () => {
    const width = bounds.width;

    if (width < 400) {
        return {
            columns: 2,
            showIcons: false,
            useCompactNumbers: true,
            orientation: "vertical",
        };
    } else if (width < 768) {
        return {
            columns: 3,
            showIcons: true,
            useCompactNumbers: true,
            orientation: "vertical",
        };
    } else {
        return {
            columns: 4,
            showIcons: true,
            useCompactNumbers: false,
            orientation: "horizontal",
        };
    }
};

const layoutConfig = getLayoutConfig();
const formatFn = layoutConfig.useCompactNumbers ? Utils.formatNumberCompact : Utils.formatNumber;

return (
    <div ref={measureRef} className={`site-totals-responsive columns-${layoutConfig.columns}`}>
        {totals.map((stat) => (
            <div key={stat.recordType} className="stat-item">
                {layoutConfig.showIcons && <Components.Icon icon={stat.iconName} />}
                <span className="stat-number">{formatFn(stat.count)}</span>
                <span className="stat-label">{Utils.translate(stat.label)}</span>
            </div>
        ))}
    </div>
);
```

## Advanced Site Totals Examples

### Real-Time Activity Dashboard

**Example Usage:**

```tsx
// Comprehensive activity dashboard with live updates
export default function ActivityDashboard(props) {
    const [measureRef, bounds] = Utils.useMeasure();
    const currentUser = Utils.useCurrentUser();

    // Live activity statistics
    const { data: activityStats } = Utils.useQuery({
        queryKey: ["activity-dashboard"],
        queryFn: async () => {
            const response = await fetch("/api/v2/analytics/activity-dashboard");
            return response.json();
        },
        refetchInterval: 10000, // Update every 10 seconds
    });

    // User-specific statistics
    const { data: userStats } = Utils.useQuery({
        queryKey: ["user-stats", currentUser?.userID],
        queryFn: async () => {
            const response = await fetch(`/api/v2/users/${currentUser.userID}/stats`);
            return response.json();
        },
        enabled: !!currentUser,
        staleTime: 60000, // Cache for 1 minute
    });

    const generalStats = [
        {
            recordType: "members",
            label: Utils.t("Total Members"),
            count: activityStats?.totalMembers || 0,
            iconName: "users",
            isCalculating: !activityStats,
        },
        {
            recordType: "discussions",
            label: Utils.t("Discussions"),
            count: activityStats?.totalDiscussions || 0,
            iconName: "discussion",
            isCalculating: !activityStats,
        },
        {
            recordType: "comments",
            label: Utils.t("Comments"),
            count: activityStats?.totalComments || 0,
            iconName: "comment",
            isCalculating: !activityStats,
        },
        {
            recordType: "online",
            label: Utils.t("Online Now"),
            count: activityStats?.onlineUsers || 0,
            iconName: "online",
            isCalculating: !activityStats,
        },
    ];

    const userPersonalStats =
        currentUser && userStats
            ? [
                  {
                      recordType: "user-posts",
                      label: Utils.t("Your Posts"),
                      count: userStats.totalPosts || 0,
                      iconName: "edit",
                      isCalculating: !userStats,
                  },
                  {
                      recordType: "user-reactions",
                      label: Utils.t("Reactions Received"),
                      count: userStats.totalReactions || 0,
                      iconName: "heart",
                      isCalculating: !userStats,
                  },
              ]
            : [];

    const formatNumbers = bounds.width < 500;

    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className="activity-dashboard">
                <div className="dashboard-section">
                    <h3>{Utils.t("Community Activity")}</h3>
                    <div className="stats-grid">
                        {generalStats.map((stat) => (
                            <div key={stat.recordType} className="stat-item">
                                <Components.Icon icon={stat.iconName} />
                                <span className="stat-number">
                                    {stat.isCalculating
                                        ? "???"
                                        : formatNumbers
                                        ? Utils.formatNumberCompact(stat.count)
                                        : Utils.formatNumber(stat.count)}
                                </span>
                                <span className="stat-label">{stat.label}</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Personal statistics for logged-in users */}
                {currentUser && userPersonalStats.length > 0 && (
                    <div className="dashboard-section">
                        <h3>{Utils.t("Your Activity")}</h3>
                        <div className="stats-grid">
                            {userPersonalStats.map((stat) => (
                                <div key={stat.recordType} className="stat-item personal">
                                    <Components.Icon icon={stat.iconName} />
                                    <span className="stat-number">
                                        {stat.isCalculating
                                            ? "???"
                                            : formatNumbers
                                            ? Utils.formatNumberCompact(stat.count)
                                            : Utils.formatNumber(stat.count)}
                                    </span>
                                    <span className="stat-label">{stat.label}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </Components.LayoutWidget>
    );
}
```

### Growth Metrics Widget

**Example Usage:**

```tsx
// Statistics widget with growth trends and comparisons
export default function GrowthMetrics(props) {
    const [measureRef, bounds] = Utils.useMeasure();
    const [timePeriod, setTimePeriod] = useState("week");

    // Growth statistics for different time periods
    const { data: growthData } = Utils.useQuery({
        queryKey: ["growth-metrics", timePeriod],
        queryFn: async () => {
            const response = await fetch(`/api/v2/analytics/growth?period=${timePeriod}`);
            return response.json();
        },
        staleTime: 5 * 60 * 1000, // Cache for 5 minutes
    });

    const timePeriods = [
        { value: "day", label: Utils.t("Today") },
        { value: "week", label: Utils.t("This Week") },
        { value: "month", label: Utils.t("This Month") },
        { value: "year", label: Utils.t("This Year") },
    ];

    const getGrowthIndicator = (current, previous) => {
        if (!previous || previous === 0) return null;

        const growthRate = ((current - previous) / previous) * 100;
        const isPositive = growthRate > 0;

        return {
            rate: Math.abs(growthRate),
            direction: isPositive ? "up" : "down",
            isPositive,
        };
    };

    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className="growth-metrics">
                <div className="metrics-header">
                    <h3>{Utils.t("Growth Metrics")}</h3>

                    {/* Time period selector */}
                    <div className="time-period-selector">
                        {timePeriods.map((period) => (
                            <button
                                key={period.value}
                                className={`period-btn ${timePeriod === period.value ? "active" : ""}`}
                                onClick={() => setTimePeriod(period.value)}
                            >
                                {period.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="growth-stats">
                    {growthData?.metrics?.map((metric) => {
                        const growth = getGrowthIndicator(metric.current, metric.previous);

                        return (
                            <div key={metric.type} className="growth-stat-item">
                                <div className="stat-header">
                                    <Components.Icon icon={metric.icon} />
                                    <span className="stat-label">{Utils.translate(metric.label)}</span>
                                </div>

                                <div className="stat-value">
                                    <span className="current-value">
                                        {bounds.width < 500
                                            ? Utils.formatNumberCompact(metric.current)
                                            : Utils.formatNumber(metric.current)}
                                    </span>

                                    {growth && (
                                        <span
                                            className={`growth-indicator ${
                                                growth.isPositive ? "positive" : "negative"
                                            }`}
                                        >
                                            <Components.Icon
                                                icon={growth.direction === "up" ? "arrow-up" : "arrow-down"}
                                            />
                                            {Utils.formatNumber(growth.rate, {
                                                maximumFractionDigits: 1,
                                            })}%
                                        </span>
                                    )}
                                </div>

                                {metric.previous && (
                                    <div className="stat-comparison">
                                        {Utils.t("Previous: {count}", {
                                            count: Utils.formatNumber(metric.previous),
                                        })}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </Components.LayoutWidget>
    );
}
```

### Category-Specific Statistics

**Example Usage:**

```tsx
// Statistics widget that shows category-specific metrics
export default function CategoryStats(props) {
    const siteSection = Utils.getSiteSection();
    const [measureRef, bounds] = Utils.useMeasure();

    // Category-specific statistics
    const { data: categoryStats } = Utils.useQuery({
        queryKey: ["category-stats", siteSection?.categoryID],
        queryFn: async () => {
            const categoryId = siteSection?.categoryID || "all";
            const response = await fetch(`/api/v2/categories/${categoryId}/stats`);
            return response.json();
        },
        staleTime: 2 * 60 * 1000, // Cache for 2 minutes
    });

    // Top contributors for this category
    const { data: topContributors } = Utils.useQuery({
        queryKey: ["top-contributors", siteSection?.categoryID],
        queryFn: async () => {
            const categoryId = siteSection?.categoryID || "all";
            const response = await fetch(`/api/v2/categories/${categoryId}/top-contributors`);
            return response.json();
        },
        staleTime: 5 * 60 * 1000, // Cache for 5 minutes
    });

    const categoryName = siteSection?.categoryName || Utils.t("All Categories");

    const stats = [
        {
            recordType: "discussions",
            label: Utils.t("Discussions"),
            count: categoryStats?.totalDiscussions || 0,
            iconName: "discussion",
            isCalculating: !categoryStats,
        },
        {
            recordType: "comments",
            label: Utils.t("Comments"),
            count: categoryStats?.totalComments || 0,
            iconName: "comment",
            isCalculating: !categoryStats,
        },
        {
            recordType: "participants",
            label: Utils.t("Participants"),
            count: categoryStats?.uniqueParticipants || 0,
            iconName: "users",
            isCalculating: !categoryStats,
        },
        {
            recordType: "recent-activity",
            label: Utils.t("Recent Activity"),
            count: categoryStats?.recentActivity || 0,
            iconName: "time",
            isCalculating: !categoryStats,
        },
    ];

    return (
        <Components.LayoutWidget>
            <div ref={measureRef} className="category-stats">
                <div className="stats-header">
                    <h3>{Utils.t("Statistics for {category}", { category: categoryName })}</h3>
                </div>

                <div className="stats-grid">
                    {stats.map((stat) => (
                        <div key={stat.recordType} className="stat-item">
                            <Components.Icon icon={stat.iconName} />
                            <span className="stat-number">
                                {stat.isCalculating
                                    ? "???"
                                    : bounds.width < 400
                                    ? Utils.formatNumberCompact(stat.count)
                                    : Utils.formatNumber(stat.count)}
                            </span>
                            <span className="stat-label">{stat.label}</span>
                        </div>
                    ))}
                </div>

                {/* Top contributors section */}
                {topContributors && topContributors.length > 0 && (
                    <div className="top-contributors">
                        <h4>{Utils.t("Top Contributors")}</h4>
                        <div className="contributors-list">
                            {topContributors.slice(0, 5).map((contributor) => (
                                <div key={contributor.userID} className="contributor-item">
                                    <img
                                        src={contributor.photoUrl}
                                        alt={contributor.name}
                                        className="contributor-avatar"
                                    />
                                    <div className="contributor-info">
                                        <span className="contributor-name">{contributor.name}</span>
                                        <span className="contributor-posts">
                                            {Utils.formatNumber(contributor.postCount)} posts
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </Components.LayoutWidget>
    );
}
```

## Site Totals Best Practices

### Data Presentation

-   **Meaningful Metrics**: Choose statistics that reflect community health and engagement
-   **Visual Hierarchy**: Use size, color, and positioning to emphasize important numbers
-   **Context**: Provide comparisons or growth indicators when possible
-   **Accuracy**: Ensure statistics are current and calculated correctly

### Performance Optimization

-   **Smart Caching**: Use appropriate `staleTime` values for different data types
-   **Lazy Loading**: Load statistics only when the widget is visible
-   **Error Handling**: Gracefully handle API failures and loading states
-   **Responsive Design**: Adapt layout and number formatting for different screen sizes

### User Experience

-   **Loading States**: Show placeholder content while data loads
-   **Empty States**: Handle cases where statistics might be zero
-   **Accessibility**: Provide proper labels and ARIA attributes
-   **Internationalization**: Support multiple languages and number formats

### Visual Design

-   **Icon Consistency**: Use appropriate icons that match your community's style
-   **Color Psychology**: Use colors that convey the right emotional tone
-   **Typography**: Ensure numbers are easy to read and scan
-   **Spacing**: Provide adequate whitespace for visual clarity

The Site Totals Fragment helps build social proof and engagement by showcasing your community's vitality and growth in compelling, easy-to-understand metrics.
