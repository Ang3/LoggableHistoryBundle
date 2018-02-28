<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Helper;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Joanis ROUANET
 */
class History
{
    /**
     * Events list.
     *
     * @var array
     */
    protected $events;

    /**
     * Constructor of the history.
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /**
     * Adds event.
     *
     * @param HistoryEvent $event
     *
     * @return History
     */
    public function addEvent(HistoryEvent $event)
    {
        if (!$this->events->containsKey($date = $event->getLog()->getLoggedAt()->format('Y-m-d H:i:s'))) {
            $this->events->set($date, new ArrayCollection());
        }

        $this->events->get($date)->add($event);

        return $this;
    }

    /**
     * Removes an event.
     *
     * @param HistoryEvent $event
     *
     * @return HistoryEvent
     */
    public function removeEvent(HistoryEvent $event)
    {
        $this->events->removeElement($event);

        return $this;
    }

    /**
     * Removes event by date.
     *
     * @param DateTime $date
     *
     * @return History
     */
    public function removeEventsByDate(DateTime $date)
    {
        $this->events->remove($event->getLog()->getLoggedAt()->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * Removes event by date and code(s).
     *
     * @param DateTime $date
     *
     * @return History
     */
    public function removeEventsByDateAndCode(DateTime $date, $codes)
    {
        if ($this->events->containsKey($date = $event->getLog()->getLoggedAt()->format('Y-m-d H:i:s'))) {
            foreach ($this->events->get($date) as $key => $event) {
                $codes = $codes ? (is_array($codes) ? $codes : [$codes]) : [];

                foreach ($codes as $code) {
                    if ($code === $event->getCode()) {
                        unset($this->events[$date][$key]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Gets events.
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events->toArray();
    }

    //
    // Public methods
    //

    /**
     * Sorts grouped events by with closure.
     *
     * @param Closure $f
     *
     * @return History
     */
    public function sortGroupedEvents(Closure $f)
    {
        // Pour chaque date d'évènements groupés
        foreach ($this->events as $date => $events) {
            // Récupération en tableau
            $events = $events->toArray();

            // Tri des logs via la closure
            usort($events, $f);

            // Ré-enregistrement de la collection
            $this->events->set($date, new ArrayCollection($events));
        }

        // Retour de l'historique
        return $this;
    }

    /**
     * Reverses order of events dates.
     *
     * @return History
     */
    public function reverse()
    {
        // Ré-enregistrement de la collection
        $this->events = new ArrayCollection(array_reverse($this->events->toArray()));

        // Retour de l'historique
        return $this;
    }
}
