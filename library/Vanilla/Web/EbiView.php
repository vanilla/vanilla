<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web;

use Ebi\Ebi;
use Garden\EventManager;
use Garden\Web\Data;
use Garden\Web\Dispatcher;
use Garden\Web\Exception\HttpException;
use Garden\Web\RequestInterface;
use Garden\Web\ViewInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\EbiTemplateLoader;
use Vanilla\InternalRequest;
use Vanilla\PluralizationTrait;

class EbiView implements ViewInterface {
    use PluralizationTrait;

    /**
     * @var Ebi
     */
    private $ebi;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    private $ids = [];

    public function __construct(Ebi $ebi,
        \Gdn_Session $session,
        \Gdn_Locale $locale,
        AddonManager $addonManager,
        \UserModel $userModel,
        RequestInterface $request,
        Dispatcher $dispatcher
    ) {
        $this->ebi = $ebi;
        $this->dispatcher = $dispatcher;
        $userFunction = $this->makeUserFunction($userModel);

        // Add meta information.
        $ebi->setMeta('user', $userFunction($session->UserID));

        $locale = [
            'key' => $locale->current(),
            'htmlLanguage' => str_replace('_', '-', $locale->language(true))
        ];
        $ebi->setMeta('locale', $locale);
        $ebi->setMeta('device', ['type' => userAgentType(), 'mobile' => isMobile()]);
        $ebi->setMeta('request', ['query' => $request->getQuery()]);
        $ebi->setMeta('theme', $this->getJsonData('theme'));

        // Add custom components.
        $ebi->defineComponent('asset', function ($props) use ($ebi) {
            if ($controller = $ebi->getMeta('.controller')) {
                /* @var \Gdn_Controller $controller */
                $controller->renderAsset($props['name']);
            }
        });
        $ebi->defineComponent('x-script', function ($props, $children = []) {
            echo '<script>';
            if (!empty($children[0])) {
                call_user_func($children[0], $props);
            }
            echo '</script>';
        });
        $ebi->defineComponent('page', function ($props) {
            if (empty(props['path'])) {
                throw new \InvalidArgumentException("Missing required property 'path' in page component.", 400);
            }
            $props += ['query' => []];

            $this->writePage($props['path'], $props['query']);
        });

        // Define a simple component not found component to help troubleshoot.
        $ebi->defineComponent('@component-not-found', function ($props) {
            $this->echoConsoleLog('error', 'Ebi component "%s" not found.', $props['component']);
        });

        // Define a simple component exception.
        $ebi->defineComponent('@exception', function ($props) {
            $this->echoConsoleLog('Ebi exception in component %s. %s', $props['component'], $props['message']);
        });

        // Add custom functions.
        $fn = function ($url, $withDomain = false) use ($addonManager) {
            if (preg_match('`^(~[^/]+)(.*)$`', $url, $m)) {
                $addonKey = ltrim($m[1], '~');
                $path = '/'.ltrim($m[2], '/');

                if ($addonKey === 'asset') {
                    return asset($path, $withDomain);
                } elseif ($addonKey === 'url') {
                    return url($path, $withDomain);
                } elseif ($addonKey === 'root') {
                    return \Gdn::request()->urlDomain(true).$path;
                }
                $addon = $addonManager->lookupAddon($addonKey);
                if (!$addon) {
                    $addon = $addonManager->lookupTheme($addonKey);
                }

                if ($addon) {
                    return asset($addon->path($path, Addon::PATH_ADDON), $withDomain);
                }
            } else {
                return asset($url, $withDomain);
            }

            return '#not-found';
        };
        $ebi->defineFunction('api', $this->makeApiFunction($dispatcher));
        $ebi->defineFunction('assetUrl', $fn);
        $ebi->defineFunction('bodyImage', [$this, 'bodyImage']);
        $ebi->defineFunction('category', function ($category) {
            if (is_numeric($category)) {
                $category = \CategoryModel::categories($category);
            }
            $result = arrayTranslate($category, [
                'CategoryID' => 'categoryID',
                'Name' => 'name',
                'Description' => 'description',
            ]);

            $result['url'] = url($category['Url'], true);
            return $result;
        });
        $ebi->defineFunction('categoryUrl');
        $ebi->defineFunction('commentUrl');
        $ebi->defineFunction('discussionUrl');
        $ebi->defineFunction('expandData', [$this, 'expandData']);
        $ebi->defineFunction('formatBigNumber', [\Gdn_Format::class, 'bigNumber']);
        $ebi->defineFunction('formatHumanDate', [\Gdn_Format::class, 'date']);
        $ebi->defineFunction('formatPlainText', [\Gdn_Format::class, 'plainText']);
        $ebi->defineFunction('formatSlug', [\Gdn_Format::class, 'url']);
        $ebi->defineFunction('generateNumberedClass', [$this, 'generateNumberedClass']);
        $ebi->defineFunction('getJsonData', [$this, 'getJsonData']);
        $ebi->defineFunction('getActiveKey', [$this, 'getActiveKey']);
        $ebi->defineFunction('getData', [$this, 'getData']);
        $ebi->defineFunction('id', function ($id, $prefix = true) {
            return $this->idAttribute($id, $prefix);
        });
        $ebi->defineFunction('jsonEncode', function ($v) {
            jsonFilter($v);
            return json_encode($v, JSON_PRETTY_PRINT);
        });
        $ebi->defineFunction('longestCharacterCount', [$this, 'longestCharacterCount']);
        $ebi->defineFunction('normalizeCategoryTree', [$this, 'normalizeCategoryTree']);
        $ebi->defineFunction('meta', function ($name = null, $default = null) use ($ebi) {
            if ($name) {
                return $ebi->getMeta($name, $default);
            } else {
                return $ebi->getMetaArray();
            }
        });
        $ebi->defineFunction('pagerData', [$this, 'pagerData']);
        $ebi->defineFunction('plural');
        $ebi->defineFunction('pluralText', [$this, 'pluralText']);
        $ebi->defineFunction('queryData', [$this, 'queryData']);
        $ebi->defineFunction('registerUrl');
        $ebi->defineFunction('signInUrl');
        $ebi->defineFunction('signOutUrl');
        $ebi->defineFunction('t');
        $ebi->defineFunction('user', $this->makeUserFunction($userModel));
        $ebi->defineFunction('tileClasses', [$this, 'tileClasses']);
        $ebi->defineFunction('url');

        // Add custom attribute filters.
        $ebi->defineFunction('@script:src', $fn);
        $ebi->defineFunction('@link:href', $fn);
        $ebi->defineFunction('@img:src', $fn);
        $ebi->defineFunction('@a:href', 'url');
        $ebi->defineFunction('@form:action', 'url');
        $ebi->defineFunction('@id', [$this, 'idAttribute']);
    }

