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
        $logContext = [
            'device_token_preview' => substr($deviceToken, 0, 20) . '...',
            'title' => $title,
            'body_preview' => substr($body, 0, 50) . (strlen($body) > 50 ? '...' : ''),
            'has_image' => !empty($image),
            'has_extra_data' => !empty($extraData),
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Attempting to send Firebase push notification', $logContext);

        try {
            // Create notification object
            $notification = Notification::create($title, $body);
            
            // Add image if provided
            if ($image) {
                $notification = Notification::create($title, $body)
                    ->withImageUrl($image);
                Log::debug('Notification image added', ['image_url' => $image]);
            }
            
            // Create the message
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);
            
            // Add data payload if provided
            if (!empty($extraData)) {
                $message = $message->withData($extraData);
                Log::debug('Extra data added to notification', ['data_keys' => array_keys($extraData)]);
            }
            
            // Send the message
            $result = $this->messaging->send($message);
            
            Log::info('Firebase push notification sent successfully', array_merge($logContext, [
                'status' => 'success',
                'message_id' => $result ?? 'N/A',
            ]));
            
            return true; // Notification sent successfully
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Firebase push notification failed', array_merge($logContext, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_class' => get_class($e),
            ]));
            
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
        $logContext = [
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'driver_email' => $driver->email,
            'title' => $title,
            'has_notification_token' => !empty($driver->notification_token),
        ];

        Log::info('Attempting to send push notification to driver', $logContext);

        if (!$driver->notification_token) {
            Log::warning('Push notification skipped: Driver has no notification token', $logContext);
            return false;
        }

        $result = $this->sendPushNotification(
            $driver->notification_token,
            $title,
            $body,
            $image,
            $extraData
        );

        if ($result) {
            Log::info('Push notification sent to driver successfully', array_merge($logContext, [
                'status' => 'success',
            ]));
        } else {
            Log::error('Push notification failed to send to driver', array_merge($logContext, [
                'status' => 'failed',
            ]));
        }

        return $result;
    }
}

