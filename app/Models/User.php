<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_CASHIER = 'cashier';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return list<string>
     */
    public static function staffRoles(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_CASHIER];
    }

    /**
     * Canonical role for RBAC (lowercase, trimmed). Null if empty.
     */
    public function normalizedRole(): ?string
    {
        $r = trim((string) ($this->attributes['role'] ?? ''));

        return $r === '' ? null : strtolower($r);
    }

    public function hasValidStaffRole(): bool
    {
        return in_array($this->normalizedRole(), self::staffRoles(), true);
    }

    public function setRoleAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['role'] = '';

            return;
        }

        $this->attributes['role'] = strtolower(trim($value));
    }

    /**
     * Human-facing name on invoices/logs: use real full name unless the stored
     * `name` is only a role placeholder (e.g. "Cashier", "Administrator").
     */
    public function attributionName(): string
    {
        $name = trim((string) ($this->attributes['name'] ?? ''));

        if ($name === '') {
            return $this->attributionFallbackFromEmail() ?? '—';
        }

        $nr = $this->normalizedRole();
        $roleLabels = [];

        $genericsWithoutRole = ['cashier', 'admin', 'administrator'];
        if ($nr === null) {
            foreach ($genericsWithoutRole as $g) {
                if (strcasecmp($name, $g) === 0) {
                    return $this->attributionFallbackFromEmail() ?? $name;
                }
            }
        }

        if ($nr === self::ROLE_CASHIER) {
            $roleLabels[] = self::ROLE_CASHIER;
        }
        if ($nr === self::ROLE_ADMIN) {
            $roleLabels[] = self::ROLE_ADMIN;
            $roleLabels[] = 'administrator';
        }

        foreach ($roleLabels as $syn) {
            if (strcasecmp($name, $syn) === 0) {
                return $this->attributionFallbackFromEmail() ?? $name;
            }
        }

        return $name;
    }

    private function attributionFallbackFromEmail(): ?string
    {
        $email = trim((string) ($this->attributes['email'] ?? ''));
        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        $localRaw = strtolower(explode('@', $email, 2)[0]);
        $localSpaced = str_replace(['.', '_'], ' ', $localRaw);
        $compact = preg_replace('/\s+/', '', $localSpaced) ?? '';

        if ($compact !== '' && preg_match('/^([a-z]+)(\d+)$/', $compact, $m)) {
            return \Illuminate\Support\Str::title($m[1].' '.$m[2]);
        }

        return \Illuminate\Support\Str::title($localSpaced);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
