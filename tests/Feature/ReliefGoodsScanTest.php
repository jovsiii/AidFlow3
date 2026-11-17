<?php

namespace Tests\Feature;

use App\Http\Controllers\PhoneFeatures\ReliefGoodsController;
use App\Services\FirebaseService;
use App\Services\ReliefPackCalculator;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ReliefGoodsScanTest extends TestCase
{
    public function test_scanning_a_relief_pack_consumes_its_inventory_items(): void
    {
        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('getReliefPackPlan')->once()->andReturn(null);
        $firebase->shouldReceive('consumeReliefPack')
            ->once()
            ->with(1, [['name' => 'Rice', 'quantity' => 6, 'unit' => 'kg']], 'Test Account')
            ->andReturn(['status' => 'consumed']);

        $calculator = Mockery::mock(ReliefPackCalculator::class);
        $calculator->shouldReceive('requiredItemDetails')->once()->andReturn([['name' => 'Rice', 'quantity' => 6, 'unit' => 'kg']]);

        $request = Request::create('/phoneFeatures/reliefGoods/scan', 'POST', ['package_id' => '#1']);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put('user', ['fullName' => 'Test Account']);

        $response = (new ReliefGoodsController())->scan(
            $request,
            $firebase,
            $calculator
        );

        $this->assertTrue($response->getData()->success);
    }

    public function test_scanning_a_pack_with_insufficient_stock_fails(): void
    {
        $firebase = Mockery::mock(FirebaseService::class);
        $firebase->shouldReceive('getReliefPackPlan')->once()->andReturn(null);
        $firebase->shouldReceive('consumeReliefPack')
            ->once()
            ->with(1, [['name' => 'Rice', 'quantity' => 6, 'unit' => 'kg']], null)
            ->andReturn([
                'status' => 'insufficient_stock',
                'item' => 'Rice',
            ]);

        $calculator = Mockery::mock(ReliefPackCalculator::class);
        $calculator->shouldReceive('requiredItemDetails')->once()->andReturn([['name' => 'Rice', 'quantity' => 6, 'unit' => 'kg']]);

        $request = Request::create('/phoneFeatures/reliefGoods/scan', 'POST', ['package_id' => '#1']);
        $request->setLaravelSession(app('session')->driver());
        $response = (new ReliefGoodsController())->scan(
            $request,
            $firebase,
            $calculator
        );

        $this->assertFalse($response->getData()->success);
        $this->assertSame(422, $response->getStatusCode());
    }
}