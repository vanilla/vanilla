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
         * @type {Object}
         */
        var links;

        /**
         * @type {Object}
         */
        var range = {
            from: null,
            to: null
        };

        /**
         * @type {string}
         */
        var slotType = 'm';

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
         * @returns {string}
         */
        this.getApiUrl = function() {
            if (typeof apiUrl === "undefined") {
                apiUrl = gdn.definition("VanillaStatsUrl", "//analytics.vanillaforums.com");
            }

            return apiUrl;
        };

        /**
         * @returns {bool|string}
         */
        this.getAuthToken = function() {
            if (typeof authToken === "undefined") {
                authToken = gdn.definition("AuthToken");
            }

            return authToken;
        };

        /**
         * @returns {Object}
         */
        this.getLinks = function() {
            return links;
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
         * @returns {bool|string}
         */
        this.getVanillaID = function() {
            if (typeof vanillaID === "undefined") {
                vanillaID = gdn.definition("VanillaID");
            }

            return vanillaID;
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
                links = newLinks;
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
            if (Array.isArray(newTimeline)) {
                timeline = newTimeline;
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
        }
    };

    /**
     * @returns {bool|string}
     */
    VanillaStats.prototype.updateChart = function(refDate) {
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
        console.log(refDate);

        this.apiRequest(
            this.getPaths("timeline").replace("{vanillaID}", this.getVanillaID()),
            {
                date: date,
                slotType: this.getSlotType()
            },
            this.timelineResponseHandler
        );
    };

    return new VanillaStats();
}());

$(document).ready(function() {
    vanillaStats.updateChart();
});
