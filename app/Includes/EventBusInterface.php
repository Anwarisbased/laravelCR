<?php

namespace App\Includes;

interface EventBusInterface
{
    public function listen(string $event, callable $callback): void;
    public function broadcast(object $event): void;
    public function dispatch(string $eventName, array $payload = []): void;
}