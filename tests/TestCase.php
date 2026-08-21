<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * True when this test instance wrote the disposable Firebase credential file.
     */
    protected bool $createdTestFirebaseCredentials = false;

    /**
     * Cached RSA private key so tests do not regenerate on every case.
     */
    protected static ?string $testFirebasePrivateKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTestFirebaseCredentialsExist();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFirebaseCredentials();

        parent::tearDown();
    }

    /**
     * CI and fresh checkouts do not include production Firebase credentials.
     * Create a disposable service-account JSON only under a test-only path and
     * point config there so production storage/app credentials are never touched.
     */
    protected function ensureTestFirebaseCredentialsExist(): void
    {
        $path = storage_path('framework/testing/firebase-service-account.json');

        if (! is_file($path)) {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            if (self::$testFirebasePrivateKey === null) {
                $key = openssl_pkey_new([
                    'private_key_bits' => 2048,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                ]);

                openssl_pkey_export($key, $privateKey);
                self::$testFirebasePrivateKey = $privateKey;
            }

            $account = [
                'type' => 'service_account',
                'project_id' => 'ci-test',
                'private_key_id' => 'ci-test-key',
                'private_key' => self::$testFirebasePrivateKey,
                'client_email' => 'firebase-adminsdk@ci-test.iam.gserviceaccount.com',
                'client_id' => '1234567890',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ];

            file_put_contents($path, json_encode($account, JSON_PRETTY_PRINT));
            $this->createdTestFirebaseCredentials = true;
        }

        config([
            'services.firebase.credentials_path' => $path,
        ]);
    }

    /**
     * Remove only the disposable test credential file created by this framework.
     * Never touches storage/app/firebase-service-account.json.
     */
    protected function cleanupTestFirebaseCredentials(): void
    {
        if (! $this->createdTestFirebaseCredentials) {
            return;
        }

        $path = storage_path('framework/testing/firebase-service-account.json');

        if (is_file($path)) {
            @unlink($path);
        }

        $this->createdTestFirebaseCredentials = false;
    }
}
