<?php
/**
 * Adds 301 redirects for Vanilla from common forum platforms.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class RedirectorPlugin
 */
class RedirectorPlugin extends Gdn_Plugin {
    /**
     *
     * @var array
     */
    public static $Files = [
        'archive' => [__CLASS__, 'showthreadFilter'],
        'category.jspa' => [  // jive 4 category
            'categoryID' => 'CategoryID',
        ],
        'categories.aspx' => [ // Fusetalk
            'catid' => 'CategoryID',
        ],
        'index.php' => [ // smf
            'board' => [
                'CategoryID',
                'Filter' => [__CLASS__, 'smfOffset']
            ],
            'topic' => [
                'DiscussionID',
                'Filter' => [__CLASS__, 'smfOffset'],
            ],
            'action' => [
                '_',
                'Filter' => [__CLASS__, 'smfAction'],
            ],
        ],
        'forum' => [__CLASS__, 'forumFilter'],
        'forum.jspa' => [ // jive 4; forums imported as tags
            'forumID' => 'TagID',
            'start' => 'Offset'
        ],
        'forumdisplay.php' => [__CLASS__, 'forumDisplayFilter'], // vBulletin category
        'forumindex.jspa' => [ // jive 4 category
             'categoryID' => 'CategoryID',
        ],
        'forums' => [ // xenforo cateogry
            '_arg0' => [
                'CategoryID',
                'Filter' => [__CLASS__, 'xenforoID'],
            ],
            '_arg1' => [
                'Page',
                'Filter' => [__CLASS__, 'getNumber']
            ],
        ],
        'member.php' => [ // vBulletin user
            'u' => 'UserID',
            '_arg0' => [
                'UserID',
                'Filter' => [__CLASS__, 'removeID'],
            ],
        ],
        'memberlist.php' => [ // phpBB user
            'u' => 'UserID',
        ],
        'members' => [ // xenforo profile
            '_arg0' => [
                'UserID',
                'Filter' => [__CLASS__, 'xenforoID'],
            ],
        ],
        'messageview.aspx' => [ // Fusetalk
            'threadid' => 'DiscussionID',
        ],
        'thread.jspa' => [ //jive 4 comment/discussion
            'threadID' => 'DiscussionID',
        ],
        'post' => [ // punbb comment
            '_arg0' => 'CommentID',
        ],
        'profile.jspa' => [ //jive4 profile
            'userID' => 'UserID',
        ],
        'showpost.php' => [__CLASS__, 'showpostFilter'], // vBulletin comment
        'showthread.php' => [__CLASS__, 'showthreadFilter'], // vBulletin discussion
        'threads' => [ // xenforo discussion
            '_arg0' => [
                'DiscussionID',
                'Filter' => [__CLASS__, 'xenforoID'],
            ],
            '_arg1' => [
                'Page',
                'Filter' => [__CLASS__, 'getNumber'],
            ],
        ],
        't5' => [__CLASS__, 't5Filter'], // Lithium
        'topic' => [__CLASS__, 'topicFilter'],
        'viewforum.php' => [ // phpBB category
            'f' => 'CategoryID',
            'start' => 'Offset',
        ],
        'viewtopic.php' => [ // phpBB discussion/comment
            't' => 'DiscussionID',
            'p' => 'CommentID',
            'start' => 'Offset',
        ],
        'topics' => [ // get satisfaction discussion
            '_arg0' => 'DiscussionID', // this should be a url slug
        ],
    ];

