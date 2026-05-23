<?php
// app/Services/MainApiService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MainApiService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('api.main_app.api_base'), '/');
        $this->token   = session('api_token', '');
    }

    public function get(string $path, array $query = [])
    {
        return Http::withoutVerifying()
            ->withToken($this->token)           // Bearer token every time
            ->timeout(15)
            ->get($this->baseUrl . $path, $query);
    }

    public function post(string $path, array $data = [])
    {
        return Http::withoutVerifying()
            ->withToken($this->token)
            ->timeout(15)
            ->post($this->baseUrl . $path, $data);
    }

    public function postMultipart(string $path, array $fields, array $files = [])
    {
        $http = Http::withoutVerifying()
            ->withToken($this->token)
            ->timeout(30)
            ->asMultipart();

        foreach ($files as $name => $file) {
            $http = $http->attach($name, file_get_contents($file), $file->getClientOriginalName());
        }

        return $http->post($this->baseUrl . $path, $fields);
    }
}