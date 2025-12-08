<?php
// src/Message/CheckSiteMessage.php
namespace App\Message\ExternalScript;

class CheckSiteMessage
{
    public function __construct(
        private int $siteId
    ) {}

    public function getSiteId(): int
    {
        return $this->siteId;
    }
}