    /**
     *
     */
    public function gdn_Dispatcher_NotFound_Handler() {
        $path = Gdn::request()->path();
        $get = Gdn::request()->get();
        /**
         * There may be two incoming p URL parameters.  If that is the case, we need to compensate for it.  This is done
         * by manually parsing the server's QUERY_STRING variable, if available.
         */
        $queryString = Gdn::request()->getValueFrom('server', 'QUERY_STRING', false);
        trace(['QUERY_STRING' => $queryString], 'Server Variables');
        if ($queryString && preg_match('/(^|&)p\=\/?(showpost\.php|showthread\.php|viewtopic\.php)/i', $queryString)) {
            // Check for multiple values of p in our URL parameters
            if ($queryString && preg_match_all('/(^|\?|&)p\=(?P<val>[^&]+)/', $queryString, $queryParameters) > 1) {
                trace($queryParameters['val'], 'p Values');
                // Assume the first p is Vanilla's path
                $path = trim($queryParameters['val'][0], '/');
                // The second p is used for our redirects
                $get['p'] = $queryParameters['val'][1];
            }
        }

        trace(['Path' => $path, 'Get' => $get], 'Input');

        // Figure out the filename.
        $parts = explode('/', $path);
        $after = [];
        $filename = '';
        while(count($parts) > 0) {
            $v = array_pop($parts);
            if (preg_match('`.*\.php`', $v)) {
                $filename = $v;
                break;
            }

            array_unshift($after, $v);
        }
        if ($filename == 'index.php') {
            // Some site have an index.php?the/path.
            $tryPath = val(0, array_keys($get));
            if (!$get[$tryPath]) {
                $after = array_merge(explode('/', $tryPath));
                unset($get[$tryPath]);
                $filename = '';
            } elseif (preg_match('#archive/index\.php$#', $path) === 1) { // vBulletin archive
                $filename = 'archive';
            }
        }
        if (!$filename) {
            // There was no filename, so we can try the first folder as the filename.
            while (count($after) > 0) {
                $filename = array_shift($after);
                if (isset(self::$Files[$filename]))
                    break;
            }
        }

        // Add the after parts to the array.
        $i = 0;
        foreach ($after as $arg) {
            $get["_arg$i"] = $arg;
            $i++;
        }

        $url = $this->filenameRedirect($filename, $get);

        if ($url) {
            if (debug()) {
                trace($url, 'Redirect found');
            } else {
                redirectTo($url, 301);
            }
        }
    }

