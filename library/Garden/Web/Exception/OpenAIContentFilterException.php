<?php

namespace Garden\Web\Exception;

/**
 * Represents an exception triggered by the OpenAI content policy.
 */
class OpenAIContentFilterException extends ClientException
{
    /**
     * Construct the exception.
     *
     * @param string|bool $filterName
     * @param $code
     * @param $context
     * @param \Throwable|null $previous
     * @return void
     */
    public function construct(string|bool $filterName, $code, $context = [], ?\Throwable $previous = null)
    {
        $message = "The content filter for $filterName has blocked the content.";
        parent::__construct($message, $code, $context, $previous);
    }
}
