<?php if (!defined("APPLICATION")) {
    exit();
}

use Vanilla\Addon;

$St = Gdn::structure();
$Sql = Gdn::sql();

Gdn::permissionModel()->define([
    "Reactions.Positive.Add" => "Garden.SignIn.Allow",
    "Reactions.Negative.Add" => "Garden.SignIn.Allow",
    "Reactions.Flag.Add" => "Garden.SignIn.Allow",
]);

$St->table("ReactionType");
$ReactionTypeExists = $St->tableExists();

$St->column("UrlCode", "varchar(32)", false, "primary")
    ->column("Name", "varchar(32)")
    ->column("Description", "text", true)
    ->column("Class", "varchar(10)", true)
    ->column("TagID", "int")
    ->column("Attributes", "text", true)
    ->column("Sort", "smallint", true)
    ->column("Active", "tinyint(1)", 0)
    ->column("Custom", "tinyint(1)", 0)
    ->column("Hidden", "tinyint(1)", 0)
    ->set();

$St->table("UserTag")
    ->column(
        "RecordType",
        [
            "Discussion",
            "Discussion-Total",
            "Comment",
            "Comment-Total",
            "User",
            "User-Total",
            "Activity",
            "Activity-Total",
            "ActivityComment",
            "ActivityComment-Total",
        ],
        false,
        ["primary", "index.combined"]
    )
    ->column("RecordID", "int", false, "primary")
    ->column("TagID", "int", false, ["primary", "key", "index.combined"])
    ->column("UserID", "int", false, ["primary", "key", "index.combined"])
    ->column("DateInserted", "datetime", false, ["index", "index.combined"])
    ->column("Total", "int", 0, ["index.combined"])
    ->set();

$Rm = new ReactionModel();

// Insert some default tags.
$Rm->defineReactionType([
    "UrlCode" => "Spam",
    "Name" => "Spam",
    "Sort" => 100,
    "Class" => "Flag",
    "Log" => "Spam",
    "LogThreshold" => 5,
    "RemoveThreshold" => 5,
    "ModeratorInc" => 5,
    "Protected" => true,
    "IncrementColumn" => "Score",
    "IncrementValue" => -1,
    "Points" => -1,
    "Description" =>
        "Allow your community to report any spam that gets posted so that it can be removed as quickly as possible.",
]);
$Rm->defineReactionType([
    "UrlCode" => "Abuse",
    "Name" => "Abuse",
    "Sort" => 101,
    "Class" => "Flag",
    "Log" => "Moderate",
    "LogThreshold" => 5,
    "RemoveThreshold" => 10,
    "ModeratorInc" => 5,
    "Protected" => true,
    "IncrementColumn" => "Score",
    "IncrementValue" => -1,
    "Points" => -1,
    "Description" =>
        "Report posts that are abusive or violate your terms of service so that they can be alerted to a moderator's attention.",
]);
//$Rm->defineReactionType(array('UrlCode' => 'Troll', 'Name' => 'Troll', 'Sort' => 102, 'Class' => 'Flag', 'ModeratorInc' => 5, 'Protected' => TRUE, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
//   'Description' => "Troll posts are typically trying to elicit a heated argument from other people. Trolls are community poison, making your community a scary place for new members. Troll posts will be buried."));

$Rm->defineReactionType([
    "UrlCode" => "Promote",
    "Name" => "Promote",
    "Sort" => 0,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "IncrementValue" => 5,
    "Points" => 5,
    "Permission" => "Garden.Curation.Manage",
    "Description" =>
        "Moderators have the ability to promote the best posts in the community. This way they can be featured for new visitors.",
]);

$Rm->defineReactionType([
    "UrlCode" => "OffTopic",
    "Name" => "Off Topic",
    "Sort" => 1,
    "Class" => "Negative",
    "IncrementColumn" => "Score",
    "IncrementValue" => -1,
    "Points" => 0,
    "Description" =>
        "Off topic posts are not relevant to the topic being discussed. If a post gets enough off-topic votes then it will be buried so it won't derail the discussion.",
]);
$Rm->defineReactionType([
    "UrlCode" => "Insightful",
    "Name" => "Insightful",
    "Sort" => 2,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "Points" => 1,
    "Description" =>
        "Insightful comments bring new information or perspective to the discussion and increase the value of the conversation as a whole.",
]);

