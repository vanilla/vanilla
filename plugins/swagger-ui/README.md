# Swagger UI

This addon gives admins a live explorer of their API v2 endpoints.

## How it works

All of the heavy lifting is done with the SwaggerApiController and SwaggerModel. Here is how it works.
 
 1. The AddonManager is interrogated to get a list of classes that match the __*ApiController__ pattern.
 2. The methods on each controller are enumerated and reverse routed to get a list of public endpoints.
 3. Each endpoint is then called with some replacement methods to gather the input and output schemas. Once the output schema is encountered the rest of the call is cancelled via an exception.
 
 ## Notes
 
 * The addon currently implements the OpenAPI 2.0 (swagger) spec. As of this writing the OpenAPI 3.0 has been finalized and is supported by the swagger UI tool. In the near future the addon will be forked and made to implement OpenAPI 3.0.
 * This addon is currently internal, but will eventually moved into core when forked.
 * This addon is quite fragile as it depends on the dispatchers specific routing scheme and requires endpoints to properly declare their permissions and schemas. For this reason we'll need to smoke test the addon before deploys. A simple "does Vanilla jump into an infinite loop" style test is sufficient. When the addon is moved into core then it can be added to regular unit tests.
 
 ## Possible Enhancements
 
 * Methods added by event handlers are not currently listed.
 * Capture and list permissions as scopes once the permission names are finalized in the API.
 * List standard return codes and meanings for endpoints. For example we could infer that `GET /id` endpoints would return a 404 under certain circumstances and add that return code for all such endpoints.
