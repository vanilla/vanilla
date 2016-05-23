# API 0 Tests

All tests in this directory are meant to be run via the `APIv0` client. This client just mimics the posts that would
come from the browser, but usually with JSON responses.

## Adding New Tests

1. Subclass the `VanillaTests\APIv0\BaseTest` class or add new methods to an existing class such as
`VanillaTests\APIv0\SmokeTest`.

2. Make API calls with the API client provided in the `$this->api()` method.

3. You can set the context of the user making the API call with `$this->api()->setUser()`. If you want to make calls
without a user context then it's a good practice to make sure there isn't one set from a previous test by passing null
 to `setUser()`.