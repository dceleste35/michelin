<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Génère un QR code SVG en local (via bacon/bacon-qr-code, déjà fourni par Fortify 2FA).
 * Aucune dépendance externe ni appel réseau.
 */
class QrCode
{
    /**
     * Rendu d'un QR code en SVG inline pour la donnée fournie (ex. une URL).
     */
    public static function svg(string $data, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd,
        ));

        $svg = $writer->writeString($data);

        // On retire le prologue XML pour pouvoir injecter le SVG directement dans la page.
        $start = strpos($svg, '<svg');

        return $start === false ? $svg : substr($svg, $start);
    }
}
