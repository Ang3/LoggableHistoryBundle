<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Helper;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * @author Joanis ROUANET
 */
class AdvancedLog extends AbstractLogEntry
{
    /**
     * Object versioned data at this time.
     *
     * @var array
     */
    protected $streamData = [];

    //
    // Getters and setters
    //

    /**
     * Sets streamData.
     *
     * @param array $streamData
     *
     * @return AdvancedLog
     */
    public function setStreamData(array $streamData)
    {
        $this->streamData = $streamData;

        return $this;
    }

    /**
     * Gets streamData.
     *
     * @return array
     */
    public function getStreamData()
    {
        return $this->streamData;
    }

    //
    // Public methods
    //

    /**
     * Returns TRUE if the log is on create action.
     *
     * @return bool
     */
    public function isOnCreate()
    {
        return 'create' === $this->action;
    }

    /**
     * Returns TRUE if the log is on update action.
     *
     * @return bool
     */
    public function isOnUpdate()
    {
        return 'update' === $this->action;
    }

    /**
     * Returns TRUE if the log is on delete action.
     *
     * @return bool
     */
    public function isOnDelete()
    {
        return 'delete' === $this->action;
    }
}
