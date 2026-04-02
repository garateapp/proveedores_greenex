<?php

namespace Tests\Unit;

use Tests\TestCase;

class FilesystemDiskConfigTest extends TestCase
{
    /**
     * Ensure the private filesystem disk is configured.
     */
    public function test_private_disk_is_configured(): void
    {
        $privateDisk = config('filesystems.disks.private');

        $this->assertIsArray($privateDisk);
        $this->assertSame('local', $privateDisk['driver'] ?? null);
        $this->assertSame(storage_path('app/private'), $privateDisk['root'] ?? null);
    }
}
