<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function getWallet(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $wallet = $user->wallet;
        if (!$wallet) {
            $wallet = Wallet::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id, 
                'balance_cents' => 0
            ]);
        }

        $transactions = $wallet->transactions()->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Wallet retrieved successfully.',
            'data' => [
                'balance_cents' => $wallet->balance_cents,
                'transactions' => $transactions->items(),
            ],
            'meta' => [
                'pagination' => [
                    'total' => $transactions->total(),
                    'count' => $transactions->count(),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                ],
            ],
            'errors' => null,
        ]);
    }
}
