<?php

namespace App\Message\ExternalScript;

class AddCloudflareMessage
{
    public function __construct(private int $siteId) {}

    public function getSiteId(): int { return $this->siteId; }
}