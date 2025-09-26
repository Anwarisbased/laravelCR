<?php

namespace App\Includes;

class SimpleEventBus implements EventBusInterface
{
    private array $listeners = [];

    public function listen(string $event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $callback;
    }

    public function broadcast(object $event): void
    {
        $eventName = get_class($event);
        $this->dispatchEvent($eventName, $event);
    }

    public function dispatch(string $eventName, array $payload = []): void
    {
        $this->dispatchEvent($eventName, $payload);
    }

    private function dispatchEvent(string $eventName, $data): void
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                call_user_func($listener, $data, $eventName);
            }
        }
    }
}