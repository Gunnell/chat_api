<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Function to authebticate user by token from the header
function authenticate_request($pdo, Request $request, Response $response) {
    if (!$request->hasHeader('Authorization')) {
        return null;
    }

    $token = $request->getHeader("Authorization")[0];
    $sql = 'SELECT user_id, exp_date FROM Tokens WHERE token=:token';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage(),];
        return respondWithJson($response, $error, 500);
    }

    if (!$user) {
        return null;
    }

    $user_id = $user["user_id"];
    $token_expiration = $user["exp_date"];

    if (new DateTime() > new DateTime($token_expiration)) {
        return null;
    }

    return $user_id;
}

// Function to generate token
function generate_token($user_id, $time) {
    $salt =  bin2hex(random_bytes(16));;
    return hash('sha256', $user_id . $time . $salt);
}

// Function to create token for user login
function create_login_token($user_id, $pdo, Response $response) {
    // Checking if token exists
    $sql = 'SELECT user_id, token FROM Tokens WHERE user_id=:user_id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage(), ];
        return respondWithJson($response, $error, 500);
    }

    if (!$token) {
        //  The exp date is set to +10 days for the assessment check to make it easier.
        $exp_date = (new DateTime())->modify('+10 day')->format("Y-m-d H:i:s");
        $token = generate_token($user_id, $exp_date);
        $sql = 'INSERT INTO Tokens (user_id, token, exp_date) VALUES (:user_id, :token, :exp_date)';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':exp_date', $exp_date);
            $result = $stmt->execute();
        } catch (PDOException $e) {
            
            $error = ['message' => 'Token creation failed!',];
            return respondWithJson($response, $error, 400);
        }
        if(!$result){
            
            $error = array('message' => "Token creation failed!" );
            return respondWithJson($response, $error, 400);
        }
    }

    else {
        return refresh_token($user_id, $pdo, $response);
    }

    return $response
        ->withHeader("content-type","application/json")
        ->withStatus(200);
    
}

// Function to refresh token
function refresh_token($user_id, $pdo, Response $response) {

    $exp_date = (new DateTime())->modify('+1 day')->format("Y-m-d H:i:s");
    $token = generate_token($user_id, $exp_date);
    $sql = 'UPDATE Tokens SET token=:token, exp_date=:exp_date WHERE user_id=:user_id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':exp_date', $exp_date);
        $result = $stmt->execute();
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage(),];
        return respondWithJson($response, $error, 400);
    }
    if(!$result){
        
        $error = array('message' => "Token creation failed!" ); 
        return respondWithJson($response, $error, 400);
    }

    return $response
    ->withHeader("content-type","application/json")
    ->withStatus(200);
}





