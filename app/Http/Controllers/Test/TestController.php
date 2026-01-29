<?php

namespace App\Http\Controllers\Test;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       
        // 1) حكمة عربية
        $quoteRes = Http::get("https://api.adviceslip.com/advice");
        $quoteData = json_decode($quoteRes->body(), true);
        $quote = $quoteData['slip']['advice'] ?? "لا توجد حكمة الآن";

        // 2) أسعار العملات مقابل الشيكل
        $cur = Http::get("https://open.er-api.com/v6/latest/ILS")->json();

        $usd = $cur['rates']['USD'] ?? 0;
        $eur = $cur['rates']['EUR'] ?? 0;
        $egp = $cur['rates']['EGP'] ?? 0;

        // 3) مواقيت الصلاة غزة + تحويل 12 ساعة + العربي
        $prayerRes = Http::get("https://api.aladhan.com/v1/timingsByCity", [
            "city" => "Gaza",
            "country" => "Palestine",
            "method" => 4
        ]);

        $t = $prayerRes->json()['data']['timings'];

        // تحويل وقت لصيغة 12 AM/PM
        function to12($time) {
            return Carbon::createFromFormat('H:i', $time)->format('h:i A');
        }

        $prayer = [
            "الفجر"   => to12($t['Fajr']),
            "الظهر"   => to12($t['Dhuhr']),
            "العصر"   => to12($t['Asr']),
            "المغرب"  => to12($t['Maghrib']),
            "العشاء"  => to12($t['Isha']),
        ];

        return view('test', compact('quote', 'usd', 'eur', 'egp', 'prayer'));   

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
