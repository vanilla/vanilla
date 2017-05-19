var vanillaStats = (function() {
    function VanillaStats() {
        /**
         * Base API URL.
         * @type {string}
         */
        var apiUrl;

        /**
         * API authentication token.
         * @type {string}
         */
        var authToken;

        /**
         * Primary instance of chart object.
         * @type {object}
         */
        var chart;

        /**
         * Totals for each data type.
         * @type {Object}
         */
        var counts = {
            "Comments": 0,
            "Discussions": 0,
            "Users": 0,
            "Views": 0
        };

        /**
         * Next/previous navigation link targets.
         * @type {Object}
         */
        var links = {
            Next: null,
            Prev: null
        };

        /**
         * Date range.
         * @type {Object}
         */
        var range = {
            from: null,
            to: null
        };

        /**
         * Slot type (e.g. m for monthly, d for daily).
         * @type {string}
         */
        var slotType = "m";

        /**
         * A collection of sparkline chart instances, indexed by data type.
         * @type {Object}
         */
        var sparklines = {};

        /**
         * Timeline data.
         * @type {Array}
         */
        var timeline;

        /**
         * Vanilla site ID.
         * @type {string}
         */
        var vanillaID;

        /**
         * API endpoint paths.
         * @type {string}
         */
        var paths = {
            timeline: "/stats/timeline/{vanillaID}.json"
        };

        /**
         * Get the active data type.
         *
         * @return {string}
         */
        this.getActiveType = function() {
            var activeTypeElement = $("#StatsOverview").find("li.active");
            var activeType = "NewUsers";

            if (activeTypeElement.length > 0) {
                switch (activeTypeElement.get(0).id) {
                    case "StatsComments":
                        activeType = "Comments";
                        break;
                    case "StatsDiscussions":
                        activeType = "Discussions";
                        break;
                    case "StatsPageViews":
                        activeType = "PageViews";
                        break;
                    case "StatsUsers":
                        activeType = "Users";
                        break;
                }
            }

            return activeType;
        };

        /**
         * Get the configured API site URL.
         *
         * @returns {string}
         */
        this.getApiUrl = function() {
            if (typeof apiUrl === "undefined") {
                apiUrl = gdn.definition("VanillaStatsUrl", "//analytics.vanillaforums.com");
            }

            return apiUrl;
        };

        /**
         * Get the configured API authentication token.
         *
         * @returns {boolean|string}
         */
        this.getAuthToken = function() {
            if (typeof authToken === "undefined") {
                authToken = gdn.definition("AuthToken");
            }

            return authToken;
        };

        /**
         * Get a reference to the primary chart instance.
         *
         * @returns {c3}
         */
        this.getChart = function() {
            if (typeof chart !== "object") {
                var statsChart = document.getElementById("StatsChart");

                if (statsChart) {
                    chart = c3.generate({
                        axis: {
                            x: {
                                tick: {
                                    format: (function(date) { return this.formatDate(date) }).bind(this)
                                },
                                type: "timeseries"
                            },
                            y : {
                                tick: {
                                    format: function (d) {
                                        // Don't return decimal labels.
                                        if (d % 1 !== 0) {
                                            return '';
                                        }
                                        return d.toLocaleString();
                                    }
                                }
                            }
                        },
                        grid: {
                            x: {
                                show: true
                            },
                            y: {
                                show: true
                            }
                        },
                        bindto: statsChart,
                        data: {
                            columns: [],
                            type: 'area'
                        },
                        legend: {
                            show: false
                        },
                        onrendered: function() {
                            $(document).trigger('c3Init');
                        },
                        tooltip: {
                            contents: function (d, defaultTitleFormat, defaultValueFormat, color) {
                                valueFormat = defaultValueFormat;
                                value = valueFormat(d[0].value, d[0].ratio, d[0].id, d[0].index);
                                return '<div class="popover popover-single popover-analytics popover-name-" + d[0].id + ">' + value + '</div>';
                            }
                        }
                    });
                }

                this.initializeUI();
            }

            return chart;
        };

        /**
         * Get counts for one or all data types.
         *
         * @param {string} [key]
         */
        this.getCounts = function(key) {
            if (key) {
                if (typeof counts[key] === "number") {
                    return counts[key];
                } else {
                    return false;
                }
            } else {
                return counts;
            }
        };

        /**
         * Format a date value, based on the currently configured slot type, using d3.
         *
         * @returns {string} Formatted date.
         */
        this.formatDate = function(date) {
            var dateFormat;
            date = new Date(date);

            if (this.getSlotType() === "m") {
                dateFormat = "%b %Y";
            } else {
                dateFormat = "%b %d"
            }

            var formatter = d3.time.format(dateFormat);

            return formatter(date);
        };

        /**
         * Get links associated with next/previous navigation.
         *
         * @param {string} [type] Prev or Next to grab the specific link value.
         * @returns {Object}
         */
        this.getLinks = function(type) {
            if (type) {
                if (typeof type === "string") {
                    type = type.toLowerCase();

                    switch (type) {
                        case "next":
                            return links.Next;
                        case "prev":
                            return links.Prev;
                    }
                } else {
                    return null;
                }
            } else {
                return links;
            }
        };

        /**
         * Get a specific API endpoint or paths to all endpoints.
         *
         * @param {string} [key] Key associated with the API endpoint.
         * @returns {Object|string|bool}
         */
        this.getPaths = function(key) {
            if (typeof key !== "undefined") {
                if (typeof paths[key] !== "undefined") {
                    return paths[key];
                } else {
                    return false;
                }
            } else {
                return paths;
            }
        };

        /**
         * Get the current date range.
         *
         * @param {string} [key] The specific date value to fetch (e.g. to, from).
         * @returns {Object|string|bool}
         */
        this.getRange = function(key) {
            if (typeof key === "undefined") {
                return range;
            }
            if (key !== 'to' && key !== 'from') {
                return false;
            }
            if (range[key] === null) {
                // defaults
                if (key === 'to') {
                    // eod yesterday
                    range.to = moment().subtract(1, 'days').endOf('day').toDate();
                }
                if (key === 'from') {
                    // 1 year from eod yesterday
                    range.from = moment().subtract(1, 'days').subtract(1, 'years').endOf('day').toDate();

                }
            }
            return range[key];
        };

        /**
         * Get the current slot type.
         *
         * @returns {string}
         */
        this.getSlotType = function() {
            return slotType;
        };

        /**
         * Get a reference to a sparkline chart.
         *
         * @param {string} key Data type of the sparkline attempting to be retrieve.
         * @returns {object}
         */
        this.getSparkline = function(key) {
            if (typeof sparklines[key] !== "object") {
                var parentContainer = document.getElementById("Stats" + key);
                var container = $(parentContainer).find(".Sparkline").get(0);

                if (container) {
                    sparklines[key] = c3.generate({
                        axis: {
                            x: {
                                show:false,
                                type: "timeseries"
                            },
                            y: { show:false }
                        },
                        bindto: container,
                        data: { columns: [] },
                        legend: { show: false },
                        point: { show: false },
                        size: {
                            height: 40,
                            width: 80
                        },
                        tooltip: { show: false }
                    });
                }
            }

            return sparklines[key];
        };

        /**
         * Get the current timeline data.
         *
         * @returns {Array}
         */
        this.getTimeline = function() {
            return timeline;
        };

        /**
         * Get the Vanilla site ID.
         *
         * @returns {boolean|string}
         */
        this.getVanillaID = function() {
            if (typeof vanillaID === "undefined") {
                vanillaID = gdn.definition("VanillaID");
            }

            return vanillaID;
        };

        /**
         * Wire up navigation UI.
         */
        this.initializeUI = function() {
            $("#StatsOverview").find("li").click((function (eventObject) {
                this.toggleUI("Overview", eventObject.currentTarget.id);
                this.writeData();
            }).bind(this));

            $("#StatsSlotSelector").find("button").click((function (eventObject) {
                switch (eventObject.currentTarget.id) {
                    case "StatsSlotDay":
                        this.setSlotType("d");
                        break;
                    default:
                        this.setSlotType("m");
                }
                this.updateStats();
            }).bind(this));

            $(".js-daterange").daterangepicker({
                alwaysShowCalendars: true,
                startDate: this.getRange('from'),
                endDate: this.getRange('to'),
                opens: 'left',
                buttonClasses: "btn",
                applyClass: "btn-primary",
                cancelClass: "btn-secondary",
                maxDate: moment(this.getRange('to')),
                ranges: {
                    'Past Week': [
                        moment(this.getRange('to')).subtract(1, 'week'),
                        this.getRange('to')
                    ],
                    'Past 30 Days': [
                        moment(this.getRange('to')).subtract(30, 'days'),
                        this.getRange('to')
                    ],
                    'This Month': [
                        moment(this.getRange('to')).startOf('month'),
                        this.getRange('to')
                    ],
                    'Last Month': [
                        moment(this.getRange('to')).startOf('month').subtract(1, 'month'),
                        moment(this.getRange('to')).startOf('month')
                    ],
                    'Past Year': [
                        moment(this.getRange('to')).subtract(1, 'year'),
                        this.getRange('to')
                    ],
                    'Year to Date': [
                        moment(this.getRange('to')).startOf('year'),
                        this.getRange('to')
                    ]
                },
            });

            $(".js-daterange").on('apply.daterangepicker', (function (ev, picker) {
                this.setRange(picker.startDate.endOf('day'), picker.endDate.endOf('day'));
                this.updateStats();
            }).bind(this));

            $(".js-daterange").on('show.daterangepicker', function (ev, picker) {
                $('.daterangepicker').css('display', 'flex');
            });

            $("#StatsNavigation").find("button").click((function (eventObject) {
                switch (eventObject.currentTarget.id) {
                    case "StatsNavNext":
                        this.apiRequest(this.getLinks("Next"), null, this.timelineResponseHandler);
                        break;
                    case "StatsNavPrev":
                        this.apiRequest(this.getLinks("Prev"), null, this.timelineResponseHandler);
                        break;
                    default:
                        var diff = moment(this.getRange('to')).diff(moment(this.getRange('from')), 'days');
                        var dateTo = moment().endOf('day');
                        var dateFrom = moment(dateTo).subtract(diff + 1, 'days');

                        var data = {
                            date: dateTo.toDate(),
                            dateFrom: dateFrom.toDate(),
                            slotType: this.getSlotType()
                        };

                        // Make the formal request.
                        this.apiRequest(
                            this.getPaths("timeline").replace("{vanillaID}", this.getVanillaID()),
                            data,
                            this.timelineResponseHandler
                        );
                        break;
                }
            }).bind(this));
        };

        /**
         * Set the counts for each of the specific data types.
         *
         * @param {Object} newCounts A plain object containing totals for each data type.
         * @returns {VanillaStats} Returns the current instance for fluent calls.
         */
        this.setCounts = function(newCounts) {
            if (typeof newCounts !== "object") {
                return false;
            }

            var requiredValues = [
                "Comments",
                "Discussions",
                "Users",
                "Views"
            ];

            var validCounts = true;
            requiredValues.forEach(function (value, index, array) {
                if (typeof newCounts[value] !== "number") {
                    return validCounts = false;
                }
            });

            if (validCounts) {
                counts = newCounts;
            }

            return this;
        };

        /**
         * Process the data provided in an API response and update all relevant attributes.
         *
         * @param {Object} data A plain object containing the full response from an API request.
         * @returns {VanillaStats} Returns the current instance for fluent calls.
         */
        this.setData = function(data) {
            if (typeof data === "object") {
                if (typeof data.Links !== "undefined") {
                    this.setLinks(data.Links);
                }

                if (typeof data.From !== "undefined" || typeof data.To !== "undefined") {
                    var newFrom = typeof data.From !== "undefined" ? data.From : null;
                    var newTo = typeof data.To !== "undefined" ? data.To : null;

                    this.setRange(moment(newFrom).endOf('day'), moment(newTo).endOf('day'));
                }

                if (typeof data.SlotType !== "undefined") {
                    this.setSlotType(data.SlotType);
                }

                if (typeof data.Timeline !== "undefined") {
                    this.setTimeline(data.Timeline);
                }
            }

            return this;
        };

        /**
         * Set the links associated with next/previous navigation.
         *
         * @param {Object} newLinks
         * @returns {VanillaStats} Returns the current instance for fluent calls.
         */
        this.setLinks = function(newLinks) {
            if (typeof newLinks === "object") {
                links.Next = (typeof newLinks.Next === "string") ? newLinks.Next : null;
                links.Prev = (typeof newLinks.Prev === "string") ? newLinks.Prev : null;
            }

            return this;
        };

        /**
         * Set the current slot type (monthly or daily).
         *
         * @param {string} newSlotType New slot type (e.g. m for monthly, d for daily).
         * @returns {VanillaStats} Returns the current instance for fluent calls.
         */
        this.setSlotType = function(newSlotType) {
            if (typeof newSlotType === "string") {
                slotType = newSlotType === "d" ? "d" : "m";
            }

            return this;
        };

        /**
         * Update timeline data.
         *
         * @param {Array} newTimeline A collection of properly-formatted analytics data.
         * @returns {VanillaStats} Returns the current instance for fluent calls.
         */
        this.setTimeline = function(newTimeline) {
            var updatedCounts = {
                "Comments": 0,
                "Discussions": 0,
                "Users": 0,
                "Views": 0
            };

            if (Array.isArray(newTimeline)) {
                timeline = newTimeline;
                timeline.forEach(function(interval, index, array) {
                    if (typeof interval.CountViews === "number") {
                        updatedCounts.Views += interval.CountViews;
                    }
                    if (typeof interval.CountUsers === "number") {
                        updatedCounts.Users += interval.CountUsers;
                    }
                    if (typeof interval.CountDiscussions === "number") {
                        updatedCounts.Discussions += interval.CountDiscussions;
                    }
                    if (typeof interval.CountComments === "number") {
                        updatedCounts.Comments += interval.CountComments;
                    }
                });

                this.setCounts(updatedCounts);
            }

            return this;
        };

        /**
         * Set the date range.
         *
         * @param {Date|string} newFrom The "from" portion of the date range.
         * @param {Date|string} newTo The "to"portion of the date range.
         * @return {VanillaStats} Returns the current instance for fluent calls.
         */
        this.setRange = function(newFrom, newTo) {
            if (!(newFrom instanceof Date)) {
                newFrom = new Date(newFrom);
            }
            if (!(newTo instanceof Date)) {
                newTo = new Date(newTo);
            }

            if (!isNaN(newFrom.getTime()) && !isNaN(newTo.getTime())) {
                range.from = newFrom;
                range.to = newTo;
            }

            return this;
        };
    }

    /**
     * Perform an API request to the central stats site.
     *
     * @param {string} path Path to the endpoint.
     * @param {Object} [data] Data to send along with the request.
     * @param {function} [successCallback] A function to be called upon successful completion of the request.
     */
    VanillaStats.prototype.apiRequest = function(path, data, successCallback) {
        var requestConfig = {
            complete: function(jqXHR, textStatus) { },
            context: this,
            dataType: "json",
            error: function (jqXHR, textStatus, errorThrown) { },
            headers: {
                Authorization: "token " + this.getAuthToken()
            },
            url: this.buildUrl(path)
        };

        if (typeof data === "object") {
            requestConfig.data = data;
        }

        if (typeof successCallback === "function") {
            requestConfig.success = successCallback;
        }

        $.ajax(requestConfig);
    };

    /**
     * Build a simple API URL.
     * @param {string} path
     * @returns {string}
     */
    VanillaStats.prototype.buildUrl = function(path) {
        if (typeof path === "string" && path.match(/^https?:\/\//)) {
            return path;
        }

        var baseUrl = this.getApiUrl();

        if (typeof path !== "string") {
            return baseUrl;
        } else {
            // Trim up the path and append it.
            path = path.replace(/(^\/+)/, "");
            return baseUrl.replace(/\/+$/, "") + "/" + path;
        }
    };

    /**
     * Process API responses.
     *
     * @param {Object} data
     * @param {string} textStatus
     * @param {Object} jqXHR
     */
    VanillaStats.prototype.timelineResponseHandler = function(data, textStatus, jqXHR) {
        if (jqXHR.status === 200 && typeof data === "object") {
            this.setData(data);
            this.writeData();
        }
    };

    /**
     * Handle toggling of UI elements and trigger associated functionality.
     *
     * @param {string} control
     * @param {string} activeElementID
     */
    VanillaStats.prototype.toggleUI = function(control, activeElementID) {
        switch (control) {
            case "Overview":
                var typeSelector = document.getElementById("StatsOverview");
                if (typeSelector) {
                    var typeElements = typeSelector.getElementsByTagName("li");
                    for (var i = 0; i < typeElements.length; i++) {
                        if (typeElements[i].id === activeElementID) {
                            $(typeElements[i]).addClass("active");
                        } else {
                            $(typeElements[i]).removeClass("active");
                        }
                    }
                    $("#StatsChart").removeClass(function (index, css) {
                        return (css.match (/(^|\s)Chart\S+/g) || []).join(' ');
                    });
                    $("#StatsChart").addClass('Chart' + activeElementID);
                }
                break;
            case "SlotSelector":
                var slotSelector = document.getElementById("StatsSlotSelector");
                if (slotSelector) {
                    var slotElements = slotSelector.getElementsByTagName("button");
                    for (var x = 0; x < slotElements.length; x++) {
                        if (slotElements[x].id === activeElementID) {
                            $(slotElements[x]).addClass("active");
                        } else {
                            $(slotElements[x]).removeClass("active");
                        }
                    }
                }
                break;
        }
    };

    /**
     * Update the navigation UI based on current data.
     */
    VanillaStats.prototype.updateUI = function() {
        var daterangepicker = $(".js-daterange").data('daterangepicker');
        var navNext = document.getElementById("StatsNavNext");
        var navPrev = document.getElementById("StatsNavPrev");
        var navToday = document.getElementById("StatsNavToday");

        if (daterangepicker !== undefined) {
            daterangepicker.setStartDate(moment(this.getRange('from')));
            daterangepicker.setEndDate(moment(this.getRange('to')));
        }
        if (navPrev !== null) {
            navPrev.disabled = this.getLinks("Prev") === null;
        }
        if (navNext !== null) {
            var navNextDisabled = this.getLinks("Next") === null;
            navNext.disabled = navNextDisabled;
            navToday.disabled = navNextDisabled;
        }
    };

    VanillaStats.prototype.getSummaries = function(container) {
        if (!gdn.getMeta('DashboardSummaries', true)) {
            return;
        }

        var rangeFrom = this.getRange("from");
        var rangeTo = this.getRange("to");
        var dateRange = {
            from: rangeFrom instanceof Date ? rangeFrom.toUTCString() : rangeFrom,
            to: rangeTo instanceof Date ? rangeTo.toUTCString() : rangeTo
        };

        $.ajax({
            url: gdn.url('/dashboard/settings/dashboardsummaries?DeliveryType=VIEW'),
            data: {range: dateRange},
            success: function(data) {
                $(container).html(data);
            },
            error: function(xhr, status, error) {
                $(container).html('<div class="NoStats">Remote Analytics Server request failed.</div>');
            },
            timeout: 15000 // 15 seconds in ms
        });
    },

    /**
     * Refresh the chart by populating with timeline data of the specified type.
     *
     * @param {string} key The type of data to refresh the chart with (e.g. pageviews, comments).
     * @param {c3} [chart] Instance of the chart to update.  Defaults to the value of this.getChart().
     * @return {boolean} False on error.
     */
    VanillaStats.prototype.updateChart = function(key, chart) {
        // We need an explicit data type.
        if (typeof key !== "string") {
            return false;
        }

        if (typeof chart === "undefined") {
            chart = this.getChart();
        } else if (typeof chart !== "object") {
            return false;
        }

        // Based on key, configure the parameters we'll need to extract specific analytics data.
        var dataIndex, label;
        switch (key.toLowerCase()) {
            case "pageviews":
                dataIndex = "CountViews";
                label = "Views";
                break;
            case "comments":
                dataIndex = "CountComments";
                label = "Comments";
                break;
            case "discussions":
                dataIndex = "CountDiscussions";
                label = "Discussions";
                break;
            case "users":
                dataIndex = "CountUsers";
                label = "Users";
                break;
            default:
                dataIndex = key;
                label = "Count";
        }

        // Build the basic data structure we'll be populating and handing off to the charting library.
        var newData = {
            json: [],
            keys: {
                x: "Date",
                value: ["Count"]
            }
        };
        var timeline = this.getTimeline();

        // Verify we have data, then iterate through it, populating the newData object.
        if (Array.isArray(timeline)) {
            timeline.forEach(function (value, index, array) {
                var newInterval = {
                    Date: ""
                };
                newInterval[label] = 0;

                if (typeof value["Date"] === "string") {
                    newInterval.Date = value["Date"];
                }
                if (typeof value[dataIndex] === "number") {
                    newInterval["Count"] = value[dataIndex];
                }

                newData.json.push(newInterval);
            });
        }

        // Load the newly constructed data into our chart.
        chart.load(newData);
        this.updateUI();
    };

    /**
     * Request data from the mothership.
     *
     * @param {string|Date} refDateTo The ending reference date for requested analytics data.
     * @param {string|Date} refDateFrom The starting reference date for requested analytics data.
     * @returns {boolean|string}
     */
    VanillaStats.prototype.updateStats = function() {
        var refDateTo = this.getRange('to');
        var refDateFrom = this.getRange('from');

        var diff = moment(refDateTo).diff(moment(refDateFrom), 'seconds');
        var daySeconds = 86400;
        var monthSeconds = 2620800;
        var maxTicks = 150;
        var minTicks = 2;
        var monthDisabled = (diff / monthSeconds) < minTicks;
        var dayDisabled = (diff / daySeconds) > maxTicks;

        if (monthDisabled) {
            document.getElementById("StatsSlotMonth").disabled = true;
        } else {
            document.getElementById("StatsSlotMonth").disabled = false;
        }

        if (dayDisabled) {
            document.getElementById("StatsSlotDay").disabled = true;
        } else {
            document.getElementById("StatsSlotDay").disabled = false;
        }

        if (this.getSlotType() === 'm' && monthDisabled) {
            this.setSlotType('d');
        } else if (this.getSlotType() === 'd' && dayDisabled) {
            this.setSlotType('m');
        }

        this.toggleUI("SlotSelector", this.getSlotType() === 'd' ? "StatsSlotDay" : "StatsSlotMonth");

        // Build a date string the API can use (YYYY-MM-DD).
        var dateTo = refDateTo.getFullYear() + "-" + (refDateTo.getMonth() + 1) + "-" + refDateTo.getDate();
        var dateFrom = refDateFrom.getFullYear() + "-" + (refDateFrom.getMonth() + 1) + "-" + refDateFrom.getDate();

        var data = {
            date: dateTo,
            dateFrom: dateFrom,
            slotType: this.getSlotType()
        };

        // Make the formal request.
        this.apiRequest(
            this.getPaths("timeline").replace("{vanillaID}", this.getVanillaID()),
            data,
            this.timelineResponseHandler
        );
    };

    /**
     * @param {string} containerID
     * @param {number} count
     * @return {boolean}
     */
    VanillaStats.prototype.writeCount = function(containerID, count) {
        var countString;

        if (typeof count !== "number") {
            countString = "-";
        } else {
            countString = count.toLocaleString();
        }

        var containerElement = document.getElementById(containerID);
        if (containerElement) {
            var valueElements = containerElement.getElementsByClassName("StatsValue");
            if (valueElements.length) {
                valueElements[0].innerHTML = countString;
                return true;
            }
        }

        return false;
    };

    /**
     * Update stats and chart element values with current data.
     */
    VanillaStats.prototype.writeData = function() {
        var commentsSparkline = this.getSparkline("Comments");
        if (typeof commentsSparkline === "object") {
            this.updateChart("Comments", commentsSparkline)
        }
        this.writeCount("StatsComments", this.getCounts("Comments"));

        var discussionsSparkline = this.getSparkline("Discussions");
        if (typeof discussionsSparkline === "object") {
            this.updateChart("Discussions", discussionsSparkline)
        }
        this.writeCount("StatsDiscussions", this.getCounts("Discussions"));

        var usersSparkline = this.getSparkline("Users");
        if (typeof usersSparkline === "object") {
            this.updateChart("Users", usersSparkline)
        }
        this.writeCount("StatsUsers", this.getCounts("Users"));

        var viewsSparkline = this.getSparkline("PageViews");
        if (typeof viewsSparkline === "object") {
            this.updateChart("PageViews", viewsSparkline)
        }
        this.writeCount("StatsPageViews", this.getCounts("Views"));

        this.updateChart(this.getActiveType(), this.getChart());

        this.getSummaries(".js-dashboard-widgets-summaries")
    };

    return new VanillaStats();
}());

$(document).ready(function() {
    // Here we go...
    vanillaStats.updateStats();
    vanillaStats.toggleUI("Overview", "StatsPageViews");
});
