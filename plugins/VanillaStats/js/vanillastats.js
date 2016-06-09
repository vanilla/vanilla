vanillaStats = (function() {
    function VanillaStats() {
        /**
         * @type {string}
         */
        var apiUrl;

        /**
         * @type {string}
         */
        var authToken;

        /**
         * @type {object}
         */
        var chart;

        /**
         * @type {Object}
         */
        var counts = {
            "Comments": 0,
            "Discussions": 0,
            "Users": 0,
            "Views": 0
        };

        /**
         * @type {Object}
         */
        var links = {
            Next: null,
            Prev: null
        };

        /**
         * @type {Object}
         */
        var range = {
            From: null,
            To: null
        };

        /**
         * @type {string}
         */
        var slotType = "m";

        /**
         * @type {Array}
         */
        var timeline;

        /**
         * @type {string}
         */
        var vanillaID;

        /**
         * @type {string}
         */
        var paths = {
            timeline: "/stats/timeline/{vanillaID}.json"
        };

        /**
         *
         */
        this.getActiveType = function() {
            var activeTypeElement = $("#StatsOverview").find("li.Active");
            var activeType = "NewUsers";

            if (activeTypeElement.length > 0) {
                switch (activeTypeElement.get(0).id) {
                    case "StatsNewComments":
                        activeType = "Comments";
                        break;
                    case "StatsNewDiscussions":
                        activeType = "Discussions";
                        break;
                    case "StatsPageViews":
                        activeType = "PageViews";
                        break;
                    case "StatsNewUsers":
                        activeType = "Users";
                        break;
                }
            }

            return activeType;
        };

        /**
         * @returns {string}
         */
        this.getApiUrl = function() {
            if (typeof apiUrl === "undefined") {
                apiUrl = gdn.definition("VanillaStatsUrl", "//analytics.vanillaforums.com");
            }

            return apiUrl;
        };

        /**
         * @returns {boolean|string}
         */
        this.getAuthToken = function() {
            if (typeof authToken === "undefined") {
                authToken = gdn.definition("AuthToken");
            }

            return authToken;
        };

        /**
         * @returns {object}
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
                            y:{
                                padding: 0
                            }
                        },
                        bindto: statsChart,
                        data: {
                            columns: []
                        },
                        legend: {
                            show: false
                        }
                    });
                }

                this.initializeUI();
            }

            return chart;
        };

        /**
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
         * @returns {string} date
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
         * @param {string} [key]
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
         * @param {string} [key]
         * @returns {Object|string|bool}
         */
        this.getRange = function(key) {
            if (typeof key !== "undefined") {
                if (typeof range[key] !== "undefined") {
                    return range[key];
                } else {
                    return false;
                }
            } else {
                return range;
            }
        };

        /**
         * @returns {string}
         */
        this.getSlotType = function() {
            return slotType;
        };

        /**
         * @returns {Array}
         */
        this.getTimeline = function() {
            return timeline;
        };

        /**
         * @returns {boolean|string}
         */
        this.getVanillaID = function() {
            if (typeof vanillaID === "undefined") {
                vanillaID = gdn.definition("VanillaID");
            }

            return vanillaID;
        };

        /**
         *
         */
        this.initializeUI = function() {
            $("#StatsOverview").find("li").click((function (eventObject) {
                this.toggleUI("Overview", eventObject.currentTarget.id);
            }).bind(this));

            document.getElementById("StatsSlotDay").disabled = false;
            document.getElementById("StatsSlotMonth").disabled = false;

            $("#StatsSlotSelector").find("input").click((function (eventObject) {
                switch (eventObject.currentTarget.id) {
                    case "StatsSlotDay":
                        this.setSlotType("d");
                        this.toggleUI("SlotSelector", "StatsSlotDay");
                        break;
                    default:
                        this.setSlotType("m");
                        this.toggleUI("SlotSelector", "StatsSlotMonth");
                }
            }).bind(this));

            $("#StatsNavigation").find("input").click((function (eventObject) {
                switch (eventObject.currentTarget.id) {
                    case "StatsNavNext":
                        this.apiRequest(this.getLinks("Next"), null, this.timelineResponseHandler);
                        break;
                    case "StatsNavPrev":
                        this.apiRequest(this.getLinks("Prev"), null, this.timelineResponseHandler);
                        break;
                    default:
                        this.apiRequest(
                            this.getPaths("timeline").replace("{vanillaID}", this.getVanillaID()),
                            { slotType: this.getSlotType() },
                            this.timelineResponseHandler
                        );
                        break;
                }
            }).bind(this));
        };

        /**
         * @param {Object} newCounts
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
         * @param {Object} data
         * @returns {VanillaStats}
         */
        this.setData = function(data) {
            if (typeof data === "object") {
                if (typeof data.Links !== "undefined") {
                    this.setLinks(data.Links);
                }

                if (typeof data.From !== "undefined" || typeof data.To !== "undefined") {
                    var newFrom = typeof data.From !== "undefined" ? data.From : null;
                    var newTo = typeof data.To !== "undefined" ? data.To : null;

                    this.setRange(newFrom, newTo);
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
         * @param {Object} newLinks
         * @returns {VanillaStats}
         */
        this.setLinks = function(newLinks) {
            if (typeof newLinks === "object") {
                links.Next = (typeof newLinks.Next === "string") ? newLinks.Next : null;
                links.Prev = (typeof newLinks.Prev === "string") ? newLinks.Prev : null;
            }

            return this;
        };

        /**
         * @param {string} newSlotType
         * @returns {VanillaStats}
         */
        this.setSlotType = function(newSlotType) {
            if (typeof newSlotType === "string") {
                slotType = newSlotType === "d" ? "d" : "m";
            }

            return this;
        };

        /**
         * @param {Array} newTimeline
         * @returns {VanillaStats}
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
         * @param {Date|string} newFrom
         * @param {Date|string} newTo
         * @return {VanillaStats}
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
     * @param {string} [path]
     * @param {Object} [data]
     * @param {function} [successCallback]
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
                            $(typeElements[i]).addClass("Active");
                        } else {
                            $(typeElements[i]).removeClass("Active");
                        }
                    }
                }
                this.writeData();
                break;
            case "SlotSelector":
                var slotSelector = document.getElementById("StatsSlotSelector");
                if (slotSelector) {
                    var slotElements = slotSelector.getElementsByTagName("input");
                    for (var x = 0; x < slotElements.length; x++) {
                        if (slotElements[x].id === activeElementID) {
                            $(slotElements[x]).addClass("Active");
                        } else {
                            $(slotElements[x]).removeClass("Active");
                        }
                    }
                }
                this.updateStats();
                break;
        }
    };

    /**
     *
     */
    VanillaStats.prototype.updateUI = function() {
        var navNext = document.getElementById("StatsNavNext");
        var navPrev = document.getElementById("StatsNavPrev");
        var navToday = document.getElementById("StatsNavToday");

        if (navPrev !== null) {
            navPrev.disabled = this.getLinks("Prev") === null;
        }
        if (navNext !== null) {
            var navNextDisabled = this.getLinks("Next") === null;
            navNext.disabled = navNextDisabled;
            navToday.disabled = navNextDisabled;
        }
    };

    /**
     * @param {string} key
     */
    VanillaStats.prototype.updateChart = function(key) {
        if (typeof key !== "string") {
            return false;
        }

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

        var newData = {
            json: [],
            keys: {
                x: "Date",
                value: ["Count"]
            }
        };
        var timeline = this.getTimeline();

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

        this.getChart().load(newData);
        this.updateUI();
    };

    /**
     * @returns {boolean|string}
     */
    VanillaStats.prototype.updateStats = function(refDate) {
        if (typeof refDate === "undefined") {
            refDate = new Date();
        } else {
            if (!(refDate instanceof Date)) {
                refDate = new Date(refDate);
            }

            if (isNaN(refDate.getTime())) {
                refDate = new Date();
            }
        }

        var date = refDate.getFullYear() + "-" + (refDate.getMonth() + 1) + "-" + refDate.getDate();

        this.apiRequest(
            this.getPaths("timeline").replace("{vanillaID}", this.getVanillaID()),
            {
                date: date,
                slotType: this.getSlotType()
            },
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
            countString = count.toString(10);
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
     *
     */
    VanillaStats.prototype.writeData = function() {
        this.writeCount("StatsNewComments", this.getCounts("Comments"));
        this.writeCount("StatsNewDiscussions", this.getCounts("Discussions"));
        this.writeCount("StatsNewUsers", this.getCounts("Users"));
        this.writeCount("StatsPageViews", this.getCounts("Views"));

        this.updateChart(this.getActiveType());
    };

    return new VanillaStats();
}());

$(document).ready(function() {
    vanillaStats.updateStats();
});