    /**
     * Write out a javascript console.log script to output debugging information in the browser.
     *
     * The arguments passed to this method get JSON encoded and output as arguments of the resulting console.log call.
     * If you pass "error", "log", or "info" as the first argument here then the respective console method will be called
     * instead.
     *
     * @param array $args The console.log arguments.
     */
    private function echoConsoleLog(...$args) {
        if (in_array(reset($args), ['error', 'log', 'info'])) {
            $method = array_shift($args);
        } else {
            $method = 'log';
        }

        $jsonArgs = array_map('json_encode', $args);
        $argsStr = implode(', ', $jsonArgs);

        echo "\n<script>console.$method($argsStr);</script>\n";
    }


    /**
     * Generate numbered css class in a range from 1 to X based on the length of a string
     *
     * @param string $input string used to generate class
     * @param int $max highest value possible
     * @param string $prefix class prefix
     * @return string class
     */

    public function generateNumberedClass($input, $max=10, $prefix="secondaryColor-") {
        $colorIndex = strlen($input) % 10 + 1;
        return "secondaryColor-" . $colorIndex;
    }

    private function makeApiFunction(Dispatcher $dispatcher) {
        return function($path, $query = []) use ($dispatcher) {
            $request = new InternalRequest('GET', "/api/v2/".ltrim($path, '/'), (array)$query + ['expand' => true]);

            $response = $dispatcher->dispatch($request);

            if (substr($response->getStatus(), 0, 1) !== '2') {
                throw HttpException::createFromStatus($response->getStatus(), $response->getDataItem('message'));
            }

            return $response->getData();
        };
    }

