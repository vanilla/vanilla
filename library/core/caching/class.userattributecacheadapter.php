<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Class UserAttributeCacheAdapter
 */
class UserAttributeCacheAdapter implements \Vanilla\CacheInterface {

    /**
     * @var Gdn_Session
     */
    private $session;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * UserAttibuteAttributeCacheAdapter constructor.
     *
     * @param Gdn_Session $session
     * @param UserModel $userModel
     */
    public function __construct(Gdn_Session $session, UserModel $userModel) {
        $this->session = $session;
        $this->userModel = $userModel;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null) {
        return $this->session->getAttribute($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null) {
        return $this->setMultiple([$key => $value]);
    }

    /**
     * @inheritDoc
     */
    public function delete($key) {
        return $this->deleteMultiple([$key]);
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null) {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null) {
        $success = $this->session->isValid();
        if ($success) {
            try {
                // This also update the session!
                $this->userModel->saveAttribute($this->session->UserID, $values);
            } catch(Exception $e) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys) {
        return $this->setMultiple(array_fill_keys($keys, null));
    }

    /**
     * @inheritDoc
     */
    public function has($key) {
        return $this->get($key) !== null;
    }


}
