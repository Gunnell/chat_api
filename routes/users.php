<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

//Get all users' info
$app->get("/users/all", function (Request $request, Response $response) {
    $sql = "SELECT id, display_name FROM Users";
    
    try {
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($users)) {
            $responseMessage = ['message' => 'No users found.'];
            return respondWithJSON($response, $responseMessage, 404);
        }
        return respondWithJSON($response, $users, 200);
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});


//Get user's info by display name
$app->get("/users/{display_name}", function (Request $request, Response $response, array $args) {
    $display_name = $args["display_name"];
    
    $sql = "SELECT id, display_name FROM Users WHERE display_name = :display_name";

    try {
        $db = new DB();
        $conn = $db->connect();
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":display_name", $display_name);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return respondWithJSON($response, $user, 200);
        } else {
            $error = ['message' => 'User not found'];
            return respondWithJSON($response, $error, 404);
        }
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});

//User Register
$app->post("/users/register", function (Request $request, Response $response) {
    $parsedBody = json_decode($request->getBody(), true);

    if (!isset($parsedBody["display_name"]) || !isset($parsedBody["password"])) {
        $responseMessage = ['message' => 'Missing Fields!'];
        return respondWithJSON($response, $responseMessage, 400);
    }

    $display_name = $parsedBody["display_name"];
    $password = $parsedBody["password"];

    $sqlCheckUser = "SELECT * FROM Users WHERE display_name = :display_name";
    

    try {
        $db = new DB();
        $conn = $db->connect();


        $stmt = $conn->prepare($sqlCheckUser);
        $stmt->bindParam(":display_name", $display_name);
        $stmt->execute();
        $userExists = $stmt->fetchColumn();

        if ($userExists > 0) {
            $responseMessage = ['message' => 'User already exists'];
            return respondWithJSON($response, $responseMessage, 409);
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); 
        $sql = "INSERT INTO Users (display_name, password) VALUES (:display_name, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":display_name", $display_name);
        $stmt->bindParam(":password", $hashedPassword); 
        $result = $stmt->execute();

        if ($result) {
            $responseMessage = ['message' => 'User registered successfully'];
            return respondWithJSON($response, $responseMessage, 201);
        } else {
            $error = ['message' => 'User registration failed'];
            return respondWithJSON($response, $error, 500);
        }
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});


//User login
$app->post("/users/login", function (Request $request, Response $response) {
    $parsedBody = json_decode($request->getBody(), true);

    if (!isset($parsedBody["display_name"]) || !isset($parsedBody["password"])) {
        $responseMessage = ['message' => 'Missing Fields!'];
        return respondWithJSON($response, $responseMessage, 400);
    }

    $display_name = $parsedBody["display_name"];
    $password = $parsedBody["password"];

    
    $sql = "SELECT * FROM Users WHERE display_name = :display_name";
    

    try {
        $db = new DB();
        $conn = $db->connect();


        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":display_name", $display_name);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            //if ($password == $user['password']) {
            if (password_verify($password, $user['password'])) { 
                $responseMessage = ['message' => 'Login successful', 'user_id' => $user['id']];
                respondWithJSON($response, $responseMessage, 200);
                // Create token if loginned
                return create_login_token($user["id"], $conn, $response);
            } else {
                $error = ['message' => 'Incorrect password'];
                return respondWithJSON($response, $error, 401);
            }
        } else {
            $error = ['message' => 'User not found'];
            return respondWithJSON($response, $error, 404);
        }
    } catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
});



// Join group by group id
$app->post('/users/join/{gr_id}', function (Request $request, Response $response) {

    try{
        $db = new DB();
        $conn = $db->connect();
        $user_id = authenticate_request($conn, $request, $response);
    
        if (!$user_id) {
            $responseMessage = ['message' => 'Unauthorized'];
            return respondWithJSON($response, $responseMessage, 403);
        }
    
        $gr_id = $request->getAttribute("gr_id");


        // Check gr id 
        $sql = 'SELECT * FROM Group_Users WHERE gr_id = :gr_id AND user_id = :user_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':gr_id', $gr_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $responseMessage = ['message' => 'User is already in that group!'];
            return respondWithJSON($response, $responseMessage, 400);
        }
    
        $sqlCheck = "SELECT id FROM Groups WHERE id = :gr_id";
        $stmt = $conn->prepare($sqlCheck);
        $stmt->bindParam(':gr_id', $gr_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            $responseMessage = ['message' => 'There is no such group available to join!'];
            return respondWithJSON($response, $responseMessage, 400);
        }
    
        $sqlJoin = "INSERT into Group_Users (gr_id, user_id) VALUES (:gr_id, :user_id)";
        $stmt = $conn->prepare($sqlJoin);
        $stmt->bindParam(":gr_id", $gr_id);
        $stmt->bindParam(":user_id", $user_id);
    
        $result = $stmt->execute();

        if ($result) {
            $responseMessage = ['message' => 'User joined the group successfully!'];
            return respondWithJSON($response, $responseMessage, 201);
        } else {
            $error = ['message' => 'Joining user to the group failed!'];
            return respondWithJSON($response, $error, 500);
        }

    }
    catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }

});
