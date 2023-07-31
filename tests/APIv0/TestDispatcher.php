<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

use Garden\Container\Container;
use Garden\EventManager;
use Garden\Web\ControllerDispatchedEvent;
use Garden\Web\Data;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Vanilla\Analytics\TrackableLegacyControllerInterface;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\StringUtils;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\PrivateAccessTrait;
use VanillaTests\VanillaTestCase;

/**
 * A test dispatcher that dispatches to `Gdn_Controller` endpoints.
 */
class TestDispatcher
{
    use PrivateAccessTrait;

    public const ALLOWED_DELIVERY_TYPES = [
        DELIVERY_TYPE_DATA,
        DELIVERY_TYPE_VIEW,
        DELIVERY_TYPE_ALL,
        DELIVERY_TYPE_ASSET,
        DELIVERY_TYPE_BOOL,
        DELIVERY_TYPE_MESSAGE,
    ];

    public const ALLOWED_DELIVERY_METHODS = [
        DELIVERY_METHOD_XHTML,
        DELIVERY_METHOD_JSON,
        DELIVERY_METHOD_ATOM,
        DELIVERY_METHOD_PLAIN,
        DELIVERY_METHOD_RSS,
        DELIVERY_METHOD_TEXT,
        DELIVERY_METHOD_XML,
    ];

    const OPT_THROW_FORM_ERRORS = "throwFormErrors";
    const OPT_DELIVERY_METHOD = "deliveryMethod";
    const OPT_DELIVERY_TYPE = "deliveryType";
    const OPT_PERMANENT = "permanent";

    /**
     * @var Container
     */
    private $container;

    /**
     * @var string|null
     */
    private $lastOutput;

    /** @var \Gdn_Controller|null */
    private $lastController;

    /** @var ControllerDispatchedEvent|null */
    private $lastDispatchedEvent;

    /** @var array */
    private $lastHeaders = [];

    /** @var bool */
    private $rethrowExceptions = true;

    /**
     * TestDispatcher constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create a request with a given set of parameters.
     *
     * @param string $method
     * @param string $path
     * @param array|string $queryOrBody
     * @param \Gdn_Request|null $onRequest Mutate this request instead of creating a new.
     *
     * @return \Gdn_Request
     */
    public function createRequest(
        string $method,
        string $path,
        $queryOrBody = [],
        ?\Gdn_Request $onRequest = null
    ): \Gdn_Request {
        $request = $onRequest ?? \Gdn_Request::create();
        $request->fromEnvironment();
        $request->setMethod($method);
        $request->setUrl($path);
        // Kludge due to a bug in the dispatcher not understanding extensions properly.
        if ($request->getExt()) {
            $request->setPath($request->getPathExt());
            $request->setExt("");
        }
        // Kludge due to the request not understanding roots.
        if ($request->getRoot() && str_starts_with($request->getPath(), $request->getRoot() . "/")) {
            $path = StringUtils::substringLeftTrim($request->getPath(), $request->getRoot());
            $request->setPath($path);
        }

        if ($method === "POST") {
            $request->setRequestArguments(\Gdn_Request::INPUT_POST, $queryOrBody);
        } elseif (!empty($queryOrBody)) {
            $get = $request->getRequestArguments(\Gdn_Request::INPUT_GET);
            $get = array_replace($get, $queryOrBody);
            $request->setRequestArguments(\Gdn_Request::INPUT_GET, $get);
        }

        // Kludge to ensure the path can be reloaded from the environment args.
        $request->setURI($request->getPath());

        return $request;
    }