    /**
     *
     *
     * @param $Filename
     * @param $Get
     * @return bool|string
     */
    public function filenameRedirect($Filename, $Get) {
        trace(['Filename' => $Filename, 'Get' => $Get], 'Testing');
        $Filename = strtolower($Filename);
        array_change_key_case($Get);

        if (!isset(self::$Files[$Filename]))
            return false;

        $Row = self::$Files[$Filename];

        if (is_callable($Row)) {
            // Use a callback to determine the translation.
            $Row = call_user_func_array($Row, [&$Get]);
        }
        trace($Get, 'New Get');

        // Translate all of the get parameters into new parameters.
        $Vars = [];
        foreach ($Get as $Key => $Value) {
            if (!isset($Row[$Key]))
                continue;

            $Opts = (array)$Row[$Key];

            if (isset($Opts['Filter'])) {
                // Call the filter function to change the value.
                $R = call_user_func($Opts['Filter'], $Value, $Opts[0]);
                if (is_array($R)) {
                    if (isset($R[0])) {
                        // The filter can change the column name too.
                        $Opts[0] = $R[0];
                        $Value = $R[1];
                    } else {
                        // The filter can return return other variables too.
                        $Vars = array_merge($Vars, $R);
                        $Value = null;
                    }
                } else {
                    $Value = $R;
                }
            }

            if ($Value !== null)
                $Vars[$Opts[0]] = $Value;
        }
        trace($Vars, 'Translated Arguments');
        // Now let's see what kind of record we have.
        // We'll check the various primary keys in order of importance.
        $Result = false;

        if (isset($Vars['CommentID'])) {
            trace("Looking up comment {$Vars['CommentID']}.");
            $CommentModel = new CommentModel();
            // If a legacy slug is provided (assigned during a merge), attempt to lookup the comment using it
            if (isset($Get['legacy']) && Gdn::structure()->table('Comment')->columnExists('ForeignID')) {
                $Comment = $CommentModel->getWhere(['ForeignID' => $Vars['CommentID']])->firstRow();

            } else {
                $Comment = $CommentModel->getID($Vars['CommentID']);
            }
            if ($Comment) {
                $Result = commentUrl($Comment, '//');
            } else {
                // vBulletin, defaulting to discussions (foreign ID) when showthread.php?p=xxxx returns no comment
                $Vars['DiscussionID'] = $Vars['CommentID'];
                unset($Vars['CommentID']);
                $Get['legacy'] = true;
            }
        }
        // Splitting the if statement to default to discussions (foreign ID) when showthread.php?p=xxxx returns no comment
        if (isset($Vars['DiscussionID'])) {
            trace("Looking up discussion {$Vars['DiscussionID']}.");
            $DiscussionModel = new DiscussionModel();
            $DiscussionID = $Vars['DiscussionID'];
            $Discussion = false;

            if (is_numeric($DiscussionID)) {
                // If a legacy slug is provided (assigned during a merge), attempt to lookup the discussion using it
                if (isset($Get['legacy']) && Gdn::structure()->table('Discussion')->columnExists('ForeignID') && $Filename !== 't5') {
                    $Discussion = $DiscussionModel->getWhere(['ForeignID' => $DiscussionID])->firstRow();
                } else {
                    $Discussion = $DiscussionModel->getID($Vars['DiscussionID']);
                }
            } else {
                // This is a slug style discussion ID. Let's see if there is a UrlCode column in the discussion table.
                $DiscussionModel->defineSchema();
                if ($DiscussionModel->Schema->fieldExists('Discussion', 'UrlCode')) {
                    $Discussion = $DiscussionModel->getWhere(['UrlCode' => $DiscussionID])->firstRow();
                }
            }

            if ($Discussion) {
                $Result = discussionUrl($Discussion, self::pageNumber($Vars, 'Vanilla.Comments.PerPage'), '//');
            }
        } elseif (isset($Vars['UserID'])) {
            trace("Looking up user {$Vars['UserID']}.");

            $User = Gdn::userModel()->getID($Vars['UserID']);
            if ($User) {
                $Result = url(userUrl($User), '//');
            }
        } elseif (isset($Vars['TagID'])) {
            $Tag = TagModel::instance()->getID($Vars['TagID']);
            if ($Tag) {
                 $Result = tagUrl($Tag, self::pageNumber($Vars, 'Vanilla.Discussions.PerPage'), '//');
            }
        } elseif (isset($Vars['CategoryID'])) {
            trace("Looking up category {$Vars['CategoryID']}.");

            // If a legacy slug is provided (assigned during a merge), attempt to lookup the category ID based on it
            if (isset($Get['legacy']) && Gdn::structure()->table('Category')->columnExists('ForeignID')) {
                $CategoryModel = new CategoryModel();
                $Category = $CategoryModel->getWhere(['ForeignID' => $Get['legacy'] . '-' . $Vars['CategoryID']])->firstRow();
            } else {
                $Category = CategoryModel::categories($Vars['CategoryID']);
            }
            if ($Category) {
                $Result = categoryUrl($Category, self::pageNumber($Vars, 'Vanilla.Discussions.PerPage'), '//');
            }
        } elseif (isset($Vars['CategoryCode'])) {
            trace("Looking up category {$Vars['CategoryCode']}.");

            $category = CategoryModel::instance()->getByCode($Vars['CategoryCode']);
            if ($category) {
                $pageNumber = self::pageNumber($Vars, 'Vanilla.Discussions.PerPage');
                if ($pageNumber > 1) {
                    $pageParam = '?Page='.$pageNumber;
                } else {
                    $pageParam = null;
                }
                $Result = categoryUrl($category, '', '//').$pageParam;
            }
        }

        return $Result;
    }

    /**
     *
     *
     * @param $get
     * @return array
     */
    public static function forumFilter(&$get) {
        if (val('_arg2', $get) == 'page') {
            // This is a punbb style forum.
            return [
                '_arg0' => 'CategoryID',
                '_arg3' => 'Page',
            ];
        } elseif (val('_arg1', $get) == 'page') {
            // This is a bbPress style forum.
            return [
                '_arg0' => 'CategoryID',
                '_arg2' => 'Page',
            ];
        } else {
            // This is an ipb style topic.
            return [
                '_arg0' => [
                    'CategoryID',
                    'Filter' => [__CLASS__, 'removeID'],
                ],
                '_arg1' => [
                    'Page',
                    'Filter' => [__CLASS__, 'IPBPageNumber'],
                ],
            ];
        }
    }

