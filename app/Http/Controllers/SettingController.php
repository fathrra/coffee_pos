<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const DEFAULTS = [
        'store_name' => 'CoffeePOS',
        'store_address' => '',
        'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
        'default_tax' => 0,
    ];

    public function index()
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = Setting::get($key, $default);
        }

        return response()->json($settings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_address' => 'nullable|string|max:500',
            'receipt_footer' => 'nullable|string|max:500',
            'default_tax' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json($this->index()->getData());
    }
}
