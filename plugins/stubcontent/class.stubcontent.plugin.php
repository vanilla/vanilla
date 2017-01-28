<?php

/**
 * @copyright 2010-2017 Vanilla Forums Inc
 * @license Proprietary
 */

use Symfony\Component\Yaml\Yaml;

// Define the plugin:
$PluginInfo['stubcontent'] = array(
    'Name' => 'Stub Content',
    'Description' => "This plugin adds stub content to new forums.",
    'Version' => '1.0a',
    'MobileFriendly' => true,
    'RequiredApplications' => false,
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'RegisterPermissions' => false,
    'Icon' => 'stubcontent-plugin.png',
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);


/**
 * Stub Content Plugin
 *
 * This plugin provides newly spawned forums with some initial content that both
 * improves its look-and-feel by filling empty spaces with content, and also helps
 * familiarize new admins and moderators with best practices.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package internal
 * @since 1.0
 */
class StubContentPlugin extends Gdn_Plugin {

    const RECORD_KEY = 'stubcontent.record.%s';

    protected $contentPaths = [
        'user' => 'content/user.yaml',
        'discussion' => 'content/discussion.yaml',
        'comment' => 'content/comment.yaml',
        'conversation' => 'content/conversation.yaml'
    ];


    public function __construct() {

    }

    /**
     * Get stub content raw data by type
     *
     * @param string $type
     * @throws Exception
     * @return array
     */
    public function getStubContent($type) {
        if (!array_key_exists($type, $this->contentPaths)) {
            throw new Exception("Unsupported stub content type '{$type}'");
        }

        $filename = $this->contentPaths[$type];
        $filepath = $this->getResource($filename);
        if (!file_exists($filepath)) {
            throw new Exception("Missing stub content data file for '{$type}': {$filepath}");
        }

        $contents = file_get_contents($filepath);
        if (empty($contents)) {
            throw new Exception("Empty stub content data file for '{$type}': {$filepath}");
        }

        $parsed = Yaml::parse($contents);
        if (empty($parsed) || empty($parsed[$type])) {
            throw new Exception("Corrupt stub content data file for '{$type}': {$filepath}");
        }

        foreach ($parsed[$type] as &$content) {
            $content['type'] = $type;
        }
        return $parsed[$type];
    }

    /**
     * Add or update stub content by type
     *
     * This method loads the stub content data file for the given type, iterates
     * over each item within it and checks whether it has been inserted into
     * the forum. If not, it is inserted. If it already exists, its locale
     * is checked and updated if needed.
     *
     * @param string $type
     * @return boolean
     */
    public function addStubContent($type) {
        debugMethod(__METHOD__, func_get_args());
        $allContent = $this->getStubContent($type);

        $activeLocale = Gdn::locale()->language();

        // Iterate over each requested piece of content
        foreach ($allContent as $content) {

            $record = $this->getRecordByContent($content);

            switch ($type) {
                case 'user':
                    $name = $content['name'];
                    break;
                case 'discussion':
                    $name = $content['title'];
                    break;
                case 'comment':
                    $name = $content['author'].' on '.$content['parent'];
                    break;
            }
            echo "{$type}: {$name}\n";

            // If no receipt, add record
            if (!$record['receipt']) {

                echo " adding record\n";

                $record = $this->insertContent($content);

            // Otherwise, perhaps update
            } else if ($record['row']) {
                // Update if locale mismatch

                echo " record exists\n";

            } else {
                echo " record deleted\n";
            }

        }
    }

