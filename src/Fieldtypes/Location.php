<?php

declare(strict_types=1);

namespace Ethernick\ActivityPubPlaces\Fieldtypes;

use Statamic\Fields\Fieldtype;

class Location extends Fieldtype
{
    protected $icon = 'location';

    /**
     * The blank/default value.
     *
     * @return array
     */
    public function defaultValue()
    {
        return [
            'osm_type' => null,
            'osm_id' => null,
            'latitude' => null,
            'longitude' => null,
            'address' => null,
            'title' => null,
        ];
    }

    /**
     * Pre-process the data before it's sent to the Vue component.
     *
     * @param mixed $data
     * @return array
     */
    public function preProcess($data)
    {
        return array_merge($this->defaultValue(), (array) $data);
    }

    /**
     * Process the data before it's saved.
     *
     * @param mixed $data
     * @return array
     */
    public function process($data)
    {
        return $data;
    }
}
