<?php

declare(strict_types=1);

namespace App\Service;

interface MediaIndexPort
{
    /**
     * Ставит сохранённые записи медиа в производную поисковую синхронизацию.
     *
     * @param list<array<string, mixed>> $mediaRecords
     */
    public function scheduleSavedMedia(array $mediaRecords): void;
}
