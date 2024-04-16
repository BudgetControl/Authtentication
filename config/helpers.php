<?php
/**
 * This file is a helper file that contains various functions.
 */

if(!function_exists('config')) {
    function confing(string $key, string $value): string {
        return $_ENV[$key] ?? $value;
    }
}

if(!function_exists('response')) {
    function response(array $dataResponse, int $statusCode = 200, array $headers=[]): \Psr\Http\Message\ResponseInterface {
        $response = new \Slim\Psr7\Response();

        // Codifica i dati in JSON e scrivi nel corpo della risposta
        $jsonData = json_encode($dataResponse);
        if ($jsonData === false) {
            // Gestione degli errori durante la codifica JSON
            // In questo esempio, restituisci una risposta di errore
            $errorResponse = new \Slim\Psr7\Response();
            $errorResponse->getBody()->write('Errore nella codifica JSON dei dati');
            return $errorResponse->withStatus(500);
        }
        
        $response->getBody()->write($jsonData);

        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }
        
        // Aggiungi intestazione Content-Type e stato alla risposta
        $response = $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        
        return $response;
    }
}

// More functions...
if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword()
    {
        $length = 12;  // Puoi regolare la lunghezza della password qui
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';

        // Mescola i caratteri per garantire che almeno uno di ogni tipo sia incluso
        $allChars = $uppercase . $lowercase . $numbers . $specialChars;
        $password = $uppercase[random_int(0, strlen($uppercase) - 1)] .
            $lowercase[random_int(0, strlen($lowercase) - 1)] .
            $numbers[random_int(0, strlen($numbers) - 1)] .
            $specialChars[random_int(0, strlen($specialChars) - 1)];

        // Completa la password con caratteri casuali
        while (strlen($password) < $length) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Mescola ulteriormente la password per rendere l'ordine casuale
        $password = str_shuffle($password);

        return $password;
    }
}