    /**
     * Inset stub content
     *
     * @param array $content
     */
    public function insertContent($content) {

        $contentID = $this->getContentID($content);
        switch ($content['type']) {

            case 'user':

                $model = new UserModel;
                $rowID = $model->save([
                    'Name'          => $content['name'],
                    'Email'         => $content['email'],
                    'Photo'         => $content['photo'],
                    'Password'      => betterRandomString(24),
                    'HashMethod'    => 'Random',
                    'Attributes'    => [
                        'Locale'            => Gdn::locale()->language(),
                        'StubContentID'     => $contentID,
                        'StubContentTag'    => $content['tag']
                    ]
                ]);

                if ($rowID) {
                    $receipt = $this->createReceipt($content, $rowID);
                }

                break;

            case 'discussion':

                // Get author
                $authorTag = $content['author'];
                $authorRecord = $this->getRecord('user', $authorTag);

                // Get category
                $categoryTag = $content['category'];
                $category = CategoryModel::instance()->getByCode($categoryTag);

                if ($authorRecord['row'] && !empty($category)) {

                    $authorID = $authorRecord['receipt']['rowID'];
                    $category = (array)$category;

                    $model = new DiscussionModel;
                    $rowID = $model->save([
                        'Type'      => 'stub',
                        'ForeignID' => $contentID,
                        'CategoryID' => $category['CategoryID'],
                        'InsertUserID' => $authorID,
                        'Name'      => $content['title'],
                        'Body'      => formatString($content['body'],[
                            'author' => $authorRecord['row']
                        ]),
                        'Announce'  => val('announce', $content, 0),
                        'Format'    => val('format', $content, 'Markdown'),
                        'Attributes'    => [
                            'Locale'            => Gdn::locale()->language(),
                            'StubContentID'     => $contentID,
                            'StubContentTag'    => $content['tag']
                        ]
                    ]);

                    if ($rowID) {
                        $receipt = $this->createReceipt($content, $rowID);
                    }

                }

                break;

            case 'comment':

                // Get author
                $authorTag = $content['author'];
                $authorRecord = $this->getRecord('user', $authorTag);

                // Get parent
                $parentTag = $content['parent'];
                $parentRecord = $this->getRecord('discussion', $parentTag);

                if ($authorRecord['row'] && $parentRecord['row']) {

                    $authorID = $authorRecord['receipt']['rowID'];
                    $parentID = $parentRecord['receipt']['rowID'];

                    $parentAuthor = Gdn::userModel()->getID($parentRecord['row']['InsertUserID'], DATASET_TYPE_ARRAY);

                    $model = new CommentModel;
                    $rowID = $model->save([
                        'DiscussionID' => $parentID,
                        'InsertUserID' => $authorID,
                        'Body'      => formatString($content['body'], [
                            'author' => $authorRecord['row'],
                            'parent' => $parentAuthor
                        ]),
                        'Format'    => val('format', $content, 'Markdown'),
                        'Attributes'    => [
                            'Locale'            => Gdn::locale()->language(),
                            'StubContentID'     => $contentID,
                            'StubContentTag'    => $content['tag']
                        ]
                    ]);

                    if ($rowID) {
                        $receipt = $this->createReceipt($content, $rowID);
                    }

                }

                break;

            case 'conversation':

                $model = new ConversationModel;
                $rowID = $model->save([
                    'Attributes'    => [
                        'Locale'            => Gdn::locale()->language(),
                        'StubContentID'     => $contentID,
                        'StubContentTag'    => $content['tag']
                    ]
                ]);

                if (!$rowID) {
                    $receipt = $this->createReceipt($content, $rowID);
                }

                break;
        }

        if (!$rowID) {
            echo " failed to insert\n";
            if ($model) {
                foreach ($model->validationResults() as $result) {
                    echo " {$result[0]}\n";
                }
            }
        }
    }

    /**
     * Retrieve record bundle from content
     *
     * @param array $content
     * @return array
     */
    public function getRecordByContent($content) {
        return $this->getRecord($content['type'], $content['tag']);
    }

    /**
     * Retrieve record bundle
     *
     * @param string $type
     * @param string $tag
     * @return array
     */
    public function getRecord($type, $tag) {
        $contentID = $this->makeContentID($type, $tag);
        $record = [
            'id'        => $contentID,
            'receipt'   => null,
            'row'       => null
        ];

        $recordReceipt = $this->getReceipt($contentID);
        if (!$recordReceipt) {
            return $record;
        }

        $record['receipt'] = $recordReceipt;

        switch ($type) {

            case 'user':
                $model = new UserModel;
                break;

            case 'discussion':
                $model = new DiscussionModel;
                break;

            case 'comment':
                $model = new CommentModel;
                break;

            case 'conversation':
                $model = new ConversationModel;
                break;
        }

        // Try to retrieve referenced record
        $rowID = $recordReceipt['rowID'];
        $row = $model->getID($rowID, DATASET_TYPE_ARRAY);
        if ($row) {
            $record['row'] = $row;
        }

        return $record;
    }

    /**
     * Apply locale updates to a record
     *
     * @param array $record
     */
    public function updateContent($record) {

    }

    /**
     * Generate creation receipt for stub content
     *
     * @param array $content
     * @param int $rowID
     */
    public function createReceipt($content, $rowID) {
        $contentID = $this->getContentID($content);
        $receipt = [
            'contentID' => $contentID,
            'rowID' => $rowID,
            'type' => $content['type']
        ];

        // Encode and store receipt
        $receiptKey = sprintf(self::RECORD_KEY, $contentID);
        $encoded = json_encode($receipt);
        Gdn::set($receiptKey, $encoded);

        return $receipt;
    }

    /**
     * Get unique ID for content piece
     *
     * @param array $content
     * @return string
     */
    public function getContentID($content) {
        return $this->makeContentID($content['type'], $content['tag']);
    }

    /**
     * Generate unique ID by type and tag
     *
     * @param string $type
     * @param string $tag
     * @return string
     */
    public function makeContentID($type, $tag) {
        return $type.'-'.substr(md5($tag), 0, 16);
    }

    /**
     * Try to find a creation receipt for a piece of content
     *
     * @param array $content
     * @return array|bool
     */
    public function getReceiptByContent($content) {
        $contentID = $this->getContentID($content);
        return $this->getReceipt($contentID);
    }

    /**
     * Try to find creation receipt for a content ID
     *
     * @param string $contentID
     * @return array|bool
     */
    public function getReceipt($contentID) {
        $receiptKey = sprintf(self::RECORD_KEY, $contentID);

        // Retrieve and decode receipt
        $encoded = Gdn::get($receiptKey);
        return json_decode($encoded, true);
    }

    /**
     * Handle initial plugin setup
     *
     *
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Add missing stub content on spawn and update
     *
     *
     */
    public function structure() {

        // User
        $this->addStubContent('user');

        // Discussions
        $this->addStubContent('discussion');

        // Comments
        $this->addStubContent('comment');

        die();

        // Conversations
        //$this->addStubContent('conversation');
    }

}