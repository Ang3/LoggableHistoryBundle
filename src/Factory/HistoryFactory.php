<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Factory;

use Exception;
use Ang3\Bundle\LoggableHistoryBundle\Helper\History;
use Ang3\Bundle\LoggableHistoryBundle\Helper\HistoryEvent;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @author Joanis ROUANET
 */
class HistoryFactory
{
    /**
     * History factory name.
     *
     * @var string
     */
    protected $name;

    /**
     * History events.
     *
     * @var array
     */
    protected $events;

    /**
     * Symfony expression language component.
     *
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * Constructor of the factory.
     *
     * @param string     $name
     * @param array|null $events
     */
    public function __construct($name, array $events = [])
    {
        $this->name = (string) $name;
        $this->events = $events ?: [];
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * Creates an history from logs.
     *
     * @param Collection|array $logs
     * @param int|null         $sort
     *
     * @return History
     */
    public function create($logs, $sort = SORT_ASC)
    {
        // Si on a un objet en paramètre
        if (is_object($logs)) {
            // Si c'est une collection
            if ($logs instanceof Collection) {
                // Convertion en tableau
                $logs = $logs->toArray();
            // Sinon si c'est un log Gedmo
            } elseif ($logs instanceof AbstractLogEntry) {
                // Mise en tableau
                $logs = [$log];
            } else {
                throw new Exception('Unexpected object of class "%s"', ClassUtils::getClass($logs));
            }
        }

        // Si on a toujours pas un tableau
        if (!is_array($logs)) {
            throw new Exception('Unexpected type "%s"', gettype($logs));
        }

        // Tri des logs par date
        usort($logs, function ($a, $b) {
            return $a->getLoggedAt() < $b->getLoggedAt() ? 1 : -1;
        });

        // On inverse l'ordre des logs pour l'hydratation de l'historique
        $logs = array_reverse($logs);

        // Création de l'historique
        $history = new History();

        // Pour chaque log
        foreach ($logs as $log) {
            // Pour chaque paramètre par code d'évènement
            foreach ($this->events as $code => $parameters) {
                // Si on a une classe de log et que le log n'est pas une instance spécifiée dans la config
                if ($parameters['log_classes'] && !$this->checkLogClass($log, $parameters['log_classes'])) {
                    // Log suivant
                    continue;
                }

                // Si la classe cible du log n'est pas une classe spécifiée dans la config
                if (!$this->checkObjectClass($log, $parameters['subjects'])) {
                    // Log suivant
                    continue;
                }

                // Si l'action du log n'est pas spécifié dans la config
                if (!in_array($log->getAction(), $parameters['actions'])) {
                    // Log suivant
                    continue;
                }

                // Si on a des champs filtrants et que le log contient au moins un des champs
                if ($parameters['fields'] && !$this->checkFields($log, $parameters['fields'])) {
                    // Log suivant
                    continue;
                }

                // Si on a une expression de validation
                if ($parameters['validation']) {
                    try {
                        // Evaluation de l'expression de validation
                        $validation = (bool) $this->expressionLanguage->evaluate($parameters['validation'], [
                            'stream' => (object) $log->getStreamData(),
                            'log' => (object) $log->getData(),
                            'keys' => (object) array_keys($log->getData()),
                        ]);
                    } catch (Exception $e) {
                        throw new Exception(sprintf('Unable to validate expression of event code "%s" (%s)', $code, $e->getMessage()), 0, $e);
                    }

                    // Si pas de validation
                    if (!$validation) {
                        // Log suivant
                        continue;
                    }
                }

                // Création, identification et enregistrement de l'évènement dans l'historique
                $history->addEvent($this->createHistoryEvent($code, $log));
            }
        }

        // Récupération des évènements pour la closure
        $events = $this->events;

        // On trie les évènements groupés selon l'ordre des évènements dans la config
        $history->sortGroupedEvents(function ($a, $b) use ($events) {
            // Si les dates sont identiques
            if ($a->getLog()->getLoggedAt() == $b->getLog()->getLoggedAt()) {
                // Retour de la comparaison de la clé des évènements dans la config
                // (De façon à ce que le premier soit prioritaire, puis le second, etc.)
                return array_search($b->getCode(), array_keys($events)) - array_search($a->getCode(), array_keys($events));
            }

            // Retour de la comparaison des dates
            return $a->getLog()->getLoggedAt() < $b->getLog()->getLoggedAt() ? -1 : 1;
        });

        // Si on demande un sens inverse
        if (SORT_DESC === $sort) {
            // Inversion des dates pour un ordre anti-chronologique
            $history->reverse();
        }

        // Retour de l'historique
        return $history;
    }

    /**
     * Créer un évènement d'historique.
     *
     * @param scalar           $code
     * @param AbstractLogEntry $log
     *
     * @return HistoryEvent
     */
    public function createHistoryEvent($code, AbstractLogEntry $log)
    {
        // Construction de l'évènement
        $event = new HistoryEvent();

        // Retour de l'évènement configuré
        return $event
            ->setCode($code)
            ->setLog($log)
        ;
    }

    /**
     * Returns TRUE if the log is an instance of at least one given class.
     *
     * @param AbstractLogEntry $log
     * @param array            $classes
     *
     * @return bool
     */
    protected function checkLogClass(AbstractLogEntry $log, array $classes = [])
    {
        // Pour chaque classe
        foreach ($classes as $class) {
            // Si l'objet est une instance de la classe
            if ($object instanceof $class) {
                // Retour positif
                return true;
            }
        }

        // Retour négatif par défaut
        return false;
    }

    /**
     * Returns TRUE if the object class of log is an instance of at least one given class.
     *
     * @param AbstractLogEntry $log
     * @param array            $classes
     *
     * @return bool
     */
    protected function checkObjectClass(AbstractLogEntry $log, array $classes = [])
    {
        // Pour chaque classe
        foreach ($classes as $class) {
            // Si la classe de l'objet est égale à la classe courante
            if ($log->getObjectClass() === $class) {
                // Retour positif
                return true;
            }
        }

        // Retour négatif par défaut
        return false;
    }

    /**
     * Returns TRUE if log fields contains at least one of given fields.
     *
     * @param AbstractLogEntry $log
     * @param array            $fields
     *
     * @return bool
     */
    protected function checkFields(AbstractLogEntry $log, array $fields = [])
    {
        // Pour chaque champ autorisé
        foreach ($fields as $field) {
            // Si les champs modifiés du log contienne le champ courant
            if (in_array($field, array_keys($log->getData()))) {
                // Retour positif
                return true;
            }
        }

        // Retour négatif par défaut
        return false;
    }
}
