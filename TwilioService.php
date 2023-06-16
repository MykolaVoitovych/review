<?php

namespace App\Services;

use App\Models\TwilioPhoneNumber;
use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(env("TWILIO_ACCOUNT_SID"), env("TWILIO_AUTH_TOKEN"));
    }

    public function sendSms(string $phoneFrom, string $phoneTo, string $message)
    {
        if (getenv("APP_ENV") === 'testing') {
            return;
        }
        $this->client->messages->create(
            $phoneTo, // Text this number
            [
                'from' => $phoneFrom, // From a valid Twilio number
                'body' => $message,
                'StatusCallback' => route('twilio.receiving-errors'),
            ]
        );
    }

    public function getIncomingPhoneNumbers()
    {
        return $this->client->incomingPhoneNumbers->read();
    }

    public function syncPhoneNumbers()
    {
        foreach ($this->getIncomingPhoneNumbers() as $incomingPhoneNumber) {
            TwilioPhoneNumber::firstOrCreate([
                'phone_number' => $incomingPhoneNumber->phoneNumber
            ], [
                'phone_number' => $incomingPhoneNumber->phoneNumber
            ]);
        }
    }

    protected function deleteNotSupportPhoneNumbers(array $phoneNumbers)
    {
        TwilioPhoneNumber::whereNotIn('phone_number', $phoneNumbers)->delete();
    }

    public function getMessage(string $sid)
    {
        return $this->client->messages($sid)->fetch();
    }

}
