<?php
namespace Solidarity\Delegate\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Factory\AbstractFactory;
use Solidarity\Delegate\Entity\Delegate;
use Solidarity\Transaction\Entity\Project;

class DelegateFactory extends AbstractFactory
{
    public static function compileEntityForCreate($data, EntityManagerInterface $em)
    {
        $data['projects'] = self::resolveProjectEntities($data, $em);
        return parent::compileEntityForCreate($data, $em);
    }

    public static function compileEntityForUpdate($data, $em)
    {
        $projects = self::resolveProjectEntities($data, $em);
        unset($data['projects']);

        $entity = $em->getRepository(Delegate::class)->find($data['id']);
        $entity->projects->clear();
        foreach ($projects as $project) {
            $entity->projects->add($project);
        }
        $entity = static::formatForWrite($entity, $data, $em);

        return $entity->id;
    }

    private static function resolveProjectEntities(array $data, EntityManagerInterface $em): array
    {
        if (!empty($data['projects']) && is_array($data['projects'])) {
            $repo = $em->getRepository(Project::class);
            $projects = array_map(fn($id) => $repo->find((int) $id), $data['projects']);
            return array_filter($projects);
        }
        return [];
    }
}