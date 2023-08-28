<?php

namespace App\Library;

abstract class RelationshipType
{
    /**
    * Ex - one employee has one gender
    *
    * Employee table          Gender table
    * |-genderId              |-id
    *
    * Employee Model          Gender Model
    * |-gender: HAS_ONE       |-employee: BELONGS_TO
    */
    const HAS_ONE = 'HAS_ONE'; // ONE_TO_ONE, on foreign key side
    const BELONGS_TO = 'BELONGS_TO'; // ONE_TO_ONE, on primary key side

    /**
    * Ex - one employee has many qualifications
    *
    * Employee table          Qualification table
    * |-id                     |-employeeId
    *
    * Employee Model          Qualification Model
    * |-gender: HAS_MANY       |-employee: BELONGS_TO_MANY
    */
    const HAS_MANY = 'HAS_MANY'; // ONE_TO_MANY, on primary key side
    const BELONGS_TO_MANY = 'BELONGS_TO_MANY'; // ONE_TO_MANY, on foreign key side

    /**
    * Ex - one employee has many competencies, and competency also having more than one employee
    *
    * Employee table          Competency table
    * |-competencyIds         |-id
    *
    * Employee Model                    Qualification Model
    * |-competency: HAS_MANY_TO_MANY    |-employee: 
    */
    const HAS_MANY_TO_MANY = 'HAS_MANY_TO_MANY'; // MANY_TO_MANY, on foreign key side
}
