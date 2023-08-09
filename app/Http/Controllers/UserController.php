<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    // Синхронизация аккаунта из идентификационного сервиса FINDCREEK ID с базой данных мессенджера.
    public function registerUser($id)
    {
        $url = env("ID_SERVICE_URL") . "users.getInfo/";
        $response = Http::get($url, [
            "serviceToken" => env("SERVICE_TOKEN"),
            "usersIDs" => $id,
            "fields" => "id"
        ]);

        if ($response->successful()) {
            $json = $response->json();

            if (empty($json["response"])) {
                return response()->json([
                    'message' => "Failed to verify user id"
                ], 500);
            }

            User::firstOrCreate(
                ["id" => $json["response"][0]["id"]],
                ["id" => $json["response"][0]["id"]]
            );

            return response()->json("", 204);

        } else {
            return response()->json([
                'message' => "Failed to send a request to verify the user ID"
            ], 500);
        }
    }
}
