<?php
/**
 * CIS Controls Plugin for GLPI 11
 * inc/risk.class.php - Cybersecurity Risk model
 */

class PluginCiscontrolsRisk extends CommonDBTM {

    static $rightname = 'plugin_ciscontrols';

    public static function getTypeName($nb = 0) {
        return 'Riesgo de Ciberseguridad';
    }

    public static function getIcon() {
        return 'ti ti-alert-triangle';
    }

    public static function categories(): array {
        return [
            'acceso'          => 'Acceso y Autenticación',
            'datos'           => 'Gestión de Datos y Privacidad',
            'infraestructura' => 'Infraestructura y Redes',
            'malware'         => 'Malware y Amenazas',
            'cumplimiento'    => 'Cumplimiento Normativo',
            'continuidad'     => 'Continuidad del Negocio',
            'terceros'        => 'Terceros y Proveedores',
            'fisico'          => 'Seguridad Física',
            'desarrollo'      => 'Desarrollo y Aplicaciones',
            'otro'            => 'Otro',
        ];
    }

    public static function likelihoodLabels(): array {
        return [
            1 => 'Rara',
            2 => 'Improbable',
            3 => 'Posible',
            4 => 'Probable',
            5 => 'Casi Segura',
        ];
    }

    public static function impactLabels(): array {
        return [
            1 => 'Insignificante',
            2 => 'Menor',
            3 => 'Moderado',
            4 => 'Mayor',
            5 => 'Catastrófico',
        ];
    }

    public static function treatmentOptions(): array {
        return [
            'mitigar'    => 'Mitigar',
            'aceptar'    => 'Aceptar',
            'transferir' => 'Transferir',
            'evitar'     => 'Evitar',
        ];
    }

    public static function statusOptions(): array {
        return [
            'abierto'       => 'Abierto',
            'en_tratamiento' => 'En tratamiento',
            'residual'      => 'Riesgo residual',
            'cerrado'       => 'Cerrado',
        ];
    }

    public static function score(int $likelihood, int $impact): int {
        return $likelihood * $impact;
    }

    public static function level(int $score): string {
        if ($score >= 15) return 'critico';
        if ($score >= 10) return 'alto';
        if ($score >= 5)  return 'medio';
        return 'bajo';
    }

    public static function levelLabel(int $score): string {
        return ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto', 'critico' => 'Crítico'][self::level($score)];
    }

    public static function levelBadgeClass(int $score): string {
        return ['bajo' => 'success', 'medio' => 'warning text-dark', 'alto' => 'orange', 'critico' => 'danger'][self::level($score)];
    }

    public static function levelHex(int $score): string {
        return ['bajo' => '#198754', 'medio' => '#ffc107', 'alto' => '#fd7e14', 'critico' => '#dc3545'][self::level($score)];
    }

    // Background CSS class for matrix cell
    public static function cellStyle(int $likelihood, int $impact): string {
        $hex = self::levelHex(self::score($likelihood, $impact));
        return "background-color:{$hex};color:" . ($likelihood <= 2 && $impact <= 2 ? '#000' : '#fff') . ";opacity:0.85;";
    }
}
