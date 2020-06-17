<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\Reference;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Forum\EmbeddedContent\Factories\CommentEmbedFactory;
use Vanilla\Forum\EmbeddedContent\Factories\DiscussionEmbedFactory;
use \Garden\Container;
use Vanilla\Forum\Search\CommentSearchType;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Search\AbstractSearchDriver;

Gdn::getContainer()
    ->rule(EmbedService::class)
    ->addCall('registerFactory', [
        'embedFactory' => new Container\Reference(DiscussionEmbedFactory::class),
        'priority' => EmbedService::PRIORITY_NORMAL
    ])
    ->addCall('registerFactory', [
        'embedFactory' => new Container\Reference(CommentEmbedFactory::class),
        'priority' => EmbedService::PRIORITY_NORMAL
    ])
    ->rule(\Vanilla\Site\SiteSectionModel::class)
    ->addCall(
        'registerApplication',
        [
            'forum',
            ['name' => 'Forum']
        ]
    )
    ->rule(\Vanilla\Navigation\BreadcrumbModel::class)
    ->addCall('addProvider', [new Reference(\Vanilla\Forum\Navigation\ForumBreadcrumbProvider::class)])

    // Search.
    ->rule(AbstractSearchDriver::class)
    ->addCall('registerSearchType', [new Reference(DiscussionSearchType::class)])
    ->addCall('registerSearchType', [new Reference(CommentSearchType::class)])

;
