<?php

namespace Tests\Feature;

use App\Http\Controllers\PhoneFeatures\EvacuationController;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PhoneEvacuationScanTest extends TestCase
{
    public function test_scan_records_tent_in_firebase(): void
    {
        $controller = new EvacuationController();
        $request = Request::create('/phoneFeatures/evacuation/scan', 'POST', [
            'tent_code' => 'BB-001',
            'barangay_code' => 'BB',
            'barangay_name' => 'Balong-Bato',
            'full_name' => 'Maria Santos',
            'household_size' => 4,
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put('user', ['fullName' => 'Test Account']);
        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('getOccupiedTent')->once()->with('BB-001')->andReturn(null);
        $firebase->shouldReceive('recordTentScan')->once()->with(
            'BB-001',
            Mockery::on(fn ($data) =>
                $data['tentCode'] === 'BB-001'
                && $data['barangayCode'] === 'BB'
                && $data['barangayName'] === 'Balong-Bato'
                && $data['accountName'] === 'Test Account'
                && isset($data['scannedAt'])
            ),
            true,
            Mockery::on(fn ($profile) =>
                $profile['fullName'] === 'Maria Santos'
                && $profile['householdSize'] === 4
                && $profile['tentCode'] === 'BB-001'
                && $profile['barangay'] === 'Balong-Bato'
            )
        )->andReturn(true);

        $response = $controller->scan($request, $firebase);

        $this->assertTrue($response->getData()->success);
        $this->assertSame('occupied', $response->getData()->action);
    }

    public function test_scan_rejects_numbers_and_symbols_in_full_name(): void
    {
        $controller = new EvacuationController();
        $firebase = Mockery::mock(FirebaseService::class);

        foreach (['Maria2 Santos', 'Maria@ Santos', 'Maria-Santos'] as $fullName) {
            $request = Request::create('/phoneFeatures/evacuation/scan', 'POST', [
                'tent_code' => 'BB-001',
                'barangay_code' => 'BB',
                'barangay_name' => 'Balong-Bato',
                'full_name' => $fullName,
                'household_size' => 4,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

            try {
                $controller->scan($request, $firebase);
                $this->fail("The full name [{$fullName}] should be rejected.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('full_name', $exception->errors());
            }
        }
    }

    public function test_second_scan_requires_confirmation_then_marks_tent_unoccupied(): void
    {
        $controller = new EvacuationController();
        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('getOccupiedTent')->twice()->with('WC-002')->andReturn([
            'tentCode' => 'WC-002',
        ]);

        $confirmationResponse = $controller->scan(Request::create(
            '/phoneFeatures/evacuation/scan',
            'POST',
            [
                'tent_code' => 'WC-002',
                'barangay_code' => 'WC',
                'barangay_name' => 'West Crame',
                'full_name' => 'Juan Dela Cruz',
                'household_size' => 3,
            ]
        ), $firebase);
        $this->assertTrue($confirmationResponse->getData()->requires_confirmation);

        $firebase->shouldReceive('recordTentScan')->once()->with(
            'WC-002',
            Mockery::on(fn ($data) => $data['barangayName'] === 'West Crame'),
            false,
            null
        )->andReturn(true);

        $request = Request::create(
            '/phoneFeatures/evacuation/scan',
            'POST',
            [
                'tent_code' => 'WC-002',
                'barangay_code' => 'WC',
                'barangay_name' => 'West Crame',
                'full_name' => 'Juan Dela Cruz',
                'household_size' => 3,
                'confirm_unoccupy' => true,
            ]
        );
        $request->setLaravelSession(app('session')->driver());

        $response = $controller->scan($request, $firebase);
        $this->assertTrue($response->getData()->success);
        $this->assertSame('unoccupied', $response->getData()->action);
    }
}