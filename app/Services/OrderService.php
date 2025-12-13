<?php

namespace App\Services;

use App\Helpers\ApiFormatter;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderService
{
    public function createOrder($data)
    {
        DB::beginTransaction();

        try {
            $userId = $data['user_id'];
            $items = $data['items'];

            $itemsToInsert = [];
            $total = 0;

            $products = Product::whereIn(
                'id', collect($items)->pluck('product_id')
            )->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->firstWhere('id', $item['product_id']);

                if (!$product) {
                    DB::rollBack();
                    throw new Exception('Product not found', 404);
                }

                if ($product->stock <= 0 || $product->stock < $item['quantity']) {
                    DB::rollBack();
                    throw new Exception('Out of stock', 400);
                }

                $itemsToInsert[] = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $product['price'],
                ];

                $total += $product->price * $item['quantity'];

                $product->decrement('stock', $item['quantity']);
            }

            $order = Order::create([
                'user_id' => $userId,
                'total'   => $total,
            ]);

            $mappingItems = collect($itemsToInsert)->map(function ($item) use ($order) {
                return [
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            OrderItem::insert($mappingItems->toArray());

            DB::commit();

            return $order;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e instanceof HttpException ? $e->getStatusCode() : 500);
        }
    }
}
