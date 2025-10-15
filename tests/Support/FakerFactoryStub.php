<?php

namespace Tests\Support;

class FakerFactoryStub
{
    public static function create($locale = null)
    {
        return new class($locale)
        {
            public function __construct($locale = null) {}

            public function __call(string $name, array $arguments)
            {
                if ($name === 'randomDigit') {
                    return 5;
                }
                if ($name === 'name') {
                    return 'Test User';
                }
                if ($name === 'email') {
                    return 'user@example.com';
                }
                if ($name === 'safeEmail') {
                    return 'user@example.com';
                }
                if ($name === 'phoneNumber') {
                    return '+10000000000';
                }
                if ($name === 'address') {
                    return '123 Test St';
                }
                if ($name === 'uuid') {
                    return '00000000-0000-0000-0000-000000000000';
                }
                if ($name === 'word') {
                    return 'word';
                }
                if ($name === 'sentence') {
                    return 'sentence';
                }
                if ($name === 'text') {
                    return 'text';
                }
                if ($name === 'city') {
                    return 'City';
                }
                if ($name === 'country') {
                    return 'Country';
                }
                if ($name === 'postcode') {
                    return '00000';
                }
                if ($name === 'randomElement') {
                    return $arguments[0][0] ?? null;
                }
                if ($name === 'randomNumber') {
                    return 42;
                }

                return $arguments[0] ?? (string) $name;
            }
        };
    }
}
