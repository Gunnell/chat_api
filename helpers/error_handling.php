<?php
use Psr\Http\Message\ResponseInterface as Response;

// Function to handle errors and return JSON responses
function respondWithJSON(Response $response, $responseMsg, $statusCode) {
    $response->getBody()->write(json_encode($responseMsg));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($statusCode);
}
