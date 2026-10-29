<?php

declare(strict_types=1);

namespace Tests\Doubles\Persistence;

use DateTimeImmutable;
use LogicException;
use Src\Shared\Application\Idempotency\IdempotencyReservation;
use Src\Shared\Application\Idempotency\IdempotencyResult;
use Src\Shared\Application\Idempotency\IdempotencyStoreInterface;

final class InMemoryIdempotencyStore implements IdempotencyStoreInterface
{
    /**
     * @var array<string, array{
     *     fingerprint: string,
     *     expiresAt: DateTimeImmutable,
     *     status: string,
     *     result: ?IdempotencyResult
     * }>
     */
    private array $records = [];

    public function reserve(
        string $key,
        string $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyReservation {
        $now = new DateTimeImmutable;
        $record = $this->records[$key] ?? null;

        if ($record !== null && $record['expiresAt'] <= $now) {
            unset($this->records[$key]);
            $record = null;
        }

        if ($record !== null) {
            if ($record['fingerprint'] !== $fingerprint) {
                return IdempotencyReservation::conflict();
            }

            if ($record['status'] === 'completed') {
                return IdempotencyReservation::replay(
                    $record['result']
                    ?? throw new LogicException('Completed record has no result.'),
                );
            }

            return IdempotencyReservation::inProgress();
        }

        $this->records[$key] = [
            'fingerprint' => $fingerprint,
            'expiresAt' => $expiresAt,
            'status' => 'in_progress',
            'result' => null,
        ];

        return IdempotencyReservation::acquired();
    }

    public function complete(
        string $key,
        string $fingerprint,
        IdempotencyResult $result,
    ): void {
        $record = $this->records[$key] ?? null;

        if (
            $record === null
            || $record['fingerprint'] !== $fingerprint
            || $record['status'] !== 'in_progress'
        ) {
            throw new LogicException('Cannot complete this reservation.');
        }

        $this->records[$key]['status'] = 'completed';
        $this->records[$key]['result'] = $result;
    }

    public function release(string $key, string $fingerprint): void
    {
        $record = $this->records[$key] ?? null;

        if (
            $record !== null
            && $record['fingerprint'] === $fingerprint
            && $record['status'] === 'in_progress'
        ) {
            unset($this->records[$key]);
        }
    }
}
