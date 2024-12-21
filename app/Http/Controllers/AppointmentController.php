<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if($request->only_current_user) {
            $appointments = Appointment::where('user_id', $request->user()->id)->orderBy('date_with_time', 'asc')->get();

            return [ 'appointments' => $appointments];
        }

        $start_date = Carbon::parse($request->start_date) ?? Carbon::now();
        $start_date = $start_date->startOfDay();
        $end_date = (clone $start_date)->addDays(6)->endOfDay();

        for ($i = 0; $i < 7; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i days", strtotime($start_date)));
        }

        $times = [
            '08:00:00', '08:30:00', '09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00',
            '13:00:00', '13:30:00', '14:00:00', '14:30:00', '15:00:00', '15:30:00', '16:00:00', '16:30:00'
        ];

        $appointments = Appointment::whereBetween('date_with_time', [$start_date, $end_date])->orderBy('date_with_time', 'asc')->get();

        return ['dates' => $dates, 'times' => $times,  'appointments' => $appointments];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dateWithTimeData = $request->validate([
            'dateWithTime' => 'required|date',
        ]);

        $dateWithTime = $dateWithTimeData['dateWithTime'];

        $dateNow = Carbon::now()->setTimezone('America/Managua');
        $dateWithTimeCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $dateWithTime, 'America/Managua');

        if($dateWithTimeCarbon->isBefore($dateNow)) {
            return response()->json([
                'error' => "The date and time selected can not be before the current date and time",
            ], 422);
        }

        $previousAppointmentsCount = Appointment::where('date_with_time', $dateWithTime)->where('status', 'Reserved')->count();

        if($previousAppointmentsCount > 0) {
            return response()->json([
                'error' => "Date and time is already reserved",
            ], 422);
        }

        $appointment = Appointment::create(
            [
                'user_id' => $request->user()->id,
                'date_with_time' => $dateWithTime,
                'status' => 'Reserved'
            ]
        );

        return ['appointment' => $appointment];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $appointment = Appointment::where('id', $id)->update([
            'status' =>'Canceled'
        ]);

        return ['appointment' => $appointment];
    }
}
