<?php

class User {
    private $id;
    private $username;
    private $email;

    public function __construct($id, $username, $email) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public static function findById($id) {
        // Logic to find a user by ID from the database
    }

    public static function findByUsername($username) {
        // Logic to find a user by username from the database
    }

    public static function create($username, $email, $password) {
        // Logic to create a new user in the database
    }
}