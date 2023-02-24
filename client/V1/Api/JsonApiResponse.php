<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Api;

use Hydrawiki\Reverb\Client\V1\Exceptions\DocumentMissing;

class JsonApiResponse
{
    /**
     * Response HTTP Status Code.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Optional Document included in the Response.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Api\Document|null
     */
    protected $document;

    /**
     * Constructs a new JSON API Response.
     *
     * @param int                                           $statusCode
     * @param \Hydrawiki\Reverb\Client\V1\Api\Document|null $document
     */
    public function __construct(int $statusCode, ?Document $document = null)
    {
        $this->statusCode = $statusCode;
        $this->document = $document;
    }

    /**
     * Does the response have a Document?
     *
     * @return bool
     */
    public function hasDocument(): bool
    {
        return ! is_null($this->document);
    }

    /**
     * Get the response Document.
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\Document
     */
    public function document(): Document
    {
        if (! $this->hasDocument()) {
            throw DocumentMissing::fromResponse();
        }

        return $this->document;
    }

    /**
     * Is this a successful response to an Index request?
     *
     * @return bool
     */
    public function isSuccessfulIndex(): bool
    {
        return $this->isStatusCode([200]) && $this->hasDocument() && $this->document()->isMany();
    }

    /**
     * Is this a successful response to a Read request?
     *
     * @return bool
     */
    public function isSuccessfulRead(): bool
    {
        return $this->isStatusCode([200]) && $this->hasDocument() && $this->document()->isOne();
    }

    /**
     * Is this a successful response to a Create request?
     *
     * @return bool
     */
    public function isSuccessfulCreate(): bool
    {
        return $this->isStatusCode([201]) && $this->hasDocument() && $this->document()->isOne();
    }

    /**
     * Is this a successful response to an Update request?
     *
     * @return bool
     */
    public function isSuccessfulUpdate(): bool
    {
        return $this->isSuccessfulUpdateWithDocument() OR $this->isSuccessfulUpdateWithoutDocument();
    }

    /**
     * Get the HTTP Status Code of the response.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Is this a successful response to an Update request with a document?
     *
     * @return bool
     */
    protected function isSuccessfulUpdateWithDocument(): bool
    {
        return $this->isStatusCode([200]) && $this->hasDocument() && $this->document()->isOne();
    }

    /**
     * Is this a successful response to an Update request without a document?
     *
     * @return bool
     */
    protected function isSuccessfulUpdateWithoutDocument(): bool
    {
        return $this->isStatusCode([204]) && ! $this->hasDocument();
    }

    /**
     * Does the HTTP Status Code match any of these status codes?
     *
     * @param array $statusCodes
     *
     * @return bool
     */
    protected function isStatusCode(array $statusCodes): bool
    {
        return in_array($this->statusCode, $statusCodes);
    }
}
