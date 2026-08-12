<?php

namespace App\Repositories\Contracts;

interface SystemSettingRepositoryInterface extends RepositoryInterface
{
    /**
     * Retrieve the raw value of a setting by key, or the default.
     */
    public function value(string $key, mixed $default = null): mixed;
}
