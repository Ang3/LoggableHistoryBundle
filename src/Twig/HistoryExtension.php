<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Twig;

use Twig_Extension;
use Twig_SimpleFilter;
use Ang3\Bundle\LoggableHistoryBundle\Manager\LogEntryManager;

/**
 * Dossier extensions.
 *
 * @author Joanis Rouanet
 */
class HistoryExtension extends Twig_Extension
{
    /**
     * Doctrine entity manager.
     *
     * @var LogEntryManager
     */
    protected $logEntryManager;

    /**
     * Constructor of the extension.
     *
     * @param LogEntryManager $logEntryManager
     */
    public function __construct(LogEntryManager $logEntryManager)
    {
        $this->logEntryManager = $logEntryManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('entityAdvancedLogs', array($this, 'getEntityAdvancedLogs')),
            new Twig_SimpleFilter('entitiesAdvancedLogs', array($this, 'getEntitiesAdvancedLogs')),
        ];
    }

    /**
     * Returns the history of an entity.
     *
     * @param object $entity
     *
     * @return \Ang3\Bundle\LoggableHistoryBundle\Helper\AdvancedLog[]
     */
    public function getEntityAdvancedLogs($entities)
    {
        return $this->logEntryManager()->getAdvancedLogs($entity);
    }

    /**
     * Returns the history of entities.
     *
     * @param Collection|array|object $entities
     *
     * @return \Ang3\Bundle\LoggableHistoryBundle\Helper\AdvancedLog[]
     */
    public function getEntitiesAdvancedLogs($entities)
    {
        return $this->logEntryManager()->getMergedAdvancedLogs($entities);
    }

    /**
     * Retourne le nom de l'extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'ang3_loggable_history.history_extension';
    }
}
