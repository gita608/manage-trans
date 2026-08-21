<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTestFirebaseCredentialsExist();
    }

    /**
     * CI and fresh checkouts do not include the gitignored Firebase credentials
     * file. Create a disposable service-account JSON so services that resolve
     * FirebaseNotificationService can boot during tests (calls remain mocked).
     */
    protected function ensureTestFirebaseCredentialsExist(): void
    {
        $path = storage_path('app/firebase-service-account.json');

        if (is_file($path)) {
            return;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($key, $privateKey);

        $account = [
            'type' => 'service_account',
            'project_id' => 'ci-test',
            'private_key_id' => 'ci-test-key',
            'private_key' => $privateKey,
            'client_email' => 'firebase-adminsdk@ci-test.iam.gserviceaccount.com',
            'client_id' => '1234567890',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];

        file_put_contents($path, json_encode($account, JSON_PRETTY_PRINT));
    }
}
