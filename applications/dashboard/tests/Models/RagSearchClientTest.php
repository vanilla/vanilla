<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Models;

use Garden\Container\ContainerException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\AiConversationModel;
use Vanilla\Dashboard\Models\NexusRagSearchClient;
use Vanilla\Dashboard\Tests\NexusRagSearchMockClient;
use VanillaTests\SiteTestCase;

/**
 * Test the RagSearchClient.
 */
class RagSearchClientTest extends SiteTestCase
{
    private NexusRagSearchClient $client;

    /**
     * Determine if we are running against Nexus or against a MockClient.
     *
     * To set this up, simply configure the following environment variables in your phpunit.xml.
     *
     * @return void
     * @throws ContainerException
     */
    public function setUp(): void
    {
        $this->config = $this->container()->get(ConfigurationInterface::class);
        $url = getenv("NEXUS_RAG_URL");
        $authUrl = getenv("NEXUS_RAG_AUTH_URL");
        $clientID = getenv("NEXUS_CLIENT_ID");
        $clientSecret = getenv("NEXUS_CLIENT_SECRET");
        $model = getenv("NEXUS_MODEL");

        $this->config->saveToConfig([
            AiConversationModel::RAG_SEARCH_FEATURE_CONFIG => true,
        ]);

        if ($url && $authUrl && $clientID && $clientSecret && $model) {
            $this->config->saveToConfig([
                NexusRagSearchClient::NEXUS_RAG_URL_CONFIG_KEY => $url,
                NexusRagSearchClient::NEXUS_AUTH_URL_CONFIG_KEY => $authUrl,
                NexusRagSearchClient::NEXUS_RAG_CLIENT_ID_CONFIG_KEY => $clientID,
                NexusRagSearchClient::NEXUS_RAG_CLIENT_SECRET_CONFIG_KEY => $clientSecret,
                NexusRagSearchClient::NEXUS_RAG_MODEL_CONFIG_KEY => $model,
            ]);
            $this->client = $this->container()->get(NexusRagSearchClient::class);
        } else {
            $this->client = $this->container()->get(NexusRagSearchMockClient::class);
            $this->container()->setInstance(NexusRagSearchClient::class, $this->client);
            $this->mockRun = true;
        }

        parent::setUp();
    }

    /**
     * Test creating and refreshing a token.
     *
     * @return void
     */
    public function testCreateAndRefreshToken(): void
    {
        $oldToken = $this->client->createToken();
        // Force the token to refresh should be refreshed when running utility update.
        $this->bessy()->get("/utility/update");

        $currentToken = $this->config->get(NexusRagSearchClient::NEXUS_RAG_TOKEN_CONFIG_KEY);
        $this->assertNotEquals($oldToken, $currentToken);
    }
}
