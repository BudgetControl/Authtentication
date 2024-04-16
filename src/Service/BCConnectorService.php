<?php
namespace Budgetcontrol\Authtentication\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class to connect to the Budget Control API of the microservice.
 */
class BCConnectorService {

    /**
     * Adds a workspace for a user.
     *
     * @param int $userId The ID of the user.
     * @return void
     */
    public static function AddWorkspace_api(int $userId): void
    {
        $path = env('BC_WORKSPACE') . "/$userId/add";

        $payload = [
            'name' => 'Default WS',
            'description' => 'My personal workspace',
        ];

        $response = Http::post($path, $payload);
        if($response->status() != 200 || $response->status() != 201) {
            Log::error("Error creating workspace ".$response->body());
            throw new \Exception("Error creating workspace", 500);
        }

    }
}