    /**
     * Make a request to a controller and return it.
     *
     * @param string $method The request method, either GET or POST.
     * @param string $path The path of the request.
     * @param array $queryOrBody An array containing a parsed querystring or a post body.
     * @param array $options An array of additional options.
     * @return \Gdn_Controller|null
     */
    public function request(string $method, string $path, $queryOrBody = [], array $options = []): ?\Gdn_Controller
    {
        $options += [
            self::OPT_PERMANENT => true,
            self::OPT_DELIVERY_TYPE => DELIVERY_TYPE_VIEW,
            self::OPT_DELIVERY_METHOD => DELIVERY_METHOD_XHTML,
            self::OPT_THROW_FORM_ERRORS => true,
        ];
        $this->lastController = $this->lastOutput = $this->lastDispatchedEvent = null;

        TestCase::assertContains($options[self::OPT_DELIVERY_TYPE], self::ALLOWED_DELIVERY_TYPES);
        TestCase::assertContains($options[self::OPT_DELIVERY_METHOD], self::ALLOWED_DELIVERY_METHODS);

        $this->resetStatics();

        /** @var \Gdn_Dispatcher $dispatcher */
        $dispatcher = $this->container->get(\Gdn_Dispatcher::class);
        $dispatcher->setRethrowExceptions($this->rethrowExceptions);
        /** @var EventManager $events */
        $events = $this->container->get(EventManager::class);
        /** @var \Gdn_Session $session */
        $session = $this->container->get(\Gdn_Session::class);

        $this->callOn(
            $dispatcher,
            function (string $deliveryType, string $deliveryMethod) {
                $this->deliveryType = $deliveryType;
                $this->deliveryMethod = $deliveryMethod;
            },
            $options[self::OPT_DELIVERY_TYPE],
            $options[self::OPT_DELIVERY_METHOD]
        );

        // Back up the old request so that it doesn't pollute future tests.
        $oldRequest = clone \Gdn::request();
        $request = self::createRequest($method, $path, $queryOrBody);

        if ($method === "POST") {
            $session->validateTransientKey(true);
        }

        $fn = function ($sender, $args) {
            $this->lastController = $args["Controller"];
        };
        $events->bind("base_beforeControllerMethod", $fn);
        $events->bind(ControllerDispatchedEvent::class, [$this, "handleControllerDispatched"]);

        try {
            $this->lastOutput = null;

            $obLevelStart = ob_get_level();
            // Capture output.
            ob_start();
            $dispatcher->dispatch($request, $options[self::OPT_PERMANENT]);
            $output = ob_get_contents();
            $this->lastOutput = $output;
            $this->lastHeaders = $dispatcher->getSentHeaders();
        } finally {
            \Gdn::request($oldRequest);
            $events->unbind("base_beforeControllerMethod", $fn);

            ob_end_clean();
            $obLevelEnd = ob_get_level();
            Assert::assertSame(
                $obLevelStart,
                $obLevelEnd,
                "Output buffer levels were different at the start and end of the request. Ending HTML:\n" .
                    $this->lastOutput
            );
        }

        if ($options[self::OPT_THROW_FORM_ERRORS]) {
            $form = $this->lastController->Form ?? ($this->lastController->form ?? null);

            if ($form) {
                ModelUtils::validationResultToValidationException($form, \Gdn::locale(), true);
            }
        }

        // Validate that our site meta serialize properly.
        if ($this->lastController instanceof \Gdn_Controller) {
            TestCase::assertIsString($this->lastController->validateDefinitionList());
        }

        return $this->lastController;
    }

    /**
     * Track our last dispatched controller.
     *
     * @param ControllerDispatchedEvent $event
     * @return ControllerDispatchedEvent
     */
    public function handleControllerDispatched(ControllerDispatchedEvent $event)
    {
        $this->lastDispatchedEvent = clone $event;
        return $event;
    }

    /**
     * Assert that a particular controller and method were just dispatched.
     *
     * @param string $path
     * @param string $className
     * @param string $methodName
     */
    public function assertUrlDispatchesController(string $path, string $className, string $methodName)
    {
        $this->get($path);
        TestCase::assertNotNull($this->lastDispatchedEvent, "No controller was dispatched.");
        $lastDispatchedClass = $this->lastDispatchedEvent->getDispatchedClass();
        $lastDispatchedMethod = $this->lastDispatchedEvent->getDispatchedMethod();
        TestCase::assertEquals($className, $lastDispatchedClass, "The wrong class was dispatched.");
        TestCase::assertEquals($methodName, $lastDispatchedMethod, "The wrong method was dispatched.");
    }

    /**
     * Get a controller instance for an url and make assertions about its trackable pageview data.
     *
     * @param string $path
     * @param array $query
     * @param array $expected
     *
     * @return \Gdn_Controller
     */
    public function getAndAssertTrackableData(string $path, array $query, array $expected): \Gdn_Controller
    {
        $controller = $this->get($path, $query);
        TestCase::assertInstanceOf(TrackableLegacyControllerInterface::class, $controller);
        $trackableData = $controller->getTrackableData();
        VanillaTestCase::assertDataLike($expected, $trackableData);
        return $controller;
    }

