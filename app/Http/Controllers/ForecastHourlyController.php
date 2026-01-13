<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForecastHourlyRequest;
use App\Http\Requests\UpdateForecastHourlyRequest;
use App\Models\ForecastHourly;

class ForecastHourlyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreForecastHourlyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ForecastHourly $forecastHourly)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ForecastHourly $forecastHourly)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateForecastHourlyRequest $request, ForecastHourly $forecastHourly)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ForecastHourly $forecastHourly)
    {
        //
    }
}
