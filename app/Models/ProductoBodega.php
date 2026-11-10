<?php

namespace App\Models;

/**
 * Modelo de compatibilidad retrospectiva para el inventario de urnas.
 * Hereda todas las capacidades comerciales y logísticas del modelo Urna.
 */
class ProductoBodega extends Urna
{
    protected $table = 'urnas';
}
