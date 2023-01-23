<?php

namespace dnj\Invoice\Casts;

use dnj\Number\Contracts\INumber;
use dnj\Number\Number;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class DistributionPlan implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param string                              $value
     * @param array                               $attributes
     *
     * @return array<int,INumber>
     */
    public function get($model, $key, $value, $attributes)
    {
        $data = json_decode($value, true);
        foreach ($data as &$v) {
            $v = Number::fromInput($v);
        }
        return $data;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param array<int,INumber>                  $value
     * @param array                               $attributes
     *
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        foreach ($value as &$v) {
            $v = (string) $v;
        }
        return json_encode($value);
    }
}
