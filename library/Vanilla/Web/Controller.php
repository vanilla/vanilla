<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Gdn_Session as SessionInterface;
use Gdn_Locale as LocaleInterface;
use Gdn_Upload as Upload;
use Gdn_Validation as DataValidation;
use Vanilla\Exception\PermissionException;
use Vanilla\InjectableInterface;
use Vanilla\UploadedFile;
use Vanilla\Utility\CamelCaseScheme;

/**
 * The controller base class.
 */
abstract class Controller implements InjectableInterface {
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var LocaleInterface;
     */
    private $locale;

    /** @var Upload */
    private $upload;

    /**
     * Set the base dependencies of the controller.
     *
     * This method allows subclasses to declare their dependencies in their constructor without worrying about these
     * dependencies.
     *
     * @param SessionInterface|null $session The session of the current user.
     * @param EventManager|null $eventManager The event manager dependency.
     * @param LocaleInterface|null $local The current locale for translations.
     * @param Upload $upload File upload handler.
     */
    public function setDependencies(
        SessionInterface $session = null,
        EventManager $eventManager = null,
        LocaleInterface $local = null,
        Upload $upload
    ) {
        $this->session = $session;
        $this->eventManager = $eventManager;
        $this->locale = $local;
        $this->upload = $upload;
    }

    /**
     * Enforce the following permission(s) or throw an exception that the dispatcher can handle.
     *
     * When passing several permissions to check the user can have any of the permissions. If you want to force several
     * permissions then make several calls to this method.
     *
     * @throws \Exception if no session is available.
     * @throws HttpException if a ban has been applied on the permission(s) for this session.
     * @throws PermissionException if the user does not have the specified permission(s).
     *
     * @param string|array $permission The permissions you are requiring.
     * @param int|null $id The ID of the record we are checking the permission of.
     */
    public function permission($permission = null, $id = null) {
        if (!$this->session instanceof SessionInterface) {
            throw new \Exception("Session not available.", 500);
        }
        $permissions = (array)$permission;

        /**
         * First check to see if the user is banned.
         */
        if ($ban = $this->session->getPermissions()->getBan($permissions)) {
            $ban += ['code' => 401, 'msg' => 'Access denied.'];

            throw HttpException::createFromStatus($ban['code'], $ban['msg'], $ban);
        }

        if (!$this->session->getPermissions()->hasAny($permissions, $id)) {
            throw new PermissionException($permissions);
        }
    }

    /**
     * Create a schema attached to an endpoint.
     *
     * @param array|Schema $schema The schema definition. This can be either an array or an actual schema object.
     * @param string|array $type The type of schema. This can be one of the following:
     *
     * - **"in" or "out"**. Says this is an input or output schema.
     * - **"TypeID"**. Says this the name of the schema being defined, but not used in an endpoint. You specify a type
     * ID so the schema is pluggable. You name a type like you name a class.
     * - **["TypeID", "in" or "out"]**. When you need to define both a type ID and input or output.
     * @return Schema Returns a schema object.
     */
    public function schema($schema, $type = 'in') {
        $id = '';
        if (is_array($type)) {
            $origType = $type;
            list($id, $type) = $origType;
        } elseif (!in_array($type, ['in', 'out'], true)) {
            $id = $type;
            $type = '';
        }

        // Figure out the name.
        if (is_array($schema)) {
            $schema = Schema::parse($schema);
        } elseif ($schema instanceof Schema) {
            $schema = clone $schema;
        }

        // Fire an event for schema modification.
        if (!empty($id)) {
            // The type is a specific type of schema.
            $schema->setID($id);

            $this->eventManager->fire("{$id}Schema_init", $schema);
        }

        // Fire a generic schema event for documentation.
        if (!empty($type)) {
            $this->eventManager->fire("controller_schema", $this, $schema, $type);
        }

        return $schema;
    }

    /**
     * Get the session.
     *
     * @return SessionInterface Returns the session.
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * Set the session.
     *
     * @param SessionInterface $session The new session.
     * @return $this
     */
    public function setSession($session) {
        $this->session = $session;
        return $this;
    }

    /**
     * Get the event manager.
     *
     * @return EventManager Returns the event manager.
     */
    public function getEventManager() {
        return $this->eventManager;
    }

    /**
     * Set the event manager.
     *
     * @param EventManager $eventManager The new event manager.
     * @return $this
     */
    public function setEventManager($eventManager) {
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * Get the locale.
     *
     * @return LocaleInterface Returns the locale.
     */
    public function getLocale() {
        return $this->locale;
    }

    /**
     * Set the locale.
     *
     * @param LocaleInterface $locale The locale of the current user.
     * @return $this
     */
    public function setLocale($locale) {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Generate a valid upload path, relative to the uploads directory.
     *
     * @param string $ext The file's extension.
     * @param bool $chunk Include an additional random subdirectory?
     * @return string
     */
    public function generateUploadPath($ext, $chunk = false) {
        $path = $this->upload->generateTargetName(PATH_UPLOADS, $ext, $chunk);
        $result = stringBeginsWith($path, PATH_UPLOADS.'/', false, true);
        return $result;
    }

    /**
     * @param UploadedFile $upload
     * @param string $destination
     * @param string $nameFormat
     * @param bool $copy
     * @throws \Exception if failed to save the upload.
     * @returns array|bool
     */
    public function saveUpload(UploadedFile $upload, $destination, $nameFormat = '%s', $copy = false) {
        $destination = $result = stringBeginsWith($destination, PATH_UPLOADS.'/', false, true);
        $ext = pathinfo($destination, PATHINFO_EXTENSION);
        $baseName = basename($destination, ".{$ext}");
        $dirName = dirname($destination);

        $target = sprintf($nameFormat, $baseName);
        if (!empty($ext)) {
            $target .= ".{$ext}";
        }
        if (!empty($dirName)) {
            $target = "{$dirName}/{$target}";
        }
        $target = PATH_UPLOADS."/{$target}";

        $result = $this->upload->saveAs(
            $upload->getFile(),
            $target,
            [],
            $copy
        );
        return $result;
    }

    /**
     * Given a model, analyze its validation property and return failures.
     *
     * @param object $model The model to analyze the Validation property of.
     * @param bool $throw If errors are found, should an exception be thrown?
     * @throws ValidationException if errors are detected and $throw is true.
     * @return Validation
     */
    public function validateModel($model, $throw = true) {
        $validation = new Validation();
        $caseScheme = new CamelCaseScheme();

        if (property_exists($model, 'Validation') && $model->Validation instanceof DataValidation) {
            $results = $model->Validation->results();
            $results = $caseScheme->convertArrayKeys($results);
            foreach ($results as $field => $errors) {
                foreach ($errors as $error) {
                    $message = trim(sprintf(
                        $this->locale->translate($error),
                        $this->locale->translate($field)
                    ), '.').'.';
                    $validation->addError(
                        $field,
                        $error,
                        ['message' => $message]
                    );
                }
            }
        }

        if ($throw && $validation->getErrorCount() > 0 ) {
            throw new ValidationException($validation);
        }

        return $validation;
    }
}
