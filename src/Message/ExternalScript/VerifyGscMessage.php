<?php

namespace App\Message\ExternalScript;

class VerifyGscMessage
{
    public function __construct(private int $siteId) {}

    public function getSiteId(): int { return $this->siteId; }
}