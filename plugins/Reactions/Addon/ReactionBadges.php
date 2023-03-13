<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Reactions\Addon;

use BadgeModel;

/**
 * Define the default reaction badges.
 */
class ReactionBadges
{
    const reactions = [
        "Insightful" => "Insightfuls",
        "Agree" => "Agrees",
        "Like" => "Likes",
        "Up" => "Up Votes",
        "Awesome" => "Awesomes",
        "LOL" => "LOLs",
    ];

    const thresholds = [
        5 => 5,
        25 => 5,
        100 => 10,
        250 => 25,
        500 => 50,
        1000 => 50,
        1500 => 50,
        2500 => 50,
        5000 => 50,
        10000 => 50,
    ];

    const sentences = [
        1 => "We like that.",
        2 => "You're posting some good content. Great!",
        3 => "When you're liked this much, you'll be an MVP in no time!",
        4 => "Looks like you're popular around these parts.",
        5 => "It ain't no fluke, you post great stuff and we're lucky to have you here.",
        6 => "The more you post, the more people like it. Keep it up!",
        7 => "You must be a source of inspiration for the community.",
        8 => "People really notice you, in case you haven't noticed.",
        9 => "Your ratio of signal to noise is something to be proud of.",
        10 => "Wow! You are being swarmed with reactions.",
    ];

    /**
     * Generate the default reaction badges.
     * @psalm-suppress UndefinedClass
     */
    public function generateDefaultBadges()
    {
        $badgeModel = new BadgeModel();

        $exists = $badgeModel->getWhere(["Type" => "Reaction"])->firstRow();

        // Make sure the reactions badges do not already exists.
        if ($exists === false) {
            foreach (self::reactions as $class => $nameSuffix) {
                $classSlug = strtolower($class);
                $level = 1;

                foreach (self::thresholds as $threshold => $points) {
                    $sentence = self::sentences[$level];
                    $thresholdFormatted = number_format($threshold);

                    //foreach ($Likes as $Count => $Body) {
                    $badgeModel->define([
                        "Name" => "$thresholdFormatted $nameSuffix",
                        "Slug" => "$classSlug-$threshold",
                        "Type" => "Reaction",
                        "Body" => "You received $thresholdFormatted $nameSuffix. $sentence",
                        "Photo" => "https://badges.v-cdn.net/svg/$classSlug-$level.svg",
                        "Points" => $points,
                        "Threshold" => $threshold,
                        "Class" => $class,
                        "Level" => $level,
                        "CanDelete" => 0,
                    ]);

                    $level++;
                }
            }
        }
    }
}
