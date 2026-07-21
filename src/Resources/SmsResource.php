<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;

/**
 * Resource class for SMS integration helpers.
 *
 * Currently exposes the relay-reconciliation endpoint used by an integrator to keep its local
 * inbound-relay allowlist in sync with the numbers the Hub expects to receive replies for.
 */
class SmsResource extends BaseResource
{
    /**
     * List the E.164 numbers the Hub expects relayed inbound SMS for.
     *
     * Returns the numbers registered for the authenticated tenant's effective SMS owner (its
     * group when grouped, else the tenant itself). Intended as a periodic reconciliation source
     * for an integrator's local relay allowlist, not a per-message lookup.
     *
     * @return string[] The active E.164 phone numbers
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function relayedNumbers(): array
    {
        $response = $this->request('GET', 'api/v1/sms/relayed-numbers');

        return $response['numbers'] ?? [];
    }
}
