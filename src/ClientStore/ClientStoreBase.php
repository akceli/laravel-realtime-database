<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use App\ClientStore\ClientStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class ClientStoreBase
{
    public static function getProperties(int $store_id): array
    {
        $class = new ReflectionClass(static::class);
        $propertyMethods = collect($class->getMethods(ReflectionMethod::IS_STATIC))
            ->filter(fn(ReflectionMethod $method) => Str::endsWith($method->getName(), 'Property'));

        $properties = [];
        foreach ($propertyMethods as $method) {
            $method = $method->getName();
            $property = substr($method, 0, -8);
            $class = static::class;
            $store = $class::$method($store_id);
            $properties[$property] = $store;
        }

        return $properties;
    }

    /**
     * @param int $channel_id
     * @param $callback
     * @param null $default
     * @return ClientStorePropertyRaw|ClientStorePropertyInterface
     */
    public static function raw(int $channel_id, $callback, $default = null): ClientStorePropertyRaw
    {
        return new ClientStorePropertyRaw($channel_id, self::getStore(), self::getProperty(), $callback, $default);
    }

    /**
     * @param int $channel_id
     * @param $builder
     * @param string $resource
     * @return ClientStorePropertyRaw|ClientStorePropertyInterface
     */
    public static function single(int $channel_id, $builder, string $resource): ClientStorePropertySingle
    {
        return new ClientStorePropertySingle($channel_id, self::getStore(), self::getProperty(), $builder, $resource);
    }

    /**
     * @param int $channel_id
     * @param $builder
     * @param string|null $resource
     * @param int $size
     * @return ClientStorePropertySingle
     */
    public static function collection(int $channel_id, $builder, string $resource = null, int $size = 50): ClientStorePropertyCollection
    {
        return new ClientStorePropertyCollection($channel_id, self::getStore(), self::getProperty(), $builder, $resource, $size);
    }
    
    private static function getStore(): string
    {
        $parts = explode('\\', static::class);
        $store = array_pop($parts);
        return Str::camel(substr($store, 0, -5));
    }

    private static function getProperty(): string
    {
        return substr((new \Exception())->getTrace()[2]['function'], 0, -8);
    }
}