<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags')]
#[ApiResource(
    shortName: 'Tags',
    description: "Cette ressource permet de gérer les tags. Les tags sont des sortes de badges que l'on peut assigner aux élèves pour les mettre en valeur ou les identifier plus spécifiquement. Ils ont un intérêt purement esthétique et ne confèrent aucun avantage ou désavantage dans le cadre des fonctionnalités de l'API. ",
)]
#[Get(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'Le tag demandé.'),
        '404' => new Response(description: 'Aucun tag ne correspond.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Récupère un tag.',
))]
#[GetCollection(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'La liste des tags.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Liste les tags.',
))]
#[Delete(
    openapi: new Operation(
        responses: [
            '204' => new Response(description: 'Le tag a été supprimé.'),
            '403' => new Response(description: 'Accès refusé : réservé aux super-administrateurs.'),
            '404' => new Response(description: 'Aucun tag ne correspond.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Supprime un tag.',
    ),
    security: "is_granted('ROLE_SUPER_ADMIN')",
)]
#[Patch(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'Le tag a été modifié.'),
        '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
        '404' => new Response(description: 'Aucun tag ne correspond.'),
        '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Modifie partiellement un tag.',
))]
#[Post(openapi: new Operation(
    responses: [
        '201' => new Response(description: 'Le tag a été créé.'),
        '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
        '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Crée un tag.',
))]
#[Put(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'Le tag a été remplacé.'),
        '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
        '404' => new Response(description: 'Aucun tag ne correspond.'),
        '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Remplace un tag.',
))]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(length: 80)]
    #[ApiProperty(description: "Libellé du tag (80 caractères maximum).", example: 'Bénévole')]
    private ?string $tag = null;

    #[ORM\Id]
    #[ORM\Column(length: 200)]
    #[ApiProperty(description: "Émoji associé au tag.", example: '🎉')]
    private ?string $emoji = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(description: "Utilisateur auquel le tag est attribué (IRI de l'utilisateur).", example: '/api/users/1')]
    private ?User $user = null;


    public function getId(): string
    {
        return $this->tag . '|' . $this->emoji . '|' . $this->user->getId();
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): static
    {
        $this->emoji = $emoji;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
