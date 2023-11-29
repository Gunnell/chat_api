<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


//Get info about groups
$app->get('/groups/info', function (Request $request, Response $response) {
    
    try {
        $db = new Db();
        $conn = $db->connect();
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }

    $user_id = authenticate_request($conn, $request, $response);
    if (!$user_id) {
        $responseMessage = ['message' => 'Unauthorized'];
        return respondWithJSON($response, $responseMessage, 403);
    }

    $sql = "SELECT * FROM Groups";

    try {
        $stmt = $conn->query($sql);
        $groups = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (empty($groups)) {
            $responseMessage = ['message' => 'No groups found.'];
            return respondWithJSON($response, $responseMessage, 404);
        }

        return respondWithJSON($response, $groups, 200);
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});


//Get information about which users belong to which groups.
$app->get('/groups/info/detailed', function (Request $request, Response $response) {
    
    try {
        $db = new Db();
        $conn = $db->connect();
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }

    $user_id = authenticate_request($conn, $request, $response);
    if (!$user_id) {
        $responseMessage = ['message' => 'Unauthorized'];
        return respondWithJSON($response, $responseMessage, 403);
    }

    $sql = "SELECT  gr_id, user_id FROM Group_Users";

    try {
        $stmt = $conn->query($sql);
        $groups = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (empty($groups)) {
            $responseMessage = ['message' => 'No groups found.'];
            return respondWithJSON($response, $responseMessage, 404);
        }

        return respondWithJSON($response, $groups, 200);
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});
//Create new group
$app->post('/groups/create', function (Request $request, Response $response) {
    
    try {
        $db = new Db();
        $conn = $db->connect();
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }

    $user_id = authenticate_request($conn, $request, $response);
    if (!$user_id) {
        $responseMessage = ['message' => 'Unauthorized'];
        return respondWithJSON($response, $responseMessage, 403);
    }

    $parsedBody = json_decode($request->getBody(), true);

    if (!isset($parsedBody["group_name"])) {
        $responseMessage = ['message' => 'Missing Field!'];
        return respondWithJSON($response, $responseMessage, 400);
    }

    $group_name = $parsedBody["group_name"];


    try {
        $sqlCheck = "SELECT * FROM Groups WHERE group_name = :group_name";
        $stmt = $conn->prepare($sqlCheck);
        $stmt->bindParam(":group_name", $group_name);
        $stmt->execute();
        $groupExists = $stmt->fetchColumn();

        if ($groupExists > 0) {
            $responseMessage = ['message' => 'This group name has already been taken!'];
            return respondWithJSON($response, $responseMessage, 409);
        }

        $sql = "INSERT INTO Groups (group_name) VALUES (:group_name)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":group_name", $group_name);

        $result = $stmt->execute();

        if ($result) {
            $responseMessage = ['message' => 'New group was created successfully'];
            return respondWithJSON($response, $responseMessage, 201);
        } else {
            $error = ['message' => 'Group creation failed'];
            return respondWithJSON($response, $error, 500);
        }
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});
