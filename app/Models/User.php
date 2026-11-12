<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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

    public const SEDES = [
        'Calle Blanco' => 'Salón Calle Blanco (Casa Matriz)',
        'Taller' => 'Taller de Preparación y Urnas',
        'Ambas' => 'Ambas Sedes (Móvil / Operativo)',
    ];

    public const TIPOS_LICENCIA = [
        'A2' => 'Clase A2 (Ambulancias y Transporte)',
        'A3' => 'Clase A3 (Buses y Minibuses)',
        'A4' => 'Clase A4 (Carga Simple)',
        'B' => 'Clase B (Vehículos Particulares)',
    ];

    public const ESTADOS_OPERATIVOS = [
        'Disponible' => [
            'label' => 'Disponible',
            'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'dot' => 'text-emerald-500',
        ],
        'En Ruta' => [
            'label' => 'En Ruta',
            'badge' => 'bg-amber-100 text-amber-900 border-amber-300',
            'dot' => 'text-amber-500',
        ],
        'Vacaciones' => [
            'label' => 'Vacaciones',
            'badge' => 'bg-slate-100 text-slate-700 border-slate-300',
            'dot' => 'text-slate-400',
        ],
        'Licencia Médica' => [
            'label' => 'Licencia Médica',
            'badge' => 'bg-purple-100 text-purple-800 border-purple-300',
            'dot' => 'text-purple-500',
        ],
        'Suspendido' => [
            'label' => 'Suspendido',
            'badge' => 'bg-rose-100 text-rose-800 border-rose-300',
            'dot' => 'text-rose-500',
        ],
    ];

    public const ESTADOS_CUENTA = [
        'activa' => [
            'label' => 'Activa',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'text-emerald-600',
        ],
        'suspendida' => [
            'label' => 'Suspendida',
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            'dot' => 'text-rose-600',
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
        'rut',
        'telefono',
        'tipo_licencia',
        'sede_asignada',
        'estado_operativo',
        'estado_cuenta',
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
            'deleted_at' => 'datetime',
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
            'rut' => ['nullable', 'string', 'max:15', Rule::unique('users', 'rut')],
            'telefono' => ['nullable', 'string', 'max:25'],
            'sede_asignada' => ['nullable', Rule::in(array_keys(self::SEDES))],
            'tipo_licencia' => ['nullable', Rule::in(array_keys(self::TIPOS_LICENCIA))],
            'estado_operativo' => ['nullable', Rule::in(array_keys(self::ESTADOS_OPERATIVOS))],
            'estado_cuenta' => ['nullable', Rule::in(array_keys(self::ESTADOS_CUENTA))],
        ];
    }

    public function sedeLabel(): string
    {
        return self::SEDES[$this->sede_asignada] ?? ($this->sede_asignada ?: 'Salón Calle Blanco');
    }

    public function licenciaLabel(): ?string
    {
        return self::TIPOS_LICENCIA[$this->tipo_licencia] ?? ($this->tipo_licencia ?: null);
    }

    /**
     * @return array<string, string>
     */
    public function estadoOperativoMeta(): array
    {
        return self::ESTADOS_OPERATIVOS[$this->estado_operativo] ?? self::ESTADOS_OPERATIVOS['Disponible'];
    }

    /**
     * @return array<string, string>
     */
    public function estadoCuentaMeta(): array
    {
        return self::ESTADOS_CUENTA[$this->estado_cuenta] ?? self::ESTADOS_CUENTA['activa'];
    }

    public function hasRole(string ...$roles): bool
    {
        $currentRole = strtolower($this->role ?? '');
        $roles = array_map('strtolower', $roles);

        return in_array($currentRole, $roles, true);
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
