<?php

namespace App\Data\Mock\Contracts;

interface MockDataProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
