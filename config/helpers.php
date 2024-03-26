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
