<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(
 * title="Marketplace API - Proyecto de Cátedra",
 * version="1.0.0",
 * description="API para la gestión de productos, pedidos y pagos con roles de Comprador, Vendedor y Admin."
 * )
 * @OA\Server(
 * url="http://localhost:8000/api",
 * description="Servidor de Desarrollo Local"
 * )
 * @OA\SecurityScheme(
 * type="http",
 * securityScheme="bearerAuth",
 * scheme="bearer",
 * bearerFormat="JWT",
 * description="Ingresa el token generado al hacer login para acceder a las rutas protegidas."
 * )
 * @OA\Schema(schema="User", title="Usuario",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="name", type="string", example="Alexander"),
 * @OA\Property(property="email", type="string", example="alex@example.com"),
 * @OA\Property(property="phone", type="string", example="7777-1234"),
 * @OA\Property(property="is_active", type="boolean", example=true)
 * )
 * @OA\Schema(schema="Product", title="Producto",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="name", type="string", example="Laptop Gamer"),
 * @OA\Property(property="description", type="string", example="Core i7, 16GB RAM"),
 * @OA\Property(property="price", type="number", format="float", example=1200.50),
 * @OA\Property(property="stock", type="integer", example=10),
 * @OA\Property(property="category_id", type="integer", example=2),
 * @OA\Property(property="seller_id", type="integer", example=3),
 * @OA\Property(property="seller_profile_id", type="integer", example=5)
 * )
 * @OA\Schema(schema="Category", title="Categoría",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="name", type="string", example="Electrónica"),
 * @OA\Property(property="description", type="string", example="Computadoras y accesorios")
 * )
 * @OA\Schema(schema="Order", title="Pedido",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="user_id", type="integer", example=3),
 * @OA\Property(property="total", type="number", format="float", example=2400.00),
 * @OA\Property(property="status", type="string", enum={"pending", "paid", "shipped", "completed", "cancelled"}),
 * @OA\Property(property="created_at", type="string", format="date-time")
 * )
 * @OA\Schema(schema="OrderItem", title="Detalle del Pedido",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="order_id", type="integer", example=1),
 * @OA\Property(property="product_id", type="integer", example=5),
 * @OA\Property(property="quantity", type="integer", example=2),
 * @OA\Property(property="price", type="number", format="float", example=1200.50)
 * )
 * @OA\Schema(schema="Payment", title="Pago",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="order_id", type="integer", example=1),
 * @OA\Property(property="amount", type="number", format="float", example=2400.00),
 * @OA\Property(property="payment_method", type="string", example="credit_card"),
 * @OA\Property(property="status", type="string", example="approved")
 * )
 * @OA\Schema(schema="Return", title="Devolución",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="order_id", type="integer", example=1),
 * @OA\Property(property="reason", type="string", example="Producto defectuoso"),
 * @OA\Property(property="status", type="string", enum={"requested", "approved", "rejected", "refunded"})
 * )
 * @OA\Schema(schema="Review", title="Reseña/Calificación",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="product_id", type="integer", example=5),
 * @OA\Property(property="user_id", type="integer", example=3),
 * @OA\Property(property="rating", type="integer", example=5),
 * @OA\Property(property="comment", type="string", example="Excelente producto")
 * )
 * @OA\Schema(schema="Cart", title="Carrito Temporal",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="user_id", type="integer", example=3),
 * @OA\Property(property="product_id", type="integer", example=5),
 * @OA\Property(property="quantity", type="integer", example=1)
 * )
 * @OA\Tag(name="Autenticación", description="Registro, Login y gestión de Tokens")
 * @OA\Tag(name="Usuarios", description="Gestión de perfiles y roles")
 * @OA\Tag(name="Productos", description="Listado y gestión de artículos del marketplace")
 * @OA\Tag(name="Pedidos", description="Flujo de compras y carrito")
 * @OA\Tag(name="Pagos", description="Procesamiento de transacciones")
 * @OA\Tag(name="Devoluciones", description="Gestión de reclamos y retornos")
 */
abstract class Controller
{
    use AuthorizesRequests;
}
