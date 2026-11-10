<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = [
        'administrador' => [
            'label' => 'Administrador',
            'form_label' => 'Administrador Total',
            'badge_class' => 'bg-indigo-900 text-white',
            'modules' => 'Acceso Global (Finanzas, Usuarios, Convenios, Reportes, S3)',
            'finances' => true,
        ],
        'gerencia' => [
            'label' => 'Gerencia',
            'form_label' => 'Gerencia',
            'badge_class' => 'bg-indigo-900 text-white',
            'modules' => 'Acceso Global (Finanzas, Usuarios, Convenios, Reportes, S3)',
            'finances' => true,
        ],
        'asistente' => [
            'label' => 'Asistente',
            'form_label' => 'Asistente Administrativo',
            'badge_class' => 'bg-purple-100 text-purple-800',
            'modules' => 'Expedientes, Bodega, Flota y Semáforo Documental',
            'finances' => false,
        ],
        'bodega' => [
            'label' => 'Bodega',
            'form_label' => 'Encargado de Bodega',
            'badge_class' => 'bg-amber-100 text-amber-900',
            'modules' => 'Control de Bodega Dual (Salón Calle Blanco y Taller)',
            'finances' => false,
        ],
        'chofer' => [
            'label' => 'Chofer',
            'form_label' => 'Chofer / Instalador',
            'badge_class' => 'bg-blue-100 text-blue-800',
            'modules' => 'Solo Ruta Móvil y Traspaso Taller → Salón',
            'finances' => false,
        ],
        'finanzas' => [
            'label' => 'Finanzas',
            'form_label' => 'Finanzas y Convenios',
            'badge_class' => 'bg-amber-100 text-amber-900',
            'modules' => 'Liquidación de Convenios IPS/AFP y Comprobantes PDF',
            'finances' => true,
        ],
    ];

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

    /**
     * @return list<string>
     */
    public static function validRoles(): array
    {
        return array_keys(self::ROLES);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function baseValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(self::validRoles())],
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function homeRoute(): string
    {
        return $this->hasRole('chofer')
            ? route('movil.chofer')
            : route('dashboard');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role]['label'] ?? 'Sin rol';
    }

    public function roleBadgeClass(): string
    {
        return self::ROLES[$this->role]['badge_class'] ?? 'bg-slate-100 text-slate-800';
    }

    public function authorizedModulesDescription(): string
    {
        return self::ROLES[$this->role]['modules'] ?? 'Sin módulos asignados';
    }

    public function canAccessFinances(): bool
    {
        return (bool) (self::ROLES[$this->role]['finances'] ?? false);
    }
}
