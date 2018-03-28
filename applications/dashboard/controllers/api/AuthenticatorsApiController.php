<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\SSOAuthenticator;

/**
 * Class AuthenticatorsApiController
 */
class AuthenticatorsApiController extends AbstractApiController  {

    /** @var AuthenticatorModel */
    private $authenticatorModel;

    /** @var Schema */
    private $idParamSchema;

    /** @var Schema */
    private $fullSchema;

    /**
     * AuthenticatorsApiController constructor.
     *
     * @param AuthenticatorModel $authenticatorModel
     */
    public function __construct(AuthenticatorModel $authenticatorModel) {
        $this->authenticatorModel = $authenticatorModel;
    }

    /**
     * Get an authenticator.
     *
     * @param string $type
     * @param string $id
     * @return Authenticator
     */
    public function authenticator(string $type, string $id): Authenticator {
        try {
            $authenticator = $this->authenticatorModel->getAuthenticator($type, $id);
        } catch (Exception $e) {
            Logger::log(Logger::DEBUG, 'authenticator_not_found', [
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ]);

            throw new NotFoundException('Authenticator');
        }

        return $authenticator;
    }

    /**
     * Get an authenticator by its ID.
     *
     * @param string $id
     * @return Authenticator
     */
    public function authenticatorByID(string $id): Authenticator {
        try {
            $authenticator = $this->authenticatorModel->getAuthenticatorByID($id);
        } catch (Exception $e) {
            Logger::log(Logger::DEBUG, 'authenticator_not_found', [
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ]);

            throw new NotFoundException('Authenticator');
        }

        return $authenticator;
    }

    /**
     * Get the full authenticator schema.
     *
     * @return Schema
     */
    public function fullSchema() {
        if (!$this->fullSchema) {
            $this->fullSchema = $this->schema(
                // Use the SSOAuthenticator schema but make the sso attribute optional.
                Schema::parse([
                    'resourceUrl:s' => 'API URL to get the authenticator',
                    'sso?',
                ])->merge(SSOAuthenticator::getAuthenticatorSchema()),
                'Authenticator'
            );
        }

        return $this->fullSchema;
    }

    /**
     * Get an ID-only authenticator record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamSchema() {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:s' => 'The authenticator ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, 'in');
    }

    /**
     * Get a Type-only authenticator record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function typeParamSchema(): Schema {
        if ($this->typeParamSchema === null) {
            $this->typeParamSchema = $this->schema(
                Schema::parse(['type:s' => 'The authenticator type.']),
                $type
            );
        }
        return $this->schema($this->typeParamSchema, 'in');
    }

    /**
     * GET an authenticator.
     *
     * @param string $type
     * @param string $id
     * @return array
     */
    public function get(string $type, string $id): array {
        $this->permission('Garden.Setting.Manage');

        $this->typeParamSchema();
        $this->idParamSchema();
        $this->schema([], ['AuthenticatorGet', 'in'])->setDescription('Get an authenticator.');
        $out = $this->schema($this->fullSchema(), 'out');

        $authenticator = $this->authenticator($type, $id);

        $result = $this->normalizeOutput($authenticator);

        return $out->validate($result);
    }

    /**
     * List authenticators.
     *
     * @return Data
     */
    public function index(): Data {
        $this->permission('Garden.Setting.Manage');

        $this->schema([], ['AuthenticatorIndex', 'in'])->setDescription('List authenticators.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $authenticators = $this->authenticatorModel->getAuthenticators();
        $result = [];
        foreach ($authenticators as $authenticator) {
            $result[] = $this->normalizeOutput($authenticator);
        }

        $data = $out->validate($result);

        return new Data($data);
    }

    /**
     * Normalize an authenticator to match the Schema definition.
     *
     * @param Authenticator $authenticator
     * @return array Return a Schema record.
     */
    protected function normalizeOutput(Authenticator $authenticator): array {
        $record = $authenticator->getAuthenticatorInfo();
        $record['authenticatorID'] = strtolower($record['authenticatorID']);
        $record['type'] = strtolower($record['type']);
        $record['resourceUrl'] = strtolower(url('/api/v2/authenticators/'.$authenticator::getType().'/'.$authenticator->getID()));

        return $record;
    }

    /**
     * Update an authenticator.
     *
     * @param string $id
     * @param array $body
     * @return array
     */
    public function patch(string $id, array $body): array {
        $this->permission('Garden.Setting.Manage');

        $this->idParamSchema();
        $in = $this->schema(
            Schema::parse(['isActive'])->add($this->fullSchema()),
            ['AuthenticatorPatch', 'in']
        )->setDescription('Update an authenticator.');
        $out = $this->schema($this->fullSchema(), 'out');

        $authenticator = $this->authenticatorByID($id);

        $body = $in->validate($body);

        $authenticator->setActive($body['active']);

        $result = $this->normalizeOutput($authenticator);

        return $out->validate($result);
    }
}
