<?php

return [

    /*
    |--------------------------------------------------------------------------
    | pdftk Binary
    |--------------------------------------------------------------------------
    |
    | DMV forms are produced by filling the official PDF templates with pdftk.
    | It is an external binary, not a Composer package, so it has to come from
    | somewhere on the host.
    |
    | Resolution order:
    |
    |   1. PDFTK_BINARY, if set. An explicit override always wins.
    |   2. The self-contained install in vendor-bin/pdftk, staged at build time
    |      by scripts/install-pdftk.sh. This is what production uses, because
    |      Laravel Cloud's image has no pdftk and no way to apt-install one.
    |   3. 'pdftk' on the PATH. This is what a developer machine normally uses
    |      and means local work is unaffected by any of the above.
    |
    | The bundled path is only used when it is actually executable, so a failed
    | or partial install falls through to the system binary rather than
    | breaking a machine that was working.
    |
    */

    'binary' => env('PDFTK_BINARY'),

    'bundled_path' => base_path('vendor-bin/pdftk/bin/pdftk'),

    'fallback' => 'pdftk',

];
