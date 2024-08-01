<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license Proprietary
 */

namespace Vanilla\Contracts\SiteSync;

use Garden\Http\HttpClient;

/**
 * Abstract synchronizer of resources of a specific type between a source site and a destination site
 */
abstract class AbstractResourceTypeSiteSynchronizer implements SiteSyncResourceTypeSynchronizerInterface
{
    /** @var SiteSyncProducerInterface $siteSyncProducer */
    protected $siteSyncProducer;

    /** @var SiteSyncConsumerInterface $siteSyncConsumer */
    protected $siteSyncConsumer;

    /**
     * DI Constructor
     *
     * @param SiteSyncProducerInterface $producer Produces resources to sync from source to destination
     * @param SiteSyncConsumerInterface $consumer Consumes resources to sync from source to destination
     */
    public function __construct(SiteSyncProducerInterface $producer, SiteSyncConsumerInterface $consumer)
    {
        $this->siteSyncProducer = $producer;
        $this->siteSyncConsumer = $consumer;
    }

    /**
     * Get the name of the resource type synchronized between source and destination by this synchronizer
     *
     * @return string
     */
    abstract public function getResourceTypeName(): string;

    /**
     * @inheritdoc
     */
    public function getSiteSyncProducer(): SiteSyncProducerInterface
    {
        return $this->siteSyncProducer;
    }

    /**
     * @inheritdoc
     */
    public function getSiteSyncConsumer(): SiteSyncConsumerInterface
    {
        return $this->siteSyncConsumer;
    }

    /**
     * @inheritdoc
     */
    public function isSyncAllEnabled(HttpClient $producerClient, HttpClient $consumerClient): bool
    {
        return $this->siteSyncProducer->isProduceAllEnabled($producerClient) &&
            $this->siteSyncConsumer->isConsumeAllEnabled($consumerClient);
    }

    /**
     * @inheritdoc
     */
    public function syncAllApi(
        HttpClient $producerClient,
        HttpClient $consumerClient,
        ?string $foreignIDPrefix = null
    ): void {
        $contentToSync = $this->siteSyncProducer->produceAllApi($producerClient);
        if (is_array($contentToSync)) {
            $this->siteSyncConsumer->consumeAllApi($consumerClient, $contentToSync, $foreignIDPrefix);
        }
    }
}
