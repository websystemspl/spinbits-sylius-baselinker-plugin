<?php

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Security;

interface ConnectorPasswordProviderInterface
{
    /**
     * Returns the password BaseLinker must send for the current request,
     * or null when no password is configured (the connector then rejects every request).
     */
    public function getPassword(): ?string;
}
