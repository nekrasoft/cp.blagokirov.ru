<?php

namespace App\Filament\Support;

use App\Filament\Resources\BunkerFillRequestResource;
use App\Filament\Resources\BunkerResource;
use App\Filament\Resources\CounterpartyResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\WorkResource;
use ReflectionClass;
use ReflectionProperty;

final class RuntimeSchemaCache
{
    /**
     * @var array<class-string, array<int, string>>
     */
    private const PROPERTIES = [
        BunkerFillRequestResource::class => ['hasTableCache', 'hasColumnCache', 'hasCounterpartyColumnCache'],
        BunkerResource::class => ['hasTableCache', 'hasColumnCache', 'hasCounterpartyColumnCache'],
        CounterpartyResource::class => ['hasColumnCache'],
        InvoiceResource::class => ['hasTableCache', 'hasColumnCache', 'hasCounterpartyColumnCache'],
        WorkResource::class => ['hasTableCache', 'hasColumnCache'],
        DashboardMetrics::class => ['tableCache', 'columnCache'],
    ];

    public static function flush(): void
    {
        foreach (self::PROPERTIES as $class => $properties) {
            $reflectionClass = new ReflectionClass($class);

            foreach ($properties as $propertyName) {
                if (! $reflectionClass->hasProperty($propertyName)) {
                    continue;
                }

                $property = $reflectionClass->getProperty($propertyName);

                if (! $property->isStatic()) {
                    continue;
                }

                $property->setAccessible(true);
                $property->setValue(null, self::emptyValueFor($property));
            }
        }
    }

    private static function emptyValueFor(ReflectionProperty $property): mixed
    {
        $type = $property->getType();

        if ($type && str_contains($type->getName(), 'array')) {
            return [];
        }

        return null;
    }
}
