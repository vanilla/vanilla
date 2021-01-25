<?php if (!defined('APPLICATION')) exit;

use Vanilla\Addon;

$St = Gdn::structure();
$Sql = Gdn::sql();

Gdn::permissionModel()->define([
    'Reactions.Positive.Add' => 'Garden.SignIn.Allow',
    'Reactions.Negative.Add' => 'Garden.SignIn.Allow',
    'Reactions.Flag.Add' => 'Garden.SignIn.Allow']);

$St->table('ReactionType');
$ReactionTypeExists = $St->tableExists();

$St
    ->column('UrlCode', 'varchar(32)', FALSE, 'primary')
    ->column('Name', 'varchar(32)')
    ->column('Description', 'text', TRUE)
    ->column('Class', 'varchar(10)', TRUE)
    ->column('TagID', 'int')
    ->column('Attributes', 'text', TRUE)
    ->column('Sort', 'smallint', TRUE)
    ->column('Active', 'tinyint(1)', 0)
    ->column('Custom', 'tinyint(1)', 0)
    ->column('Hidden', 'tinyint(1)', 0)
    ->set();

$St->table('UserTag')
    ->column('RecordType', ['Discussion', 'Discussion-Total', 'Comment', 'Comment-Total', 'User', 'User-Total', 'Activity', 'Activity-Total', 'ActivityComment', 'ActivityComment-Total'], FALSE, ['primary', 'index.combined'])
    ->column('RecordID', 'int', FALSE, 'primary')
    ->column('TagID', 'int', FALSE, ['primary', 'key', 'index.combined'])
    ->column('UserID', 'int', FALSE, ['primary', 'key', 'index.combined'])
    ->column('DateInserted', 'datetime', false, ['index', 'index.combined'])
    ->column('Total', 'int', 0, ['index.combined'])
    ->set();

$Rm = new ReactionModel();

// Insert some default tags.
$Rm->defineReactionType(['UrlCode' => 'Spam', 'Name' => 'Spam', 'Sort' => 100, 'Class' => 'Flag', 'Log' => 'Spam', 'LogThreshold' => 5, 'RemoveThreshold' => 5, 'ModeratorInc' => 5, 'Protected' => TRUE, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
    'Description' => "Allow your community to report any spam that gets posted so that it can be removed as quickly as possible."]);
$Rm->defineReactionType(['UrlCode' => 'Abuse', 'Name' => 'Abuse', 'Sort' => 101, 'Class' => 'Flag', 'Log' => 'Moderate', 'LogThreshold' => 5, 'RemoveThreshold' => 10, 'ModeratorInc' => 5, 'Protected' => TRUE, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
    'Description' => "Report posts that are abusive or violate your terms of service so that they can be alerted to a moderator's attention."]);
//$Rm->defineReactionType(array('UrlCode' => 'Troll', 'Name' => 'Troll', 'Sort' => 102, 'Class' => 'Flag', 'ModeratorInc' => 5, 'Protected' => TRUE, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
//   'Description' => "Troll posts are typically trying to elicit a heated argument from other people. Trolls are community poison, making your community a scary place for new members. Troll posts will be buried."));

$Rm->defineReactionType(['UrlCode' => 'Promote', 'Name' => 'Promote', 'Sort' => 0, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'IncrementValue' => 5, 'Points' => 5, 'Permission' => 'Garden.Curation.Manage',
    'Description' => "Moderators have the ability to promote the best posts in the community. This way they can be featured for new visitors."]);

$Rm->defineReactionType(['UrlCode' => 'OffTopic', 'Name' => 'Off Topic', 'Sort' => 1, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => 0,
    'Description' => "Off topic posts are not relevant to the topic being discussed. If a post gets enough off-topic votes then it will be buried so it won't derail the discussion."]);
$Rm->defineReactionType(['UrlCode' => 'Insightful', 'Name' => 'Insightful', 'Sort' => 2, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
    'Description' => "Insightful comments bring new information or perspective to the discussion and increase the value of the conversation as a whole."]);

$Rm->defineReactionType(['UrlCode' => 'Disagree', 'Name' => 'Disagree', 'Sort' => 3, 'Class' => 'Negative',
    'Description' => "Users that disagree with a post can give their opinion with this reaction. Since a disagreement is highly subjective, this reaction doesn't promote or bury the post or give any points."]);
