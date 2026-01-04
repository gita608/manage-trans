<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = config('services.firebase.credentials_path', storage_path('app/firebase-service-account.json'));

        $firebase = (new Factory)
            ->withServiceAccount($credentialsPath);

        $this->messaging = $firebase->createMessaging();
    }

    /**
     * Send push notification to a specific device
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param string|null $image
     * @param array $extraData
     * @return bool
     */
    public function sendPushNotification($deviceToken, $title, $body, $image = null, $extraData = [])
    {
        try {
            // ALWAYS include title and body in data payload
            // This ensures onMessageReceived() is called in ALL app states (foreground, background, closed)
            $dataPayload = array_merge([
                'title' => $title,
                'body' => $body,
            ], $extraData);
            
            // Add image URL to data if provided
            if ($image) {
                $dataPayload['image'] = $image;
            }
            
            // Create the message with data-only payload
            // This is the CORRECT way to ensure background notification handling works
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withData($dataPayload)
                ->withAndroidConfig([
                    'priority' => 'high',
                ]);
            
            // Send the message
            $this->messaging->send($message);
            
            return true; // Notification sent successfully
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     *
     * @param array $deviceTokens
     * @param string $title
     * @param string $body
     * @param string|null $image
     * @param array $extraData
     * @return array Returns array with 'success' and 'failed' counts
     */
    public function sendPushNotificationToMultiple($deviceTokens, $title, $body, $image = null, $extraData = [])
    {
        $results = [
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($deviceTokens as $token) {
            if ($this->sendPushNotification($token, $title, $body, $image, $extraData)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send push notification to a driver
     *
     * @param \App\Models\Driver $driver
     * @param string $title
     * @param string $body
     * @param string|null $image
     * @param array $extraData
     * @return bool
     */
    public function sendToDriver($driver, $title, $body, $image = null, $extraData = [])
    {
        if (!$driver->notification_token) {
            return false;
        }

        return $this->sendPushNotification(
            $driver->notification_token,
            $title,
            $body,
            $image,
            $extraData
        );
    }
}

