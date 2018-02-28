<?php

namespace Ang3\Bundle\LoggableHistoryBundle\Helper;

/**
 * @author Joanis ROUANET
 */
class HistoryEvent
{
    /**
     * Event code.
     *
     * @var int
     */
    protected $code = 0;

    /**
     * Event log.
     *
     * @var AdvancedLog|null
     */
    protected $log = null;

    /**
     * Sets code.
     *
     * @param int $code
     *
     * @return HistoryEvent
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Gets code.
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets log.
     *
     * @param AdvancedLog|null $log
     *
     * @return HistoryEvent
     */
    public function setLog(AdvancedLog $log = null)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Gets log.
     *
     * @return AdvancedLog|null
     */
    public function getLog()
    {
        return $this->log;
    }
}
