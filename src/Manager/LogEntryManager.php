<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Manager;

use Ang3\Bundle\LoggableHistoryBundle\Factory\AdvancedLogFactory;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Mapping\Annotation\Loggable;

/**
 * @author Joanis ROUANET
 */
class LogEntryManager
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Doctrine annotation reader.
     *
     * @var Reader
     */
    protected $annotationReader;

    /**
     * Constructor of the resolver.
     *
     * @param EntityManagerInterface $entityManager
     * @param Reader                 $annotationReader
     * @param AdvancedLogFactory     $advancedLogFactory
     */
    public function __construct(EntityManagerInterface $entityManager, Reader $annotationReader, AdvancedLogFactory $advancedLogFactory)
    {
        $this->entityManager = $entityManager;
        $this->annotationReader = $annotationReader;
        $this->advancedLogFactory = $advancedLogFactory;
    }

    /**
     * Returns the log class of an entity.
     *
     * @param object|string $entity
     *
     * @throws LogicException when no "loggable" annotation configured for the entity
     *
     * @return string
     */
    public function resolveLogClass($entity)
    {
        // Si l'entité est une classe de log
        if (is_object($entity) && $entity instanceof AbstractLogEntry) {
            // Retour de la classe de l'entité
            return ClassUtils::getClass($entity);
        }

        // Récupération de la réflection de la classe
        $entityReflectionClass = $this->entityManager
			->getClassMetadata(is_object($entity) ? ClassUtils::getClass($entity) : (string) $entity)
			->getReflectionClass()
		;

        // Tentative de récuépration d'une annotation de log
        $loggableAnnotation = $this->annotationReader->getClassAnnotation($entityReflectionClass, Loggable::class);

        // Si pas d'annotation
        if (!$loggableAnnotation) {
            throw new LogicException(sprintf('No loggable annotation configured for the entity class "%s".', $entityReflectionClass->getName()), 1);
        }

        // Retour de la classe configurée ou celle par défaut
        return $loggableAnnotation->logEntryClass ?: LogEntry::class;
    }

    /**
     * Returns logs of an entity.
     *
     * @param object $entity
     *
     * @return AbstractLogEntry[]
     */
    public function getLogs($entity)
    {
        return $this->entityManager
            ->getRepository($this->resolveLogClass($entity))
            ->getLogEntries($entity)
        ;
    }

    /**
     * Returns advanced logs of an entity.
     *
     * @param object $entity
     *
     * @return AdvancedLog[]
     */
    public function getAdvancedLogs($entity)
    {
        // Récupération des logs de l'entité
        $logs = $this->getLogs($entity);

        // Inversement des logs
        $logs = $this->sortLogsByDate($logs);

        // Initialisation des variables necessaires dans la boucle
        list($advancedLogs, $streamData) = [[], []];

        // Pour chaque log
        foreach ($logs as $log) {
            // Si ce n'est pas un log ou qu'il n'a pas de données
            if (!$log instanceof AbstractLogEntry || !is_array($log->getData())) {
                // Log suivant
                continue;
            }

            // Création du log avancé
            $advancedLog = $this->advancedLogFactory->createFromLog($log, $streamData);

            // Mise-à-jour des données du flux
            $streamData = $advancedLog->getStreamData();

            // Enregistrement du log avancé dans les logs avancés
            $advancedLogs[] = $advancedLog;
        }

        // Retour des logs inversés
        return $this->sortLogsByDate($advancedLogs, SORT_DESC);
    }

    /**
     * Gets merged advanced logs from entities.
     *
     * @param array $entities
     *
     * @return AdvancedLog[]
     */
    public function getMergedAdvancedLogs(array $entities)
    {
        // Initialisation des logs fusionnés
        $mergedLogs = [];

        // Pour chaque entité
        foreach ($entities as $entity) {
            $mergedLogs = array_merge($mergedLogs, array_values($this->getAdvancedLogs($entity)));
        }

        // Retour des logs fusionnés
        return $mergedLogs;
    }

    /**
     * Sorts basic or advanced logs.
     *
     * @param array $logs
     * @param int   $sort
     *
     * @return array
     */
    public function sortLogsByDate(array $logs, $sort = SORT_ASC)
    {
        // Tri des logs par date
        usort($logs, function ($a, $b) use ($sort) {
            $result = SORT_ASC === $sort ? 1 : -1;

            return $a->getLoggedAt() < $b->getLoggedAt() ? $result * (-1) : $result;
        });

        // Retour des logs triés
        return $logs;
    }
}
