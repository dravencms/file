<?php declare(strict_types=1);

namespace Dravencms\File;

use Dravencms\User\Acl\{AclPermission, AclProviderInterface};

final class AclProvider implements AclProviderInterface
{
    public function getPermissions(): iterable
    {
        yield new AclPermission('file', 'edit', 'file.acl.permissions.edit.label', resourceLabel: 'file.acl.resource');
        yield new AclPermission('file', 'delete', 'file.acl.permissions.delete.label', resourceLabel: 'file.acl.resource');
    }
}
