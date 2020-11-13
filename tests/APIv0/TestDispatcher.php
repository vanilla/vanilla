<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

use Garden\Container\Container;
use Garden\EventManager;
use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\StringUtils;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\PrivateAccessTrait;

/**
 * A test dispatcher that dispatches to `Gdn_Controller` endpoints.
 */
class TestDispatcher {
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

    const OPT_THROW_FORM_ERRORS = 'throwFormErrors';
    const OPT_DELIVERY_METHOD = 'deliveryMethod';
    const OPT_DELIVERY_TYPE = 'deliveryType';
    const OPT_PERMANENT = 'permanent';

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

    /**
     * TestDispatcher constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Make a request to a controller and return it.
     *
     * @param string $method The request method, either GET or POST.
     * @param string $path The path of the request.
     * @param array $queryOrBody An array containing a parsed querystring or a post body.
     * @param array $options An array of additional options.
     * @return \Gdn_Controller
     */
    public function request(string $method, string $path, $queryOrBody = [], array $options = []): \Gdn_Controller {
        $options += [
            self::OPT_PERMANENT => true,
            self::OPT_DELIVERY_TYPE => DELIVERY_TYPE_VIEW,
            self::OPT_DELIVERY_METHOD => DELIVERY_METHOD_XHTML,
            self::OPT_THROW_FORM_ERRORS => true,
        ];
        $this->lastController = $this->lastOutput = null;

        TestCase::assertContains($options[self::OPT_DELIVERY_TYPE], self::ALLOWED_DELIVERY_TYPES);
        TestCase::assertContains($options[self::OPT_DELIVERY_METHOD], self::ALLOWED_DELIVERY_METHODS);

        $this->resetStatics();

        /** @var \Gdn_Dispatcher $dispatcher */
        $dispatcher = $this->container->get(\Gdn_Dispatcher::class);
        $dispatcher->setRethrowExceptions(true);
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

        $request = \Gdn_Request::create()->fromEnvironment()->setMethod($method)->setUrl($path);
        // Kludge due to a bug in the dispatcher not understanding extensions properly.
        if ($request->getExt()) {
            $request->setPath($request->getPathExt());
            $request->setExt('');
        }
        // Kludge due to the request not understanding roots.
        if ($request->getRoot() &&  (str_starts_with($request->getPath(), $request->getRoot().'/'))) {
            $path = StringUtils::substringLeftTrim($request->getPath(), $request->getRoot());
            $request->setPath($path);
        }

        if ($method === 'POST') {
            $session->validateTransientKey(true);
            $request->setRequestArguments(\Gdn_Request::INPUT_POST, $queryOrBody);
        } elseif (!empty($queryOrBody)) {
            $get = $request->getRequestArguments(\Gdn_Request::INPUT_GET);
            $get = array_replace($get, $queryOrBody);
            $request->setRequestArguments(\Gdn_Request::INPUT_GET, $get);
        }

        $fn = function ($sender, $args) {
            $this->lastController = $args['Controller'];
        };
        $events->bind('base_beforeControllerMethod', $fn);

        $ex = null;
        try {
            // Capture output.
            ob_start();
            $dispatcher->dispatch($request, $options[self::OPT_PERMANENT]);
            $output = ob_get_contents();
            $this->lastOutput = $output;
        } finally {
            ob_end_clean();
            \Gdn::request($oldRequest);
            $events->unbind('base_beforeControllerMethod', $fn);
        }

        if ($this->lastController === null) {
            throw new \Exception("The controller was not properly rendered.");
        }
        if ($options[self::OPT_THROW_FORM_ERRORS] && isset($this->lastController->Form) && $this->lastController->Form instanceof \Gdn_Form) {
            ModelUtils::validationResultToValidationException($this->lastController->Form, \Gdn::locale(), true);
        }

        return $this->lastController;
    }

