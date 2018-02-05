<?php

class WebScraper {

    /** Page info caching expiry (24 hours). */
    const CACHE_EXPIRY = 24 * 60 * 60;

    /** Valid image extensions. */
    const IMAGE_EXTENSIONS = ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff'];

    /** Default scrape type. */
    const TYPE_DEFAULT = 'site';

    /** Type for generic image URLs. */
    const TYPE_IMAGE = 'image';

    /** @var bool */
    private $disableFetch = false;

    /** @var array */
    private $types = [
        'getty' => ['domains' => ['embed.gettyimages.com']],
        'hitbox' => ['domains' => ['hitbox.tv']],
        'imgur' => ['domains' => ['i.imgur.com']],
        'instagram' => ['domains' => ['instagram.com', 'instagr.am']],
        'pinterest' => ['domains' => ['pinterest.com']],
        'soundcloud' => ['domains' => ['soundcloud.com']],
        'twitch' => ['domains' => ['twitch.tv']],
        'twitter' => ['domains' => ['twitter.com']],
        'vimeo' => ['domains' => ['vimeo.com']],
        'vine' => ['domains' => ['vine.co']],
        'wistia' => ['domains' => ['wistia.com', 'wi.st']],
        'youtube' => ['domains' => ['youtube.com', 'youtu.be']]
    ];

    /**
     * Fetch page info and normalize its keys.
     *
     * @param string $url Full target URL.
     * @return array
     * @throws Exception if there was an error encountered while getting the page's info.
     */
    private function fetchPageInfo($url) {
        $result = [
            'url' => $url,
            'name' => null,
            'body' => null,
            'photoUrl' => null
        ];

        if (!$this->disableFetch) {
            $pageInfo = fetchPageInfo($url);

            if ($pageInfo['Exception']) {
                throw new Exception($pageInfo['Exception']);
            }

            $result['name'] = $pageInfo['Title'] ?: null;
            $result['body'] = $pageInfo['Description'] ?: null;
            $result['photoUrl'] = !empty($pageInfo['Images']) ? reset($pageInfo['Images']) : null;
        }

        return $result;
    }

    /**
     * Get the status of the "disable fetch" flag.
     *
     * @return bool
     */
    public function getDisableFetch() {
        return $this->disableFetch;
    }

    /**
     * Get data about a page, including site-specific information, if available.
     *
     * @param string $url Full target URL.
     * @param bool $forceRefresh Should loading from the cache be skipped?
     * @return string|null
     * @throws Exception if errors encountered during processing.
     */
    public function getPageInfo($url, $forceRefresh = false) {
        $urlKey = md5($url);
        $cacheKey = "WebScraper.{$urlKey}";
        $result = null;

        if (!$forceRefresh) {
            $info = Gdn::cache()->get($cacheKey);

            if ($info === Gdn_Cache::CACHEOP_FAILURE) {
                unset($info);
            }
        }

        if (!isset($info)) {
            $type = $this->getTypeFromUrl($url);
            $info = $this->getInfoByType($type, $url);
        }

        if ($info) {
            Gdn::cache()->store($cacheKey, $info, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_EXPIRY]);
        }

