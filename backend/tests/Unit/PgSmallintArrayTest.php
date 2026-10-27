<?php

namespace Tests\Unit;

use App\Casts\PgSmallintArray;
use App\Models\Task;
use PHPUnit\Framework\TestCase;

class PgSmallintArrayTest extends TestCase
{
    public function test_reads_postgres_array_literal(): void
    {
        $cast = new PgSmallintArray;

        $this->assertSame([1, 3, 5], $cast->get(new Task, 'recurrence_days', '{1,3,5}', []));
        $this->assertSame([], $cast->get(new Task, 'recurrence_days', '{}', []));
        $this->assertNull($cast->get(new Task, 'recurrence_days', null, []));
    }

    public function test_writes_postgres_array_literal(): void
    {
        $cast = new PgSmallintArray;

        $this->assertSame('{1,3,5}', $cast->set(new Task, 'recurrence_days', [1, '3', 5], []));
        $this->assertSame('{}', $cast->set(new Task, 'recurrence_days', [], []));
        $this->assertNull($cast->set(new Task, 'recurrence_days', null, []));
    }
}
