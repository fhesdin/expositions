<?php

namespace Core;

/**
 * Validation des entrées + accès sécurisé.
 */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, ?string $message = null): self
    {
        $val = $this->data[$field] ?? null;
        if ($val === null || $val === '' || (is_array($val) && count($val) === 0)) {
            $this->errors[$field] = $message ?? "Le champ « $field » est obligatoire.";
        }
        return $this;
    }

    public function email(string $field, ?string $message = null): self
    {
        $val = $this->data[$field] ?? '';
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "Le champ « $field » doit être un e-mail valide.";
        }
        return $this;
    }

    public function min(string $field, int $min, ?string $message = null): self
    {
        $val = (string) ($this->data[$field] ?? '');
        if ($val !== '' && mb_strlen($val) < $min) {
            $this->errors[$field] = $message ?? "Le champ « $field » doit faire au moins $min caractères.";
        }
        return $this;
    }

    public function max(string $field, int $max, ?string $message = null): self
    {
        $val = (string) ($this->data[$field] ?? '');
        if (mb_strlen($val) > $max) {
            $this->errors[$field] = $message ?? "Le champ « $field » ne doit pas dépasser $max caractères.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, ?string $message = null): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '' && !in_array($val, $allowed, false)) {
            $this->errors[$field] = $message ?? "Valeur invalide pour « $field ».";
        }
        return $this;
    }

    public function numeric(string $field, ?string $message = null): self
    {
        $val = $this->data[$field] ?? '';
        if ($val !== '' && !is_numeric($val)) {
            $this->errors[$field] = $message ?? "Le champ « $field » doit être numérique.";
        }
        return $this;
    }

    public function range(string $field, float $min, float $max, ?string $message = null): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '' && is_numeric($val)) {
            $v = (float) $val;
            if ($v < $min || $v > $max) {
                $this->errors[$field] = $message ?? "Le champ « $field » doit être entre $min et $max.";
            }
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }

    public function get(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }
}
