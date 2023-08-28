<?php

namespace App\Library\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed insert($model, $data, $fetch = false)
 * @method static mixed updateById(JsonModel $model, $id, $data, $fetch = false)
 * @method static mixed deleteById($model, $id)
 * @method static mixed getById($model, $id, $columns = ['*'])
 * @method static mixed getAll($model, $columns = ['*'])
 * @method static \Illuminate\Support\Facades\DB getFacade()
 *
 * @see \App\Library\Store
 */
class Store extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'store';
    }
}
