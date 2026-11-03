<?php declare(strict_types=1);

namespace Dravencms\File;

use Dravencms\Admin\Menu\{AdminMenuItem, AdminMenuProviderInterface};

final class AdminMenuProvider implements AdminMenuProviderInterface
{
    public function getItems(): iterable
    {
        yield new AdminMenuItem('file.overview', 'file.menu.overview', destination: ':Admin:File:File:default', icon: 'fa-archive', order: 400, resource: 'file', privilege: 'edit');
    }
}
