<?php

namespace App\Support;

use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CsvExport
 * =========
 * Génère un CSV lisible par Excel en français, en streaming.
 *
 * Deux détails font la différence à l'usage :
 *  — le séparateur est le POINT-VIRGULE (Excel FR n'ouvre pas correctement un
 *    CSV à virgules : tout atterrit dans la colonne A) ;
 *  — le fichier commence par le BOM UTF-8, sans quoi les accents s'affichent
 *    en mojibake dans Excel.
 *
 * La sortie est streamée ligne par ligne : exporter dix mille ventes ne charge
 * pas dix mille lignes en mémoire.
 */
class CsvExport
{
    /**
     * @param array<int, string>            $headers  en-têtes de colonnes
     * @param iterable                      $rows     lignes (chacune un array de valeurs)
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->stream(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 : indispensable pour les accents dans Excel.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ';');
            }

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            // Un export ne doit jamais être servi depuis un cache navigateur.
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    /**
     * Normalise une valeur pour le tableur.
     *
     * Les nombres sortent avec la VIRGULE décimale (convention française) et
     * sans séparateur de milliers, pour qu'Excel les reconnaisse comme des
     * nombres et non comme du texte.
     */
    private static function cell(mixed $value): string
    {
        if ($value === null) return '';
        if (is_bool($value)) return $value ? 'oui' : 'non';
        if (is_float($value) || is_int($value)) {
            return str_replace('.', ',', (string) round((float) $value, 2));
        }
        return (string) $value;
    }

    /** Nom de fichier horodaté, sans caractère problématique. */
    public static function filename(string $prefix): string
    {
        return $prefix . '-' . now()->format('Y-m-d-Hi') . '.csv';
    }

    /** Itère une requête par paquets sans tout charger en mémoire. */
    public static function lazy($query): LazyCollection
    {
        return $query->lazyById(500);
    }
}
