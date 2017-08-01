<?php

/**
 * @copyright 2010-2017 Vanilla Forums Inc
 * @license Proprietary
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Stub Content Plugin
 *
 * This plugin provides newly spawned forums with some initial content that both
 * improves its look-and-feel by filling empty spaces with content, and also helps
 * familiarize new admins and moderators with best practices.
 *
 * All pieces of stub content receive a unique ID. When they're inserted into the
 * forum, this ID is associated with a "receipt" that is stored in the UserMeta
 * table. This receipt prevent repeat insertions of stub content when that content
 * is deleted from the forum.
 *
 * LOCALE SUPPORT
 *
 * The titles and body contents of all stub content can be easily translated using
 * Vanilla's locale system. Whenever a piece of content is being added, it is passed
 * through the translation system. Translation codes are used, and are based on the
 * "tag" field of each piece of content.
 *
 * For a discussion, the available translations are:
 *
 *      <tag>.title
 *      <tag>.body
 *
 * For a comment:
 *
 *      <tag>.body
 *
 * For a conversation:
 *
 *      <tag>.subject
 *      <tag>.body
 *
 * @author Tim Gunter <tim@vanillaforums.com>
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

    /**
     * Handle locale changes to translate stub content
     *
     * @param Gdn_ConfigurationSource $sender
     */
    public function gdn_configurationSource_beforeSave_handler($sender) {

        // Don't re-check inserted stub content unless an admin caused a config change
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        $newLocale = $sender->get('Garden.Locale', 'en');
        $oldLocale = Gdn::get(sprintf(self::RECORD_KEY, 'locale'));

        if ($newLocale != $oldLocale) {
            // Initialize new locale
            Gdn::locale()->set($newLocale);

            // Process stub content under new locale
            $this->processStubContent();
        }
    }

    /**
     * Add or update all stub content
     *
     */
    public function processStubContent() {

        // User
        $this->addStubContent('user');

        // Discussions
        $this->addStubContent('discussion');

        // Comments
        $this->addStubContent('comment');

        // Conversations @TODO
        //$this->addStubContent('conversation');

        try {
            Gdn::set(sprintf(self::RECORD_KEY, 'locale'), c('Garden.Locale'));
        } catch (Exception $ex) {
            // Nothing
        }
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
        $activeLocale = c('Garden.Locale');

        $allContent = $this->getStubContent($type);

        // Iterate over each requested piece of content
        foreach ($allContent as $content) {

            // Retrieve stub content record bundle

            /*
             * $record = [
             *      id => discussion-8733a97ed5cea009
             *      receipt => [
             *          contentID => discussion-8733a97ed5cea009
             *          rowID => 15
             *          type => discussion
             *      ]
             *      row => [
             *          // actual DB row
             *      ]
             * ]
             *
             */
            $record = $this->getRecordByContent($content);

            // If no receipt, add record
            if (!$record['receipt']) {

                $record = $this->insertContent($content);

            // Otherwise, perhaps update
            } else if ($record['row']) {

                // Update if locale mismatch
                $stubLocale = valr('Attributes.StubLocale', $record['row']);
                if ($stubLocale != $activeLocale) {
                    $this->updateContent($content, $record);
                }

            }

        }
    }

    /**
     * Insert stub content
     *
     * @param array $content
     */
    public function insertContent($content) {

        // Don't affect installed forums
        if (c('Garden.Installed', false) === true) {
            return;
        }

        $contentID = $this->getContentID($content);
        $activeLocale = c('Garden.Locale');

        switch ($content['type']) {

            case 'user':

                // Get role
                $roleTag = val('role', $content, 'member');
                $roleModel = new RoleModel;
                $role = $roleModel->getByType($roleTag)->firstRow(DATASET_TYPE_ARRAY);

                if (!empty($role)) {
                    $model = new UserModel;
                    $rowID = $model->save([
                        'Name'              => $content['name'],
                        'Email'             => $content['email'],
                        'Photo'             => $content['photo'],
                        'Password'          => betterRandomString(24),
                        'HashMethod'        => 'Random',
                        'RoleID'            => [
                            $role['RoleID']
                        ],
                        'Attributes'        => [
                            'StubLocale'        => $activeLocale,
                            'StubContentID'     => $contentID,
                            'StubContentTag'    => $content['tag']
                        ]
                    ], [
                        'ValidateEmail' => false,
                        'NoConfirmEmail' => true,
                        'SaveRoles' => true
                    ]);

                    if ($rowID) {
                        $receipt = $this->createReceipt($content, $rowID);
                    } else {
                        Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                            'type' => $content['type'],
                            'content' => $contentID,
                            'error' => print_r($model->validationResults(), true)
                        ]);
                    }
                } else {
                    $errors = [];
                    if (empty($role)) {
                        $errors[] = "missing role: {$roleTag}";
                    }

                    Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                        'type' => $content['type'],
                        'content' => $contentID,
                        'error' => print_r($errors, true)
                    ]);
                }
                break;

            case 'discussion':

                // Get author
                $authorTag = $content['author'];
                $authorRecord = $this->getRecord('user', $authorTag);

                // Get category
                $categoryTag = $content['category'];
                $category = (array)CategoryModel::instance()->getByCode($categoryTag);

                if ($authorRecord['row'] && !empty($category)) {

                    $authorID = $authorRecord['receipt']['rowID'];
                    $category = (array)$category;

                    // Build translation tags
                    $translateName = "{$content['tag']}.title";
                    $translateBody = "{$content['tag']}.body";

                    $model = new DiscussionModel;
                    $rowID = $model->save([
                        'Type'          => 'stub',
                        'ForeignID'     => $contentID,
                        'CategoryID'    => $category['CategoryID'],
                        'InsertUserID'  => $authorID,
                        'Name'          => t($translateName, $content['title']),
                        'Body'          => formatString(t($translateBody, $content['body']),[
                            'author' => $authorRecord['row']
                        ]),
                        'Announce'      => val('announce', $content, 0),
                        'Format'        => val('format', $content, 'Markdown'),
                        'Attributes'    => [
                            'StubLocale'        => $activeLocale,
                            'StubContentID'     => $contentID,
                            'StubContentTag'    => $content['tag']
                        ]
                    ], [
                        'CheckPermission' => false
                    ]);

                    if ($rowID) {
                        $receipt = $this->createReceipt($content, $rowID);
                    } else {
                        Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                            'type' => $content['type'],
                            'content' => $contentID,
                            'error' => print_r($model->validationResults(), true)
                        ]);
                    }
                } else {
                    $errors = [];
                    if (!$authorRecord['row']) {
                        $errors[] = "missing author: {$authorTag}";
                    }
                    if (empty($category)) {
                        $errors[] = "missing category: {$categoryTag}";
                    }

                    Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                        'type' => $content['type'],
                        'content' => $contentID,
                        'error' => print_r($errors, true)
                    ]);
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

                    // Build translation tags
                    $translateBody = "{$content['tag']}.body";

                    $model = new CommentModel;
                    $rowID = $model->save([
                        'DiscussionID'  => $parentID,
                        'InsertUserID'  => $authorID,
                        'Body'          => formatString(t($translateBody, $content['body']), [
                            'author' => $authorRecord['row'],
                            'parent' => $parentAuthor
                        ]),
                        'Format'        => val('format', $content, 'Markdown'),
                        'Attributes'    => [
                            'StubLocale'        => $activeLocale,
                            'StubContentID'     => $contentID,
                            'StubContentTag'    => $content['tag']
                        ]
                    ]);

                    if ($rowID) {
                        $receipt = $this->createReceipt($content, $rowID);
                    } else {
                        Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                            'type' => $content['type'],
                            'content' => $contentID,
                            'error' => print_r($model->validationResults(), true)
                        ]);
                    }
                } else {
                    $errors = [];
                    if (!$authorRecord['row']) {
                        $errors[] = "missing author: {$authorTag}";
                    }
                    if (!$parentRecord['row']) {
                        $errors[] = "missing parent: {$parentTag}";
                    }

                    Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                        'type' => $content['type'],
                        'content' => $contentID,
                        'error' => print_r($errors, true)
                    ]);
                }

                break;

            case 'conversation':

                $model = new ConversationModel;
                $rowID = $model->save([
                    'Attributes'        => [
                        'StubLocale'        => $activeLocale,
                        'StubContentID'     => $contentID,
                        'StubContentTag'    => $content['tag']
                    ]
                ]);

                if ($rowID) {
                    $receipt = $this->createReceipt($content, $rowID);
                } else {
                    Logger::event("stubcontent-insertfailed", "Failed to insert {type} ({content}): {error}", [
                        'type' => $content['type'],
                        'content' => $contentID,
                        'error' => print_r($model->validationResults(), true)
                    ]);
                }
                break;
        }

        if (!$rowID) {
            trace("Failed to insert stub content");
            if ($model) {
                trace($model->validationResults());
            }
        }
    }

    /**
     * Apply locale updates to a record
     *
     * @param array $content
     * @param array $record
     */
    public function updateContent($content, $record) {

        $activeLocale = c('Garden.Locale');
        setvalr('Attributes.StubLocale', $record['row'], $activeLocale);

        switch ($content['type']) {
            case 'user':
                $model = new UserModel;

                // Nothing to update except locale

                $model->save($record['row'], [
                    'ValidateEmail' => false,
                    'NoConfirmEmail' => true
                ]);
                break;

            case 'discussion':
                $model = new DiscussionModel;

                // Get author
                $authorTag = $content['author'];
                $authorRecord = $this->getRecord('user', $authorTag);

                // Build translation tags
                $translateName = "{$content['tag']}.title";
                $translateBody = "{$content['tag']}.body";

                // Apply translations
                $record['row']['Name'] = t($translateName, $content['title']);
                $record['row']['Body'] = formatString(t($translateBody, $content['body']),[
                    'author' => $authorRecord['row']
                ]);

                // Save
                $model->save($record['row']);
                break;

            case 'comment':
                $model = new CommentModel;

                // Get author
                $authorTag = $content['author'];
                $authorRecord = $this->getRecord('user', $authorTag);

                // Get parent
                $parentTag = $content['parent'];
                $parentRecord = $this->getRecord('discussion', $parentTag);

                // Get parent author
                $parentAuthor = Gdn::userModel()->getID($parentRecord['row']['InsertUserID'], DATASET_TYPE_ARRAY);

                // Build translation tag
                $translateBody = "{$content['tag']}.body";

                // Apply translation
                $record['row']['Body'] = formatString(t($translateBody, $content['body']), [
                    'author' => $authorRecord['row'],
                    'parent' => $parentAuthor
                ]);

                // Save
                $model->save($record['row']);
                break;

            case 'conversation':
                $model = new ConversationModel;

                // Get author
                $authorTag = $content['author'];
                $authorRecord = $this->getRecord('user', $authorTag);

                // Build translation tag
                $translateBody = "{$content['tag']}.body";

                // Apply translation
                $record['row']['Body'] = formatString(t($translateBody, $content['body']),[
                    'author' => $authorRecord['row']
                ]);

                // Save
                $model->save($record['row']);
                break;
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
            if (array_key_exists('Attributes', $row) && is_string($row['Attributes'])) {
                $row['Attributes'] = dbdecode($row['Attributes']);
            }
            $record['row'] = $row;
        }

        return $record;
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
     * Add or update stub content on spawn and update
     *
     *
     */
    public function structure() {
        $this->processStubContent();
    }

}
