<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener categorías existentes
        $categories = Category::all();

        // Crear 3 vendedores con datos mock
        $sellers = [
            [
                'name' => 'Juan Martinez - Tech Store',
                'email' => 'juan@marketplace.com',
                'store_name' => 'Tech Store',
                'store_description' => 'Tienda especializada en electrónica y gadgets tecnológicos de última generación',
            ],
            [
                'name' => 'María García - Electronics Plus',
                'email' => 'maria@marketplace.com',
                'store_name' => 'Electronics Plus',
                'store_description' => 'Venta de dispositivos electrónicos certificados con garantía',
            ],
            [
                'name' => 'Carlos López - Digital Marketplace',
                'email' => 'carlos@marketplace.com',
                'store_name' => 'Digital Marketplace',
                'store_description' => 'Plataforma de productos digitales y accesorios de calidad premium',
            ],
        ];

        // Productos mock para cada vendedor
        $productsByStore = [
            'Tech Store' => [
                [
                    'name' => 'Laptop ASUS VivoBook 15',
                    'description' => 'Laptop potente con procesador Intel Core i7, 16GB RAM, SSD 512GB. Pantalla Full HD de 15.6 pulgadas, batería de larga duración.',
                    'price' => 899.99,
                    'stock' => 5,
                ],
                [
                    'name' => 'Mouse Gamer Corsair M65',
                    'description' => 'Mouse gaming profesional con sensor óptico de 18000 DPI, 8 botones programables, cable reforzado.',
                    'price' => 59.99,
                    'stock' => 25,
                ],
                [
                    'name' => 'Monitor LG 27" 144Hz',
                    'description' => 'Monitor gaming 1440p con frecuencia de refresco de 144Hz, tiempo de respuesta de 1ms, soporte HDMI y DisplayPort.',
                    'price' => 399.99,
                    'stock' => 8,
                ],
                [
                    'name' => 'Teclado Mecánico Razer RGB',
                    'description' => 'Teclado mecánico con switches azules, iluminación RGB personalizable, apoyabrazos desmontable.',
                    'price' => 139.99,
                    'stock' => 12,
                ],
                [
                    'name' => 'Webcam Logitech 4K',
                    'description' => 'Cámara web 4K con micrófono estéreo integrado, enfoque automático, compatible con OBS y Zoom.',
                    'price' => 79.99,
                    'stock' => 15,
                ],
            ],
            'Electronics Plus' => [
                [
                    'name' => 'Samsung Galaxy S24 Ultra',
                    'description' => 'Smartphone flagship con pantalla AMOLED 120Hz, cámara de 200MP, batería de 5000mAh, 5G.',
                    'price' => 1299.99,
                    'stock' => 3,
                ],
                [
                    'name' => 'Apple AirPods Pro (2da Generación)',
                    'description' => 'Audífonos inalámbricos con cancelación de ruido activa, audio espacial, estuche de carga MagSafe.',
                    'price' => 249.99,
                    'stock' => 10,
                ],
                [
                    'name' => 'Smartwatch Apple Watch Series 9',
                    'description' => 'Reloj inteligente con pantalla Always-On, medidor de temperatura, resistencia al agua 50m.',
                    'price' => 399.99,
                    'stock' => 7,
                ],
                [
                    'name' => 'Powerbank Anker 30000mAh',
                    'description' => 'Batería externa de alta capacidad con carga rápida 65W, múltiples puertos USB-C.',
                    'price' => 49.99,
                    'stock' => 30,
                ],
            ],
            'Digital Marketplace' => [
                [
                    'name' => 'iPad Pro 12.9" M2',
                    'description' => 'Tablet premium con chip M2, pantalla Liquid Retina XDR, compatible con Apple Pencil y Magic Keyboard.',
                    'price' => 1099.99,
                    'stock' => 4,
                ],
                [
                    'name' => 'Sony WH-1000XM5 Headphones',
                    'description' => 'Auriculares premium con cancelación de ruido líder en la industria, 30 horas de batería, conector 3.5mm.',
                    'price' => 349.99,
                    'stock' => 9,
                ],
                [
                    'name' => 'GoPro Hero 12 Black',
                    'description' => 'Cámara de acción 5.3K con estabilización avanzada, resistencia al agua, batería removible.',
                    'price' => 499.99,
                    'stock' => 6,
                ],
                [
                    'name' => 'DJI Mini 4 Pro Drone',
                    'description' => 'Drone compacto con cámara 4K, alcance de 25km, tiempo de vuelo de 34 minutos, estabilización de 3 ejes.',
                    'price' => 759.99,
                    'stock' => 5,
                ],
            ],
        ];

        // Crear vendedores, perfiles y productos
        foreach ($sellers as $sellerData) {
            // Crear usuario vendedor
            $seller = User::firstOrCreate(
                ['email' => $sellerData['email']],
                [
                    'name' => $sellerData['name'],
                    'password' => 'password',
                    'phone' => null,
                ]
            );

            // Asignar rol de vendedor
            if (!$seller->hasRole('seller')) {
                $seller->assignRole('seller');
            }

            // Crear perfil de vendedor
            $sellerProfile = SellerProfile::firstOrCreate(
                ['user_id' => $seller->id],
                [
                    'store_name' => $sellerData['store_name'],
                    'description' => $sellerData['store_description'],
                ]
            );

            // Crear productos para este vendedor
            $products = $productsByStore[$sellerData['store_name']] ?? [];
            
            foreach ($products as $productData) {
                $randomCategory = $categories->random();
                
                Product::create([
                    'seller_profile_id' => $sellerProfile->id,
                    'category_id' => $randomCategory->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'status' => 'active',
                ]);
            }
        }

        // Crear un comprador de prueba
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@marketplace.com'],
            [
                'name' => 'Comprador Test',
                'password' => 'password',
                'phone' => null,
            ]
        );

        if (!$buyer->hasRole('buyer')) {
            $buyer->assignRole('buyer');
        }

        \App\Models\BuyerProfile::firstOrCreate([
            'user_id' => $buyer->id,
        ], [
            'address' => '123 Fake Street',
            'city' => 'Test City',
            'country' => 'Test Country',
        ]);
    }
}
