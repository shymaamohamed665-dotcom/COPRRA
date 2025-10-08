<?php

namespace Tests\Performance;

use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PerformanceBenchmarkTest extends TestCase
{
    public function test_performance_benchmark(): void
    {
        $startTime = microtime(true);

        // Simulate some work
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = $i * $i;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime); // Should complete in less than 1 second
        $this->assertCount(1000, $data);
    }

    public function test_benchmark_comparison(): void
    {
        $method1Start = microtime(true);
        $result1 = array_map('strtoupper', range('a', 'z'));
        $method1Time = microtime(true) - $method1Start;

        $method2Start = microtime(true);
        $result2 = [];
        foreach (range('a', 'z') as $letter) {
            $result2[] = strtoupper($letter);
        }
        $method2Time = microtime(true) - $method2Start;

        $this->assertCount(26, $result1);
        $this->assertCount(26, $result2);
        $this->assertEquals($result1, $result2);
    }

    public function test_benchmark_trends(): void
    {
        $iterations = 100;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            // Simulate varying workload
            $workload = $i % 10 + 1;
            $data = array_fill(0, $workload * 100, 'test');
            $result = array_sum(array_map('strlen', $data));
            $this->assertGreaterThan(0, $result);

            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum((array) $times) / count((array) $times);
        $maxTime = max($times);

        $this->assertLessThan(0.1, $avgTime); // Average should be less than 0.1 seconds
        $this->assertLessThan(0.5, $maxTime); // Max should be less than 0.5 seconds
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