    public function writePage($path, $query = []) {
        $request = new InternalRequest('GET', $path, (array)$query);
        $response = $this->dispatcher->dispatch($request);
        $response->setHeader('Content-Type', 'text/html');
        $this->dispatcher->render($request, $response);
    }



    /**
     * Get the data that a pager component needs to build a pager.
     *
     * @param array $paging An array of paging options.
     *
     * - page: The current page.
     * - pageCount: The total number of pages.
     * - urlFormat: Required. A URL format where "%s" will be replaced with a page number.
     * - more: Whether or not there are more records.
     * @param int $maxPages The maximum number of pages to show.
     */
    public function pagerData($paging, $maxPages = 5) {
        $paging += [
            'page' => 0,
            'pageCount' => null,
            'urlFormat' => '?page=%s',
            'more' => true
        ];
        $page = (int)$paging['page'];
        $pageCount = $paging['pageCount'];
        $hasMore = $page && $paging['more'] && (!$pageCount || $page < $pageCount);
        $urlFormat = $paging['urlFormat'];
        if ($page) {
            $paging['page'] = $page;
        }

        if ($page > 1) {
            $paging['previous'] = [
                'type' => 'previous',
                'url' => sprintf($urlFormat, $page - 1)
            ];
        }
        if ($hasMore) {
            $paging['next'] = [
                'type' => 'next',
                'url' => sprintf($urlFormat, $page + 1)
            ];
        }

        if ($pageCount) {
            if ($pageCount <= $maxPages) {
                $groups = [[1, $pageCount]];
            } else {
                $groups = [[1, 1]];
                $basis = $paging['page'] ?: 1;

                if ($basis + $maxPages > $pageCount) {
                    $groups[] = [$pageCount - $maxPages + 1, $pageCount];
                } else {
                    $groups[] = [$basis + 1, $basis + $maxPages - 2];
                    $groups[] = [$pageCount, $pageCount];
                }
            }

            $pages = [];
            $last = 0;
            foreach ($groups as $group) {
                for ($i = $group[0]; $i <= $group[1]; $i++) {
                    if ($i > $last + 1) {
                        // Add an ellipsis between non-consecutive pages.
                        $pages[] = [
                            'type' => 'ellipsis'
                        ];
                    }
                    $pages[] = [
                        'type' => 'page',
                        'url' => sprintf($urlFormat, $i),
                        'page' => $i,
                        'current' => $i === $page
                    ];

                    $last = $i;
                }
            }
            $paging['pages'] = $pages;
        }
        return $paging;
    }


    /**
     * Get data for component
     *
     * @param array $data
     * @return mixed $key
     */
    public function getActiveKey($data) {
        $key = false;
        foreach ($data as $i => $item) {
            if ($item['active']) {
                $key = $i;
                break;
            }
        }
        return $key;
    }


