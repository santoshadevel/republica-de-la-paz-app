<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public contact channels
    |--------------------------------------------------------------------------
    |
    | Everything the public landing needs to reach the brand. Kept in config
    | (not hardcoded in views) so a white-label deploy only swaps the .env.
    |
    */

    /**
     * WhatsApp number in international format, digits only (no +, spaces or
     * dashes) — that is what wa.me expects. Leave blank to hide the channel.
     */
    'whatsapp' => env('CONTACT_WHATSAPP'),

    /** Instagram handle without the leading @. Blank hides the channel. */
    'instagram' => env('CONTACT_INSTAGRAM'),

    /** Public inbox shown as a fallback for people who do not use WhatsApp. */
    'email' => env('CONTACT_EMAIL'),

    /** Human-readable location shown next to the map. */
    'location' => env('CONTACT_LOCATION'),

    /** Google Maps embed URL (iframe src). Blank hides the map. */
    'map_embed_url' => env('CONTACT_MAP_EMBED_URL'),

];
