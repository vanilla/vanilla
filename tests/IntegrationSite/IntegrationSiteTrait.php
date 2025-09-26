<?php

namespace VanillaTests\IntegrationSite;

use Exception;
use Garden\Http\HttpClient;

/**
 * Trait for accessing the integration site via the api.
 */
trait IntegrationSiteTrait
{
    public function api(): HttpClient
    {
        $client = new HttpClient("https://devintegrationtest.vanillawip.com/api/v2");
        $token = getenv("TEST_INTEGRATION_SITE_API_KEY");
        if ($token === false) {
            throw new Exception("TEST_INTEGRATION_SITE_API_KEY must be set in the environment.");
        }
        $client->setDefaultHeader("Authorization", "Bearer " . $token);
        return $client;
    }
}
