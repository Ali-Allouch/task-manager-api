<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Task Management System API",
 * description="",
 * @OA\Contact(
 * email="contact@aliallouche.me"
 * ),
 * )
 *
 * @OA\Server(
 * url="http://127.0.0.1:8000",
 * description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT",
 * securityScheme="bearerAuth",
 * description="Enter the Bearer Token obtained from the Login API"
 * )
 */

abstract class Controller
{
    //
}
