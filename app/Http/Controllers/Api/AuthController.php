<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     * path="/register",
     * summary="Registro de nuevos usuarios",
     * description="Crea un nuevo usuario en el marketplace y le asigna un rol (comprador o vendedor).",
     * tags={"Autenticación"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","email","password","phone","role"},
     * @OA\Property(property="name", type="string", example="Juan Pérez"),
     * @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="phone", type="string", example="7777-7777"),
     * @OA\Property(property="role", type="string", example="vendedor")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Usuario registrado exitosamente",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="user", type="object"),
     * @OA\Property(property="token", type="string")
     * )
     * )
     * )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
        ]);

        $user->assignRole($request->role);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $request->role,
            ],
            'token' => $token,
        ], 'Usuario registrado exitosamente', 201);
    }

    /**
     * @OA\Post(
     * path="/login",
     * summary="Inicio de sesión",
     * description="Autentica al usuario y devuelve un token Bearer.",
     * tags={"Autenticación"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", example="juan@example.com"),
     * @OA\Property(property="password", type="string", example="password123")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Login exitoso",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="token", type="string")
     * )
     * )
     * ),
     * @OA\Response(response=401, description="Credenciales incorrectas"),
     * @OA\Response(response=403, description="Cuenta suspendida")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Credenciales incorrectas', 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return $this->errorResponse('Cuenta suspendida', 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'token' => $token,
        ], 'Login exitoso');
    }

    /**
     * @OA\Post(
     * path="/logout",
     * summary="Cerrar sesión",
     * description="Invalida el token actual del usuario.",
     * tags={"Autenticación"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Sesión cerrada")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Sesión cerrada exitosamente');
    }

    /**
     * @OA\Get(
     * path="/me",
     * summary="Perfil del usuario",
     * description="Retorna la información del usuario autenticado mediante el Token.",
     * tags={"Autenticación"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Datos del perfil")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user())
        );
    }
}
