<?php

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\DomainEvent\Service;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\UnitOfWork;
use GpsLab\Domain\Event\Aggregator\AggregateEvents;
use GpsLab\Domain\Event\Event;

class EventPuller
{
    /**
     * @param UnitOfWork $uow
     *
     * @return Event[]
     */
    public function pull(UnitOfWork $uow)
    {
        $events = [];

        $events = array_merge($events, $this->pullFromEntities($uow->getScheduledEntityDeletions()));
        $events = array_merge($events, $this->pullFromEntities($uow->getScheduledEntityInsertions()));
        $events = array_merge($events, $this->pullFromEntities($uow->getScheduledEntityUpdates()));

        // other entities
        foreach ($uow->getIdentityMap() as $entities) {
            $events = array_merge($events, $this->pullFromEntities($entities));
        }

        return $events;
    }

    /**
     * @param array $entities
     *
     * @return Event[]
     */
    private function pullFromEntities(array $entities)
    {
        $events = [];
        foreach ($entities as $entity) {
            // ignore Doctrine not initialized proxy classes
            // proxy class can't have a domain events
            if ((!($entity instanceof Proxy) || $entity->__isInitialized()) && $entity instanceof AggregateEvents) {
                $events = array_merge($events, $entity->pullEvents());
            }
        }

        return $events;
    }
}
