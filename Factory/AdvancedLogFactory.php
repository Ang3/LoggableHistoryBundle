<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Factory;

use DateTime;
use Ang3\Bundle\LoggableHistoryBundle\Helper\AdvancedLog;
use Ang3\Common\Reflection\ObjectReflector;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * @author Joanis ROUANET
 */
class AdvancedLogFactory
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Constructor of the factory.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Creates an advanced log.
     *
     * @param scalar|null $id
     * @param string      $objectClass
     * @param scalar      $objectId
     * @param DateTime    $loggedAt
     * @param string      $action
     * @param int         $version
     * @param array       $data
     * @param array       $streamData
     * @param string|null $username
     *
     * @return AdvancedLog
     */
    public function create($id = 0, $objectClass, $objectId, DateTime $loggedAt, $action, $version = 1, array $data = [], array $streamData = [], $username = null)
    {
        // Création du log avancé
        $advancedLog = new AdvancedLog();

        // Décoration du helper
        $logReflector = new ObjectReflector($advancedLog);

        // Hydratation
        $logReflector->id = $id;
        $logReflector->objectClass = $objectClass;
        $logReflector->objectId = $objectId;
        $logReflector->loggedAt = $loggedAt;
        $logReflector->action = $action;
        $logReflector->version = $version;
        $logReflector->data = $data;
        $logReflector->streamData = $streamData;
        $logReflector->username = $username;

        // Retour du log avancé
        return $advancedLog;
    }

    /**
     * Creates an advanced lof of an entity from a log.
     *
     * @param object           $entity
     * @param AbstractLogEntry $log
     *
     * @return AdvancedLog
     */
    public function createFromLog(AbstractLogEntry $log, array $streamData = [])
    {
        // Initialisation des données formatées
        $data = [];

        // Pour chaque données
        foreach ($log->getData() as $propertyName => $newValue) {
            // Initialisation du changement
            $changeSet = [
                'old' => null,
                'new' => $newValue,
            ];

            // Si une ancienne valuer existe
            if (isset($streamData[$propertyName])) {
                // Enregistrement de l'ancienne valeur
                $changeSet['old'] = $streamData[$propertyName];
            }

            // Si la valeur n'a pas changé
            if ($changeSet['old'] === $changeSet['new']) {
                // Propriété suivante
                continue;
            }

            // Enregistrement des changements
            $data[$propertyName] = $changeSet;

            // Enregistrement des données actuelles comme anciennes données
            $streamData[$propertyName] = $newValue;
        }

        // Retour de la création du log avancé
        return $this->create(
            $log->getId(),
            $log->getObjectClass(),
            $log->getObjectId(),
            $log->getLoggedAt(),
            $log->getAction(),
            $log->getVersion(),
            $data,
            $streamData,
            $log->getUsername()
        );
    }
}
