<?php
/**
 * Tagging plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Tagging
 */

/**
 * Class TagModule
 */
class TagModule extends Gdn_Module {

    protected $_TagData;

    protected $ParentID;

    protected $ParentType;

    protected $CategorySearch;

    /**
     * @param string $sender
     */
    public function __construct($sender = '') {
        parent::__construct($sender);
        $this->_TagData = false;
        $this->ParentID = null;
        $this->ParentType = 'Global';
        $this->CategorySearch = c('Vanilla.Tagging.CategorySearch', false);
    }

    /**
     *
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        if ($name == 'Context') {
            $this->autoContext($value);
        }
    }

    /**
     *
     *
     * @param null $hint
     */
    protected function autoContext($hint = null) {
        // If we're already configured, don't auto configure
        if (!is_null($this->ParentID) && is_null($hint)) {
            return;
        }

        // If no hint was given, determine by environment
        if (is_null($hint)) {
            if (Gdn::controller() instanceof Gdn_Controller) {
                $discussionID = Gdn::controller()->data('Discussion.DiscussionID', null);
                $categoryID = Gdn::controller()->data('Category.CategoryID', null);

                if ($discussionID) {
                    $hint = 'Discussion';
                } elseif ($categoryID) {
                    $hint = 'Category';
                } else {
                    $hint = 'Global';
                }
            }
        }

        switch ($hint) {
            case 'Discussion':
                $this->ParentType = 'Discussion';
                $discussionID = Gdn::controller()->data('Discussion.DiscussionID');
                $this->ParentID = $discussionID;
                break;

            case 'Category':
                if ($this->CategorySearch) {
                    $this->ParentType = 'Category';
                    $categoryID = Gdn::controller()->data('Category.CategoryID');
                    $this->ParentID = $categoryID;
                }
                break;
        }

        if (!$this->ParentID) {
            $this->ParentID = 0;
            $this->ParentType = 'Global';
        }

    }

    /**
     *
     *
     * @throws Exception
     */
    public function getData() {
        $tagQuery = Gdn::sql();

        $this->autoContext();

        $tagCacheKey = "TagModule-{$this->ParentType}-{$this->ParentID}";
        switch ($this->ParentType) {
            case 'Discussion':
                $tags = TagModel::instance()->getDiscussionTags($this->ParentID, false);
                break;
            case 'Category':
                $tagQuery->join('TagDiscussion td', 't.TagID = td.TagID')
                    ->select('COUNT(DISTINCT td.TagID)', '', 'NumTags')
                    ->where('td.CategoryID', $this->ParentID)
                    ->where('t.Type', '') // Only show user generated tags
                    ->groupBy('td.TagID')
                    ->cache($tagCacheKey, 'get', [Gdn_Cache::FEATURE_EXPIRY => 120]);
                break;

            case 'Global':
                $tagCacheKey = 'TagModule-Global';
                $tagQuery->where('t.CountDiscussions >', 0, false)
                    ->where('t.Type', '') // Only show user generated tags
                    ->cache($tagCacheKey, 'get', [Gdn_Cache::FEATURE_EXPIRY => 120]);

                if ($this->CategorySearch) {
                    $tagQuery->where('t.CategoryID', '-1');
                }

                break;
        }

        if (isset($tags)) {
            $this->_TagData = new Gdn_DataSet($tags, DATASET_TYPE_ARRAY);
        } else {
            $this->_TagData = $tagQuery
                ->select('t.*')
                ->from('Tag t')
                ->where('t.Type', '') // Only show user generated tags
                ->orderBy('t.CountDiscussions', 'desc')
                ->limit(25)
                ->get();
        }

        $this->_TagData->datasetType(DATASET_TYPE_ARRAY);
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @return string
     */
    public function inlineDisplay() {
        if (!c('Tagging.Discussions.Enabled')) {
            return;
        }

        if (!$this->_TagData) {
            $this->getData();
        }

        if ($this->_TagData->numRows() == 0) {
            return '';
        }

        $string = '';
        ob_start();
        ?>
        <div class="InlineTags Meta">
            <?php echo t('Tagged'); ?>:
            <ul>
                <?php foreach ($this->_TagData->resultArray() as $tag) :
?>
                    <?php if ($tag['Name'] != '') :
?>
                        <li><?php
                            echo anchor(
                                htmlspecialchars(tagFullName($tag)),
                                tagUrl($tag, '', '/'),
                                ['class' => 'Tag_'.str_replace(' ', '_', $tag['Name'])]
                            );
                            ?></li>
                    <?php
endif; ?>
                <?php
endforeach; ?>
            </ul>
        </div>
        <?php
        $string = ob_get_clean();
        return $string;
    }

    /**
     * Render the module.
     *
     * @return string
     */
    public function toString() {
        if (!c('Tagging.Discussions.Enabled')) {
            return;
        }

        if (!$this->_TagData) {
            $this->getData();
        }

        if ($this->_TagData->numRows() == 0) {
            return '';
        }

        $string = '';
        ob_start();
        ?>
        <div class="Box Tags">
            <?php echo panelHeading(t($this->ParentID > 0 ? 'Tagged' : 'Popular Tags')); ?>
            <ul class="TagCloud">
                <?php foreach ($this->_TagData->result() as $tag) :
?>
                    <?php if ($tag['Name'] != '') :
?>
                        <li><?php
                            echo anchor(
                                htmlspecialchars(tagFullName($tag)).' '.wrap(number_format($tag['CountDiscussions']), 'span', ['class' => 'Count']),
                                tagUrl($tag, '', '/'),
                                ['class' => 'Tag_'.str_replace(' ', '_', $tag['Name'])]
                            );
                            ?></li>
                    <?php
endif; ?>
                <?php
endforeach; ?>
            </ul>
        </div>
        <?php
        $string = ob_get_clean();
        return $string;
    }
}
