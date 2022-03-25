<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Discussion');

$QnAExists = Gdn::structure()->columnExists('QnA');
$DateAcceptedExists = Gdn::structure()->columnExists('DateAccepted');

Gdn::structure()
    ->column('QnA', ['Unanswered', 'Answered', 'Accepted', 'Rejected'], null, 'index')
    ->column('DateAccepted', 'datetime', true) // The
    ->column('DateOfAnswer', 'datetime', true) // The time to answer an accepted question.
    ->set();

Gdn::structure()
    ->table('Comment')
    ->column('QnA', ['Accepted', 'Rejected'], null)
    ->column('DateAccepted', 'datetime', true)
    ->column('AcceptedUserID', 'int', true)
    ->set();

Gdn::structure()
    ->table('User')
    ->column('CountAcceptedAnswers', 'int', '0')
    ->set();

if ($this->questionFollowupFeatureEnabled()) {
    Gdn::structure()
        ->table('Category')
        ->column('QnaFollowUpNotification', 'tinyint(1)', ['Null' => false, 'Default' => 0])
        ->set();
}

Gdn::sql()->replace(
    'ActivityType',
    ['AllowComments' => '0', 'RouteCode' => 'question', 'Notify' => '1', 'Public' => '0', 'ProfileHeadline' => '', 'FullHeadline' => ''],
    ['Name' => 'QuestionAnswer'], true);
Gdn::sql()->replace(
    'ActivityType',
    ['AllowComments' => '0', 'RouteCode' => 'answer', 'Notify' => '1', 'Public' => '0', 'ProfileHeadline' => '', 'FullHeadline' => ''],
    ['Name' => 'AnswerAccepted'], true);

if ($QnAExists && !$DateAcceptedExists) {
    // Default the date accepted to the accepted answer's date.
    $Px = Gdn::database()->DatabasePrefix;
    $Sql = "update {$Px}Discussion d set DateAccepted = (select min(c.DateInserted) from {$Px}Comment c where c.DiscussionID = d.DiscussionID and c.QnA = 'Accepted')";
    Gdn::sql()->query($Sql, 'update');
    Gdn::sql()->update('Discussion')
        ->set('DateOfAnswer', 'DateAccepted', false, false)
        ->put();

    Gdn::sql()->update('Comment c')
        ->join('Discussion d', 'c.CommentID = d.DiscussionID')
        ->set('c.DateAccepted', 'c.DateInserted', false, false)
        ->set('c.AcceptedUserID', 'd.InsertUserID', false, false)
        ->where('c.QnA', 'Accepted')
        ->where('c.DateAccepted', null)
        ->put();
}


// Define 'Answer' badges

if (Gdn::addonManager()->isEnabled('badges', \Vanilla\Addon::TYPE_ADDON) && c('Plugins.QnA.Badges', true)) {
    $this->Badges = true;
}

if ($this->Badges && class_exists('BadgeModel')) {
    $BadgeModel = new BadgeModel();

    // Answer Counts
    $BadgeModel->define([
        'Name' => 'First Answer',
        'Slug' => 'answer',
        'Type' => 'UserCount',
        'Body' => 'Answering questions is a great way to show your support for a community!',
        'Photo' => 'https://badges.v-cdn.net/svg/answer-1.svg',
        'Points' => 2,
        'Attributes' => ['Column' => 'CountAcceptedAnswers'],
        'Threshold' => 1,
        'Class' => 'Answerer',
        'Level' => 1,
        'CanDelete' => 0
    ]);
    $BadgeModel->define([
        'Name' => '5 Answers',
        'Slug' => 'answer-5',
        'Type' => 'UserCount',
        'Body' => 'Your willingness to share knowledge has definitely been noticed.',
        'Photo' => 'https://badges.v-cdn.net/svg/answer-2.svg',
        'Points' => 3,
        'Attributes' => ['Column' => 'CountAcceptedAnswers'],
        'Threshold' => 5,
        'Class' => 'Answerer',
        'Level' => 2,
        'CanDelete' => 0
    ]);
    $BadgeModel->define([
        'Name' => '25 Answers',
        'Slug' => 'answer-25',
        'Type' => 'UserCount',
        'Body' => 'Looks like you&rsquo;re starting to make a name for yourself as someone who knows the score!',
        'Photo' => 'https://badges.v-cdn.net/svg/answer-3.svg',
        'Points' => 5,
        'Attributes' => ['Column' => 'CountAcceptedAnswers'],
        'Threshold' => 25,
        'Class' => 'Answerer',
        'Level' => 3,
        'CanDelete' => 0
    ]);
    $BadgeModel->define([
        'Name' => '50 Answers',
        'Slug' => 'answer-50',
        'Type' => 'UserCount',
        'Body' => 'Why use Google when we could just ask you?',
        'Photo' => 'https://badges.v-cdn.net/svg/answer-4.svg',
        'Points' => 10,
        'Attributes' => ['Column' => 'CountAcceptedAnswers'],
        'Threshold' => 50,
        'Class' => 'Answerer',
        'Level' => 4,
        'CanDelete' => 0
    ]);
    $BadgeModel->define([
        'Name' => '100 Answers',
        'Slug' => 'answer-100',
        'Type' => 'UserCount',
        'Body' => 'Admit it, you read Wikipedia in your spare time.',
        'Photo' => 'https://badges.v-cdn.net/svg/answer-5.svg',
        'Points' => 15,
        'Attributes' => ['Column' => 'CountAcceptedAnswers'],
        'Threshold' => 100,
        'Class' => 'Answerer',
        'Level' => 5,
        'CanDelete' => 0
    ]);
    $BadgeModel->define([
        'Name' => '250 Answers',
        'Slug' => 'answer-250',
        'Type' => 'UserCount',
        'Body' => 'Is there *anything* you don&rsquo;t know?',
        'Photo' => 'https://badges.v-cdn.net/svg/answer-6.svg',
        'Points' => 20,
        'Attributes' => ['Column' => 'CountAcceptedAnswers'],
        'Threshold' => 250,
        'Class' => 'Answerer',
        'Level' => 6,
        'CanDelete' => 0
    ]);
}

// Define 'Accept' reaction

if (Gdn::addonManager()->isEnabled('Reactions', \Vanilla\Addon::TYPE_ADDON) && c('Plugins.QnA.Reactions', true)) {
    $this->Reactions = true;
}

if ($this->Reactions && class_exists('ReactionModel')) {
    $Rm = new ReactionModel();

    if (Gdn::structure()->table('ReactionType')->columnExists('Hidden')) {
        $points = 3;
        if (c('QnA.Points.Enabled', false)) {
            $points = c('QnA.Points.AcceptedAnswer', 1);
        }

        // AcceptAnswer
        $record = $Rm->getWhere(['UrlCode' => 'AcceptAnswer'])->resultArray();
        if (!$record) {
            $result = $Rm->defineReactionType([
                'UrlCode' => 'AcceptAnswer',
                'Name' => 'Accept Answer',
                'Sort' => 0,
                'Class' => 'Positive',
                'IncrementColumn' => 'Score',
                'IncrementValue' => 5,
                'Points' => $points,
                'Permission' => 'Garden.Curation.Manage',
                'Hidden' => 1,
                'Description' => "When someone correctly answers a question, they are rewarded with this reaction."
            ]);
        } else {
            $Rm->save([
                'UrlCode' => 'AcceptAnswer',
                'Points' => $points,
            ]);
        }
    }

    Gdn::structure()->reset();
}
