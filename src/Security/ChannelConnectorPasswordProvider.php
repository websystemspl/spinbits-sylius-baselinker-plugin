<?php

declare(strict_types=1);

namespace Spinbits\SyliusBaselinkerPlugin\Security;

use Sylius\Component\Channel\Context\ChannelContextInterface;

/**
 * Each BaseLinker shop connection has its own password, so a channel served on its own
 * domain can be connected as a separate shop. Channels without an entry in the map
 * use the default password.
 */
final class ChannelConnectorPasswordProvider implements ConnectorPasswordProviderInterface
{
    /**
     * @param array<string, string|null> $channelPasswords passwords indexed by channel code
     */
    public function __construct(
        private ChannelContextInterface $channelContext,
        private string $defaultPassword,
        private array $channelPasswords = []
    ) {
    }

    public function getPassword(): ?string
    {
        $channelCode = (string) $this->channelContext->getChannel()->getCode();

        // A channel listed with an empty password must not fall back to the default one,
        // otherwise another connection's password would grant access to its data.
        $password = array_key_exists($channelCode, $this->channelPasswords)
            ? $this->channelPasswords[$channelCode]
            : $this->defaultPassword;

        return null === $password || '' === $password ? null : $password;
    }
}