    /**
     * Filter parameters properly.
     *
     * @param array $get Request parameters
     * @return array
     */
    public static function t5Filter(&$get) {
        $result = false;

        if (val('_arg0', $get) == 'user' && val('_arg2', $get) == 'user-id') {
            $result = [
                '_arg3' => [
                    'UserID'
                ],
            ];

            if (val('_arg3', $get) == 'page') {
                $result['_arg4'] = 'Page';
            }
        } elseif (in_array(val('_arg1', $get), ['bd-p', 'ct-p' , 'bg-p'])) { // Board = Category
            $result = [
                '_arg2' => [
                    'CategoryCode',
                    'Filter' => [__CLASS__, 'lithiumCategoryCodeFilter']
                ],
            ];

            if (val('_arg3', $get) == 'page') {
                $result['_arg4'] = 'Page';
            }
        } elseif (val('_arg2', $get) == 'm-p') { // Message => Comment
            $result = [
                '_arg3' => 'CommentID',
            ];
        } elseif (val('_arg2', $get) == 'ta-p') { // Thread = Discussion
            $result = [
                '_arg3' => 'DiscussionID',
            ];
        } elseif (val('_arg2', $get) == 'td-p') { // Thread = Discussion
            $result = [
                '_arg3' => 'DiscussionID',
            ];

            if (val('_arg4', $get) == 'page') {
                $result['_arg5'] = 'Page';
            }
        }

        return $result;
    }

    /**
     * Filter vBulletin category requests, specifically to handle "friendly URLs".
     *
     * @param $get Request parameters
     *
     * @return array Mapping of vB parameters
     */
    public static function forumDisplayFilter(&$get) {
        self::vbFriendlyUrlID($get, 'f');

        return [
            'f' => 'CategoryID',
            'page' => 'Page',
            '_arg0' => [
                'CategoryID',
                'Filter' => [__CLASS__, 'removeID']
            ],
            '_arg1' => [
                'Page',
                'Filter' => [__CLASS__, 'getNumber']
            ],
        ];
    }

    /**
     *
     *
     * @param $value
     * @return null
     */
    public static function getNumber($value) {
        if (preg_match('`(\d+)`', $value, $matches))
            return $matches[1];
        return null;
    }

    /**
     *
     *
     * @param $value
     * @return array|null
     */
    public static function iPBPageNumber($value) {
        if (preg_match('`page__st__(\d+)`i', $value, $matches))
            return ['Offset', $matches[1]];
        return self::getNumber($value);
    }

    /**
     * Return the page number from the given variables that may have an offset or a page.
     *
     * @param array $vars The variables that should contain an Offset or Page key.
     * @param int|string $pageSize The pagesize or the config key of the pagesize.
     * @return int
     */
    public static function pageNumber($vars, $pageSize) {
        if (isset($vars['Page']))
            return $vars['Page'];
        if (isset($vars['Offset'])) {
            if (is_numeric($pageSize))
                return pageNumber($vars['Offset'], $pageSize, false, Gdn::session()->isValid());
            else
                return pageNumber($vars['Offset'], c($pageSize, 30), false, Gdn::session()->isValid());
        }
        return 1;
    }

    /**
     *
     *
     * @param $value
     * @return null
     */
    public static function removeID($value) {
        if (preg_match('`^(\d+)`', $value, $matches))
            return $matches[1];
        return null;
    }

    /**
     * Filter vBulletin comment requests, specifically to handle "friendly URLs".
     *
     * @param $get Request parameters
     *
     * @return array Mapping of vB parameters
     */
    public static function showpostFilter(&$get) {
        self::vbFriendlyUrlID($get, 'p');

        return [
            'p' => 'CommentID'
        ];

    }

