<?php declare(strict_types=1);
namespace Dravencms\File\Access;

use Dravencms\Security\{Authenticator, Authorizator};
use Dravencms\User\Acl\AclPermissionBuilder;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\Http\Session;
use Nette\Security\User;

/** Private plugin previews use the admin login without changing frontend authentication. */
class AdminFileAccess
{
    private ?User $user = null;

    public function __construct(private Session $session, private Authenticator $authenticator, private AclPermissionBuilder $builder) {}

    public function isAllowed(string $resource, string $privilege): bool
    {
        if ($this->user === null) {
            $storage = (new SessionStorage($this->session))->setNamespace('Admin');
            $this->user = new User($storage, $this->authenticator, new Authorizator($storage, $this->authenticator, $this->builder));
        }
        return $this->user->isLoggedIn() && $this->user->isAllowed($resource, $privilege);
    }
}
