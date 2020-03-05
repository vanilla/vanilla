<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Models;

use Vanilla\Contracts\Models\UserProviderInterface;
use Vanilla\Exception\Database\NoResultsException;

/**
 * Mock user provider that can be configured for some users.
 */
class MockUserProvider implements UserProviderInterface {

    private $currentID = 1;

    private $usersByID = [];

    /**
     * @inheritdoc
     */
    public function getFragmentByID(int $id, bool $useUnknownFallback = false): array {
        $user = $this->usersByID[$id] ?? null;
        if (!$user) {
            if ($useUnknownFallback) {
                return $this->getUnknownFragment();
            } else {
                throw new NoResultsException('No user found for ID ' . $id);
            }
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function expandUsers(array &$records, array $columnNames): void {
        foreach ($columnNames as $column) {
            $noIDName = str_replace('ID', '', $column);
            foreach ($records as $record) {
                $record[$noIDName] = $this->getFragmentByID($record[$column], true);
            }
        }
    }

    /**
     * All values are supported.
     * @return array
     */
    public function getAllowedGeneratedRecordKeys(): array {
        return [];
    }


    /**
     * Create a mock user, add it, and return it.
     *
     * @param string|null $label
     * @return array
     */
    public function addMockUser(string $label = null) {
        $record = [
            'userID' => $this->currentID,
            'name' => 'user' . $this->currentID,
            'dateLastActive' => null,
            'photoUrl' => 'image.png',
        ];

        if ($label) {
            $record['label'] = $label;
        }

        $this->usersByID[$this->currentID] = $record;
        $this->currentID++;
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function getGeneratedFragment(string $key): array {
        return [
            'userID' => 0,
            'name' => $key,
            'email' => $key . '@example.com',
            'photoUrl' => $key . '.png',
        ];
    }
}
