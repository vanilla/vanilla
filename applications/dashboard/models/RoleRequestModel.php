<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\Contracts\Models\FragmentFetcherInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\StatusFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\StringUtils;
use Webmozart\Assert\Assert;

/**
 * A model for handling applicants and invites to role assignments.
 */
class RoleRequestModel extends PipelineModel implements FragmentFetcherInterface {
    public const TYPE_APPLICATION = 'application';
    public const TYPE_INVITATION = 'invitation';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';

    public const OPT_LOG = 'log';

    public const OPT_FRAGMENT_TYPE = "type";
    public const OPT_FRAGMENT_USERID = "userID";

    /**
     * An array mapping statuses and what they are allowed to be updated to.
     */
    protected const ALLOWED_STATUS_CHANGES = [
        self::STATUS_PENDING => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_DENIED],
        self::STATUS_APPROVED => [self::STATUS_APPROVED],
        self::STATUS_DENIED => [self::STATUS_PENDING, self::STATUS_DENIED, self::STATUS_APPROVED],
    ];

    /**
     * @var \UserModel
     */
    private $userModel;

    /**
     * @var \RoleModel
     */
    private $roleModel;

    /**
     * @var RoleRequestMetaModel
     */
    private $metaModel;

    /**
     * @var \ActivityModel
     */
    private $activityModel;

    /**
     * @var Operation\CurrentUserFieldProcessor
     */
    private $userFields;

    /**
     * RoleQueueModel constructor.
     *
     * @param \UserModel $userModel Used to fulfill approvals.
     * @param RoleRequestMetaModel $metaModel
     * @param \RoleModel $roleModel
     * @param \ActivityModel $activityModel
     * @param Operation\CurrentUserFieldProcessor $userFields
     * @param Operation\CurrentIPAddressProcessor $ipFields
     * @param StatusFieldProcessor $statusFields
     */
    public function __construct(
        \UserModel $userModel,
        RoleRequestMetaModel $metaModel,
        \RoleModel $roleModel,
        \ActivityModel $activityModel,
        Operation\CurrentUserFieldProcessor $userFields,
        Operation\CurrentIPAddressProcessor $ipFields,
        StatusFieldProcessor $statusFields
    ) {
        parent::__construct('roleRequest');
        $this->userModel = $userModel;
        $this->metaModel = $metaModel;
        $this->roleModel = $roleModel;
        $this->activityModel = $activityModel;

        $prune = new Operation\PruneProcessor('dateExpires');
        $this->addPipelineProcessor($prune);

        $dateFields = new CurrentDateFieldProcessor();
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);

        $this->userFields = $userFields;
        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);

        $ipFields->camelCase();
        $this->addPipelineProcessor($ipFields);

        $statusFields
            ->setStatusField('status')
            ->setDateField('dateOfStatus')
            ->setUserField('statusUserID')
            ->setIpAddressField('statusIPAddress');
        $this->addPipelineProcessor($statusFields);

        $attributes = new JsonFieldProcessor(['attributes']);
        $this->addPipelineProcessor($attributes);
    }

    /**
     * Handle the role request operation with some additional niceties.
     *
     * @param Operation $op
     * @return mixed
     */
    protected function handleInnerOperation(Operation $op) {
        $validation = new Validation();

        // Get the current item for comparisons.
        if (Operation::TYPE_UPDATE === $op->getType()) {
            if ($op->hasAllWhereItems(...$this->getPrimaryKey())) {
                $current = $this->selectSingle($this->primaryWhere($op->pluckWhereItems(...$this->getPrimaryKey())));
            } else {
                $validation->addError('roleRequestID', 'missingField', ['messageCode' => '{field} is required.']);
            }
        } elseif (Operation::TYPE_INSERT === $op->getType()) {
            // Get a potential current item by secondary key.
            $current = $this->select(['roleID' => $op->getSetItem('roleID'), 'userID' => $op->getSetItem('userID')])[0] ?? null;
        }

        // Add some handling for a developer-friendly ttl that will set the date expires.
        if ($op->hasSetItem('ttl')) {
            $dt = strtotime($op->getSetItem('ttl'), CurrentTimeStamp::get());
            if ($dt === false) {
                $validation->addError('ttl', "The TTL was not a valid date string.");
            } else {
                $op->setSetItem('dateExpires', gmdate(CurrentTimeStamp::MYSQL_DATE_FORMAT, $dt));
                $op->removeSetItem('ttl');
            }
        }

        // Add a boolean sort for expired items.
        if ($op->hasWhereItem('expired')) {
            if ($op->getWhereItem('expired')) {
                $op->setWhereItem('dateExpires <=', CurrentTimeStamp::getDateTime());
            } else {
                $op->setWhereItem('dateExpires >', CurrentTimeStamp::getDateTime());
            }
            $op->removeWhereItem('expired');
        }

        // You can't deny a request that was previously approved.
        if (isset($current) && $op->hasSetItem('status') &&
            !in_array($op->getSetItem('status'), self::ALLOWED_STATUS_CHANGES[$current['status']])
        ) {
            $validation->addError(
                'status',
                'You are not allowed to change the status from {statusFrom} to {statusTo}',
                ['statusFrom' => $current['status'], 'statusTo' => $op->getSetItem('status')]
            );
        }

        // You can't change the role or type after the request has been made.
        if ($op->getType() === Operation::TYPE_UPDATE && ($op->hasSetItem('type') || $op->hasSetItem('roleID'))) {
            $validation->addError('', 'You are not allowed to update the type or role of an existing request.');
        }

        // Make sure the request is made against a type with a meta row.
        if ($op->getType() === Operation::TYPE_INSERT) {
            try {
                $meta = $this->metaModel->selectSingle(['type' => $op->getSetItem('type'), 'roleID' => $op->getSetItem('roleID')]);
            } catch (NoResultsException $ex) {
                throw new ForbiddenException("You are not allowed to make that kind of request to this role.");
            }
        }

        $allowReApply = !empty($meta['attributes']['allowReapply']);
        $wasInserted = false;
        if ($op->getType() === Operation::TYPE_INSERT && $allowReApply && isset($current)) {
            $op->setType(Operation::TYPE_UPDATE);
            $op->setWhere([
                'roleID' => $op->getSetItem('roleID'),
                'type' => $op->getSetItem('type'),
                'userID' => $op->getSetItem('userID')]);
            $wasInserted = true;
        }

        // You are only allowed to update one request at a time.
        if ($op->getType() === Operation::TYPE_UPDATE && isset($current)) {
            $meta = $this->metaModel->selectSingle(['type' => $current['type'], 'roleID' => $current['roleID']]);
        }

        if ($validation->getErrorCount() > 0) {
            throw new ValidationException($validation);
        }

        // After the main validation is done then validate the attributes too.
        if (isset($meta) && $op->hasSetItem('attributes')) {
            $schema = new Schema($meta['attributesSchema']);
            $schema->setValidationClass($this->createValidationClass($schema));
            $attributes = json_decode($op->getSetItem('attributes'), true);
            $attributes = $schema->validate($attributes);
            $op->setSetItem('attributes', json_encode($attributes));
        }

        try {
            $result = parent::handleInnerOperation($op);

            if (true === $result && $wasInserted && isset($current)) {
                $result = (int)$current['roleRequestID'];
            }
        } catch (\Exception $ex) {
            if ($op->getType() === Operation::TYPE_INSERT && preg_match('`^Duplicate entry`', $ex->getMessage())) {
                throw new ClientException(t('You have already applied.'), 409);
            }
            throw $ex; // @codeCoverageIgnore
        }

        // Did the status get set to approved?
        if (self::STATUS_APPROVED === $op->getSetItem('status') && in_array($op->getType(), [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])) {
            $set = $op->getSet() + ($current ?? []);

            $this->userModel->addRoles($set['userID'], [$set['roleID']], $op->getOptionItem(self::OPT_LOG, true));

            $role = $this->roleModel->getID($set['roleID'], DATASET_TYPE_ARRAY);
            $notification = [
                'ActivityType' => 'roleRequest',
                'ActivityUserID' => $op->getSetItem('statusUserID'),
                'HeadlineFormat' => $meta['attributes']['notification'][self::STATUS_APPROVED]['name'] ??
                    t('You\'ve been added to the <b>{Data.role}</b> role.'),
                'RecordType' => 'role',
                'RecordID' => $set['roleID'],
                'Route' => $meta['attributes']['notification'][self::STATUS_APPROVED]['url'] ?? '/',
                'Story' => $meta['attributes']['notification'][self::STATUS_APPROVED]['body'] ?? t('Your application has been approved.'),
                'Format' => $meta['attributes']['notification'][self::STATUS_APPROVED]['format'] ?? 'markdown',
                'NotifyUserID' => $set['userID'],
                'Data' => ['role' => $role['Name'] ?? 'Unknown'],
                'Notified' => \ActivityModel::SENT_PENDING,
                'Emailed' => \ActivityModel::SENT_PENDING,
            ];
            $this->activityModel->save($notification, false, ['Force' => true]);
        }

        $notifyDenied = $meta['attributes']['notifyDenied'] ?? false;
        if (self::STATUS_DENIED === $op->getSetItem('status')
            && in_array($op->getType(), [Operation::TYPE_INSERT, Operation::TYPE_UPDATE])
            && $notifyDenied
        ) {
            $set = $op->getSet() + ($current ?? []);
            $role = $this->roleModel->getID($set['roleID'], DATASET_TYPE_ARRAY);
            $notification = [
                'ActivityType' => 'roleRequest',
                'ActivityUserID' => $op->getSetItem('statusUserID'),
                'HeadlineFormat' => $meta['attributes']['notification'][self::STATUS_DENIED]['name'] ??
                    t('You\'re application to the <b>{Data.role}</b> role was denied.'),
                'RecordType' => 'role',
                'RecordID' => $set['roleID'],
                'Route' => $meta['attributes']['notification'][self::STATUS_DENIED]['url'] ?? '/',
                'Story' => $meta['attributes']['notification'][self::STATUS_DENIED]['body'] ?? t('Your application has been denied.'),
                'Format' => $meta['attributes']['notification'][self::STATUS_DENIED]['format'] ?? 'markdown',
                'NotifyUserID' => $set['userID'],
                'Data' => ['role' => $role['Name'] ?? 'Unknown'],
                'Notified' => \ActivityModel::SENT_PENDING,
                'Emailed' => \ActivityModel::SENT_PENDING,
            ];
            $this->activityModel->save($notification, false, ['Force' => true]);
        }
        return $result;
    }

    /**
     * Create a specific validator that supports translation.
     *
     * @param Schema $schema
     * @return Validation
     */
    private function createValidationClass(Schema $schema): Validation {
        $r = new class($schema) extends Validation {
            /**
             * @var Schema
             */
            private $schema;

            /**
             * Validation constructor.
             *
             * @param Schema $schema
             */
            public function __construct(Schema $schema) {
                $this->schema = $schema;
            }

            /**
             * Translate an error message string.
             *
             * @param string $str
             * @return string
             */
            public function translate($str) {
                $field = $this->schema->getField(['properties', $str]);
                if (is_array($field)) {
                    $str = $field['x-label'] ?? StringUtils::labelize($str);
                }
                $r = t($str);
                return $r;
            }
        };
        $r->setTranslateFieldNames(true);

        return $r;
    }

    /**
     * @inheritDoc
     */
    public function fetchFragments(array $roleIDs, array $options = []): array {
        $options += [
            self::OPT_FRAGMENT_TYPE => null,
            self::OPT_FRAGMENT_USERID => null,
        ];

        $type = $options[self::OPT_FRAGMENT_TYPE];
        Assert::string($type);

        $userID = $options[self::OPT_FRAGMENT_USERID] ?? null;
        if ($userID === null) {
            $userID = $this->userFields->getCurrentUserID();
        } else {
            Assert::integerish($userID);
            $userID = (int)$userID;
        }

        $rows = $this->select(['roleID' => $roleIDs, 'type' => $type, 'userID' => $userID]);
        $fragments = [];

        foreach ($rows as $row) {
            $fragments[$row['roleID']] = ArrayUtils::pluck($row, ['status', 'dateInserted']);
        }
        return $fragments;
    }

    /**
     * Generate a fetch fragments callback for a given type.
     *
     * @param string $type The role request type.
     * @param int|null $userID A user to filter to. Pass **null** for the current user.
     * @return callable
     */
    public function fetchFragmentsFunction(string $type, int $userID = null): callable {
        return function (array $roleIDs) use ($type, $userID): array {
            return $this->fetchFragments($roleIDs, [
                self::OPT_FRAGMENT_TYPE => $type,
                self::OPT_FRAGMENT_USERID => $userID,
            ]);
        };
    }
}