        return $info;
    }

    /**
     * Given a URL, return its type. Use default if no specific type can be determined..
     *
     * @param string $url
     * @return string
     * @throws InvalidArgumentException if the URL is invalid.
     * @throws Exception if the type does not have a valid "domains" value.
     */
    private function getTypeFromUrl($url) {
        $urlDomain = parse_url($url, PHP_URL_HOST);
        if ($urlDomain === false) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        $types = $this->getTypes();
        $result = null;
        $testDomains = [];
        $urlDomainParts = explode('.', $urlDomain);
        while ($urlDomainParts) {
            $testDomains[] = implode('.', $urlDomainParts);
            array_shift($urlDomainParts);
        }

        foreach ($types as $type => $config) {
            if (!array_key_exists('domains', $config) || !is_array($config['domains'])) {
                throw new Exception('Invalid domains for type.');
            }
            foreach ($config['domains'] as $typeDomain) {
                foreach ($testDomains as $testDomain) {
                    if ($typeDomain == $testDomain) {
                        $result = $type;
                        break 3;
                    }
                }
            }
        }

        // No site-specific matches? Test if we're dealing with an image URL.
        if ($result === null) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path !== false) {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                if ($extension && in_array(strtolower($extension), self::IMAGE_EXTENSIONS)) {
                    $result = self::TYPE_IMAGE;
                }
            }
        }

        // Still nothing? Default type.
        if ($result === null) {
            $result = self::TYPE_DEFAULT;
        }

        return $result;
    }

    /**
     * Get all supported sites and their configurations.
     *
     * @return array
     */
    private function getTypes() {
        return $this->types;
    }

    /**
     * Get site-specific data from a page.
     *
     * @param string $type A supported site type.
     * @param string $url The full URL to the page.
     * @return array
     * @throws InvalidArgumentException if the type is invalid.
     * @throws Exception if unable to find a lookup function for the type.
     */
    private function getInfoByType($type, $url) {
        if ($type === self::TYPE_DEFAULT) {
            $data = $this->lookupDefault($url);
        } elseif ($type === self::TYPE_IMAGE) {
            $data = $this->lookupImage($url);
        } else {
            $types = $this->getTypes();
            if (!array_key_exists($type, $types)) {
                throw new InvalidArgumentException("Invalid type: {$type}");
            }

            $config = $types[$type];

            $lookup = array_key_exists('function', $config) ? $config['function'] : [$this, "lookup{$type}"];
            if (!is_callable($lookup)) {
                throw new Exception("Unable to call info lookup function for type: {$type}");
            }

            $data = call_user_func($lookup, $url);
        }

        $defaults = [
            'url' => $url,
            'type' => $type,
            'name' => null,
            'body' => null,
            'photoUrl' => null,
            'height' => null,
            'width' => null,
            'attributes' => []
        ];

        $result = array_merge($defaults, $data);
        return $result;
    }

    /**
     * Gather general information about a document..
     *
     * @param string $url
     * @return array
     */
    private function lookupDefault($url) {
        $result = $this->fetchPageInfo($url);
        return $result;
    }

    /**
     * Grab info from Getty Images.
     *
     * @param string $url
     * @return array
     */
    private function lookupGetty($url) {
        preg_match(
            '/https?:\/\/embed.gettyimages\.com\/(?<mediaID>[\w=?&;+-_]*)\/(?<width>[\d]*)\/(?<height>[\d]*)/i',
            $url,
            $matches
        );
        $mediaID = $matches['mediaID'] ?: null;
        $width = $matches['width'] ?: null;
        $height = $matches['height'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['width'] = $width;
        $data['height'] = $height;
        $data['attributes'] = ['mediaID' => $mediaID];

        return $data;
    }

    /**
     * Grab info from Smashcast.tv (formerly Hitbox.tv).
     *
     * @param string $url
     * @return array
     */
    private function lookupHitbox($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?hitbox\.tv\/(?<channelID>[\w]+)/i',
            $url,
            $matches
        );
        $channelID = $matches['channelID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['channelID' => $channelID];

        return $data;
    }

    /**
     * @param string $url
     * @return array
     */
    private function lookupImage($url) {
        $data = ['photoUrl' => $url];
        return $data;
    }

    /**
     * Grab info from an Imgur embed.
     * @param $url
     * @return array
     */
    private function lookupImgur($url) {
        preg_match(
            '/https?:\/\/i\.imgur\.com\/(?<mediaID>[a-z0-9]+)\.gifv/i',
            $url,
            $matches
        );
        $mediaID = $matches['mediaID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['mediaID' => $mediaID];

        return $data;
    }

    /**
     * Grab info from Instagram.
     * @param $url
     * @return array
     */
    private function lookupInstagram($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?instagr(?:\.am|am\.com)\/p\/(?<mediaID>[\w-]+)/i',
            $url,
            $matches
        );
        $mediaID = $matches['mediaID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['mediaID' => $mediaID];

        return $data;
    }

    /**
     * Grab info from Pinterest.
     *
     * @param $url
     * @return array
     */
    private function lookupPinterest($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?pinterest\.com\/pin\/(?<pinID>[\d]+)/i',
            $url,
            $matches
        );
        $pinID = $matches['pinID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['pinID' => $pinID];

        return $data;
    }

    /**
     * Grab info from SoundCloud.
     *
     * @param $url
     * @return array
     */
    private function lookupSoundcloud($url) {
        preg_match(
            '/https?:(?:www\.)?\/\/soundcloud\.com\/(?<user>[\w=?&;+-_]*)\/(?<track>[\w=?&;+-_]*)/i',
            $url,
            $matches
        );
        $user = $matches['user'] ?: null;
        $track = $matches['track'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = [
            'user' => $user,
            'track' => $track
        ];

        return $data;
    }

    /**
     * Grab info from Twitch.tv.
     *
     * @param $url
     * @return array
     */
    private function lookupTwitch($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?twitch\.tv\/(?<channel>[\w]+)/i',
            $url,
            $matches
        );
        $channel = $matches['channel'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['channel' => $channel];

        return $data;
    }


    /**
     * Grab info from Twitter.
     *
     * @param $url
     * @return array
     */
    private function lookupTwitter($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?twitter\.com\/(?:#!\/)?(?:[^\/]+)\/status(?:es)?\/(?<statusID>[\d]+)/i',
            $url,
            $matches
        );

        $statusID = $matches['statusID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['statusID' => $statusID];

        return $data;
    }

    /**
     * Grab info from Vimeo.
     *
     * @param $url
     * @return array
     */
    private function lookupVimeo($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?vimeo\.com\/(?:channels\/[a-z0-9]+\/)?(?<videoID>\d+)/i',
            $url,
            $matches
        );

        $videoID = $matches['videoID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = [
            'videoID' => $videoID
        ];

        return $data;
    }

    /**
     * Grab info from Vine.
     *
     * @param $url
     * @return array
     */
    private function lookupVine($url) {
        preg_match(
            '/https?:\/\/(?:www\.)?vine\.co\/(?:v\/)?(?<videoID>[\w]+)/i',
            $url,
            $matches
        );

        $videoID = $matches['videoID'] ?: null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = ['videoID' => $videoID];

        return $data;
    }

    /**
     * Grab info from Wistia.
     *
     * @param $url
     * @return array
     */
    private function lookupWistia($url) {
        // Try the wvideo-style URL.
        $wvideo = preg_match(
            '/https?:\/\/(?:[A-za-z0-9\-]+\.)?(?:wistia\.com|wi\.st)\/.*?\?wvideo=(?<videoID>[A-za-z0-9]+)([\?&]wtime=(?<time>((\d)+m)?((\d)+s)?))?/i',
            $url,
            $matches
        );
        if (!$wvideo) {
            // Fallback to the medias-style URL.
            preg_match(
                '/https?:\/\/([A-za-z0-9\-]+\.)?(wistia\.com|wi\.st)\/medias\/(?<videoID>[A-za-z0-9]+)(\?wtime=(?<time>((\d)+m)?((\d)+s)?))?/i',
                $url,
                $matches
            );
        }

        $videoID = array_key_exists('videoID', $matches) ? $matches['videoID'] : null;
        $time = array_key_exists('time', $matches) ? $matches['time'] : null;

        // Get basic info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = [
            'videoID' => $videoID,
            'time' => $time
        ];

        return $data;
    }

    /**
     * Grab info about a YouTube video.
     *
     * @param string $url
     * @return array
     */
    private function lookupYouTube($url) {
        // Get info from the URL.
        preg_match(
            '/https?:\/\/(?:(?:www.)|(?:m.))?(?:(?:youtube.com)|(?:youtu.be))\/(?:(?:playlist?)|(?:(?:watch\?v=)?(?P<videoId>[\w-]{11})))(?:\?|\&)?(?:list=(?P<listId>[\w-]*))?(?:t=(?:(?P<minutes>\d*)m)?(?P<seconds>\d*)s)?(?:#t=(?P<start>\d*))?/i',
            $url,
            $urlParts
        );

        // Figure out the start time.
        $start = null;
        if (array_key_exists('start', $urlParts)) {
            $start = $urlParts['start'];
        } elseif (array_key_exists('minutes', $urlParts) || array_key_exists('seconds', $urlParts)) {
            $minutes = $urlParts['minutes'] ? intval($urlParts['minutes']) : 0;
            $seconds = $urlParts['seconds'] ? intval($urlParts['seconds']) : 0;
            $start = ($minutes * 60) + $seconds;
        }

        // Get info from the page markup.
        $data = $this->fetchPageInfo($url);

        $data['attributes'] = [
            'videoID' => array_key_exists('videoId', $urlParts) ? $urlParts['videoId'] : null,
            'listID' => array_key_exists('listId', $urlParts) ? $urlParts['listId'] : null,
            'start' => $start
        ];
        return $data;
    }

    /**
     * Set value of the "disable fetch" flag. Disabling fetch will avoid downloading page contents.
     *
     * @param bool $disableFetch
     * @return self
     */
    public function setDisableFetch($disableFetch) {
        $this->disableFetch = boolval($disableFetch);
        return $this;
    }
}
