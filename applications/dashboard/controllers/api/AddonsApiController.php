<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Vanilla\Addon;
use Vanilla\Models\AddonModel;
use Vanilla\Web\Controller;

/**
 * Endpoints for managing addons.
 */
class AddonsApiController extends Controller {
    /**
     * @var AddonModel $addonModel
     */
    private $addonModel;

    /**
     * @var Schema $schema;
     */
    private $schema;

    /**
     * AddonsApiController constructor.
     *
     * @param AddonModel $addonModel The addon model dependency.
     */
    public function __construct(AddonModel $addonModel) {
        $this->addonModel = $addonModel;
    }

    /**
     * Get schema of an addon as returned by the API.
     *
     * @return Schema Returns an initialized schema.
     */
    protected function fullSchema() {
        if ($this->schema === null) {
            $this->schema = Schema::parse([
                'addonID:s' => 'The ID of the addon used for updates.',
                'name:s' => 'The name of the addon.',
                'key:s' => 'The unique key that identifies the addon',
                'type:s' => [
                    'description' => 'The type of addon.',
                    'enum' => ['addon', 'theme', 'locale']
                ],
                'description:s?' => 'The addon\'s description',
                'iconUrl:s' => [
                    'description' => 'The addon\'s icon.',
                    'format' => 'uri',
                ],
                'version:s' => 'The addon\'s version.',
                'require:o?' => 'An associative array of addons that are required to enable the addon.',
                'enabled:b' => 'Whether or not the addon is enabled.',
            ])->setID('Addon');
        }
        return $this->schema;
    }

    /**
     * Transform an addon to its API output equivalent.
     *
     * @param Addon $addon The addon to transform.
     * @return array Returns an addon row.
     */
    protected function filterOutput(Addon $addon) {
        $r = $addon->getInfo();
        $r['addonID'] = $addon->getKey().($addon->getType() === Addon::TYPE_ADDON ? '' : '-'.$addon->getType());
        if (empty($r['name'])) {
            $r['name'] = $addon->getRawKey() ?: $addon->getKey();
        }
        $r['iconUrl'] = asset($addon->getIcon(), true);

        if ($addon->getType() === 'theme') {
            $r['enabled'] = $this->addonModel->getThemeKey() === $addon->getKey();
        } else {
            $r['enabled'] = $this->addonModel->getAddonManager()->isEnabled($addon->getKey(), $addon->getType());
        }

        return $r;
    }

    /**
     * Query the available addons.
     *
     * @param array $query The query string.
     * @return array Returns an array of addon information.
     */
    public function index(array $query) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([
            'type:s?' => [
                'description' => 'The type of addon.',
                'enum' => ['addon', 'theme', 'locale']
            ],
            'enabled:b?' => 'Filter enabled or disabled addons.',
            'themeType:s?' => [
                'description' => 'Which theme to show the enabled status for.',
                'enum' => ['desktop', 'mobile'],
                'default' => 'desktop'
            ]
        ], 'in')->setDescription('List addons.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $query = $in->validate($query);

        $addons = $this->addonModel->getWhere($query);
        $addons = array_map([$this, 'filterOutput'], $addons);
        usort($addons, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        $addons = $out->validate($addons);

        return $addons;
    }

    /**
     * Get a single addon.
     *
     * @param string $addonID The addon ID to get.
     * @param array $query The query string.
     * @return array Returns an addon info row.
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception when the addon is not found.
     */
    public function get($addonID, array $query) {
        $this->permission('Garden.Settings.Manage');

        $id = $this->schema([
            'addonID:s' => 'The ID of the addon.'
        ], 'in')->setDescription('Get an addon.');

        $in = $this->schema([
            'themeType:s?' => [
                'description' => 'Which theme to show the enabled status for.',
                'enum' => ['desktop', 'mobile'],
                'default' => 'desktop'
            ]
        ], 'in');
        $out = $this->schema($this->fullSchema(), 'out');
        $query = $in->validate($query);

        $addons = $this->addonModel->getWhere(['addonID' => $addonID]);
        if (empty($addons)) {
            throw new \Garden\Web\Exception\NotFoundException("Addon");
        }

        $row = $out->validate($this->filterOutput(reset($addons)));
        return $row;
    }

    /**
     * Enable or disable an addon.
     *
     * @param string $addonID The addon ID.
     * @param array $body The request body.
     * @return array Returns an array of affected addons.
     * @throws \Garden\Web\Exception\NotFoundException Throws an exception when the addon is not found.
     */
    public function patch($addonID, array $body) {
        $this->permission('Garden.Settings.Manage');

        $idSchema = $this->schema([
            'addonID:s' => 'The ID of the addon.'
        ], 'in');
        $in = $this->schema([
            'enabled:b' => 'Enable or disable the addon.',
            'themeType:s?' => [
                'description' => 'Which theme type to set.',
                'enum' => ['desktop', 'mobile'],
                'default' => 'desktop'
            ]
        ], 'in')->setDescription('Enable or disable an addon.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $body = $in->validate($body);

        $addons = $this->addonModel->getWhere(['addonID' => $addonID]);
        if (empty($addons)) {
            throw new \Garden\Web\Exception\NotFoundException("Addon");
        }
        $addon = reset($addons);

        $options = [];
        if (!empty($body['themeType'])) {
            $options['themeType'] = $body['themeType'];
        }
        if ($body['enabled']) {
            $r = $this->addonModel->enable($addon, $options);
        } else {
            $this->addonModel->disable($addon, $options);
            $r = [$addon];
        }

        $r = array_map([$this, 'filterOutput'], $r);
        $r = $out->validate($r);

        return $r;
    }
}
