<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

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
            // Create notification object
            $notification = Notification::create($title, $body);
            
            // Add image if provided
            if ($image) {
                $notification = Notification::create($title, $body)
                    ->withImageUrl($image);
            }
            
            // Create the message
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);
            
            // Add data payload if provided
            if (!empty($extraData)) {
                $message = $message->withData($extraData);
            }
            
            $this->messaging->send($message);
            
            Log::info('Firebase notification sent successfully', [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
            ]);
            
            return true; // Notification sent successfully
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Firebase notification error: ' . $e->getMessage(), [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
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
            Log::warning('Driver has no notification token', [
                'driver_id' => $driver->id,
            ]);
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

