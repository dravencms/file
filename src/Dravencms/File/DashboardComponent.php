<?php declare(strict_types=1);
namespace Dravencms\File;

use Dravencms\Admin\Dashboard\{DashboardDefinition, DashboardMetric, StatisticsComponent};
use Dravencms\Database\EntityManager;

final class DashboardComponent extends StatisticsComponent
{
    public function __construct(private EntityManager $entityManager) {}

    public function getDefinition(): DashboardDefinition
    {
        return new DashboardDefinition('file.overview', 'file.dashboard.title', 200,
            resource: 'file', privilege: 'edit', destination: ':Admin:File:File:default', icon: 'fa-folder-open');
    }

    protected function getMetrics(): iterable
    {
        $query = $this->entityManager->createQuery('SELECT COUNT(f.id) AS total, COALESCE(SUM(CASE WHEN f.type = \'image\' THEN 1 ELSE 0 END), 0) AS images, COALESCE(SUM(f.size), 0) AS bytes FROM Dravencms\\Model\\File\\Entities\\File f');
        $stats = $query->getSingleResult();
        yield new DashboardMetric('file.dashboard.total', (int) $stats['total']);
        yield new DashboardMetric('file.dashboard.images', (int) $stats['images']);
        yield new DashboardMetric('file.dashboard.bytes', (int) $stats['bytes'], true);
    }
}