    /**
     * Filter vBulletin discussion requests, specifically to handle "friendly URLs".
     *
     * @param $get Request parameters
     *
     * @return array Mapping of vB parameters
     */
    public static function showthreadFilter(&$get) {
        $data = [
            'p' => 'CommentID',
            'page' => 'Page',
            '_arg0' => [
                'DiscussionID',
                'Filter' => [__CLASS__, 'removeID']
            ],
            '_arg1' => [
                'Page',
                'Filter' => [__CLASS__, 'getNumber']
            ]
        ];

        if (isset($get['t']) || !isset($get['p'])) {
            $data['t'] = [
                'DiscussionID',
                'Filter' => [__CLASS__, 'removeID']
            ];
            self::vbFriendlyUrlID($get, 't');
        }

        return $data;

    }

    /**
     *
     *
     * @param $value
     * @return array
     */
    public static function smfAction($value) {
        $result = null;

        if (preg_match('`(\w+);(\w+)=(\d+)`', $value, $m)) {
            if (strtolower($m[1]) === 'profile') {
                $result = ['UserID', $m[3]];
            }
        }

        return $result;
    }

    /**
     *
     *
     * @param $value
     * @param $key
     * @return array
     */
    public static function smfOffset($value, $key) {
        $result = null;

        if (preg_match('/(\d+)\.(\d+)/', $value, $m)) {
            $result = [$key => $m[1], 'Offset' => $m[2]];
        } elseif (preg_match('/\d+\.msg(\d+)/', $value, $m)) {
            $result = ['CommentID' => $m[1]];
        }

        return $result;
    }

    /**
     *
     *
     * @param $get
     * @return array
     */
    public static function topicFilter(&$get) {
        if (val('_arg2', $get) == 'page') {
            // This is a punbb style topic.
            return [
                '_arg0' => 'DiscussionID',
                '_arg3' => 'Page',
            ];
        } elseif (val('_arg1', $get) == 'page') {
            // This is a bbPress style topc.
            return [
                '_arg0' => 'DiscussionID',
                '_arg2' => 'Page'
            ];
        } else {
            // This is an ipb style topic.
            return [
                'p' => 'CommentID',
                '_arg0' => [
                    'DiscussionID',
                    'Filter' => [__CLASS__, 'removeID'],
                ],
                '_arg1' => [
                    'Page',
                    'Filter' => [__CLASS__, 'IPBPageNumber'],
                ],
            ];
        }
    }

    /**
     * Attempt to retrieve record ID from request parameters, if target parameter isn't already populated.
     *
     * @param $get Request parameters
     * @param string $targetParam Name of the request parameter the record value should be stored in
     *
     * @return bool True if value saved, False if not (including if value was already set in target parameter)
     */
    private static function vbFriendlyUrlID(&$get, $targetParam) {
        /**
         * vBulletin 4 added "friendly URLs" that don't pass IDs as a name-value pair.  We need to extract the ID from
         * this format, if we don't already have it.
         * Ex: domain.com/showthread.php?0001-example-thread
         */
        if (!empty($get) && !isset($get[$targetParam])) {
            /**
             * The thread ID should be the very first item in the query string.  PHP interprets these identifiers as keys
             * without values.  We need to extract the first key and see if it's a match for the format.
             */
            $friendlyURLID = array_shift(array_keys($get));
            if (preg_match('/^(?P<RecordID>\d+)(-[^\/]+)?(\/page(?P<Page>\d+))?/', $friendlyURLID, $friendlyURLParts)) {
                // Seems like we have a match.  Assign it as the value of t in our query string.
                $get[$targetParam] = $friendlyURLParts['RecordID'];

                if (!empty($friendlyURLParts['Page'])) {
                    $get['page'] = $friendlyURLParts['Page'];
                }

                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param $value
     * @return string
     */
    public static function xenforoID($value) {
        if (preg_match('/(\d+)$/', $value, $matches)) {
            $value = $matches[1];
        }

        return $value;
    }

    /**
     * Convert category code from lithium to vanilla.
     *
     * @param string $categoryCode
     * @return string
     */
    public static function lithiumCategoryCodeFilter($categoryCode) {
        return str_replace('_', '-', $categoryCode);
    }
}
