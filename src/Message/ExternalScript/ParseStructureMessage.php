<?php

namespace App\Message\ExternalScript;

class ParseStructureMessage
{
    public function __construct(private int $taskId) {}

    public function getTaskId(): int { return $this->taskId; }
}