# chat_API

Simple chat backend with user authentication developed for a bunq job assessment, built using PHP, the Slim framework, and SQLite as the database. 


## Description

This API is designed to facilitate user authentication and group messaging. Users can create accounts, log in, and access group messaging features, including creating and joining groups, as well as sending messages within groups.

## Features

- User registration
- User login
- Group creation
- Group joining
- Sending messages within groups
- Token-based authentication

## Endpoints

The API includes the following endpoints:

### Users endpoint
- `GET /users/all`: Get information of all users.
- `GET /users/{display_name}`: Get user's information by display name
- `POST /users/register`: Register a new user. Give details in body{"display_name": , "password": }
- `POST /users/login`: Log in and obtain an authentication token. Give details in body{"display_name": , "password": }
- `POST /users/join/{gr_id}`: Join an existing group. Give token in Authorization header. 

### Groups endpoint
- `GET /groups/info`: Get information about available groups. Give token in Authorization header. 
- `GET /groups/info/detailed`: Get information about which users belong to which groups.Give token in Authorization header. 
- `POST /groups/create`: Create a new group. Give details in body {"group_name": "" }. Give token in Authorization header. 

###  Messages endpoint
- `GET /messages/{gr_id}`: Retrieve messages from a specific group. Give token in Authorization header. 
- `POST /messages/{gr_id}`: Send a message to a group.  Give details in body {"message": "" }Give token in Authorization header. 

## Prerequisites
Before using this API, you need to have the following installed:

- PHP
- Composer
- A database system (SQLite) and a PDO driver for PHP


## Run the project
cd bunq_chat
php -S localhost:8080 -t public



