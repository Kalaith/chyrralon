<?php

declare(strict_types=1);

namespace Chyrralon\Models;

final class AuthUser
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $email,
        public readonly ?string $username,
        public readonly ?string $displayName,
        public readonly string $role,
        public readonly array $roles,
        public readonly string $authType,
        public readonly bool $isGuest
    ) {
    }

    /**
     * @param array<string, mixed> $claims
     */
    public static function fromArray(array $claims): self
    {
        $roles = self::normalizeRoles($claims['roles'] ?? ($claims['role'] ?? 'user'));
        $isGuest = (bool) ($claims['is_guest'] ?? false) || ($claims['auth_type'] ?? null) === 'guest';
        $id = (string) ($claims['id'] ?? $claims['sub'] ?? $claims['user_id'] ?? '');

        return new self(
            $id,
            isset($claims['email']) && is_string($claims['email']) ? $claims['email'] : null,
            isset($claims['username']) && is_string($claims['username']) ? $claims['username'] : null,
            isset($claims['display_name']) && is_string($claims['display_name']) ? $claims['display_name'] : null,
            $isGuest ? 'guest' : ($roles[0] ?? 'user'),
            $isGuest ? ['guest'] : $roles,
            $isGuest ? 'guest' : (is_string($claims['auth_type'] ?? null) ? (string) $claims['auth_type'] : 'frontpage'),
            $isGuest
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'display_name' => $this->displayName,
            'role' => $this->role,
            'roles' => $this->roles,
            'auth_type' => $this->authType,
            'is_guest' => $this->isGuest,
        ];
    }

    /**
     * @return list<string>
     */
    private static function normalizeRoles(mixed $roles): array
    {
        if (is_string($roles)) {
            return [$roles];
        }

        if (!is_array($roles)) {
            return ['user'];
        }

        $normalized = [];
        foreach ($roles as $role) {
            if (is_string($role) && $role !== '') {
                $normalized[] = $role;
            }
        }

        return $normalized === [] ? ['user'] : array_values(array_unique($normalized));
    }
}
