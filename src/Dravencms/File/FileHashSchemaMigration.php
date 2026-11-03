<?php declare(strict_types=1);

namespace Dravencms\File;

use Doctrine\DBAL\Connection;

final class FileHashSchemaMigration
{
    public function __construct(private Connection $connection) {}

    public function getSql(): array
    {
        $manager = $this->connection->createSchemaManager();
        $table = $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform ? 'filefile' : 'fileFile';
        $old = $manager->introspectTable($table); $new = clone $old;
        $column = $new->getColumn('sum');
        if ($column->getLength() !== null && $column->getLength() < 64) { $column->setLength(64); }
        $diff = $manager->createComparator()->compareTables($old, $new);
        return $diff->isEmpty() ? [] : $this->connection->getDatabasePlatform()->getAlterTableSQL($diff);
    }

    public function apply(): void
    {
        $sql = $this->getSql();
        $execute = function () use ($sql): void {
            $sqlite = $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;
            if ($sqlite) { $this->connection->executeStatement('PRAGMA defer_foreign_keys = ON'); }
            foreach ($sql as $statement) { $this->connection->executeStatement($statement); }
            if ($sqlite && $this->connection->fetchAllAssociative('PRAGMA foreign_key_check') !== []) { throw new \RuntimeException('File hash migration failed foreign-key validation.'); }
        };
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform) { $execute(); }
        else { $this->connection->transactional($execute); }
    }
}
