<?php

namespace Dynamic\Shopify\Traits;

use Osiset\BasicShopifyAPI\ResponseAccess;

trait OffsetValidator
{
    /**
     * @param array $data
     *
     * @return bool
     */
    public static function hasData($response)
    {
        return $response && array_key_exists('body', $response) && $response['body']->offsetExists('data');
    }

    /**
     * @param array $response
     *
     * @return array|ResponseAccess|bool
     */
    public function getData($response)
    {
        if (!static::hasData($response)) {
            return false;
        }
        return $response['body']->data;
    }

    /**
     * @param array|ResponseAccess $data
     * @param array|string $offsets
     *
     * @return bool
     */
    public static function offsetExists($data, $offsets)
    {
        if (!is_array($offsets)) {
            $offsets = [$offsets];
        }

        $currentOffset = array_shift($offsets);
        if (!$data->offsetExists($currentOffset)) {
            return false;
        }

        if (count($offsets)) {
            return static::offsetExists($data->{$currentOffset}, $offsets);
        }

        return true;
    }
}