$Rm->defineReactionType([
    "UrlCode" => "Disagree",
    "Name" => "Disagree",
    "Sort" => 3,
    "Class" => "Negative",
    "Description" =>
        "Users that disagree with a post can give their opinion with this reaction. Since a disagreement is highly subjective, this reaction doesn't promote or bury the post or give any points.",
]);
$Rm->defineReactionType([
    "UrlCode" => "Agree",
    "Name" => "Agree",
    "Sort" => 4,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "Points" => 1,
    "Description" => "Users that agree with a post can give their opinion with this reaction.",
]);

$Rm->defineReactionType([
    "UrlCode" => "Dislike",
    "Name" => "Dislike",
    "Sort" => 5,
    "Class" => "Negative",
    "IncrementColumn" => "Score",
    "IncrementValue" => -1,
    "Points" => 0,
    "Description" => "A dislike is a general disapproval of a post. Enough dislikes will bury a post.",
]);
$Rm->defineReactionType([
    "UrlCode" => "Like",
    "Name" => "Like",
    "Sort" => 6,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "Points" => 1,
    "Description" => "A like is a general approval of a post. Enough likes will promote a post.",
]);

$Rm->defineReactionType([
    "UrlCode" => "Down",
    "Name" => "Vote Down",
    "Sort" => 7,
    "Class" => "Negative",
    "IncrementColumn" => "Score",
    "IncrementValue" => -1,
    "Points" => 0,
    "Description" => "A down vote is a general disapproval of a post. Enough down votes will bury a post.",
]);
$Rm->defineReactionType([
    "UrlCode" => "Up",
    "Name" => "Vote Up",
    "Sort" => 8,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "Points" => 1,
    "Description" => "An up vote is a general approval of a post. Enough up votes will promote a post.",
]);

$Rm->defineReactionType([
    "UrlCode" => "WTF",
    "Name" => "WTF",
    "Sort" => 9,
    "Class" => "Negative",
    "IncrementColumn" => "Score",
    "IncrementValue" => -1,
    "Points" => 0,
    "Description" => 'WTF stands for "What the Fuh?" You usually react this way when a post makes absolutely no sense.',
]);
$Rm->defineReactionType([
    "UrlCode" => "Awesome",
    "Name" => "Awesome",
    "Sort" => 10,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "Points" => 1,
    "Description" => "Awesome posts amaze you. You want to repeat them to your friends and remember them later.",
]);
$Rm->defineReactionType([
    "UrlCode" => "LOL",
    "Name" => "LOL",
    "Sort" => 11,
    "Class" => "Positive",
    "IncrementColumn" => "Score",
    "Points" => 0,
    "Description" =>
        'For posts that make you "laugh out loud." Funny content is almost always good and is rewarded with points and promotion.',
]);

if (!$ReactionTypeExists) {
    // Activate the default reactions.
    $Defaults = ["Spam", "Abuse", "Promote", "LOL", "Disagree", "Agree", "Like"];
    $Sql->update("ReactionType")
        ->set("Active", 1)
        ->whereIn("UrlCode", $Defaults)
        ->put();
    Gdn::cache()->remove("ReactionTypes");
}

// Change classes from Good/Bad to Positive/Negative.
if ($ReactionTypeExists && $Sql->getWhere("ReactionType", ["Class" => ["Good", "Bad"]])->firstRow()) {
    $Sql->put("ReactionType", ["Class" => "Positive"], ["Class" => "Good"], true);

    $Sql->put("ReactionType", ["Class" => "Negative"], ["Class" => "Bad"], true);
}

// Hande user merging.
$St->table("UserMerge");
$mergeReactions = $St->columnExists("ReactionsMerged");
$St->column("ReactionsMerged", "tinyint", "0")->set();

if ($mergeReactions) {
    $Rm->mergeOldUserReactions();
}

ReactionModel::resetStaticCache();

if (Gdn::addonManager()->isEnabled("badges", Addon::TYPE_ADDON)) {
    $reactionBadges = Gdn::getContainer()->get(\Vanilla\Reactions\Addon\ReactionBadges::class);
    $reactionBadges->generateDefaultBadges();
}