    /**
     * Make a GET request.
     *
     * @param string $path
     * @param array $query
     * @param array $options
     * @return \Gdn_Controller|null
     */
    public function get(string $path, array $query = [], array $options = []): ?\Gdn_Controller
    {
        return $this->request("GET", $path, $query, $options);
    }

    /**
     * Make a GET request and return its HTML.
     *
     * @param string $path
     * @param array $query
     * @param array $options
     * @return TestHtmlDocument
     */
    public function getHtml(string $path, array $query = [], $options = []): TestHtmlDocument
    {
        $controller = $this->get($path, $query, $options);

        TestCase::assertIsString($this->lastOutput, "Control must output HTML");
        TestCase::assertNotEmpty($this->lastOutput, "Controller output must not be empty");
        $document = new TestHtmlDocument($this->lastOutput);
        return $document;
    }

    /**
     * Get the test HTML document from the last dispatch.
     *
     * This method is useful if you already dispatched to a controller and want to also do HTML assertions.
     *
     * @return TestHtmlDocument
     */
    public function getLastHtml(): TestHtmlDocument
    {
        TestCase::assertIsString($this->lastOutput, "Control must output HTML");
        $document = new TestHtmlDocument($this->lastOutput);
        return $document;
    }

    /**
     * Test whether or not there is a last HTML output.
     *
     * @return bool
     */
    public function hasLastHtml(): bool
    {
        return $this->lastOutput !== null;
    }

    /**
     * Make a GET request and return its decoded data array.
     *
     * @param string $path
     * @param array $query
     * @param array $options
     * @return Data
     */
    public function getJsonData(string $path, array $query = [], $options = []): Data
    {
        $options += [
            self::OPT_DELIVERY_TYPE => DELIVERY_TYPE_DATA,
            self::OPT_DELIVERY_METHOD => DELIVERY_METHOD_JSON,
            "decodeResponse" => true,
        ];

        $this->get($path, $query, $options);
        TestCase::assertIsString($this->lastOutput, "Controller must output HTML");
        if ($options["decodeResponse"]) {
            $response = json_decode($this->lastOutput, true);
            TestCase::assertNotNull($response, "The controller did not return valid JSON.");
        } else {
            $response = $this->lastOutput;
        }

        $data = $this->createData($response);

        return $data;
    }

    /**
     * Make a POST request.
     *
     * @param string $path
     * @param array $body
     * @param array $options
     * @return \Gdn_Controller
     */
    public function post(string $path, $body = [], array $options = []): \Gdn_Controller
    {
        return $this->request("POST", $path, $body, $options);
    }

    /**
     * Make a POST request and return its HTML.
     *
     * @param string $path
     * @param array $post
     * @param array $options
     * @return TestHtmlDocument
     */
    public function postHtml(string $path, array $post = [], $options = []): TestHtmlDocument
    {
        $controller = $this->post($path, $post, $options);

        return $this->getLastHtml();
    }

    /**
     * Do a post using the contents of the last request as a basis for the form values.
     *
     * @param array $bodyOverride
     * @param array $options
     * @return \Gdn_Controller
     */
    public function postBack($bodyOverride = [], array $options = []): \Gdn_Controller
    {
        TestCase::assertNotNull($this->getLastHtml(), "You must call get before you can postback.");
        $form = $this->getLastHtml()->assertCssSelectorExists("form", "There is no form on the page to post back to.");
        $action = (string) $form->getAttribute("action");
        $action = StringUtils::substringLeftTrim($action, \Gdn::request()->getRoot(), true);

        $post = $this->getLastHtml()->getFormValues();
        $post = array_replace($post, $bodyOverride);
        $r = $this->post($action, $post, $options);
        return $r;
    }

    /**
     * Do a post using the contents of the last request as a basis for the form values.
     *
     * @param array $bodyOverride
     * @param array $options
     * @return TestHtmlDocument
     */
    public function postBackHtml($bodyOverride = [], array $options = []): TestHtmlDocument
    {
        $controller = $this->postBack($bodyOverride, $options);

        return $this->getLastHtml();
    }

