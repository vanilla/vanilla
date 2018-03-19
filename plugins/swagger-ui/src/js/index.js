const SwaggerUI = require("swagger-ui");
import vanillaForums from "./plugins/vanilla-forums";

jQuery(document).ready(function($) {
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
