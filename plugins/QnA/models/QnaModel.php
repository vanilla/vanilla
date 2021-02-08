<?php
/**
 * Class QnaModel
 */
class QnaModel extends Gdn_Model {

    const ACCEPTED = 'Accepted';
    const ANSWERED = 'Answered';
    const UNANSWERED = 'Unanswered';

    /**
     * Update Q&A counts through the dba/counts endpoint.
     *
     * @param string $column
     * @return array $results Formatted to match what "dba.js" expects
     */
    public function counts($column) {
        $result = ['Complete' => true];

        switch ($column) {
            // Discussion table, QnA column will be updated.
            case 'QnA':
                $request = Gdn::request()->get();
                $result = $this->recalculateDiscussionQnABatches($request['NumberOfBatchesDone'] ?? 0, $request['LatestID'] ?? 0);
                break;
        }

        return $result;
    }

    /**
     * Recalculate the QnA state of discussions.
     * There is 4 possible QnA states for questions, Unanswered, Answered, Rejected and Accepted.
     * There is 3 possible QnA states for comments, Accepted, Rejected and NULL (Untreated).
     *
     * @param array $discussionIDs discussions to be recalculated
     * @throws Exception | Being thrown from the put method of the sql object
     */
    private function recalculateDiscussionsQnA($discussionIDs) {
        // Updating questions with accepted answers.
        Gdn::sql()
            ->update('Discussion d')
            ->join('Comment c', 'c.DiscussionID = d.DiscussionID and c.QnA = \'Accepted\'')
            ->set('d.QnA', 'Accepted')
            ->whereIn('d.DiscussionID', $discussionIDs)
            ->put();

        // Updating questions with no answers.
        Gdn::sql()
            ->update('Discussion d')
            ->leftJoin('Comment c', 'c.DiscussionID = d.DiscussionID')
            ->set('d.QnA', 'Unanswered')
            ->where(['c.CommentID is null' => ''])
            ->whereIn('d.DiscussionID', $discussionIDs)
            ->put();

        // Updating questions with untreated answers but no accepted answer.
        Gdn::sql()
            ->update('Discussion d')
            ->join('Comment c', 'c.DiscussionID = d.DiscussionID and c.QnA is null')
            ->leftJoin('Comment c1', 'c1.DiscussionID = d.DiscussionID and c1.QnA = \'Accepted\'')
            ->set('d.QnA', 'Answered')
            ->where(['c1.CommentID is null' => ''])
            ->whereIn('d.DiscussionID', $discussionIDs)
            ->put();

        // Updating questions with ONLY rejected answers.
        Gdn::sql()
            ->update('Discussion d')
            ->join('Comment c', 'c.DiscussionID = d.DiscussionID and c.QnA = \'Rejected\'')
            ->leftJoin('Comment c1', 'c1.DiscussionID = d.DiscussionID and (c1.QnA = \'Accepted\' OR c1.QnA is null)')
            ->set('d.QnA', 'Rejected')
            ->where(['c1.CommentID is null' => ''])
            ->whereIn('d.DiscussionID', $discussionIDs)
            ->put();
    }

    /**
     * Preparing batches of discussions (questions) and triggering recalculation of their QnA state.
     * Updating records in batches of 1000 to make sure we don't lock the table for too long.
     *
     * @param integer $numberOfBatchesDone number of batches already processed
     * @param integer $latestID latest discussionID we treated
     * @return array current state of QnA recalculation. Formatted to match what "dba.js" expects
     */
    private function recalculateDiscussionQnABatches($numberOfBatchesDone, $latestID) {
        $perBatch = 1000;

        // Make sure we don't kill a database.
        $count = Gdn::sql()->getCount('Discussion', ['Type' => 'Question']);
        $threshold = c('Database.AlterTableThreshold', 250000);
        if ($count > $threshold)  {
            throw new Exception('Amount of questions is exceeding the database threshold of '.$threshold.'.');
        }

        // Get min and max discussionID for questions
        $result = Gdn::sql()
            ->select('DiscussionID', 'max', 'MaxValue')
            ->select('DiscussionID', 'min', 'MinValue')
            ->from('Discussion')
            ->where(['Type' => 'Question'])
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $totalBatches = ceil(($result['MaxValue'] - $result['MinValue']) / $perBatch);

        $currentBatch = Gdn::sql()
            ->select('DiscussionID')
            ->from('Discussion')
            ->where([
                'DiscussionID >' => $latestID,
                'Type' => 'Question',
            ])
            ->orderBy('DiscussionID')
            ->limit($perBatch)
            ->get()
            ->resultArray();

        $currentBatch = array_column($currentBatch, 'DiscussionID', 'DiscussionID');

        $latestID = key(array_slice($currentBatch, -1, 1, true));

        $this->recalculateDiscussionsQnA($currentBatch);

        $numberOfBatchesDone++;

        if ($totalBatches == $numberOfBatchesDone) {
            return ['Complete' => true];
        }

        return [
            'Percent' => round($numberOfBatchesDone / $totalBatches * 100) . '%',
            'Args' => [
                'NumberOfBatchesDone' => $numberOfBatchesDone,
                'LatestID' => $latestID,
            ],
        ];
    }
}
