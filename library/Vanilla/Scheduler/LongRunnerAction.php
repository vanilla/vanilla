<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\Web\RequestInterface;
use Vanilla\Web\SystemTokenUtils;

/**
 * Parameters to run a long-runner action.
 */
final class LongRunnerAction
{
    /**
     * Special arg that represents the previous total of a progressable action.
     */
    public const OPT_PREVIOUS_TOTAL = "previousProgressTotal";

    /** @var string */
    private $className;

    /** @var string */
    private $method;

    /** @var array */
    private $args;

    /** @var array */
    private $options;

    /**
     * Parameters for calling a long-runner action.
     *
     * @param string $className The class of the method. Must implement SystemCallableInterface.
     * @param string $method The method to call.
     * @param array $args The arguments to pass to the method.
     * @param array $options See LongRunner::OPT_* options.
     */
    public function __construct(string $className, string $method, array $args, array $options = [])
    {
        $this->className = $className;
        $this->method = $method;
        $this->args = $args;
        $this->options = $options;
    }

    /**
     * Encode a JWT with parameters for the next iteration of an action.
     *
     * @param SystemTokenUtils $tokenUtils
     * @param string $className
     * @param string $method
     * @param array $args
     * @param array $options
     **
     * @return string A JWT of the next job payload.
     */
    public static function makeCallbackPayload(
        SystemTokenUtils $tokenUtils,
        string $className,
        string $method,
        array $args,
        array $options
    ): string {
        $payload = [
            "class" => $className,
            "method" => $method,
            "args" => $args,
            "options" => $options,
        ];
        $jwt = $tokenUtils->encode($payload);

        return $jwt;
    }

    /**
     * Encode a JWT with parameters for the next iteration of the action.
     *
     * @param SystemTokenUtils $tokenUtils
     *
     * @return string A JWT of the next job payload.
     */
    public function asCallbackPayload(SystemTokenUtils $tokenUtils): string
    {
        return self::makeCallbackPayload($tokenUtils, $this->className, $this->method, $this->args, $this->options);
    }

    /**
     * Deocde a JWT into params for the next iteration of the action.
     *
     * @param string $jwt A JWT of the next action payload.
     * @param SystemTokenUtils $tokenUtils
     * @param RequestInterface $request
     *
     * @return LongRunnerAction
     */
    public static function fromCallbackPayload(
        string $jwt,
        SystemTokenUtils $tokenUtils,
        RequestInterface $request
    ): LongRunnerAction {
        $decoded = $tokenUtils->decode($jwt, $request)[SystemTokenUtils::CLAIM_REQUEST_BODY];
        return new LongRunnerAction(
            $decoded["class"],
            $decoded["method"],
            $decoded["args"] ?? [],
            $decoded["options"] ?? []
        );
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Apply next args to the action.
     *
     * @param LongRunnerNextArgs $args
     *
     * @return $this
     */
    public function applyNextArgs(LongRunnerNextArgs $args): LongRunnerAction
    {
        $this->args = $args->getNextArgs();
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
