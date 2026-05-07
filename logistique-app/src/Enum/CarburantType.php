<?php

namespace App\Enum;

enum CarburantType: string
{
     case DIESEL = 'Diesel';
    case HYBRIDE = 'Hybride';
    case ELECTRIQUE = 'Électrique';
    case GASOIL = 'Gasoil'; 
    case SUPER = 'Essence'; 

    // Méthode pour convertir les anciennes valeurs aux nouvelles
    public static function fromOldValue(string $oldValue): self
    {
        return match(strtoupper($oldValue)) {
            'GASOIL' => self::GASOIL,
            'SUPER' => self::SUPER,
            'DIESEL' => self::DIESEL,
            'HYBRIDE' => self::HYBRIDE,
            'ELECTRIQUE', 'ÉLECTRIQUE' => self::ELECTRIQUE,
            default => self::DIESEL // Valeur par défaut
        };
    }
}