<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class CentralPermission extends SpatiePermission
{
    // Ensure Spatie permissions use the central (landlord) connection
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }
}
