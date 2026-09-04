<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('app', ['activePage' => 'dashboard']);
    }

    public function kasir()
    {
        return view('app', ['activePage' => 'kasir']);
    }

    public function produk()
    {
        return view('app', ['activePage' => 'produk']);
    }

    public function kategori()
    {
        return view('app', ['activePage' => 'kategori']);
    }

    public function stok()
    {
        return view('app', ['activePage' => 'stok']);
    }

    public function transaksi()
    {
        return view('app', ['activePage' => 'transaksi']);
    }

    public function laporan()
    {
        return view('app', ['activePage' => 'laporan']);
    }
}
