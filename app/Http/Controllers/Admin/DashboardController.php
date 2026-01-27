<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'stats' => [
                'users' => 120,
                'active_users' => 87,
                'services' => 32,
                'income_month' => 24500,
            ],
            'recent_activity' => [
                ['message' => 'Usuario Juan creó una orden'],
                ['message' => 'Nuevo servicio registrado'],
                ['message' => 'Pago recibido'],
            ]
        ]);
    }
}
