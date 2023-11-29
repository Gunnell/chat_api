<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

//Get messages by group id
$app->get('/messages/{gr_id}', function (Request $request, Response $response) {
    
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

    $gr_id = $request->getAttribute("gr_id");

    // Check if the user is authorized to access the group
    try{
        $sql = 'SELECT * FROM Group_Users WHERE gr_id = :gr_id AND user_id = :user_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':gr_id', $gr_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
        if (!$result) {
            $responseMessage = ['message' => 'Unauthorized or the group does not exist'];
            return respondWithJSON($response, $responseMessage, 400);
        }
    
        // Retrieve messages
        $sqlChats = 'SELECT user_id, text FROM Messages WHERE gr_id = :gr_id';
        $stmt = $conn->prepare($sqlChats);
        $stmt->bindParam(':gr_id', $gr_id);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (!$messages) {
            $responseMessage = ['message' => 'No messages found for this group.'];
            return respondWithJSON($response, $responseMessage, 404);
        }
    
        return respondWithJSON($response, $messages, 200);

    }catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }


});



//Send messages by group id
$app->post('/messages/{gr_id}', function (Request $request, Response $response) {
    
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

    $gr_id = $request->getAttribute("gr_id");

    // Check if the user is authorized to send messages to the group
    try{
        $sql = 'SELECT * FROM Group_Users WHERE gr_id = :gr_id AND user_id = :user_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':gr_id', $gr_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $responseMessage = ['message' => 'Unauthorized or the group does not exist'];
            return respondWithJSON($response, $responseMessage, 400);
        }

        $parsedBody = json_decode($request->getBody(), true);

        if (!isset($parsedBody["message"]) || empty($parsedBody['message'])) {
            $responseMessage = ['message' => 'Empty messages are not allowed.'];
            return respondWithJSON($response, $responseMessage, 400);
        }

        $text = $parsedBody["message"];
        $sqlInsert = "INSERT INTO Messages (text, gr_id, user_id) VALUES (:text, :gr_id, :user_id)";
        $stmt = $conn->prepare($sqlInsert);
        $stmt->bindParam(":text", $text);
        $stmt->bindParam(":gr_id", $gr_id);
        $stmt->bindParam(":user_id", $user_id);

        $result = $stmt->execute();

        if ($result) {
            $responseMessage = ['message' => 'Message sent successfully'];
            return respondWithJSON($response, $responseMessage, 201);
        } else {
            $error = ['message' => 'Message send failed'];
            return respondWithJSON($response, $error, 500);
        }

    }
    catch (PDOException $e) {
        $error = ['message' => $e->getMessage()];
        return respondWithJSON($response, $error, 500);
    }
    

});
