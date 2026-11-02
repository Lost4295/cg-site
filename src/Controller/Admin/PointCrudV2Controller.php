<?php

namespace App\Controller\Admin;

use App\Entity\GuildInformation;
use App\Entity\Point;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function in_array;

#[IsGranted('ROLE_GUILD_HANDLER')]
class PointCrudV2Controller extends AbstractCrudController
{
    public EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface$entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public static function getEntityFqcn(): string
    {
        return Point::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $qb;
        }

        $guildIds = $this->allowedGuildIds();
        if ([] === $guildIds) {
            return $qb->andWhere('1 = 0');
        }

        return $qb
            ->andWhere('entity.guild_id IN (:user_guild_ids)')
            ->setParameter('user_guild_ids', $guildIds);
    }

    private function allowedGuildIds(): array
    {
        $user = $this->getUser();

        return $user instanceof User ? $user->getGuildIds() : [];
    }

    private function assertGuildAllowed(?string $guildId): void
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }
        if (null === $guildId || !in_array($guildId, $this->allowedGuildIds(), true)) {
            throw new AccessDeniedException('Vous ne faites pas partie de l\'association liée à ce point.');
        }
    }

    private function assertContextEntityAllowed(AdminContext $context): void
    {
        $point = $context->getEntity()->getInstance();
        if ($point instanceof Point) {
            $this->assertGuildAllowed($point->getGuildId());
        }
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        $this->assertContextEntityAllowed($context);

        return parent::detail($context);
    }

    public function edit(AdminContext $context): KeyValueStore|Response
    {
        $this->assertContextEntityAllowed($context);

        return parent::edit($context);
    }

    public function delete(AdminContext $context): KeyValueStore|Response
    {
        $this->assertContextEntityAllowed($context);

        return parent::delete($context);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->assertGuildAllowed($entityInstance->getGuildId());
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
        $this->assertGuildAllowed($original['guild_id'] ?? null);
        $this->assertGuildAllowed($entityInstance->getGuildId());
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->assertGuildAllowed($entityInstance->getGuildId());
        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        $guildIds = $this->allowedGuildIds();
        $guildNames =$this->getNames($guildIds);
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user', 'Utilisateur'),
            TextField::new('reason', 'Raison'),
            DateTimeField::new('date', 'Date')->formatValue(function ($value) {
                return $value ? $value->format('d/m/Y H:i') : '';
            }),
            NumberField::new('points', 'Points'),
            count($guildIds) === 1
                ? TextField::new('guild_id', 'Identifiant d\'association')->onlyOnIndex()
                : ChoiceField::new('guild_id', 'Guilde')->setChoices(array_combine($guildNames, $guildIds) ?: [])->onlyOnForms(),
        ];
    }
    public function getNames(array $guildIds): array
    {
        $names = [];
        foreach ($guildIds as $guildId) {
            $guild = $this->entityManager->getRepository(GuildInformation::class)->findOneBy(['guild_id' => $guildId]);
            if ($guild) {
                $names[] = $guild->getName();
            } else {
                $names[] = 'Guilde inconnue';
            }
        }
        return $names;
    }
}
