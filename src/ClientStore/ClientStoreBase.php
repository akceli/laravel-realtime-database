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

    public static function raw($callback, $default = null)
    {
        return new ClientStorePropertyRaw(self::getStore(), self::getProperty(), $callback, $default);
    }

    public static function single($builder, string $resource)
    {
        return new ClientStorePropertySingle(self::getStore(), self::getProperty(), $builder, $resource);
    }

    public static function collection($builder, string $resource = null, int $size = 50)
    {
        return new ClientStorePropertyCollection(self::getStore(), self::getProperty(), $builder, $resource, $size);
    }

    public static function singleFresh($builder, string $resource)
    {
        return new ClientStorePropertySingle(self::getStore(), self::getProperty(), $builder, $resource);
    }

    public static function collectionFresh($builder, string $resource, int $size = 50)
    {
        return new ClientStorePropertyCollection(self::getStore(), self::getProperty(), $builder, $resource, $size);
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