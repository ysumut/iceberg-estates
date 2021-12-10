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
    public function index()
    {
        $data = Appointment::all();
        return (new Collection($data))->response(true, ['Appointments listed!']);
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
            'post_code' => 'required|string|min:3|max:50'
        ]);
        if($validator->fails()) {
            return (new Collection([]))->response(false, $validator->errors()->all());
        }

        // PostCodes and GoogleMaps Service
        $service = AddressService::calculate($request->post_code);
        if(!$service['status']) {
            return (new Collection([],500))->response(false, ['Services not available!']);
        }
        $data = $service['data'];

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
        $appointment->address_destination = $data['address_destination'];
        $appointment->distance = $data['distance'];
        $appointment->appointment_date = null;
        $appointment->office_leaving_date = null;
        $appointment->office_arrival_date = null;
        $appointment->user_id = auth()->id();
        $appointment->contact_id = $contact->id;
        $appointment->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Appointment::where('id', $id)->get();
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Appointment::where('id', $id)->delete();
        return (new Collection([]))->response(true, ['Appointment deleted!']);
    }
}