$Rm->defineReactionType(['UrlCode' => 'Agree', 'Name' => 'Agree', 'Sort' => 4, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
    'Description' => "Users that agree with a post can give their opinion with this reaction."]);

$Rm->defineReactionType(['UrlCode' => 'Dislike', 'Name' => 'Dislike', 'Sort' => 5, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => 0,
    'Description' => "A dislike is a general disapproval of a post. Enough dislikes will bury a post."]);
$Rm->defineReactionType(['UrlCode' => 'Like', 'Name' => 'Like', 'Sort' => 6, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
    'Description' => "A like is a general approval of a post. Enough likes will promote a post."]);

$Rm->defineReactionType(['UrlCode' => 'Down', 'Name' => 'Vote Down', 'Sort' => 7, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => 0,
    'Description' => "A down vote is a general disapproval of a post. Enough down votes will bury a post."]);
$Rm->defineReactionType(['UrlCode' => 'Up', 'Name' => 'Vote Up', 'Sort' => 8, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
    'Description' => "An up vote is a general approval of a post. Enough up votes will promote a post."]);

$Rm->defineReactionType(['UrlCode' => 'WTF', 'Name' => 'WTF', 'Sort' => 9, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => 0,
    'Description' => 'WTF stands for "What the Fuh?" You usually react this way when a post makes absolutely no sense.']);
$Rm->defineReactionType(['UrlCode' => 'Awesome', 'Name' => 'Awesome', 'Sort' => 10, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
    'Description' => 'Awesome posts amaze you. You want to repeat them to your friends and remember them later.']);
$Rm->defineReactionType(['UrlCode' => 'LOL', 'Name' => 'LOL', 'Sort' => 11, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 0,
    'Description' => 'For posts that make you "laugh out loud." Funny content is almost always good and is rewarded with points and promotion.']);

if (!$ReactionTypeExists) {
    // Activate the default reactions.
    $Defaults = ['Spam', 'Abuse', 'Promote', 'LOL', 'Disagree', 'Agree', 'Like'];
    $Sql->update('ReactionType')
        ->set('Active', 1)
        ->whereIn('UrlCode', $Defaults)
        ->put();
    Gdn::cache()->remove('ReactionTypes');
}

// Change classes from Good/Bad to Positive/Negative.
if ($ReactionTypeExists && $Sql->getWhere('ReactionType', ['Class' => ['Good', 'Bad']])->firstRow()) {
    $Sql->put(
        'ReactionType',
        ['Class' => 'Positive'],
        ['Class' => 'Good'],
        true
    );

    $Sql->put(
        'ReactionType',
        ['Class' => 'Negative'],
        ['Class' => 'Bad'],
        true
    );
}

// Hande user merging.
$St->table('UserMerge');
$mergeReactions = $St->columnExists('ReactionsMerged');
$St->column('ReactionsMerged', 'tinyint', '0')
    ->set();

if ($mergeReactions) {
    $Rm->mergeOldUserReactions();
}

if (Gdn::addonManager()->isEnabled('badges', Addon::TYPE_ADDON)) {
    // Define some badges for the reactions.
    $badgeModel = new BadgeModel();

    $reactions = ['Insightful' => 'Insightfuls', 'Agree' => 'Agrees', 'Like' => 'Likes', 'Up' => 'Up Votes', 'Awesome' => 'Awesomes', 'LOL' => 'LOLs'];
    $thresholds = [
        5 => 5, 25 => 5, 100 => 10, 250 => 25, 500 => 50,
        1000 => 50, 1500 => 50, 2500 => 50, 5000 => 50, 10000 => 50,
    ];
    $sentences = [
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

    foreach ($reactions as $class => $nameSuffix) {
        $classSlug = strtolower($class);
        $level = 1;

        foreach ($thresholds as $threshold => $points) {
            $sentence = $sentences[$level];
            $thresholdFormatted = number_format($threshold);

            //foreach ($Likes as $Count => $Body) {
            $badgeModel->define([
                'Name' => "$thresholdFormatted $nameSuffix",
                'Slug' => "$classSlug-$threshold",
                'Type' => 'Reaction',
                'Body' => "You received $thresholdFormatted $nameSuffix. $sentence",
                'Photo' => "https://badges.v-cdn.net/svg/$classSlug-$level.svg",
                'Points' => $points,
                'Threshold' => $threshold,
                'Class' => $class,
                'Level' => $level,
                'CanDelete' => 0
            ]);

            $level++;
        }
    }
}
