<?php

test('brand assets are available for the public and Filament interfaces', function (): void {
    foreach ([
        public_path('images/brand/contpass-logo-horizontal.png'),
        public_path('images/brand/contpass-logo-mark.png'),
        public_path('images/brand/contpass-icon-32.png'),
        public_path('images/brand/contpass-icon-180.png'),
        public_path('images/brand/contpass-icon-192.png'),
        public_path('images/brand/contpass-icon-512.png'),
        public_path('favicon.ico'),
    ] as $assetPath) {
        expect($assetPath)->toBeFile();
    }
});
