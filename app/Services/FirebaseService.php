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

    private function get($path, bool $throwOnFailure = false)
    {
        $response = Http::withoutVerifying()->get(
            "{$this->databaseUrl}/{$path}.json"
        );

        if ($throwOnFailure) {
            $response->throw();
        }

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

    public function getInventory(bool $throwOnFailure = false)
    {
        $inventory = $this->get('inventory', $throwOnFailure);

        if ($throwOnFailure && $inventory !== null && !is_array($inventory)) {
            throw new \UnexpectedValueException('Invalid inventory data returned by Firebase.');
        }

        return $inventory;
    }

    public function getReliefPackPlan(): ?array
    {
        $plan = $this->get('reliefPackPlan', true);
        if ($plan === null) {
            return null;
        }

        if (!is_array($plan)) {
            throw new \UnexpectedValueException('Invalid relief pack plan returned by Firebase.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($plan, [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        if ($validator->fails()) {
            throw new \UnexpectedValueException('Invalid relief pack plan returned by Firebase.');
        }

        return $plan;
    }

    public function saveReliefPackPlan(array $items): bool
    {
        return Http::withoutVerifying()->put("{$this->databaseUrl}/reliefPackPlan.json", [
            'items' => array_values($items),
            'updatedAt' => now()->toIso8601String(),
        ])->successful();
    }

    /** Fetch only the record keys of a node, which is far cheaper than downloading its contents. */
    public function getKeys(string $path): array
    {
        $response = Http::withoutVerifying()->get(
            "{$this->databaseUrl}/{$path}.json",
            ['shallow' => 'true']
        );

        $keys = $response->successful() ? $response->json() : [];

        return is_array($keys) ? array_keys($keys) : [];
    }

    public function createInventory(array $data)
    {
        return $this->write('inventory', $data, 'post');
    }

    public function updateInventory($id, array $data)
    {
        return $this->write("inventory/{$id}", $data, 'put');
    }

    public function releaseInventory(string $id, string $itemName, int $quantity, string $purpose, ?string $accountName): string
    {
        $url = "{$this->databaseUrl}/inventory/" . rawurlencode($id) . '.json';
        $response = Http::withoutVerifying()->withHeaders(['X-Firebase-ETag' => 'true'])->get($url);

        if (!$response->successful() || !$response->header('ETag')) {
            return 'failed';
        }

        $item = $response->json();
        if (!is_array($item) || mb_strtolower(trim((string) ($item['name'] ?? ''))) !== mb_strtolower(trim($itemName))) {
            return 'not_found';
        }

        if (!empty($item['expirationDate']) && \Carbon\Carbon::parse($item['expirationDate'])->startOfDay()->lt(now()->startOfDay())) {
            return 'expired';
        }

        $stock = filter_var($item['stock'] ?? null, FILTER_VALIDATE_INT);
        if ($stock === false || $stock < $quantity) {
            return 'insufficient_stock';
        }
        if (isset($item['releases']) && !is_array($item['releases'])) {
            return 'failed';
        }

        $item['startingStock'] = (int) ($item['startingStock'] ?? $stock);
        $item['stock'] = $stock - $quantity;
        $item['releases'][(string) Str::uuid()] = [
            'itemName' => $item['name'],
            'batch' => $item['batch'] ?? null,
            'unit' => $item['unit'] ?? '',
            'quantity' => $quantity,
            'purpose' => $purpose,
            'releasedAt' => now()->toIso8601String(),
            'releasedBy' => $accountName,
        ];

        $write = Http::withoutVerifying()->withHeaders(['if-match' => $response->header('ETag')])->put($url, $item);

        if ($write->status() === 412) {
            return 'conflict';
        }

        return $write->successful() ? 'released' : 'failed';
    }

    public function deleteInventory($id)
    {
        $url = "{$this->databaseUrl}/inventory/" . rawurlencode($id) . '.json';
        $response = Http::withoutVerifying()->withHeaders(['X-Firebase-ETag' => 'true'])->get($url);
        $item = $response->json();

        if (!$response->successful() || !$response->header('ETag') || !is_array($item) || !empty($item['releases'])) {
            return false;
        }

        return Http::withoutVerifying()
            ->withHeaders(['if-match' => $response->header('ETag')])
            ->delete($url)
            ->successful();
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

    /** Admin-configured number of tents per barangay code. */
    public function getTentCounts(): array
    {
        $counts = $this->get('tentCounts');

        return is_array($counts) ? $counts : [];
    }

    public function setTentCount(string $barangayCode, int $count): bool
    {
        return $this->write('tentCounts/' . rawurlencode($barangayCode), $count) !== null;
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

    public function getFamilyProfiles(bool $throwOnFailure = false): array
    {
        $profiles = $this->get('familyProfiles', $throwOnFailure);

        if (!is_array($profiles)) {
            if ($throwOnFailure && $profiles !== null) {
                throw new \UnexpectedValueException('Invalid family profile data returned by Firebase.');
            }

            return [];
        }

        $result = [];
        foreach ($profiles as $id => $profile) {
            if (is_array($profile)) {
                $profile['id'] = (string) $id;
                $result[] = $profile;
            }
        }

        return $result;
    }

    public function createFamilyProfile(array $data)
    {
        return $this->write('familyProfiles', $data, 'post');
    }

    public function updateFamilyProfile(string $id, array $data): bool
    {
        return $this->write('familyProfiles/' . rawurlencode($id), $data, 'patch') !== null;
    }

    public function deleteFamilyProfile(string $id): bool
    {
        $response = Http::withoutVerifying()->delete(
            "{$this->databaseUrl}/familyProfiles/" . rawurlencode($id) . '.json'
        );

        return $response->successful();
    }

    public function getOccupiedTent(string $tentCode): ?array
    {
        $response = Http::get(
            "{$this->databaseUrl}/occupiedTents/" . rawurlencode($tentCode) . '.json'
        );
        $tent = $response->successful() ? $response->json() : null;

        return is_array($tent) ? $tent : null;
    }

    public function recordTentScan(
        string $tentCode,
        array $data,
        bool $occupied,
        ?array $familyProfile = null
    ): bool
    {
        $event = $data;
        $event['action'] = $occupied ? 'occupied' : 'unoccupied';
        $eventKey = (string) Str::uuid();
        $updates = [
            "scanEvents/{$eventKey}" => $event,
            'occupiedTents/' . $tentCode => $occupied ? $data : null,
        ];

        if ($occupied && $familyProfile !== null) {
            $updates['familyProfiles/tent_' . $tentCode] = $familyProfile;
        }

        $response = Http::patch("{$this->databaseUrl}/.json", $updates);

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