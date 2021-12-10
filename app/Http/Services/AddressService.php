<?php

namespace App\Http\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressService
{
    public static function service(String $postcode_origin, String $postcode_destination): array
    {
        try {
            $origin = Http::get("https://api.postcodes.io/postcodes/$postcode_origin")->json();
            $destination = Http::get("https://api.postcodes.io/postcodes/$postcode_destination")->json();

            if($origin['status'] != 200 || $destination['status'] != 200) {
                Log::error('PostCode Service ERROR:');
                Log::error($origin);
                Log::error($destination);
                return ['status' => false];
            }

            $googleMaps = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'key' => env('GOOGLE_MAPS_KEY'),
                'language' => 'en-EN',
                'destinations' => $destination['result']['latitude'] . ',' . $destination['result']['longitude'],
                'origins' => $origin['result']['latitude'] . ',' . $origin['result']['longitude'],
            ])->json();

            if(!isset($googleMaps) || !isset($googleMaps['destination_addresses']) || $googleMaps['destination_addresses'][0] == "") {
                Log::error('Google Maps ERROR:');
                Log::error($googleMaps);
                return ['status' => false];
            }

            return [
                'status' => true,
                'data' => [
                    'address_destination' => $googleMaps['destination_addresses'][0],
                    'distance' => $googleMaps['rows'][0]['elements'][0]['distance']['value'],
                    'duration' => $googleMaps['rows'][0]['elements'][0]['duration']['value'],
                ]
            ];
        }
        catch (\Exception $e) {
            Log::error('Distance Service ERROR:');
            Log::error($e->getMessage());
            return ['status' => false];
        }
    }

    public static function timePlanning(float $duration, String $appointment_date, Appointment $appointment = null): array
    {
        if($appointment) {
            $last_appointment = Appointment::where('user_id', $appointment->user_id)->where('id', '!=', $appointment->id)->orderByDesc('appointment_date')->first();
        }
        else {
            $last_appointment = Appointment::where('user_id', auth()->id())->orderByDesc('appointment_date')->first();
        }
        $office_leaving_date = Carbon::create($appointment_date)->subSeconds($duration);

        if($office_leaving_date < now()) {
            return ['status' => false, 'messages' => ['Not enough time for the leave office.']];
        }

        if($last_appointment) {
            if($last_appointment->office_arrival_date > $office_leaving_date) {
                return ['status' => false, 'messages' => ['There is another appointment on that date.']];
            }
        }

        return [
            'status' => true,
            'data' => [
                'leave_office' => $office_leaving_date->toDateTimeString(),
                'appointment_date' => $office_leaving_date->copy()->addSeconds($duration)->toDateTimeString(),
                'arrival_office' => $office_leaving_date->copy()->addSeconds($duration*2 + 60)->toDateTimeString(),
            ]
        ];
    }
}










