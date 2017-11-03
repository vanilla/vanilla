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
use Garden\Web\RequestInterface;
use Garden\Web\ViewInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\EbiTemplateLoader;
use Vanilla\PluralizationTrait;

class EbiView implements ViewInterface {
    use PluralizationTrait;

    /**
     * @var Ebi
     */
    private $ebi;

    public function __construct(Ebi $ebi,
        \Gdn_Session $session,
        \Gdn_Locale $locale,
        AddonManager $addonManager,
        \UserModel $userModel,
        RequestInterface $request
    ) {
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
        $ebi->setMeta('theme', $this->getThemeConfig($ebi->getTemplateLoader()));

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
        $ebi->defineFunction('assetUrl', $fn);
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
        $ebi->defineFunction('formatBigNumber', [\Gdn_Format::class, 'bigNumber']);
        $ebi->defineFunction('formatSlug', [\Gdn_Format::class, 'url']);
        $ebi->defineFunction('meta', function ($name = null, $default = null) use ($ebi) {
            if ($name) {
                return $ebi->getMeta($name, $default);
            } else {
                return $ebi->getMetaArray();
            }
        });
        $ebi->defineFunction('jsonEncode', function ($v) {
            jsonFilter($v);
            return json_encode($v, JSON_PRETTY_PRINT);
        });
        $ebi->defineFunction('registerUrl');
        $ebi->defineFunction('signInUrl');
        $ebi->defineFunction('signOutUrl');
        $ebi->defineFunction('t');
        $ebi->defineFunction('user', $this->makeUserFunction($userModel));
        $ebi->defineFunction('url');

        // Add custom attribute filters.
        $ebi->defineFunction('@script:src', $fn);
        $ebi->defineFunction('@link:href', $fn);
        $ebi->defineFunction('@img:src', $fn);
        $ebi->defineFunction('@a:href', 'url');
        $ebi->defineFunction('@form:action', 'url');

        $this->ebi = $ebi;
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
     * Get the theme.json config file from the current theme and parent themes.
     *
     * @param EbiTemplateLoader $loader The template loader used to traverse the theme chain.
     * @return array Returns the theme config array.
     */
    private function getThemeConfig(EbiTemplateLoader $loader) {
        $themes = array_reverse($loader->getThemeChain());
        $result = [];
        foreach ($themes as $theme) {
            /* @var Addon $theme */
            $path = $theme->path('theme.json');
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);

                if (!empty($data) && is_array($data)) {
                    $result = arrayReplaceConfig($result, $data);
                }
            }
        }
        return $result;
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
                'data' => $data->getData()
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
}
