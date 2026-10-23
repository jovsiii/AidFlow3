<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FirebaseService
{
    private $databaseUrl;

    public function __construct()
    {
        $this->databaseUrl = rtrim(env('FIREBASE_DATABASE_URL'), '/');
    }

    public function getAccount($username)
    {
        $url = "{$this->databaseUrl}/accounts/{$username}.json";

        $response = Http::get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    private function get($path)
    {
        $response = Http::withoutVerifying()->get(
            "{$this->databaseUrl}/{$path}.json"
        );

        return $response->successful()
            ? $response->json()
            : [];
    }

    private function write($path, $data, $method = 'put')
    {
        $response = Http::withoutVerifying()->{$method}(
            "{$this->databaseUrl}/{$path}.json",
            $data
        );

        return $response->successful() ? $response->json() : null;
    }

    public function getInventory()
    {
        return $this->get('inventory');
    }

    public function createInventory(array $data)
    {
        return $this->write('inventory', $data, 'post');
    }

    public function updateInventory($id, array $data)
    {
        return $this->write("inventory/{$id}", $data, 'put');
    }

    public function deleteInventory($id)
    {
        $response = Http::withoutVerifying()->delete(
            "{$this->databaseUrl}/inventory/{$id}.json"
        );

        return $response->successful();
    }

    public function getReliefPacks()
    {
        return $this->get('relief_packs');
    }

    public function consumeReliefPack(int $packNumber, array $requiredItems, ?string $accountName = null): array
    {
        $scanPath = "reliefPackScans/{$packNumber}";
        $existingScan = $this->get($scanPath);

        if (is_array($existingScan) && !empty($existingScan['scanned_at'])) {
            return ['status' => 'already_scanned'];
        }

        $inventory = $this->getInventory();
        $inventory = is_array($inventory) ? $inventory : [];
        $itemsByName = [];

        foreach ($inventory as $id => $item) {
            if (is_array($item) && filled($item['name'] ?? null)) {
                $itemsByName[mb_strtolower(trim($item['name']))][] = [$id, $item];
            }
        }

        foreach ($requiredItems as $name => $quantity) {
            $available = collect($itemsByName[mb_strtolower(trim($name))] ?? [])
                ->sum(fn($entry) => (int) ($entry[1]['stock'] ?? 0));

            if ($available < $quantity) {
                return [
                    'status' => 'insufficient_stock',
                    'item' => $name,
                    'required' => $quantity,
                    'available' => $available,
                ];
            }
        }

        $updates = [];

        foreach ($requiredItems as $name => $quantity) {
            $remaining = $quantity;
            foreach ($itemsByName[mb_strtolower(trim($name))] ?? [] as [$id, $item]) {
                if ($remaining <= 0) {
                    break;
                }

                $stock = (int) ($item['stock'] ?? 0);
                $deduction = min($stock, $remaining);
                $remaining -= $deduction;
                $newStock = $stock - $deduction;
                $updates["inventory/{$id}"] = array_merge($item, [
                    'stock' => $newStock,
                    'startingStock' => (int) ($item['startingStock'] ?? $stock),
                ]);
            }
        }

        $updates[$scanPath] = [
            'pack_number' => $packNumber,
            'scanned_at' => now()->toIso8601String(),
            'accountName' => $accountName,
        ];

        $response = Http::withoutVerifying()->patch("{$this->databaseUrl}/.json", $updates);

        return $response->successful()
            ? ['status' => 'consumed']
            : ['status' => 'failed'];
    }

    public function getNextReliefPackNumber(): int
    {
        $counter = $this->get('auditLogs/relief_pack_counter');

        if (is_array($counter) && isset($counter['next_number'])) {
            return max(1, (int) $counter['next_number']);
        }

        return 1;
    }

    public function reserveNextReliefPackNumber(): int
    {
        $nextNumber = $this->getNextReliefPackNumber();
        $issuedAt = now()->toIso8601String();

        $eventKey = 'relief_pack_' . now()->format('YmdHis') . '_' . Str::random(6);

        Http::withoutVerifying()->patch("{$this->databaseUrl}/.json", [
            "auditLogs/{$eventKey}" => [
                'type' => 'relief_pack',
                'pack_number' => $nextNumber,
                'label' => "Relief Pack: #{$nextNumber}",
                'generated_at' => $issuedAt,
            ],
            'auditLogs/relief_pack_counter' => [
                'next_number' => $nextNumber + 1,
                'last_number' => $nextNumber,
                'updated_at' => $issuedAt,
            ],
        ]);

        return $nextNumber;
    }

    public function getTents()
    {
        return $this->get('tents');
    }

    public function getScans()
    {
        return $this->get('scanEvents');
    }

    public function getReliefPackScans()
    {
        return $this->get('reliefPackScans');
    }

    public function getOccupiedTents()
    {
        return $this->get('occupiedTents');
    }

    public function getOccupiedTent(string $tentCode): ?array
    {
        $response = Http::get(
            "{$this->databaseUrl}/occupiedTents/" . rawurlencode($tentCode) . '.json'
        );
        $tent = $response->successful() ? $response->json() : null;

        return is_array($tent) ? $tent : null;
    }

    public function recordTentScan(string $tentCode, array $data, bool $occupied): bool
    {
        $event = $data;
        $event['action'] = $occupied ? 'occupied' : 'unoccupied';
        $eventKey = (string) Str::uuid();

        $response = Http::patch("{$this->databaseUrl}/.json", [
            "scanEvents/{$eventKey}" => $event,
            'occupiedTents/' . $tentCode => $occupied ? $data : null,
        ]);

        return $response->successful();
    }
    /**
     * Get all users from Firebase
     */
    public function getAllUsers()
    {
        $users = $this->get('accounts');

        if (!is_array($users)) {
            return [];
        }

        // Convert associative array to indexed array of user objects
        $result = [];
        foreach ($users as $username => $userData) {
            if (is_array($userData)) {
                $userData['username'] = $username;
                if (!isset($userData['id'])) {
                    $userData['id'] = $username;
                }
                $result[] = $userData;
            }
        }
        return $result;
    }

    /**
     * Get user by username
     */
    public function getUserByUsername($username)
    {
        $userData = $this->getAccount($username);

        if ($userData && is_array($userData)) {
            $userData['username'] = $username;
            if (!isset($userData['id'])) {
                $userData['id'] = $username;
            }
        }

        return $userData;
    }

    /**
     * Get user by ID (ID is typically the username in Firebase)
     */
    public function getUserById($id)
    {
        return $this->getUserByUsername($id);
    }

    /**
     * Create a new user in Firebase
     */
    public function createUser($username, array $data)
    {
        return $this->write("accounts/{$username}", $data, 'put');
    }

    /**
     * Update user in Firebase
     */
    public function updateUser($username, array $data)
    {
        return $this->write("accounts/{$username}", $data, 'put');
    }

    /**
     * Delete user from Firebase
     */
    public function deleteUser($username)
    {
        $response = Http::withoutVerifying()->delete(
            "{$this->databaseUrl}/accounts/{$username}.json"
        );

        return $response->successful();
    }
}