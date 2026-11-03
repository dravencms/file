<?php declare(strict_types=1);
namespace Dravencms\File\Access;
use Salamek\Files\Access\{IFileAccessProvider, FileAccess};
use Dravencms\Model\File\Repository\FileRepository;
use Nette\Security\User;

final class AdminFileProvider implements IFileAccessProvider
{
    public function __construct(private FileRepository $repository, private User $user) {}
    public static function getId(): string { return 'file.admin'; }
    public function resolve(string $reference): ?FileAccess
    {
        $id = filter_var($reference, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !($entity = $this->repository->getOneById($id))) { return null; }
        $public = false;
        if (!$public && !$this->user->isAllowed('file', 'edit')) { return null; }
        $attachment = $entity;
        return $attachment ? new FileAccess($attachment, $public, null) : null;
    }
}
