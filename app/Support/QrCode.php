<?php

namespace App\Support;

use App\Models\Participant;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    public static function svg(string $text, int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        // Drop the XML prolog so the SVG can be inlined in HTML.
        return preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($text));
    }

    /** The QR holds the public ticket URL; the scanner reads the token from its tail. */
    public static function forParticipant(Participant $participant, int $size = 220): string
    {
        return self::svg(route('ticket', $participant->qr_token), $size);
    }
}
