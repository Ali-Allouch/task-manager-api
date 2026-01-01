<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/register",
     * summary="Register a new user",
     * description="Create a new account and get an access token",
     * tags={"Authentication"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","email","password","password_confirmation"},
     * @OA\Property(property="name", type="string", example="Full Name"),
     * @OA\Property(property="email", type="string", format="email", example="youremail@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="User registered successfully"
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation error"
     * )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * summary="Login and get token",
     * tags={"Authentication"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123")
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Logged in successfully",
     * ),
     * @OA\Response(
     * response=401,
     * description="Wrong credentials"
     * )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Logout and revoke token",
     * tags={"Authentication"},
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Logged out successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Logged out successfully")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated"
     * )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
