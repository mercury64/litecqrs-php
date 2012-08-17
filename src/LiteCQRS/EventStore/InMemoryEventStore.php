<?php

namespace LiteCQRS\EventStore;

use LiteCQRS\Bus\EventMessageBus;
use LiteCQRS\DomainEvent;

/**
 * In Memory Event store for events.
 *
 * Iterates and handles all events when {@see commit()} operation is called and
 * directly passes them to the event message bus. Does not perform any
 * persistence operations on events.
 */
class InMemoryEventStore implements EventStoreInterface
{
    protected $events          = array();
    protected $seenEvents;
    protected $eventMessageBus;

    public function __construct(EventMessageBus $messageBus)
    {
        $this->eventMessageBus = $messageBus;
        $this->seenEvents      = new \SplObjectStorage();
    }

    public function store(DomainEvent $event)
    {
        if ($this->seenEvents->contains($event)) {
            return;
        }

        $this->seenEvents->attach($event);
        $this->events[] = $event;
    }

    public function beginTransaction()
    {
        if ($this->events) {
            throw new \RuntimeException("There are still events on stack, cannot start new transaction. Commit first!");
        }
        $this->events = array();
    }

    public function rollback()
    {
        $this->events = array();
    }

    public function commit()
    {
        $events = $this->sort($this->events);
        $this->events = array();

        foreach ($events as $event) {
            $this->eventMessageBus->handle($event);
        }
    }

    protected function sort($events)
    {
        return $events;
    }
}

