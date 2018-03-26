const SwaggerUI = require("swagger-ui");
import vanillaForums from "./plugins/vanilla-forums";

jQuery(document).ready(function($) {
    // We actually can't prevent SwaggerUI from overwriting the set URL with one in the query string.
    // https://github.com/swagger-api/swagger-ui/issues/4332
    if (window.location.search) {
        window.location.search = "";
    }

    window.ui = SwaggerUI({
        deepLinking: true,
        dom_id: "#swagger-ui",
        layout: "DashboardLayout",
        plugins: [
            SwaggerUI.plugins.DownloadUrl,
            vanillaForums
        ],
        presets: [
            SwaggerUI.presets.apis
        ],
        requestInterceptor: function (request) {
            request.headers["x-transient-key"] = gdn.getMeta("TransientKey");
            return request;
        },
        url: gdn.url("/api/v2/open-api/v2"),
        validatorUrl: null
    });
});