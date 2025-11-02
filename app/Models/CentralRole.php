<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class CentralRole extends SpatieRole
{
    // Ensure Spatie roles use the central (landlord) connection
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }
}
