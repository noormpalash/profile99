<?php
declare(strict_types=1);

interface MigrationInterface
{
    public function up(PDO $db): void;
    public function down(PDO $db): void;
}
