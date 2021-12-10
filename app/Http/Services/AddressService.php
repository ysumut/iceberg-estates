<?php

namespace App\Http\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressService
{
    public static function calculate(String $postcode_origin, String $postcode_destination): array
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

    public static function timePlanning(float $duration): array
    {
        $last = Appointment::where('user_id', auth()->id())->orderByDesc('appointment_date')->first();
        $now = now();
        $nowDateTime = $now->toDateTimeString();
        $base = null;

        if($last) {
            $appointment_finish_date = Carbon::create($last->appointment_date)->addHour()->toDateTimeString();

            if($last->office_arrival_date >= $nowDateTime && $appointment_finish_date <= $nowDateTime) {
                // Ofise varış saatinden hesaplanır
                $base = Carbon::create($last->office_arrival_date);
            }
            else if($appointment_finish_date >= $nowDateTime && $last->office_leaving_date <= $nowDateTime) {
                // Randevunun bitişinden hesaplanır
                $base = Carbon::create($last->appointment_date)->addHour();
            }
            else if($last->office_leaving_date >= $nowDateTime) {
                // Diğer randevuya zaman varsa şimdiden hesaplanır
                if($last->office_leaving_date->diffInSeconds($now) > $duration*2 + 60) {
                    $base = $now;
                }
            }
        }

        // Hiçbir durum gerçekleşmezse şimdiden hesaplanır
        if(!$base) {
            $base = $now;
        }

        return [
            'leave_office' => $base->toDateTimeString(),
            'appointment_date' => $base->copy()->addSeconds($duration)->toDateTimeString(),
            'arrival_office' => $base->copy()->addSeconds($duration*2 + 60)->toDateTimeString(),
        ];
    }
}










