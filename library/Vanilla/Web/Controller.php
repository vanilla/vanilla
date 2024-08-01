<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\ServerException;
use Gdn_Locale as LocaleInterface;
use Gdn_Session as SessionInterface;
use Gdn_Upload as Upload;
use Vanilla\Exception\PermissionException;
use Vanilla\InjectableInterface;
use Vanilla\Permissions;
use Vanilla\SchemaFactory;
use Vanilla\UploadedFile;
use Vanilla\Utility\ModelUtils;

/**
 * The controller base class.
 */
abstract class Controller implements InjectableInterface, CacheControlConstantsInterface
{
    use PermissionCheckTrait;

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
     * @inheridoc
     */
    protected function getPermissions(): ?Permissions
    {
        if ($this->session === null) {
            return null;
        }
        return $this->session->getPermissions();
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
    public function schema($schema, $type = "in")
    {
        if (is_array($type)) {
            $origType = $type;
            [$id, $type] = $origType;
        } elseif (!in_array($type, ["in", "out"], true)) {
            $id = $type;
            $type = "";
        }

        if (empty($id) || !is_string($id)) {
            $id = null;
        }
        if (is_array($schema)) {
            $schema = SchemaFactory::parse($schema, $id);
        } else {
            $schema = SchemaFactory::prepare($schema, $id);
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
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the session.
     *
     * @param SessionInterface $session The new session.
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Get the event manager.
     *
     * @return EventManager Returns the event manager.
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Set the event manager.
     *
     * @param EventManager $eventManager The new event manager.
     * @return $this
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * Get the locale.
     *
     * @return LocaleInterface Returns the locale.
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the locale.
     *
     * @param LocaleInterface $locale The locale of the current user.
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Given a model, analyze its validation property and return failures.
     *
     * @param object $model The model to analyze the Validation property of.
     * @param bool $throw If errors are found, should an exception be thrown?
     * @throws ValidationException if errors are detected and $throw is true.
     * @return Validation
     */
    public function validateModel($model, $throw = true)
    {
        return ModelUtils::validationResultToValidationException($model, $this->locale, $throw);
    }
}
