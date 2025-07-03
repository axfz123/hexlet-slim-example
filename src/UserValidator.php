<?php

namespace App;

class UserValidator
{
    public function validate(array $userData): array
    {
        $errors = [];
        if (empty($userData['nickname'])) {
            $errors['nickname'] = 'Name can not be empty';
        }
        if (! preg_match("/^[a-zA-Z0-9_\-]+@[a-zA-Z0-9_\-]+\.[a-zA-Z]+/", $userData['email'])) {
            $errors['email'] = 'Wrong email format';
        }
        return $errors;
    }
}
