<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    /**
     * Web app manifest, built from the configurable app settings so the installed
     * app carries the same name as the rest of the UI.
     */
    public function manifest(): JsonResponse
    {
        $name = getSetting('app_name', config('app.name', 'ManageTrans'));

        $manifest = [
            'name' => $name,
            'short_name' => $this->shortName($name),
            'description' => 'Transportation management: trips, drivers, crews and reports.',
            'id' => '/',
            'start_url' => '/?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'any',
            'theme_color' => '#405189',
            'background_color' => '#ffffff',
            'lang' => 'en',
            'dir' => 'ltr',
            'categories' => ['business', 'productivity'],
            'icons' => [
                [
                    'src' => asset('assets/images/pwa/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/images/pwa/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/images/pwa/icon-maskable-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => asset('assets/images/pwa/icon-maskable-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Trips',
                    'url' => '/trips',
                    'icons' => [[
                        'src' => asset('assets/images/pwa/icon-192.png'),
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ]],
                ],
                [
                    'name' => 'Drivers',
                    'url' => '/drivers',
                    'icons' => [[
                        'src' => asset('assets/images/pwa/icon-192.png'),
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ]],
                ],
                [
                    'name' => 'Daily Activities',
                    'url' => '/daily-activities',
                    'icons' => [[
                        'src' => asset('assets/images/pwa/icon-192.png'),
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ]],
                ],
            ],
        ];

        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function shortName(string $name): string
    {
        return mb_strlen($name) <= 12 ? $name : mb_substr($name, 0, 12);
    }
}
