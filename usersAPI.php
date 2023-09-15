<?php

//Database Connection
require_once 'config.php';

#error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);
#required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

header('Content-Type: application/json');

// Handle the request
$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents('php://input'), true);

// Extract the request URI
$requestUri = $_SERVER['REQUEST_URI'];

// Remove any query parameters from the request URI
$requestUri = strtok($requestUri, '?');

// Get the path of the request URI
$path = parse_url($requestUri, PHP_URL_PATH);

// Trim any leading or trailing slashes
$path = trim($path, '/');

// Split the path into segments
$request = explode('/', $path);

// Determine the resource and ID
#$resource = array_shift($request);
$resource = "users";
//$id = array_shift($request);
$id = isset($data['id']);
// Perform actions based on the HTTP method
switch ($method) {
    case 'GET':
        // Retrieve data
        if ($resource == 'users') {
            if ($id) {
                
                // Retrieve a specific user
                // Sanitize the ID to prevent SQL injection (optional but recommended)
                $sanitized_id = mysqli_real_escape_string($connection, $id);

                // Prepare the SQL statement to fetch the user by ID from the database
                $sql = "SELECT * FROM users WHERE id = '$sanitized_id'";

                // Execute the SQL statement
                $result = mysqli_query($connection, $sql);

                // Check if the user with the given ID exists in the database
                if (mysqli_num_rows($result) === 1) {
                    // Fetch the user details from the result
                    $user = mysqli_fetch_assoc($result);

                    // Close the database connection
                    mysqli_close($connection);

                    // Return the user details as a JSON response
                    http_response_code(200); // OK
                    echo json_encode($user);
                } else {
                    // User with the given ID not found in the database
                    // Close the database connection
                    mysqli_close($connection);

                    http_response_code(404); // Not Found
                    echo json_encode(array('message' => 'User not found.'));
                }
            } else {
                
                // Retrieve a list of users
                $sql = "SELECT id,name,email FROM users";

                // Execute the SQL statement
                $result = mysqli_query($connection, $sql);

                // Check if there are any users in the database
                if (mysqli_num_rows($result) > 0) {
                    // Fetch all rows from the result and store them in an array
                    $users = array();
                    while ($row = mysqli_fetch_assoc($result)) {
                        $users[] = $row;
                    }
                    
                    // Close the database connection
                    mysqli_close($connection);

                    // Return the list of users as a JSON response
                    http_response_code(200); // OK
                    echo json_encode($users);
                } else {
                    // No users found in the database
                    // Close the database connection
                    mysqli_close($connection);

                    http_response_code(404); // Not Found
                    echo json_encode(array('message' => 'No users found.'));
                }
            }
        }
        break;
    case 'POST':
        if ($resource == 'users') {
            // Create a new user
            // Perform some basic validation on the input data
            if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
                http_response_code(400); // Bad Request
                echo json_encode(array('message' => 'Missing required fields.'));
                exit();
            }

            // Extract the user data from the request
            $name = $data['name'];
            $email = $data['email'];
            $password = $data['password'];
            $mobile = isset($data['mobile']) ? $data['mobile'] : null;
            $address = isset($data['address']) ? $data['address'] : null;

            // Hash the password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Prepare the SQL statement to insert the new user into the database
            $sql = "INSERT INTO users (name, email, password, mobile, address) 
                        VALUES ('$name', '$email', '$hashedPassword', '$mobile', '$address')";

            // Execute the SQL statement
            if (mysqli_query($connection, $sql)) {
                // User added successfully
                http_response_code(201); // Created
                echo json_encode(array('message' => 'User created successfully.'));
            } else {
                // Error in database insertion
                http_response_code(500); // Internal Server Error
                echo json_encode(array('message' => 'Error creating user.'));
            }
        }
        break;
    case 'PUT':
        // Update an existing resource
        if ($resource == 'users' && $id) {
            // Update the user with the given ID
            // Sanitize the ID to prevent SQL injection (optional but recommended)
            $sanitized_id = mysqli_real_escape_string($connection, $id);

            // Perform some basic validation on the input data (you can add more validation here)
            if (empty($data['name']) || empty($data['email'])) {
                http_response_code(400); // Bad Request
                echo json_encode(array('message' => 'Name and email fields are required for updating a user.'));
                exit();
            }

            // Extract the user data from the request
            $name = $data['name'];
            $email = $data['email'];
            $mobile = isset($data['mobile']) ? $data['mobile'] : null;
            $address = isset($data['address']) ? $data['address'] : null;

            // Prepare the SQL statement to update the user in the database
            $sql = "UPDATE users SET name = '$name', email = '$email', mobile = '$mobile', address = '$address' WHERE id = '$sanitized_id'";

            // Execute the SQL statement
            if (mysqli_query($connection, $sql)) {
                // User updated successfully
                // Close the database connection
                mysqli_close($connection);

                http_response_code(200); // OK
                echo json_encode(array('message' => 'User updated successfully.'));
            } else {
                // Error updating the user in the database
                // Close the database connection
                mysqli_close($connection);

                http_response_code(500); // Internal Server Error
                echo json_encode(array('message' => 'Error updating user.'));
            }
        }
        break;
    case 'DELETE':
        // Delete a resource
        if ($resource == 'users' && $id) {
            // Delete the user with the given ID
            // Sanitize the ID to prevent SQL injection (optional but recommended)
            $sanitized_id = mysqli_real_escape_string($connection, $id);

            // Prepare the SQL statement to delete the user from the database
            $sql = "DELETE FROM users WHERE id = '$sanitized_id'";

            // Execute the SQL statement
            if (mysqli_query($connection, $sql)) {
                // Check if any rows were affected by the deletion
                if (mysqli_affected_rows($connection) > 0) {
                    // User deleted successfully
                    // Close the database connection
                    mysqli_close($connection);

                    http_response_code(200); // OK
                    echo json_encode(array('message' => 'User deleted successfully.'));
                } else {
                    // User with the given ID not found in the database
                    // Close the database connection
                    mysqli_close($connection);

                    http_response_code(404); // Not Found
                    echo json_encode(array('message' => 'User not found.'));
                }
            } else {
                // Error deleting the user from the database
                // Close the database connection
                mysqli_close($connection);

                http_response_code(500); // Internal Server Error
                echo json_encode(array('message' => 'Error deleting user.'));
            }
        }
        break;
}
