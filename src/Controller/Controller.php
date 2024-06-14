<?php
namespace Budgetcontrol\Authentication\Controller;

use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Controller {

    public function monitor(Request $request, Response $response)
    {
        $dbHost = env('DB_HOST');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        // Assuming you are using PDO for database connection
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbPass, $dbUser);
        } catch (PDOException $e) {
            // Connection failed
            $response->getBody()->write('Database connection failed: ' . $e->getMessage());
            return $response->withStatus(500);
        }

        return response([
            'success' => true,
            'message' => 'Authentication service is up and running'
        ]);
        
    }
}