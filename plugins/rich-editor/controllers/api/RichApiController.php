<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Formatting\FormatService;

/**
 * API Controller for the Rich Editor endpoint.
 */
class RichApiController extends AbstractApiController {

    /** @var FormatService */
    private $formatService;

    /**
     * @param FormatService $formatService
     */
    public function __construct(FormatService $formatService) {
        $this->richFormat = $formatService;
    }

    /**
     * Create a rich-compatible HTML representation of a string for quoting.
     *
     * @param array $body Body contents of the request.
     * @return array
     * @throws \Garden\Schema\ValidationException If validation of input or output fails.
     * @throws \Vanilla\Exception\PermissionException If the user does not have sign-in permissions.
     */
    public function post_quote(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'body:s' => 'Raw post text to render as a rich post quote.',
            'format' => new \Vanilla\Models\FormatSchema(true),
        ], 'in')->setDescription('Create a rich-compatible HTML representation of a string for quoting.');
        $out = $this->schema([
            'quote:s' => 'A quoted representation of the text, rendered as HTML.',
        ], 'out');

        $body = $in->validate($body);

        $quote = [
            'quote' => $this->formatService->renderQuote($body['body'], $body['format']),
        ];
        $result = $out->validate($quote);
        return $result;
    }
}
