<?php

namespace App\Support;

class PartyImportResult
{
    /**
     * @param  list<array{row: int, reason: string}>  $failures
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $failuresCount = 0,
        public array $failures = [],
    ) {}

    public function imported(): int
    {
        return $this->created + $this->updated;
    }

    public function hasFailures(): bool
    {
        return $this->failuresCount > 0;
    }

    public function summaryBody(): string
    {
        $parts = [
            "Berhasil ditambahkan: {$this->created}",
            "Diperbarui (upsert): {$this->updated}",
        ];

        if ($this->hasFailures()) {
            $parts[] = "Gagal: {$this->failuresCount}";
            $details = collect($this->failures)
                ->take(10)
                ->map(fn (array $failure) => "Baris {$failure['row']}: {$failure['reason']}")
                ->implode("\n");
            $parts[] = $details;

            if (count($this->failures) > 10) {
                $parts[] = '...dan '.(count($this->failures) - 10).' baris gagal lainnya.';
            }
        }

        return implode("\n", $parts);
    }
}
