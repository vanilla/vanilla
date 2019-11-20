<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Forum\EmbeddedContent\Factories\CommentEmbedFactory;
use Vanilla\Forum\EmbeddedContent\Factories\DiscussionEmbedFactory;
use \Garden\Container;

Gdn::getContainer()
    ->rule(EmbedService::class)
    ->addCall('registerFactory', [
        'embedFactory' => new Container\Reference(DiscussionEmbedFactory::class),
        'priority' => EmbedService::PRIORITY_NORMAL
    ])
    ->addCall('registerFactory', [
        'embedFactory' => new Container\Reference(CommentEmbedFactory::class),
        'priority' => EmbedService::PRIORITY_NORMAL
    ]);
