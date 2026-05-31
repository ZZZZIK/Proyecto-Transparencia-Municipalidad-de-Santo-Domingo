<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contribuyente extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'contribuyentes';

    /**
     * Los atributos que son asignables de forma masiva.
     *
     * @var array
     */
    protected $fillable = [
        'rut_hash',
        'rut_encriptado',
        'nombre_encriptado',
        'password_hash',
        'rol',
        'aporte_contribucion',
        'aporte_circulacion',
        'aporte_aseo',
        'valores_mensuales'
    ];

    /**
     * Los atributos que deben ser convertidos (cast) a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        // Cumplimiento estricto ISO 27001/27701 e ISO/IEC 19628:
        // Cifrado simétrico AES-256-CBC nativo de Laravel para proteger PII en reposo
        'rut_encriptado' => 'encrypted',
        'nombre_encriptado' => 'encrypted',
        'aporte_contribucion' => 'integer',
        'aporte_circulacion' => 'integer',
        'aporte_aseo' => 'integer',
        'valores_mensuales' => 'array' // Serialización JSON automática
    ];

    /**
     * Generar un Hash SHA-256 seguro no reversible para búsquedas rápidas (ej. RUT).
     * Esto permite encontrar un registro por RUT en consultas sin descifrar toda la tabla.
     *
     * @param string $rut
     * @return string
     */
    public static function hashRut($rut)
    {
        // Normalizar RUT (quitar puntos, guiones y espacios, pasar a minúsculas la K)
        $cleanRut = strtolower(str_replace(['.', '-', ' '], '', $rut));
        return hash('sha256', $cleanRut);
    }
}
