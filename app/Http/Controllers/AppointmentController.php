<?php

namespace App\Http\Controllers;

use App\Http\Resources\Collection;
use App\Http\Services\AddressService;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if($user->user_type == User::BOSS_ID) {
            $data = Appointment::with('contact');
        }
        else {
            $data = Appointment::with('contact')->where('user_id', $user->id);
        }

        if($request->filter_by_created && in_array($request->filter_by_created, ['asc','desc'])) {
            $data = $data->orderBy('created_at', $request->filter_by_created);
        }

        return (new Collection($data->get()))->response(true, ['Appointments listed!']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50|regex:/(?!^\d+$)^.+$/', // regex : not all are numbers
            'surname' => 'required|string|min:3|max:75|regex:/(?!^\d+$)^.+$/',
            'email' => 'required|email|max:100',
            'phone_number' => 'required|string|min:5|max:30',
            'post_code' => 'required|string|min:3|max:50',
            'appointment_date' => 'required|date|after:now',
        ]);
        if($validator->fails()) {
            return (new Collection([]))->response(false, $validator->errors()->all());
        }

        // PostCodes and GoogleMaps Service
        $service = AddressService::service(User::OFFICE_POST_CODE, $request->post_code);
        if(!$service['status']) {
            return (new Collection([],500))->response(false, ['Address Service not available!']);
        }

        // Time Planning
        $timePlanning = AddressService::timePlanning($service['data']['duration'], $request->appointment_date);
        if(!$timePlanning['status']) {
            return (new Collection([],500))->response(false, $timePlanning['messages']);
        }

        // Add new contact
        $contact = new Contact();
        $contact->name = $request->name;
        $contact->surname = $request->surname;
        $contact->email = $request->email;
        $contact->phone_number = $request->phone_number;
        $contact->post_code = $request->post_code;
        $contact->save();

        // Add new appointment
        $appointment = new Appointment();
        $appointment->address_destination = $service['data']['address_destination'];
        $appointment->distance = $service['data']['distance'];
        $appointment->office_leaving_date = $timePlanning['data']['leave_office'];
        $appointment->appointment_date = $timePlanning['data']['appointment_date'];
        $appointment->office_arrival_date = $timePlanning['data']['arrival_office'];
        $appointment->user_id = auth()->id();
        $appointment->contact_id = $contact->id;
        $appointment->save();

        return (new Collection([]))->response(true, ['Appointment added!']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = auth()->user();

        if($user->user_type == User::BOSS_ID) {
            $data = Appointment::with('contact')->where('id', $id)->get();
        }
        else {
            $data = Appointment::with('contact')->where('user_id', $user->id)->where('id', $id)->get();
        }

        return (new Collection($data))->response(true, ['Appointment listed!']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|min:3|max:50|regex:/(?!^\d+$)^.+$/', // regex : not all are numbers
            'surname' => 'string|min:3|max:75|regex:/(?!^\d+$)^.+$/',
            'email' => 'email|max:100',
            'phone_number' => 'string|min:5|max:30',
            'post_code' => 'string|min:3|max:50',
            'appointment_date' => 'date|after:now',
        ]);
        if($validator->fails()) {
            return (new Collection([]))->response(false, $validator->errors()->all());
        }

        $user = auth()->user();
        $appointment = Appointment::find($id);

        if(!$appointment) {
            return (new Collection([], 404))->response(false, ['Appointment not found!']);
        }

        if($user->user_type != User::BOSS_ID && $appointment->user_id != $user->id) {
            return (new Collection([], 403))->response(false, ['You cannot update this appointment! Only boss or appointment holder can update.']);
        }

        if($request->post_code || $request->appointment_date) {
            // PostCodes and GoogleMaps Service
            $service = AddressService::service(User::OFFICE_POST_CODE, $request->post_code);
            if(!$service['status']) {
                return (new Collection([],500))->response(false, ['Address Service not available!']);
            }

            // Time Planning
            $timePlanning = AddressService::timePlanning($service['data']['duration'], $request->appointment_date, $appointment);
            if(!$timePlanning['status']) {
                return (new Collection([],500))->response(false, $timePlanning['messages']);
            }
        }

        // Add new appointment
        $appointment->address_destination = isset($service) ? $service['data']['address_destination'] : $appointment->address_destination;
        $appointment->distance = isset($service) ? $service['data']['distance'] : $appointment->distance;
        $appointment->office_leaving_date = isset($timePlanning) ? $timePlanning['data']['leave_office'] : $appointment->office_leaving_date;
        $appointment->appointment_date = isset($timePlanning) ? $timePlanning['data']['appointment_date'] : $appointment->appointment_date;
        $appointment->office_arrival_date = isset($timePlanning) ? $timePlanning['data']['arrival_office'] : $appointment->office_arrival_date;
        $appointment->save();

        // Add new contact
        $contact = Contact::find($appointment->contact_id);
        $contact->name = $request->name ?? $contact->name;
        $contact->surname = $request->surname ?? $contact->surname;
        $contact->email = $request->email ?? $contact->email;
        $contact->phone_number = $request->phone_number ?? $contact->phone_number;
        $contact->post_code = $request->post_code ?? $contact->post_code;
        $contact->save();

        return (new Collection([]))->response(true, ['Appointment updated!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $data = Appointment::find($id);

        if($user->user_type != User::BOSS_ID && $data->user_id != $user->id) {
            return (new Collection([], 403))->response(false, ['You cannot delete this appointment! Only boss or appointment holder can delete.']);
        }

        $data->delete();

        return (new Collection([]))->response(true, ['Appointment deleted!']);
    }
}
