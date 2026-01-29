<?php

namespace App\Http\Controllers\Test;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         // 1) API جلب حكمة عشوائية
        $quoteRes = Http::get("https://zenquotes.io/api/random");
        $quote = $quoteRes->json()[0] ?? null;

        // 2) API أسعار العملات مقابل الشيكل
        // USD + EUR + EGP
        $currencyRes = Http::get("https://api.exchangerate.host/latest?base=ILS");
        $rates = $currencyRes->json()['rates'] ?? [];

        // 3) API مواقيت الصلاة غزة فلسطين
        $prayerRes = Http::get("https://api.aladhan.com/v1/timingsByCity", [
            "city" => "Gaza",
            "country" => "Palestine",
            "method" => 4
        ]);
        $prayer = $prayerRes->json()['data']['timings'] ?? [];

         return view('test', compact('quote', 'rates', 'prayer'));

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
        //
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
        //
    }
}
