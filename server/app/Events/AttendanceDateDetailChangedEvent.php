<?php

namespace App\Events;

class AttendanceDateDetailChangedEvent extends Event
{

    public $dataSet;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($dataSet)
    {
        $this->dataSet = $dataSet;
    }
}
