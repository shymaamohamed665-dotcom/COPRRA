<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ValidatableModel extends Model
{
    protected ?\Illuminate\Support\MessageBag $errors = null;

    /**
     * @var array<string, string>
     */
    protected array $rules = [];

    /**
     * Get validation rules for the model.
     *
     * @return array<string, string>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Validate current model attributes against defined rules.
     */
    public function validate(): bool
    {
        $validator = \Validator::make($this->getAttributes(), $this->getRules());

        if ($validator->fails()) {
            $this->errors = $validator->errors();

            return false;
        }

        $this->errors = new \Illuminate\Support\MessageBag;

        return true;
    }

    /**
     * Return validation errors as a simple array keyed by attribute.
     *
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors?->toArray() ?? [];
    }
}