    /**
     * Make a GET request.
     *
     * @param string $path
     * @param array $query
     * @param array $options
     * @return \Gdn_Controller
     */
    public function get(string $path, array $query = [], array $options = []): \Gdn_Controller {
        return $this->request('GET', $path, $query, $options);
    }

    /**
     * Make a GET request and return its HTML.
     *
     * @param string $path
     * @param array $query
     * @param array $options
     * @return TestHtmlDocument
     */
    public function getHtml(string $path, array $query = [], $options = []): TestHtmlDocument {
        $controller = $this->get($path, $query, $options);

        TestCase::assertIsString($this->lastOutput, 'Control must output HTML');
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
    public function getLastHtml(): TestHtmlDocument {
        TestCase::assertIsString($this->lastOutput, 'Control must output HTML');
        $document = new TestHtmlDocument($this->lastOutput);
        return $document;
    }

    /**
     * Make a GET request and return its decoded data array.
     *
     * @param string $path
     * @param array $query
     * @param array $options
     * @return false|mixed|string
     */
    public function getJsonData(string $path, array $query = [], $options = []) {
        $options += [
            self::OPT_DELIVERY_TYPE => DELIVERY_TYPE_DATA,
            self::OPT_DELIVERY_METHOD => DELIVERY_METHOD_JSON,
            "decodeResponse" => true,
        ];

        $this->get($path, $query, $options);
        TestCase::assertIsString($this->lastOutput, 'Controller must output HTML');
        if ($options["decodeResponse"]) {
            $response = json_decode($this->lastOutput, true);
            TestCase::assertNotNull($response, "The controller did not return valid JSON.");
        } else {
            $response = $this->lastOutput;
        }

        return $response;
    }

    /**
     * Make a POST request.
     *
     * @param string $path
     * @param array $body
     * @param array $options
     * @return \Gdn_Controller
     */
    public function post(string $path, $body = [], array $options = []): \Gdn_Controller {
        return $this->request('POST', $path, $body, $options);
    }

    /**
     * Make a POST request and return its HTML.
     *
     * @param string $path
     * @param array $post
     * @param array $options
     * @return TestHtmlDocument
     */
    public function postHtml(string $path, array $post = [], $options = []): TestHtmlDocument {
        $controller = $this->post($path, $post, $options);

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
    public function postJsonData(string $path, array $post = [], $options = []) {
        $options += [
            self::OPT_DELIVERY_TYPE => DELIVERY_TYPE_DATA,
            self::OPT_DELIVERY_METHOD => DELIVERY_METHOD_JSON,
        ];

        $controller = $this->post($path, $post, $options);
        TestCase::assertIsString($this->lastOutput, 'Controller must output HTML');
        $data = json_decode($this->lastOutput, true);
        TestCase::assertNotNull($data, "The controller did not return valid JSON.");

        return $data;
    }

    /**
     * Assert that the last dispatched controller doesn't have any form errors.
     *
     * @param \Gdn_Controller|null $controller Optionally pass another controller to make this assertion.
     */
    public function assertNoFormErrors(\Gdn_Controller $controller = null): void {
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
    public function assertFormErrorMessage(string $partialMessage): void {
        $controller = $this->lastController;
        TestCase::assertNotNull($controller, "The controller was not properly set to assert.");

        /** @var \Gdn_Form $form */
        $form = $controller->Form;
        $message = \Gdn_Validation::resultsAsText($form->validationResults());
        TestCase::assertStringContainsString($partialMessage, $message);
    }

    /**
     * Reset known static caches before making a request.
     *
     * This is to simulate a quasi-real request where you usually start with a clean slate. If you find static object
     * pollution to be hampering your tests then go ahead and add some resets here.
     */
    private function resetStatics() {
        \Gdn_Theme::resetSection();
        if (class_exists(\CategoryModel::class, false)) {
            \CategoryModel::reset();
        }
        if (class_exists(\DiscussionModel::class, false)) {
            \DiscussionModel::cleanForTests();
        }
    }
}