    /**
     * Make a POST request and return its decoded data array.
     *
     * @param string $path
     * @param array $post
     * @param array $options
     * @return mixed
     */
    public function postJsonData(string $path, array $post = [], $options = [])
    {
        $options += [
            self::OPT_DELIVERY_TYPE => DELIVERY_TYPE_DATA,
            self::OPT_DELIVERY_METHOD => DELIVERY_METHOD_JSON,
        ];

        $controller = $this->post($path, $post, $options);
        TestCase::assertIsString($this->lastOutput, "Controller must output HTML");
        $data = json_decode($this->lastOutput, true);
        TestCase::assertNotNull($data, "The controller did not return valid JSON.");

        $data = $this->createData($data);

        return $data;
    }

    /**
     * Create http response data from controller output.
     *
     * @param mixed $responseBody
     *
     * @return Data
     */
    private function createData($responseBody): Data
    {
        $data = new Data($responseBody, [], $this->lastHeaders);

        // Try to extract a status code.
        $status = $this->lastHeaders["Status"] ?? ($this->lastHeaders["status"] ?? null);
        if ($status !== null) {
            // Status will be a string status. Extract out the actual status.
            $statusCode = explode(" ", $status)[0];
            TestCase::assertIsNumeric($statusCode, "Controller returned an invalid HTTP status code: " . $statusCode);
            $data->setMeta("status", (int) $statusCode);
        }

        return $data;
    }

    /**
     * Assert that the last dispatched controller doesn't have any form errors.
     *
     * @param \Gdn_Controller|null $controller Optionally pass another controller to make this assertion.
     */
    public function assertNoFormErrors(\Gdn_Controller $controller = null): void
    {
        $controller = $controller ?? $this->lastController;
        TestCase::assertNotNull($controller, "The controller was not properly set to assert.");

        /** @var \Gdn_Form $form */
        $form = $controller->Form;
        TestCase::assertEmpty($form->validationResults(), \Gdn_Validation::resultsAsText($form->validationResults()));
    }

    /**
     * Assert that the form contains a given error message.
     *
     * @param string $partialMessage A partial error message that will be checked against the form's errors.
     */
    public function assertFormErrorMessage(string $partialMessage): void
    {
        $controller = $this->lastController;
        TestCase::assertNotNull($controller, "The controller was not properly set to assert.");

        /** @var \Gdn_Form $form */
        $form = $controller->Form;
        $message = \Gdn_Validation::resultsAsText($form->validationResults());
        TestCase::assertStringContainsString($partialMessage, $message);
    }

    /**
     * Assert that a particular form field has an error.
     *
     * @param string $name The name of the form field.
     */
    public function assertFormFieldError(string $name): void
    {
        $controller = $this->lastController;
        TestCase::assertNotNull($controller, "The controller was not properly set to assert.");

        /** @var \Gdn_Form $form */
        $results = $controller->Form->validationResults();
        TestCase::assertArrayHasKey($name, $results, "The form should have an error on the $name field.");
    }

    /**
     * Reset known static caches before making a request.
     *
     * This is to simulate a quasi-real request where you usually start with a clean slate. If you find static object
     * pollution to be hampering your tests then go ahead and add some resets here.
     */
    private function resetStatics()
    {
        \Gdn_Theme::resetSection();
        if (class_exists(\CategoryModel::class, false)) {
            \CategoryModel::reset();
        }
        if (class_exists(\DiscussionModel::class, false)) {
            \DiscussionModel::cleanForTests();
        }
    }

    /**
     * Whether or not to re-throw dispatcher exceptions.
     *
     * @return bool
     */
    public function getRethrowExceptions(): bool
    {
        return $this->rethrowExceptions;
    }

    /**
     * Set whether or not to rethrow dispatcher exceptions.
     *
     * @param bool $rethrowExceptions
     */
    public function setRethrowExceptions(bool $rethrowExceptions): void
    {
        $this->rethrowExceptions = $rethrowExceptions;
    }

    /**
     * @return string|null
     */
    public function getLastOutput(): ?string
    {
        return $this->lastOutput;
    }
}
