<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CreateOrderRequest;
use Spatie\FlareClient\Api;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(Request $request)
    {
        $userId = $request->input('user_id');
        $items = $request->input('items'); // should be array of ['product_id' => int, 'quantity' => int]

        if (!$userId || !$items) {
            return response()->json(['error' => 'Invalid input'], 400);
        }

        DB::beginTransaction();
        try {
            $total = 0;

            foreach ($items as $item) {
                $product = DB::table('products')->find($item['product_id']);
                if (!$product) {
                    return response()->json(['error' => 'Product not found'], 404);
                }

                if ($product->stock <= 0) {
                    DB::rollBack();
                    return response()->json(['error' => 'Out of stock'], 400);
                }

                DB::table('order_items')->insert([
                    'order_id' => null,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                DB::table('products')->where('id', $product->id)
                    ->update(['stock' => $product->stock - $item['quantity']]);

                $total += $product->price * $item['quantity'];
            }

            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'total' => $total,
                'created_at' => now(),
            ]);

            DB::commit();
            return response()->json(['order_id' => $orderId, 'total' => $total], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeV2(CreateOrderRequest $request)
    {
        $results = $this->orderService->createOrder($request->validated());

        if($results['success']) {
            return ApiFormatter::success(
                $results['data'],
                'Order created successfully',
                $results['code']
            );
        } else {
            return ApiFormatter::error(
                $results['data'],
                $results['code']
            );
        }
    }
}
