<?php

namespace App;

class UserRepository
{
    const string USER_DATA_FILE = __DIR__ . '/../userData.json';

    public function all(): array
    {
        $fileData = file_get_contents(self::USER_DATA_FILE);
        return json_decode($fileData, true);
    }

    public function find(int $id): ?array
    {
        $fileData = file_get_contents(self::USER_DATA_FILE);
        $usersData = json_decode($fileData, true);
        if (array_key_exists($id, $usersData)) {
            return $usersData[$id];
        }
        return null;
    }

    public function save(array $userData): void
    {
        $fileData = file_get_contents(self::USER_DATA_FILE);
        $usersData = json_decode($fileData, true) ?? [];
        $id = (array_key_last($usersData) ?? 0) + 1;
        $userData['id'] = $id;
        $usersData[$id] = $userData;
        file_put_contents(self::USER_DATA_FILE, json_encode($usersData, JSON_FORCE_OBJECT));
    }

    public function update($userData): void
    {
        $fileData = file_get_contents(self::USER_DATA_FILE);
        $usersData = json_decode($fileData, true) ?? [];
        $id = $userData['id'];
        if (array_key_exists($id, $usersData)) {
            $usersData[$id] = $userData;
            file_put_contents(self::USER_DATA_FILE, json_encode($usersData, JSON_FORCE_OBJECT));
            return;
        }
        throw new \Exception('User id not found in repository');
    }

    public function delete($id): void
    {
        $fileData = file_get_contents(self::USER_DATA_FILE);
        $usersData = json_decode($fileData, true) ?? [];

        if (array_key_exists($id, $usersData)) {
            unset($usersData[$id]);
            file_put_contents(self::USER_DATA_FILE, json_encode($usersData, JSON_FORCE_OBJECT));
            return;
        }

        throw new \Exception('User id not found in repository');
    }
}
