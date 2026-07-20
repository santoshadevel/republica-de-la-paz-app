<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Read-only view of the brand's public contact channels (config/contact.php).
 *
 * Exists so views never touch config() or build URLs by hand, and so a channel
 * left blank in a white-label deploy simply disappears from the landing.
 */
class ContactChannels
{
    public function __construct(
        private readonly ?string $whatsapp = null,
        private readonly ?string $instagram = null,
        private readonly ?string $email = null,
        private readonly ?string $location = null,
        private readonly ?string $mapEmbedUrl = null,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            whatsapp: config('contact.whatsapp'),
            instagram: config('contact.instagram'),
            email: config('contact.email'),
            location: config('contact.location'),
            mapEmbedUrl: config('contact.map_embed_url'),
        );
    }

    /** Digits-only number, as wa.me requires. */
    public function whatsappNumber(): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->whatsapp);

        return filled($digits) ? $digits : null;
    }

    /** wa.me link, optionally with a prefilled message. */
    public function whatsappUrl(?string $message = null): ?string
    {
        $number = $this->whatsappNumber();

        if ($number === null) {
            return null;
        }

        $url = "https://wa.me/{$number}";

        return filled($message)
            ? $url.'?text='.rawurlencode($message)
            : $url;
    }

    public function instagramHandle(): ?string
    {
        return blank($this->instagram) ? null : Str::start(ltrim($this->instagram, '@'), '@');
    }

    public function instagramUrl(): ?string
    {
        return blank($this->instagram)
            ? null
            : 'https://instagram.com/'.ltrim($this->instagram, '@');
    }

    public function email(): ?string
    {
        return blank($this->email) ? null : $this->email;
    }

    public function location(): ?string
    {
        return blank($this->location) ? null : $this->location;
    }

    public function mapEmbedUrl(): ?string
    {
        return blank($this->mapEmbedUrl) ? null : $this->mapEmbedUrl;
    }
}