    /**
     * Get data for component
     *
     * @param array $data
     * @param mixed $data
     * @return mixed The data
     */
    public function getData($data = [], $config = false, $page = false) {
        $processedData = $data;
        $dataSource = array_key_exists('dataSource', $data) ? $data['dataSource'] : (array_key_exists('dataSource', $config) ? $data['dataSource'] : false);
        if ($dataSource) {
            switch ($dataSource['source']) {
                case 'theme':
                    $processedData = $this->getJsonData($dataSource['source']);
                    if ($dataSource['field']) {
                        $processedData = $processedData[$dataSource['field']];
                    }
                    break;
                case 'api':
                    $query = $dataSource['query'] ?: [];
                    $processedData = $this->ebi->call('api', $dataSource['path'], $query);
                    break;
                case 'page':
                    $processedData = $page;
                    if ($dataSource['field']) {
                        $processedData = $processedData[$dataSource['field']];
                    }
                    break;
                case 'self':
                    $processedData = $dataSource['children'];
                    break;
            }
        } elseif (array_key_exists('field', $data)) {
            $processedData = $data['field'];
        }

        return $processedData;
    }

    /**
     * Expand data from a config source.
     *
     * This method returns the original data where every key that ends in **-source** will be queried with **queryData()**.
     *
     * @param array $data The data to expand.
     * @return array Returns the expanded data.
     */
    public function expandData($data = []) {
        $result = [];

        foreach ($data as $key => $value) {
            if (substr($key, -7) === '-source') {
                $result[substr($key, 0, -7)] = $this->queryData($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Query data for the template.
     *
     * Queries must define a **source** key that defines where to query the data from. Other keys specify the specific query.
     *
     * - **api**: Make an API call from **path** with **query**.
     * - **json**: Get data from a JSON file with **name**.
     * - **theme**: Get data from the theme.json file.
     * - **self**: Get data directly embedded in the query on the **data** key.
     *
     * @param array $query The query.
     */
    public function queryData($query = []) {
        switch ($query['source']) {
            case 'api':
                $query += ['path' => '', 'query' => []];
                $result = $this->ebi->call('api', $query['path'], $query['query']);
                break;
            case 'json':
            case 'theme':
                $query += ['name' => 'theme'];
                $result = $this->getJsonData($query['name']);

                if ($query['field']) {
                    $result = $result[$query['field']];
                }

                break;
            case 'self':
                $query += ['data' => []];
                $result = $query['data'];
                break;
            default:
                throw new \InvalidArgumentException("Invalid query source for queryData. ({$query['source']})", 400);
        }
        return $result;
    }




    /**
     * Get message based on count
     *
     * @param int $count number used in message
     * @param string $textEmpty message when count is 0
     * @param string $textSingular message when count is 1
     * @param string $textPlural message when count is 1
     * @return string the message
     */
    public function pluralText($count, $textEmpty, $textSingular, $textPlural) {
        if ($count === 0) {
            return $textEmpty;
        } else {
            return plural($count, $textSingular, $textPlural, number_format($count));
        }
    }

    /**
     * Get data from JSON file
     *
     * @param string $name The name of the file
     * @return mixed The data
     */
    public function getJsonData($name) {
        $loader = $this->ebi->getTemplateLoader();
        $themes = array_reverse($loader->getThemeChain());
        static $data = [];

        if (isset($data[$name])) {
            return $data[$name];
        }

        $result = [];
        foreach ($themes as $theme) {
            /* @var Addon $theme */
            $path = $theme->path("$name.json");
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);

                if (!empty($data) && is_array($data)) {
                    $result = arrayReplaceConfig($result, $data);
                }
            }
        }
        $data[$name] = $result;
        return $result;
    }


    /**
     * Get the longest character count from array (translated before calculation)
     *
     * @param array $words
     * @param int $offset
     * @return int $count
     */
    public function longestCharacterCount($words, $offset = 0) {
        $count = 0;

        if(is_array($words) && sizeof($words) > 0) {
            foreach ($words as $i => $value) {
                $length = strlen(t($value));
                if($length > $count) {
                    $count = $length;
                }
            }
        }

        return $count + $offset;
    }


    /**
     * A higher order function to get a user's information from an ID or user array.
     *
     * @param \UserModel $userModel The user model dependency.
     * @return \Closure Returns the user function.
     */
    private function makeUserFunction(\UserModel $userModel) {
        return function ($user) use ($userModel) {
            if (empty($user)) {
                return ['userID' => 0];
            }

            if (is_numeric($user)) {
                $fullUser = $userModel->getID($user, DATASET_TYPE_ARRAY);
            } else {
                $fullUser = $userModel->getID($user['userID'], DATASET_TYPE_ARRAY);
            }

            $result = arrayTranslate($fullUser, ['UserID' => 'userID', 'Name' => 'name']);
            if (!empty($fullUser['Banned'])) {
                $photo = c('Garden.BannedPhoto', '/resources/design/user-banned.svg');
            } elseif (!empty($fullUser['Punished'])) {
                $photo = c('Garden.JailedPhoto', '/resources/design/user-jailed.svg');
            } elseif (!empty($fullUser['Photo'])) {
                $photo = isUrl($fullUser['Photo']) ? $fullUser['Photo'] : \Gdn_Upload::url(changeBasename($fullUser['Photo'], 'n%s'));
            } else {
                $photo = \UserModel::getDefaultAvatarUrl($fullUser);
            }
            $result['photoUrl'] = asset($photo, true);
            $result['url'] = url(userUrl($fullUser), true);

            return $result;
        };
    }

    /**
     * Write the view to the output buffer.
     *
     * @param Data $data The data to render.
     */
    public function render(Data $data) {
        $template = $data->getMeta('template');
        $templatePath = $data->getMeta('templatePath');

        $metaBak = (array)$this->ebi->getMetaArray();
        $this->ebi->setMetaArray(array_replace($metaBak, $data->getMetaArray()));

        if (!empty($templatePath)) {
            // Controller render template paths instead of actual templates.
            if (empty($template)) {
                $template = basename($templatePath, '.html');
            }

            // See if we should custom compile the template.
//            if (preg_match('`^'.preg_quote(PATH_ROOT, '`').'/(.*)\.html$`i', $templatePath, $m)) {
//                $cacheKey = strtolower($m[1]);
//                if (!$this->ebi->cacheKeyExists($cacheKey)) {
//                    $this->ebi->compile($template, file_get_contents($templatePath), $cacheKey);
//                }

                /* @var EbiTemplateLoader $loader */
//                $loader = $this->ebi->getTemplateLoader();
//                $loader->
//
//                $template = "{$m[1]}:{$m[3]}";
//            }
            $this->ebi->write($template, $data->getData());
        } elseif (!empty($template)) {
            $this->ebi->write($template, $data->getData());
        } elseif (null !== $resource = $data->getMeta('resource')) {
            $template = $this->templateName($resource, $data->getMeta('action', ''));
            $this->ebi->write($template, $data->getData());
        } else {
            $newData = [
                'title' => 'No Template',
                'message' => 'There was not a template specified for the page.',
                'data' => $data->getJsonData()
            ];
            $this->ebi->write('error', $newData, []);
        }
        $this->ebi->setMetaArray($metaBak);
    }

    /**
     * Calculate the template name from the controller slug.
     *
     * @param string $resource The controller slug which is usually the name of the RESTful directory.
     * @param string $action The name of the class method (as opposed to the HTTP method).
     * @return string Returns a template name.
     */
    private function templateName($resource, $action) {
        $item = $this->singular(strtolower($resource));

        if (preg_match('`^get_?(.*)$`i', $action, $m)) {
            $action = strtolower($m[1]);
        }
        $action = str_replace('_', '-', $action);

        if ($action) {
            $template = "$item-$action-page";
        } else {
            $template = "$item-page";
        }
        return $template;
    }


    /**
     * Get class to add to tile to get the correct width.
     *
     * @param int $index The index of current tile.
     * @param int $count The total number of tiles.
     * @param int $maxColumns The number of columns in grid.
     * @return string class(es) for tile width
     */
    public function tileClasses($index, $count, $maxColumns = 3) {
        $index++;
        $remainder = $count % $maxColumns;
        $classes = [];
        $classPrefix = 'tile-1_';

        if ($count <= $maxColumns) { // Less than one row always takes up full width
            $classes[] = $classPrefix.$count;
        } else {
            $beforeLastRowIndex = $count - ($maxColumns + $remainder) + 1;

            if ($remainder === 0 || ($index < $beforeLastRowIndex)) {
                $classes[] = $classPrefix.$maxColumns;

                if ($index % $maxColumns === 1) {
                    $classes[] = 'isFirst';
                }

                if ($index % $maxColumns === 0) {
                    $classes[] = 'isLast';
                }

                if ($maxColumns > 2 && $maxColumns % 2 === 1 && $index == ceil($maxColumns / 2)) {
                    $classes[] = 'isMiddle';
                }

            } else {
                // Massage last 2 columns
                $lastTwoRowsCount = $maxColumns + $remainder;

                $beforeLastRowCount = ceil($lastTwoRowsCount / 2);
                $lastRowCount = floor($lastTwoRowsCount / 2);

                if ($lastTwoRowsCount === $count && $remainder > 1) {
                    $beforeLastRowCount = $maxColumns;
                    $lastRowCount = $remainder;
                }

                $lastRowIndex = $beforeLastRowIndex + $beforeLastRowCount;

                if ($index <= $count - $lastRowCount) {
                    $classes[] = $classPrefix.$beforeLastRowCount;

                    if ($index === $beforeLastRowIndex) {
                        $classes[] = 'isFirst';
                    }

                    if ($beforeLastRowCount % 2 === 1 && $index == $beforeLastRowIndex + floor($beforeLastRowCount / 2)) {
                        $classes[] = 'isMiddle';
                    }

                    if ($index == $beforeLastRowIndex + $beforeLastRowCount - 1) {
                        $classes[] = 'isLast';
                    }

                } else {
                    $classes[] = $classPrefix.$lastRowCount;

                    if ($index == $lastRowIndex) {
                        $classes[] = 'isFirst';
                    }

                    if ($lastRowCount % 2 === 1 && $index == $lastRowIndex + floor($lastRowCount / 2)) {
                        $classes[] = 'isMiddle';
                    }

                    if ($index == $count) {
                        $classes[] = 'isLast';
                    }
                }
            }
        }

        return implode(' ', $classes);
    }

    public function idAttribute($id, $px = false) {
        if ($id[0] === '@') {
            return substr($id, 1);
        } elseif (!isset($this->ids[$id])) {
            $this->ids[$id] = 0;
            return ($px ? '@' : '').$id;
        } else {
            $this->ids[$id]++;
            return ($px ? '@' : '').$id.$this->ids[$id];
        }
    }

    public function bodyImage($html) {
        $result = [];
        if (preg_match_all('/<[^>]+src="([^"]+)"[^>]*?>/', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // Skip emoji.
                if (preg_match('/class="[^"]*?\bemoji\b[^"]*"/', $match[0])) {
                    continue;
                }
                $src = $match[1];
                return $src;
            }
        }

        return $result;
    }

    /**
     * Normalize a category tree so that the first level of the tree is always headings or empty headings and group non-heading categories together.
     *
     * @param array $categories The categories to group.
     * @return array Returns an array of category headings and children.
     */
    public function normalizeCategoryTree($categories) {
        $result = [];

        $lastHeading = ['children' => []];
        foreach ($categories as $category) {
            if (!empty($category['displayAs']) && $category['displayAs'] === 'heading') {
                if (!empty($lastHeading['children'])) {
                    $result[] = $lastHeading;
                    $lastHeading = ['children' => []];
                }
                $result[] = $category;
            } else {
                $lastHeading['children'][] = $category;
            }
        }
        if (!empty($lastHeading['children'])) {
            $result[] = $lastHeading;
        }

        return $result;
    }
}
