<?php

namespace App\Filament\Resources\UserResource\Concerns;

trait SyncsRoles
{
    /**
     * @return array<int, int>
     */
    protected function roleIds(): array
    {
        return array_map('intval', array_values($this->data['roles'] ?? []));
    }
}
